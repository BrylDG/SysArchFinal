<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$firstname_form = $lastname_form = $email_form = $course_form = $yearlevel_form = $username_form = ''; // Suffix _form to avoid conflict with nav vars
$profile_picture_form = 'default_avatar.png'; // Suffix _form

$idno_session = $_SESSION['idno']; // Use a distinct variable for session ID

// Fetch user data for the form and for the top navigation bar
$sql_user_data = "SELECT firstname, lastname, email, course, yearlevel, username, profile_picture, role FROM users WHERE idno = ?";
if ($stmt_user_data = $conn->prepare($sql_user_data)) {
    $stmt_user_data->bind_param("s", $idno_session);
    $stmt_user_data->execute();
    $stmt_user_data->bind_result($firstname_form, $lastname_form, $email_form, $course_form, $yearlevel_form, $username_form, $profile_picture_form, $user_role_nav);
    $stmt_user_data->fetch();
    $stmt_user_data->close();
}

// Set variables for top navigation
$firstname_nav = $firstname_form;
$profile_picture_nav = $profile_picture_form;

// Set default profile picture if none exists or file is missing
if (empty($profile_picture_form) || !file_exists("uploads/" . $profile_picture_form)) {
    $profile_picture_form = 'default_avatar.png';
}
if (empty($profile_picture_nav) || !file_exists("uploads/" . $profile_picture_nav)) {
    $profile_picture_nav = 'default_avatar.png';
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname_posted = trim($_POST['firstname']);
    $lastname_posted = trim($_POST['lastname']);
    $email_posted = trim($_POST['email']);
    $course_posted = trim($_POST['course']);
    $yearlevel_posted = trim($_POST['yearlevel']);
    $new_username_posted = trim($_POST['username']);

    // Check if the email is already used by another user
    $email_check_sql = "SELECT idno FROM users WHERE email = ? AND idno != ?";
    if ($stmt_email_check = $conn->prepare($email_check_sql)) {
        $stmt_email_check->bind_param("ss", $email_posted, $idno_session);
        $stmt_email_check->execute();
        $stmt_email_check->store_result();
        
        if ($stmt_email_check->num_rows > 0) {
            $_SESSION['error_message_profile'] = "Error: Email is already in use by another account.";
            header("Location: edit-profile.php");
            exit();
        }
        $stmt_email_check->close();
    }

    // Update user details
    $update_sql_details = "UPDATE users SET firstname=?, lastname=?, email=?, course=?, yearlevel=?, username=? WHERE idno=?";
    if ($stmt_update_details = $conn->prepare($update_sql_details)) {
        $stmt_update_details->bind_param("sssssss", $firstname_posted, $lastname_posted, $email_posted, $course_posted, $yearlevel_posted, $new_username_posted, $idno_session);
        
        if ($stmt_update_details->execute()) {
            $_SESSION['success_message_profile'] = "Profile updated successfully!";
            $_SESSION['username'] = $new_username_posted; // Update session username if needed
            $_SESSION['firstname'] = $firstname_posted; // Update session firstname

            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $new_filename = $idno_session . '_' . time() . '.' . $file_extension; // Add timestamp to ensure unique filenames
                $target_file = $target_dir . $new_filename;
                
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_extension, $allowed_types)) {
                    // Delete old profile picture if it exists, is not default, and is different from new one
                    if (!empty($profile_picture_form) && $profile_picture_form != "default_avatar.png" && file_exists($target_dir . $profile_picture_form) && ($target_dir . $profile_picture_form !== $target_file) ) {
                        unlink($target_dir . $profile_picture_form);
                    }
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                        $update_pic_sql = "UPDATE users SET profile_picture = ? WHERE idno = ?";
                        if ($update_pic_stmt = $conn->prepare($update_pic_sql)) {
                            $update_pic_stmt->bind_param("ss", $new_filename, $idno_session);
                            $update_pic_stmt->execute();
                            $update_pic_stmt->close();
                            $_SESSION['profile_picture'] = $new_filename; // Update session profile picture
                            $_SESSION['success_message_profile'] = "Profile and picture updated successfully!";
                        }
                    } else {
                        $_SESSION['error_message_profile'] = "Error uploading new profile picture.";
                    }
                } else {
                    $_SESSION['error_message_profile'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                }
            }
            
            header("Location: edit-profile.php");
            exit();
        } else {
            $_SESSION['error_message_profile'] = "Error updating profile: " . $stmt_update_details->error;
        }
        $stmt_update_details->close();
    } else {
         $_SESSION['error_message_profile'] = "Error preparing profile update statement: " . $conn->error;
    }
    header("Location: edit-profile.php");
    exit();
}

// Display feedback messages from session
$feedback_message_html = "";
if (isset($_SESSION['success_message_profile'])) {
    $feedback_message_html = "<div class='bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-md mb-6 shadow-sm'>" . htmlspecialchars($_SESSION['success_message_profile']) . "</div>";
    unset($_SESSION['success_message_profile']);
}
if (isset($_SESSION['error_message_profile'])) {
    $feedback_message_html = "<div class='bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-md mb-6 shadow-sm'>" . htmlspecialchars($_SESSION['error_message_profile']) . "</div>";
    unset($_SESSION['error_message_profile']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - CCS Lab System</title>
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
                        secondary: '#D4C9BE', light: '#F1EFEC', dark: '#030303',
                    }
                },
            },
        }
    </script>
    <style>
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
        .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; }
        .file-input-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; }
        .file-input-button {
            border: 1px solid #cbd5e1; background-color: #f8fafc; color: #475569;
            padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer;
            font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s;
        }
        .file-input-button:hover { background-color: #e2e8f0; }
    </style>
</head>
<body class="bg-light text-dark font-sans pt-16">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-md z-40 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas <?php echo $user_role_nav === 'admin' ? 'fa-user-shield' : 'fa-laptop-house'; ?> text-light mr-2 text-xl"></i>
                        <span class="text-xl font-semibold text-light">Lab System - <?php echo ucfirst($user_role_nav ?? 'User'); ?></span>
                    </div>
                </div>
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <?php if ($user_role_nav === 'admin'): ?>
                        <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary-dark">Dashboard</a>
                        <div class="nav-dropdown">
                            <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Records</button>
                            <div class="nav-dropdown-content">
                                <a href="todays_sitins.php">Current Sit-ins</a>
                                <a href="sit_in_records.php">Sit-in Reports</a>
                                <a href="feedback_records.php">Feedback Reports</a>
                                <a href="manage_sitins.php">Manage Sit-ins</a>
                            </div>
                        </div>
                        <div class="nav-dropdown">
                            <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Management</button>
                            <div class="nav-dropdown-content">
                                <a href="studentlist.php">List of Students</a>
                                <a href="manage_reservation.php">Reservation Requests</a>
                                <a href="reservation_logs.php">Reservation Logs</a>
                                <a href="admin_upload_resources.php">Upload Resources</a>
                                <a href="admin_leaderboard.php">Leaderboard</a>
                                <a href="admin_lab_schedule.php">Lab Schedule</a>
                                <a href="lab_management.php">Lab Management</a>
                            </div>
                        </div>
                        <a href="create_announcement.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Announcements</a>
                    <?php else: // Student navigation ?>
                        <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary-dark">Dashboard</a>
                        <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Announcements</a>
                        <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Reservation</a>
                        <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Sit-in History</a>
                        <div class="nav-dropdown">
                             <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">More</button>
                             <div class="nav-dropdown-content">
                                <a href="upload_resources.php">View Resources</a>
                                <a href="student_leaderboard.php">Leaderboard</a>
                                <a href="student_lab_schedule.php">Lab Schedule</a>
                                <a href="sit-in-rules.php">Sit-in Rules</a>
                                <a href="lab-rules.php">Lab Rules</a>
                             </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-4 ml-4">
                        <div class="relative"><button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10"><i class="fas fa-bell text-lg"></i><span class="notification-badge hidden">0</span></button><div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><div class="p-3 bg-primary text-white flex justify-between items-center"><span class="font-semibold">Notifications</span><button id="markAllReadButton" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded">Mark all as read</button></div><div id="notificationList" class="max-h-80 overflow-y-auto"><div class="p-4 text-center text-gray-500">No notifications</div></div></div></div>
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center gap-2 group">
                                <img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary object-cover" src="uploads/<?php echo htmlspecialchars($profile_picture_nav); ?>" onerror="this.src='assets/default_avatar.png'" alt="Profile">
                                <span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname_nav); ?></span>
                            </button>
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) === 'edit-profile.php' ? 'bg-gray-100 text-primary font-medium' : ''; ?>"><i class="fas fa-user-edit mr-2"></i>Edit Profile</a>
                                <div class="border-t border-gray-200"></div>
                                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-sign-out-alt mr-2"></i>Log Out</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mobile-menu md:hidden flex items-center"><button id="mobileMenuButton" class="text-light hover:text-secondary"><i class="fas fa-bars text-xl"></i></button></div>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-primary border-t border-primary-dark">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                 <?php if ($user_role_nav === 'admin'): ?>
                    <a href="admin_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary-dark">Dashboard</a>
                    <a href="todays_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Current Sit-ins</a>
                    <a href="sit_in_records.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Sit-in Reports</a>
                    <a href="feedback_records.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Feedback Reports</a>
                    <a href="manage_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Manage Sit-ins</a>
                    <a href="studentlist.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">List of Students</a>
                    <a href="manage_reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Reservation Requests</a>
                    <a href="reservation_logs.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Reservation Logs</a>
                    <a href="admin_upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Upload Resources</a>
                    <a href="admin_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Leaderboard</a>
                    <a href="admin_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Lab Schedule</a>
                    <a href="lab_management.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Lab Management</a>
                    <a href="create_announcement.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Announcements</a>
                <?php else: // Student mobile navigation ?>
                    <a href="student_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary-dark">Dashboard</a>
                    <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary-dark bg-primary-darker">Edit Profile</a>
                    <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Announcements</a>
                    <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Reservation</a>
                    <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Sit-in History</a>
                    <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">View Resources</a>
                    <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Leaderboard</a>
                    <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Lab Schedule</a>
                    <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Sit-in Rules</a>
                    <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Lab Rules</a>
                <?php endif; ?>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-xl p-6 md:p-8 border border-gray-200 max-w-3xl mx-auto">
            <header class="mb-8 pb-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-primary text-center">
                    </i>Edit Profile
                </h1>
            </header>
            
            <?php echo $feedback_message_html; ?>

            <form method="POST" action="edit-profile.php" enctype="multipart/form-data" class="space-y-6">
                <div class="flex flex-col items-center mb-8">
                    <img 
                        src="uploads/<?php echo htmlspecialchars($profile_picture_form); ?>" 
                        alt="Current Profile" 
                        id="profileImagePreview"
                        class="w-32 h-32 rounded-full border-4 border-primary object-cover shadow-lg mb-4"
                        onerror="this.src='assets/default_avatar.png'"
                    >
                    <div class="file-input-wrapper">
                        <button type="button" class="file-input-button">Change Picture</button>
                        <input 
                            type="file" 
                            name="profile_picture" 
                            id="profile_picture_input" 
                            accept="image/jpeg, image/png, image/gif"
                            onchange="previewImage(event)"
                        >
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Max 2MB. JPG, PNG, GIF.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="firstname" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname_form); ?>" required 
                               class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                    </div>
                    <div>
                        <label for="lastname" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname_form); ?>" required
                               class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email_form); ?>" required
                           class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                </div>
                
                <?php if ($user_role_nav !== 'admin'): // Only show course and year for non-admins (students) ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <input type="text" name="course" id="course" value="<?php echo htmlspecialchars($course_form); ?>" required
                               class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                    </div>
                    <div>
                        <label for="yearlevel" class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                        <input type="text" name="yearlevel" id="yearlevel" value="<?php echo htmlspecialchars($yearlevel_form); ?>" required
                               class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                    </div>
                </div>
                <?php else: // Hidden fields for admin so form submission doesn't break, or remove them if admins don't have these fields in DB ?>
                    <input type="hidden" name="course" value="<?php echo htmlspecialchars($course_form); ?>">
                    <input type="hidden" name="yearlevel" value="<?php echo htmlspecialchars($yearlevel_form); ?>">
                <?php endif; ?>
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username_form); ?>" required
                           class="w-full p-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark placeholder-gray-400">
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full px-6 py-3 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>

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
        
        // --- Edit Profile Scripts ---
        document.querySelector('form').addEventListener('submit', function() {
            localStorage.setItem('scrollPosition', window.scrollY);
        });
        window.addEventListener('load', function() {
            const scrollPosition = localStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, parseInt(scrollPosition));
                localStorage.removeItem('scrollPosition');
            }
        });

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('profileImagePreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>