<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$firstname = $lastname = $email = $course = $yearlevel = $username = $profile_picture = '';
$profile_picture = 'default_avatar.png';

if (isset($_SESSION['idno'])) {
    $idno = $_SESSION['idno'];
    
    // Fetch user data
    $sql = "SELECT firstname, lastname, email, course, yearlevel, username, profile_picture FROM users WHERE idno = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $idno);
        $stmt->execute();
        $stmt->bind_result($firstname, $lastname, $email, $course, $yearlevel, $username, $profile_picture);
        $stmt->fetch();
        $stmt->close();
    }

    // Set default profile picture if none exists
    if (empty($profile_picture)) {
        $upload_dir = 'uploads/';
        $images = glob($upload_dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (!empty($images)) {
            $random_image = $images[array_rand($images)];
            $profile_picture = basename($random_image);

            $update_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("ss", $profile_picture, $idno);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);
    $yearlevel = trim($_POST['yearlevel']);
    $new_username = trim($_POST['username']);

    // Check if the email is already used by another user
    $email_check_sql = "SELECT idno FROM users WHERE email = ? AND idno != ?";
    if ($stmt = $conn->prepare($email_check_sql)) {
        $stmt->bind_param("ss", $email, $idno);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $_SESSION['error_message'] = "Error: Email is already in use by another account.";
            header("Location: edit-profile.php");
            exit();
        }
        $stmt->close();
    }

    // Update user details
    $update_sql = "UPDATE users SET firstname=?, lastname=?, email=?, course=?, yearlevel=?, username=? WHERE idno=?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("sssssss", $firstname, $lastname, $email, $course, $yearlevel, $new_username, $idno);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            $_SESSION['username'] = $new_username;
            
            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $new_filename = $idno . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                // Check if file is a valid image type
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_extension, $allowed_types)) {
                    // Delete old profile picture if it exists and isn't default
                    if (!empty($profile_picture) && $profile_picture != "default_avatar.png" && file_exists($target_dir . $profile_picture)) {
                        unlink($target_dir . $profile_picture);
                    }
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                        // Update database with new filename
                        $update_pic_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
                        if ($update_pic_stmt = $conn->prepare($update_pic_sql)) {
                            $update_pic_stmt->bind_param("ss", $new_filename, $idno);
                            $update_pic_stmt->execute();
                            $update_pic_stmt->close();
                            $profile_picture = $new_filename;
                            $_SESSION['success_message'] = "Profile and picture updated successfully!";
                        }
                    } else {
                        $_SESSION['error_message'] = "Error uploading profile picture.";
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                }
            }
            
            header("Location: edit-profile.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating profile.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CCS SIT Monitoring System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar - Matching the structure from sit-in-rules.php -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <!-- Profile Picture -->
                <img 
                    src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                    alt="Profile Picture" 
                    class="w-10 h-10 rounded-full border-2 border-white/10 object-cover"
                    onerror="this.src='assets/default_avatar.png'"
                >
                <!-- First Name -->
                <h2 class="text-xl font-semibold text-white"><?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Edit Profile</p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="student_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profile.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>Edit Profile</span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>View Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="sit-in-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in Rules</span>
                    </a>
                </li>
                <li>
                    <a href="lab-rules.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Rules & Regulations</span>
                    </a>
                </li>
                <li>
                    <a href="reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservation</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_history.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in History</span>
                    </a>
                </li>
                <li>
                    <a href="upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>View Lab Resources</span>
                    </a>
                </li>
                <li>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="student_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Schedule</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">Edit Profile</h2>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-600/20 border border-green-600/30 text-green-400 px-4 py-3 rounded-lg mb-6">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-600/20 border border-red-600/30 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                        <label for="firstname" class="block text-sm font-medium text-slate-300 mb-2">First Name</label>
                        <input 
                            type="text" 
                            name="firstname" 
                            id="firstname" 
                            value="<?php echo htmlspecialchars($firstname ?? ''); ?>" 
                            required 
                            class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                    
                    <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                        <label for="lastname" class="block text-sm font-medium text-slate-300 mb-2">Last Name</label>
                        <input 
                            type="text" 
                            name="lastname" 
                            id="lastname" 
                            value="<?php echo htmlspecialchars($lastname ?? ''); ?>" 
                            required 
                            class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                </div>
                
                <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                        required 
                        class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                        <label for="course" class="block text-sm font-medium text-slate-300 mb-2">Course</label>
                        <input 
                            type="text" 
                            name="course" 
                            id="course" 
                            value="<?php echo htmlspecialchars($course ?? ''); ?>" 
                            required 
                            class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                    
                    <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                        <label for="yearlevel" class="block text-sm font-medium text-slate-300 mb-2">Year Level</label>
                        <input 
                            type="text" 
                            name="yearlevel" 
                            id="yearlevel" 
                            value="<?php echo htmlspecialchars($yearlevel ?? ''); ?>" 
                            required 
                            class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                </div>
                
                <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                    <label for="username" class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                        required 
                        class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                </div>
                
                <div class="bg-slate-700/50 p-4 rounded-lg border border-white/5">
                    <label for="profile_picture" class="block text-sm font-medium text-slate-300 mb-2">Profile Picture</label>
                    <div class="flex items-center space-x-4">
                        <img 
                            src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                            alt="Current Profile" 
                            class="w-16 h-16 rounded-full border-2 border-white/10 object-cover"
                            onerror="this.src='assets/default_avatar.png'"
                        >
                        <input 
                            type="file" 
                            name="profile_picture" 
                            id="profile_picture" 
                            accept="image/jpeg, image/png, image/gif"
                            class="w-full bg-slate-700/70 text-white rounded-lg border border-slate-600 px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</p>
                </div>
                
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg py-3 px-5 transition-colors duration-200 focus:ring-4 focus:ring-blue-600/50"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Save scroll position before form submission
        document.querySelector('form').addEventListener('submit', function() {
            localStorage.setItem('scrollPosition', window.scrollY);
        });

        // Restore scroll position after page reload
        window.addEventListener('load', function() {
            const scrollPosition = localStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, parseInt(scrollPosition));
                localStorage.removeItem('scrollPosition');
            }
        });
    </script>
</body>
</html>