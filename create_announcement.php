<?php
session_start();
include 'db.php';

// Set the timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get admin user info from DB for the top navigation bar
$admin_idno_for_nav = $_SESSION["idno"]; // This is the admin's ID
$stmt_admin_nav = $conn->prepare("SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?");
$stmt_admin_nav->bind_param("s", $admin_idno_for_nav);
$stmt_admin_nav->execute();
$stmt_admin_nav->bind_result($admin_firstname_nav, $admin_lastname_nav, $admin_profile_picture_nav);
$stmt_admin_nav->fetch();
$stmt_admin_nav->close();

$firstname_nav = $admin_firstname_nav ?? 'Admin';
$profile_picture_nav = $admin_profile_picture_nav ?? 'default_avatar.png';
if (empty($profile_picture_nav) || !file_exists("uploads/" . $profile_picture_nav)) {
    $profile_picture_nav = 'default_avatar.png';
}

// Handle new announcement creation
$feedback_message_html = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_announcement"])) {
    $title = trim($_POST["title"]);
    $message_content = trim($_POST["message"]);

    if (!empty($title) && !empty($message_content)) {
        // REMOVED admin_id from INSERT as it's not in the original table schema
        $stmt_add = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
        $stmt_add->bind_param("ss", $title, $message_content);
        if ($stmt_add->execute()) {
            $_SESSION['feedback_message'] = "📢 Announcement posted successfully!";
            $_SESSION['feedback_type'] = "success";
        } else {
            $_SESSION['feedback_message'] = "❌ Error posting announcement: " . $stmt_add->error;
            $_SESSION['feedback_type'] = "error";
        }
        $stmt_add->close();
    } else {
        $_SESSION['feedback_message'] = "⚠️ All fields are required.";
        $_SESSION['feedback_type'] = "warning";
    }
    header("Location: create_announcement.php");
    exit();
}

// Handle announcement deletion (no change needed here for schema)
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt_delete = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    if ($stmt_delete->execute()) {
        $_SESSION['feedback_message'] = "🗑️ Announcement deleted successfully!";
        $_SESSION['feedback_type'] = "success";
    } else {
        $_SESSION['feedback_message'] = "❌ Error deleting announcement: " . $stmt_delete->error;
        $_SESSION['feedback_type'] = "error";
    }
    $stmt_delete->close();
    header("Location: create_announcement.php");
    exit();
}

// Handle announcement update (no change needed here for schema)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_announcement"])) {
    $edit_id = $_POST["id"];
    $edit_title = trim($_POST["title"]);
    $edit_message_content = trim($_POST["message"]);
    if (!empty($edit_title) && !empty($edit_message_content)) {
        $stmt_edit = $conn->prepare("UPDATE announcements SET title = ?, message = ? WHERE id = ?");
        $stmt_edit->bind_param("ssi", $edit_title, $edit_message_content, $edit_id);
        if ($stmt_edit->execute()) {
            $_SESSION['feedback_message'] = "✏️ Announcement updated successfully!";
            $_SESSION['feedback_type'] = "success";
        } else {
            $_SESSION['feedback_message'] = "❌ Error updating announcement: " . $stmt_edit->error;
            $_SESSION['feedback_type'] = "error";
        }
        $stmt_edit->close();
    } else {
        $_SESSION['feedback_message'] = "⚠️ All fields are required for update.";
        $_SESSION['feedback_type'] = "warning";
    }
    header("Location: create_announcement.php");
    exit();
}

// Fetch all announcements
// REVERTED to the original query as there's no admin_id to JOIN on
$announcements_query = "SELECT * FROM announcements ORDER BY created_at DESC"; // This was line 106
$announcements_result = mysqli_query($conn, $announcements_query);
if (!$announcements_result) { // Basic error check for the query
    die("Error fetching announcements: " . mysqli_error($conn));
}


// Display feedback messages from session
if (isset($_SESSION['feedback_message'])) {
    $alert_bg = 'bg-blue-50 border-blue-300 text-blue-700';
    if ($_SESSION['feedback_type'] === 'success') $alert_bg = 'bg-green-50 border-green-300 text-green-700';
    if ($_SESSION['feedback_type'] === 'error') $alert_bg = 'bg-red-50 border-red-300 text-red-700';
    if ($_SESSION['feedback_type'] === 'warning') $alert_bg = 'bg-yellow-50 border-yellow-300 text-yellow-700';
    
    $feedback_message_html = "<div class='{$alert_bg} p-4 rounded-md mb-6 shadow-sm'>" . htmlspecialchars($_SESSION['feedback_message']) . "</div>";
    unset($_SESSION['feedback_message']);
    unset($_SESSION['feedback_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Lab System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'], },
                    colors: {
                        primary: '#123458', 'primary-dark': '#0f2a48', 'primary-darker': '#0c223a',
                        secondary: '#D4C9BE', light: '#F1EFEC', dark: '#030303', // For text on light bg
                    }
                },
            },
        }
    </script>
    <style>
        /* Styles for Top Nav Dropdowns (same as lab_management.php) */
        .nav-dropdown { position: relative; }
        .nav-dropdown-content {
            display: none; position: absolute; background-color: white;
            min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
            z-index: 50; border-radius: 0.375rem; border: 1px solid #e5e7eb; overflow: hidden;
        }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-dropdown-content a {
            color: #374151; padding: 10px 15px; text-decoration: none;
            display: block; font-size: 0.875rem;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .nav-dropdown-content a:hover { background-color: #f3f4f6; color: #123458; }
        .nav-dropdown-content a.active-dropdown-item {
             background-color: #e0e7ff; color: #123458; font-weight: 500;
        }
        .nav-dropdown-btn::after { content: "▾"; margin-left: 5px; font-size: 0.8em; }
        .notification-badge {
            position: absolute; top: -5px; right: -5px; background-color: #ef4444; color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        /* Modal specific styles */
        .modal { display: none; }
        .modal.active { display: flex; }
        .modal-content {
            max-height: 90vh; /* Prevent modal from being too tall on small screens */
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light text-dark font-sans pt-16">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-md z-40 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center"><div class="flex-shrink-0 flex items-center"><i class="fas fa-laptop-house text-light mr-2 text-xl"></i><span class="text-xl font-semibold text-light">Lab System - Admin</span></div></div>
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'bg-primary-darker' : ''; ?>">Dashboard</a>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Records</button>
                        <div class="nav-dropdown-content">
                            <a href="todays_sitins.php" <?php echo basename($_SERVER['PHP_SELF']) === 'todays_sitins.php' ? 'class="active-dropdown-item"' : ''; ?>>Current Sit-ins</a>
                            <a href="sit_in_records.php" <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_records.php' ? 'class="active-dropdown-item"' : ''; ?>>Sit-in Reports</a>
                            <a href="feedback_records.php" <?php echo basename($_SERVER['PHP_SELF']) === 'feedback_records.php' ? 'class="active-dropdown-item"' : ''; ?>>Feedback Reports</a>
                            <a href="manage_sitins.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_sitins.php' ? 'class="active-dropdown-item"' : ''; ?>>Manage Sit-ins</a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                         <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark <?php echo (basename($_SERVER['PHP_SELF']) === 'studentlist.php' || basename($_SERVER['PHP_SELF']) === 'manage_reservation.php' || basename($_SERVER['PHP_SELF']) === 'reservation_logs.php' || basename($_SERVER['PHP_SELF']) === 'admin_upload_resources.php' || basename($_SERVER['PHP_SELF']) === 'admin_leaderboard.php' || basename($_SERVER['PHP_SELF']) === 'admin_lab_schedule.php' || basename($_SERVER['PHP_SELF']) === 'lab_management.php') ? 'bg-primary-darker text-white' : ''; ?>">
                            Management
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="studentlist.php" <?php echo basename($_SERVER['PHP_SELF']) === 'studentlist.php' ? 'class="active-dropdown-item"' : ''; ?>>List of Students</a>
                            <a href="manage_reservation.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_reservation.php' ? 'class="active-dropdown-item"' : ''; ?>>Reservation Requests</a>
                            <a href="reservation_logs.php" <?php echo basename($_SERVER['PHP_SELF']) === 'reservation_logs.php' ? 'class="active-dropdown-item"' : ''; ?>>Reservation Logs</a>
                            <a href="admin_upload_resources.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_upload_resources.php' ? 'class="active-dropdown-item"' : ''; ?>>Upload Resources</a>
                            <a href="admin_leaderboard.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_leaderboard.php' ? 'class="active-dropdown-item"' : ''; ?>>Leaderboard</a>
                            <a href="admin_lab_schedule.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_lab_schedule.php' ? 'class="active-dropdown-item"' : ''; ?>>Lab Schedule</a>
                            <a href="lab_management.php" <?php echo basename($_SERVER['PHP_SELF']) === 'lab_management.php' ? 'class="active-dropdown-item"' : ''; ?>>Lab Management</a>
                        </div>
                    </div>
                    <a href="create_announcement.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'create_announcement.php' ? 'bg-primary-darker' : 'text-secondary'; ?>">Announcements</a>
                    <div class="flex items-center gap-4 ml-4">
                        <div class="relative"><button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10"><i class="fas fa-bell text-lg"></i><span class="notification-badge hidden">0</span></button><div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><div class="p-3 bg-primary text-white flex justify-between items-center"><span class="font-semibold">Notifications</span><button id="markAllReadButton" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded">Mark all as read</button></div><div id="notificationList" class="max-h-80 overflow-y-auto"><div class="p-4 text-center text-gray-500">No notifications</div></div></div></div>
                        <div class="relative"><button id="userMenuButton" class="flex items-center gap-2 group"><img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary object-cover" src="uploads/<?php echo htmlspecialchars($profile_picture_nav); ?>" onerror="this.src='assets/default_avatar.png'" alt="Profile"><span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname_nav); ?></span></button><div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-user-edit mr-2"></i>Edit Profile</a><div class="border-t border-gray-200"></div><a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-sign-out-alt mr-2"></i>Log Out</a></div></div>
                    </div>
                </div>
                <div class="mobile-menu md:hidden flex items-center"><button id="mobileMenuButton" class="text-light hover:text-secondary"><i class="fas fa-bars text-xl"></i></button></div>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-primary border-t border-primary-dark">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="admin_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'bg-primary-darker text-white' : 'text-light hover:bg-primary-dark'; ?>">Dashboard</a>
                <a href="todays_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'todays_sitins.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Current Sit-ins</a>
                <a href="sit_in_records.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_records.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Sit-in Reports</a>
                <a href="feedback_records.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'feedback_records.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Feedback Reports</a>
                <a href="manage_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'manage_sitins.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Manage Sit-ins</a>
                <a href="studentlist.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'studentlist.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">List of Students</a>
                <a href="manage_reservation.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'manage_reservation.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Reservation Requests</a>
                <a href="reservation_logs.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'reservation_logs.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Reservation Logs</a>
                <a href="admin_upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'admin_upload_resources.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Upload Resources</a>
                <a href="admin_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'admin_leaderboard.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Leaderboard</a>
                <a href="admin_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'admin_lab_schedule.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Lab Schedule</a>
                <a href="lab_management.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'lab_management.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Lab Management</a>
                <a href="create_announcement.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'create_announcement.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Announcements</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-xl p-6 md:p-8 border border-gray-200">
            <header class="mb-8 pb-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-primary">
                    </i>Manage Announcements
                </h1>
            </header>
            
            <?php echo $feedback_message_html; ?>

            <section class="mb-10 p-6 bg-gray-50 rounded-lg border border-gray-200 shadow-sm">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Create New Announcement</h2>
                <form method="POST" action="create_announcement.php" class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title:</label>
                        <input type="text" name="title" id="title" class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400" placeholder="Enter announcement title" required>
                    </div>
                    <div>
                        <label for="message_content" class="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                        <textarea name="message" id="message_content" class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400" rows="5" placeholder="Enter your announcement details here..." required></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="w-full sm:w-auto px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark">
                        <i class="fas fa-paper-plane mr-2"></i>Post Announcement
                    </button>
                </form>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-700 mb-6">Existing Announcements</h2>
                <?php while ($row = mysqli_fetch_assoc($announcements_result)) { 
                            // Since admin_id is not in announcements table, we assume the current admin posted it.
                            // Alternatively, you can remove the "Posted by" line if it's always implied.
                        ?>
                            <article class="bg-white p-6 rounded-lg shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-200">
                                <header class="mb-3 pb-3 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-user-shield mr-1"></i>Posted by: <?php echo htmlspecialchars($firstname_nav); // Display logged-in admin's name ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-clock mr-1"></i><?php echo date("F j, Y, g:i a", strtotime($row['created_at'])); ?>
                                    </p>
                                </header>
                                <div class="prose prose-sm max-w-none text-gray-700 mb-4">
                                    <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                </div>
                                <footer class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                                    <button onclick="editAnnouncement(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['title'])); ?>', '<?php echo htmlspecialchars(addslashes($row['message'])); ?>')" class="px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 hover:bg-yellow-200 rounded-md shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-yellow-500">
                                        <i class="fas fa-edit mr-1.5"></i>Edit
                                    </button>
                                    <a href="create_announcement.php?delete=<?php echo $row['id']; ?>" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded-md shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-red-500" onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.')">
                                        <i class="fas fa-trash mr-1.5"></i>Delete
                                    </a>
                                </footer>
                            </article>
                        <?php } ?>
            </section>
        </div>
    </main>

    <!-- Edit Announcement Modal -->
    <div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center p-4 z-50 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="modal-content relative bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-auto transform transition-transform duration-300 scale-95">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-semibold text-primary"><i class="fas fa-edit mr-2"></i>Edit Announcement</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <form method="POST" action="create_announcement.php">
                <input type="hidden" name="id" id="editId">
                <div class="mb-4">
                    <label for="editTitle" class="block text-sm font-medium text-gray-700 mb-1">Title:</label>
                    <input type="text" name="title" id="editTitle" class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark" required>
                </div>
                <div class="mb-6">
                    <label for="editMessage" class="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                    <textarea name="message" id="editMessage" class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark" rows="6" required></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md shadow-sm transition duration-150">Cancel</button>
                    <button type="submit" name="edit_announcement" class="px-5 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition duration-150">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Top Navigation Bar Scripts ---
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadButton');

        if(mobileMenuButton && mobileMenu) mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        if(userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', (e) => {
                e.stopPropagation(); userDropdown.classList.toggle('hidden');
                if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) notificationDropdown.classList.add('hidden');
            });
        }
        if(notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', (e) => {
                e.stopPropagation(); notificationDropdown.classList.toggle('hidden');
                if (userDropdown && !userDropdown.classList.contains('hidden')) userDropdown.classList.add('hidden');
                if (!notificationDropdown.classList.contains('hidden')) loadNotifications();
            });
        }
        if(notificationDropdown) notificationDropdown.addEventListener('click', e => e.stopPropagation());
        if(userDropdown) userDropdown.addEventListener('click', e => e.stopPropagation());

        document.addEventListener('click', (event) => {
            if(userDropdown && !userDropdown.contains(event.target) && userMenuButton && !userMenuButton.contains(event.target) && !userMenuButton.querySelector('*').contains(event.target) ) userDropdown.classList.add('hidden');
            if(notificationDropdown && !notificationDropdown.contains(event.target) && notificationButton && !notificationButton.contains(event.target) && !notificationButton.querySelector('*').contains(event.target)) notificationDropdown.classList.add('hidden');
        });

        function loadNotifications() { const list = document.getElementById('notificationList'); if(list) list.innerHTML = '<div class="p-4 text-center text-gray-500">No new notifications.</div>'; document.querySelector('.notification-badge')?.classList.add('hidden'); }
        if(markAllReadBtn) markAllReadBtn.addEventListener('click', loadNotifications);
        loadNotifications();

        // --- Announcement Modal Scripts ---
        const editModal = document.getElementById('editModal');
        const editModalContent = editModal ? editModal.querySelector('.modal-content') : null;

        function editAnnouncement(id, title, message) {
            if (!editModal || !editModalContent) return;
            document.getElementById("editId").value = id;
            document.getElementById("editTitle").value = title;
            document.getElementById("editMessage").value = message;
            
            editModal.classList.remove("pointer-events-none", "opacity-0");
            editModal.classList.add("active"); // For potential CSS targeting if needed
            editModalContent.classList.remove("scale-95");
            editModalContent.classList.add("scale-100");
        }

        function closeEditModal() {
            if (!editModal || !editModalContent) return;
            editModal.classList.add("opacity-0");
            editModalContent.classList.remove("scale-100");
            editModalContent.classList.add("scale-95");
            setTimeout(() => {
                editModal.classList.add("pointer-events-none");
                editModal.classList.remove("active");
            }, 300); // Match transition duration
        }

        if (editModal) {
            editModal.addEventListener('click', function(e) {
                if (e.target === this) closeEditModal(); // Close if backdrop is clicked
            });
        }
        // Close modal on ESC key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && editModal && !editModal.classList.contains('pointer-events-none')) {
                closeEditModal();
            }
        });

    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>