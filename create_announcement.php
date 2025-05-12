<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get admin info
$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

// Handle new announcement creation
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_announcement"])) {
    $title = trim($_POST["title"]);
    $message_content = trim($_POST["message"]);

    if (!empty($title) && !empty($message_content)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $message_content);
        if ($stmt->execute()) {
            $message = "<div class='bg-green-600/20 text-green-400 p-4 rounded-lg mb-4'>📢 Announcement posted successfully!</div>";
        } else {
            $message = "<div class='bg-red-600/20 text-red-400 p-4 rounded-lg mb-4'>❌ Error posting announcement.</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='bg-yellow-600/20 text-yellow-400 p-4 rounded-lg mb-4'>⚠️ All fields are required.</div>";
    }
}

// Handle announcement deletion
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Announcement deleted successfully!'); window.location.href='create_announcement.php';</script>";
    } else {
        echo "<script>alert('Error deleting announcement!');</script>";
    }
    $stmt->close();
}

// Handle announcement update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_announcement"])) {
    $edit_id = $_POST["id"];
    $edit_title = trim($_POST["title"]);
    $edit_message = trim($_POST["message"]);

    if (!empty($edit_title) && !empty($edit_message)) {
        $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ? WHERE id = ?");
        $stmt->bind_param("ssi", $edit_title, $edit_message, $edit_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Announcement updated successfully!'); window.location.href='create_announcement.php';</script>";
        } else {
            echo "<script>alert('Error updating announcement!');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('⚠️ All fields are required.');</script>";
    }
}

// Fetch all announcements
$announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcements_result = mysqli_query($conn, $announcements_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
        .time-cell {
            min-width: 160px;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($firstname ?? ''); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Manage Announcements</p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="admin_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="todays_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Current Sit-in Records</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in Reports</span>
                    </a>
                </li>
                <li>
                    <a href="feedback_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Feedback Reports</span>
                    </a>
                </li>
                <li>
                    <a href="manage_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Manage Sit-ins</span>
                    </a>
                </li>
                <li>
                    <a href="create_announcement.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="studentlist.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>List of Students</span>
                    </a>
                </li>
                <li>
                    <a href="manage_reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservations Requests</span>
                    </a>
                </li>
                <li>
                    <a href="reservation_logs.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservation Logs</span>
                    </a>
                </li>
                <li>
                    <a href="admin_upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Upload Resources</span>
                    </a>
                </li>
                <li>
                    <a href="admin_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="lab_management.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Management</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">📢 Manage Announcements</h2>
            
            <?php echo $message; ?>

            <!-- Add Announcement Form -->
            <div class="mb-8">
                <h4 class="text-xl font-semibold text-white mb-4">Create New Announcement</h4>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Title:</label>
                        <input type="text" name="title" class="mt-1 block w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" placeholder="Enter title" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Message:</label>
                        <textarea name="message" class="mt-1 block w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" rows="4" placeholder="Enter message" required></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="w-full p-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all duration-200">
                        <i class="fa-solid fa-paper-plane"></i> Post Announcement
                    </button>
                </form>
            </div>

            <!-- List of Announcements -->
            <h3 class="text-xl font-semibold text-white mb-4">Existing Announcements</h3>
            <?php while ($row = mysqli_fetch_assoc($announcements_result)) { ?>
                <div class="bg-slate-700/50 p-6 rounded-lg shadow-md mb-4 hover:shadow-lg transition-all duration-200">
                    <h5 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <p class="text-sm text-slate-300 mt-2"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                    <p class="text-xs text-slate-400 mt-2"><i class="fa-solid fa-clock"></i> Posted on: <?php echo $row['created_at']; ?></p>
                    <div class="flex space-x-2 mt-4">
                        <a href="?delete=<?php echo $row['id']; ?>" class="p-2 bg-red-600/20 text-red-400 rounded-md hover:bg-red-700/20 transition-all duration-200" onclick="return confirm('Are you sure you want to delete this announcement?')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </a>
                        <button onclick="editAnnouncement(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title']); ?>', '<?php echo htmlspecialchars($row['message']); ?>')" class="p-2 bg-yellow-600/20 text-yellow-400 rounded-md hover:bg-yellow-700/20 transition-all duration-200">
                            <i class="fa-solid fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden" id="editModal">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 w-full max-w-md">
                <h5 class="text-xl font-semibold text-white mb-4">✏️ Edit Announcement</h5>
                <form method="POST">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300">Title:</label>
                        <input type="text" name="title" id="editTitle" class="mt-1 block w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300">Message:</label>
                        <textarea name="message" id="editMessage" class="mt-1 block w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" rows="4" required></textarea>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="edit_announcement" class="w-full p-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-all duration-200">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="w-full p-2 bg-slate-600 text-white rounded-md hover:bg-slate-700 transition-all duration-200">
                            <i class="fa-solid fa-xmark"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for Editing -->
    <script>
        function editAnnouncement(id, title, message) {
            document.getElementById("editId").value = id;
            document.getElementById("editTitle").value = title;
            document.getElementById("editMessage").value = message;
            document.getElementById("editModal").classList.remove("hidden");
        }
    </script>
</body>
</html>