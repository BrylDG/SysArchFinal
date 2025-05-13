<?php
session_start();
include 'db.php'; // Make sure this path is correct

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Fetch admin's details for the top bar
$admin_idno = $_SESSION["idno"];
$stmt_admin = $conn->prepare("SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?");
$stmt_admin->bind_param("s", $admin_idno);
$stmt_admin->execute();
$stmt_admin->bind_result($admin_firstname, $admin_lastname, $admin_profile_picture);
$stmt_admin->fetch();
$stmt_admin->close();

if (empty($admin_profile_picture)) {
    $admin_profile_picture = "default_avatar.png"; // Default avatar
}

// --- Leaderboard Data Fetching ---

// All students for the main leaderboard table
$students_query = "SELECT
                    u.id,
                    u.idno,
                    u.firstname,
                    u.lastname,
                    u.points,
                    u.remaining_sessions,
                    u.profile_picture,
                    COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)))), '00:00:00') AS total_duration
                  FROM users u
                  LEFT JOIN sit_in_records s ON u.id = s.student_id AND s.end_time IS NOT NULL AND s.start_time IS NOT NULL
                  WHERE u.role = 'student'
                  GROUP BY u.id
                  ORDER BY u.points DESC, u.remaining_sessions DESC"; // No arbitrary limit here, can add pagination later
$students_result = mysqli_query($conn, $students_query);

// Get top 3 students separately for the podium
$top3_query = "SELECT
                u.id,
                u.idno,
                u.firstname,
                u.lastname,
                u.points,
                u.profile_picture
              FROM users u
              WHERE u.role = 'student'
              ORDER BY u.points DESC, u.remaining_sessions DESC
              LIMIT 3";
$top3_result_q = mysqli_query($conn, $top3_query);
$top3_students = [];
while ($row = mysqli_fetch_assoc($top3_result_q)) {
    if (empty($row['profile_picture'])) {
        $row['profile_picture'] = 'default_avatar.png';
    }
    $top3_students[] = $row;
}

// Recent activity logs
$logs_query = "SELECT r.*, u.firstname, u.lastname, u.profile_picture as student_profile_picture
              FROM rewards_log r
              JOIN users u ON r.user_id = u.id
              ORDER BY r.created_at DESC
              LIMIT 10"; // Limit for recent activities
$logs_result = mysqli_query($conn, $logs_query);


// Point adjustment logic (simplified from your example - ensure student_id, points, action are validated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_points'])) {
    $student_id_to_adjust = $_POST['student_id_to_adjust'] ?? null;
    $points_to_add = $_POST['points_to_add'] ?? 0;
    $reason = $_POST['reason'] ?? 'Manual adjustment by admin';

    if ($student_id_to_adjust && is_numeric($points_to_add)) {
        $points_to_add = intval($points_to_add);

        $conn->begin_transaction();
        try {
            // Fetch current points
            $stmt_get_points = $conn->prepare("SELECT points FROM users WHERE id = ?");
            $stmt_get_points->bind_param("i", $student_id_to_adjust);
            $stmt_get_points->execute();
            $stmt_get_points->bind_result($current_points);
            $stmt_get_points->fetch();
            $stmt_get_points->close();

            $new_points = $current_points + $points_to_add;

            // Update points
            $stmt_update = $conn->prepare("UPDATE users SET points = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $new_points, $student_id_to_adjust);
            $stmt_update->execute();

            // Log the reward/deduction
            $action_type = $points_to_add >= 0 ? 'manual_reward' : 'manual_deduction';
            $stmt_log = $conn->prepare("INSERT INTO rewards_log (user_id, points_earned, action, description) VALUES (?, ?, ?, ?)");
            $stmt_log->bind_param("iiss", $student_id_to_adjust, $points_to_add, $action_type, $reason);
            $stmt_log->execute();

            $conn->commit();
            $_SESSION['success_message'] = "Points adjusted successfully for student ID {$student_id_to_adjust}.";

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error adjusting points: " . $exception->getMessage();
        }
        // Redirect to avoid form resubmission
        header("Location: admin_leaderboard.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid input for point adjustment.";
        header("Location: admin_leaderboard.php");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Leaderboard - Lab System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Styles from admin_upload_resources.php for consistency */
        .notification-badge {
            position: absolute; top: -5px; right: -5px; background-color: #ef4444; color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .nav-dropdown { position: relative; }
        .nav-dropdown-content {
            display: none; position: absolute; background-color: #123458; min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); z-index: 1; border-radius: 0 0 0.5rem 0.5rem;
        }
        .nav-dropdown-content a {
            color: #F1EFEC; padding: 12px 16px; text-decoration: none; display: block; font-size: 0.875rem;
        }
        .nav-dropdown-content a:hover { background-color: rgba(255,255,255,0.1); }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-dropdown-btn { display: flex; align-items: center; }
        .nav-dropdown-btn::after { content: "▾"; margin-left: 5px; font-size: 0.8em; }

        /* Custom podium styles */
        .podium-item { transition: transform 0.3s ease-out, box-shadow 0.3s ease-out; }
        .podium-item:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2), 0 10px 10px -5px rgba(0,0,0,0.1); }
        .podium-base {
            clip-path: polygon(0 15%, 100% 0, 100% 100%, 0 100%); /* Slanted top for base */
        }
        .rank-1 .podium-base { background-image: linear-gradient(to top, #FFD700, #FFA500); height: 12rem;} /* Gold */
        .rank-2 .podium-base { background-image: linear-gradient(to top, #C0C0C0, #A9A9A9); height: 9rem; } /* Silver */
        .rank-3 .podium-base { background-image: linear-gradient(to top, #CD7F32, #A0522D); height: 7rem; } /* Bronze */

        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#123458', // Main primary color
                        'primary-dark': '#0f2a48', // Darker shade for hover
                        'primary-darker': '#0c223a', // Even darker
                        secondary: '#D4C9BE',
                        light: '#F1EFEC',
                        dark: '#030303',
                        // For dropdown item active state, if bg-primary-700 is intended as a specific shade
                        // 'primary-700': '#your_specific_shade_for_bg-primary-700',
                    }
                },
            },
        }
    </script>
</head>
<body class="bg-slate-custom-900 text-slate-custom-200 font-sans pt-16 min-h-screen">

    <!-- Top Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 topnav shadow-lg z-50 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-laptop-house text-light mr-2 text-xl"></i>
                        <span class="text-xl font-semibold text-light">Lab System - Admin</span>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <a href="admin_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Dashboard</a>
                    
                    <!-- Records Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Records
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="todays_sitins.php" <?php echo basename($_SERVER['PHP_SELF']) === 'todays_sitins.php' ? 'class="bg-primary-700"' : ''; ?>>Current Sit-ins</a>
                            <a href="sit_in_records.php" <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_records.php' ? 'class="bg-primary-700"' : ''; ?>>Sit-in Reports</a>
                            <a href="feedback_records.php" <?php echo basename($_SERVER['PHP_SELF']) === 'feedback_records.php' ? 'class="bg-primary-700"' : ''; ?>>Feedback Reports</a>
                            <a href="manage_sitins.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_sitins.php' ? 'class="bg-primary-700"' : ''; ?>>Manage Sit-ins</a>
                        </div>
                    </div>
                    
                    <!-- Management Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Management
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="studentlist.php" <?php echo basename($_SERVER['PHP_SELF']) === 'studentlist.php' ? 'class="bg-primary-700"' : ''; ?>>List of Students</a>
                            <a href="manage_reservation.php" <?php echo basename($_SERVER['PHP_SELF']) === 'manage_reservation.php' ? 'class="bg-primary-700"' : ''; ?>>Reservation Requests</a>
                            <a href="reservation_logs.php" <?php echo basename($_SERVER['PHP_SELF']) === 'reservation_logs.php' ? 'class="bg-primary-700"' : ''; ?>>Reservation Logs</a>
                            <a href="admin_upload_resources.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_upload_resources.php' ? 'class="bg-primary-700"' : ''; ?>>Upload Resources</a>
                            <a href="admin_leaderboard.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_leaderboard.php' ? 'class="bg-primary-700"' : ''; ?>>Leaderboard</a>
                            <a href="admin_lab_schedule.php" <?php echo basename($_SERVER['PHP_SELF']) === 'admin_lab_schedule.php' ? 'class="bg-primary-700"' : ''; ?>>Lab Schedule</a>
                            <a href="lab_management.php" <?php echo basename($_SERVER['PHP_SELF']) === 'lab_management.php' ? 'class="bg-primary-700"' : ''; ?>>Lab Management</a>
                        </div>
                    </div>
                    
                    <a href="create_announcement.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'create_announcement.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Announcements</a>
                    
                    <!-- User and Notification Controls -->
                    <div class="flex items-center gap-4 ml-4">
                        <!-- Notification Button -->
                        <div class="relative">
                            <button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10 transition-all duration-200 focus:outline-none">
                                <i class="fas fa-bell text-lg"></i>
                                <span class="notification-badge hidden">0</span>
                            </button>
                            
                            <!-- Notification Dropdown -->
                            <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-secondary/20 z-50 overflow-hidden">
                                <div class="p-3 bg-primary text-white flex justify-between items-center">
                                    <span class="font-semibold">Notifications</span>
                                    <button id="markAllRead" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded transition-all">
                                        Mark all as read
                                    </button>
                                </div>
                                <div id="notificationList" class="max-h-80 overflow-y-auto">
                                    <div class="p-4 text-center text-secondary">No notifications</div>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center gap-2 group focus:outline-none">
                                <div class="relative">
                                    <img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary transition-all" 
                                        src="uploads/<?php echo htmlspecialchars($admin_profile_picture); ?>" 
                                        onerror="this.src='assets/default_avatar.png'" 
                                        alt="Profile">
                                </div>
                                <span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($admin_firstname); ?></span>
                            </button>
                            
                            <!-- Dropdown menu -->
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-secondary/20 z-50 overflow-hidden transition-all duration-200">
                                <div class="py-1">
                                    <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" 
                                    class="flex items-center px-4 py-2 text-sm text-dark hover:bg-secondary/20 transition-colors">
                                        <i class="fas fa-sign-out-alt mr-2 text-primary"></i>
                                        Log Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="mobile-menu md:hidden flex items-center">
                    <button id="mobileMenuButton" class="text-light hover:text-secondary focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu (hidden by default) -->
        <div id="mobileMenu" class="hidden md:hidden bg-primary">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="admin_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Dashboard</a>
                <a href="todays_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Current Sit-ins</a>
                <a href="sit_in_records.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in Reports</a>
                <a href="feedback_records.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Feedback Reports</a>
                <a href="manage_sitins.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Manage Sit-ins</a>
                <a href="studentlist.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">List of Students</a>
                <a href="manage_reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation Requests</a>
                <a href="reservation_logs.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation Logs</a>
                <a href="admin_upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Upload Resources</a>
                <a href="admin_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Leaderboard</a>
                <a href="admin_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
                <a href="lab_management.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Management</a>
                <a href="create_announcement.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Announcements</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-blue-500">
                </i>Student Leaderboard
            </h1>
            <p class="mt-2 text-slate-custom-400 text-lg">Recognizing top performers and their achievements.</p>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg relative mb-6 shadow-md" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>


        <!-- Podium Section -->
        <section class="mb-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
                <!-- 2nd Place -->
                <div class="flex flex-col items-center podium-item rank-2 order-2 md:order-1">
                    <?php if (isset($top3_students[1])): $student = $top3_students[1]; ?>
                    <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student['firstname']); ?>" class="w-24 h-24 rounded-full border-4 border-silver object-cover mb-[-3rem] z-10 relative shadow-lg">
                    <div class="podium-base w-full rounded-t-xl pt-16 p-6 text-center shadow-2xl">
                        <h3 class="text-2xl font-semibold text-slate-custom-900"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
                        <p class="text-3xl font-bold text-white mt-1"><?php echo $student['points']; ?> <span class="text-sm font-normal">pts</span></p>
                    </div>
                    <?php else: ?>
                        <div class="podium-base w-full rounded-t-xl p-6 text-center h-36 flex items-center justify-center"><span class="text-slate-custom-700">Position Available</span></div>
                    <?php endif; ?>
                </div>

                <!-- 1st Place -->
                <div class="flex flex-col items-center podium-item rank-1 order-1 md:order-2">
                     <?php if (isset($top3_students[0])): $student = $top3_students[0]; ?>
                    <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student['firstname']); ?>" class="w-32 h-32 rounded-full border-4 border-gold object-cover mb-[-4rem] z-10 relative shadow-xl">
                    <div class="podium-base w-full rounded-t-xl pt-20 p-8 text-center shadow-2xl">
                        <h3 class="text-3xl font-bold text-slate-custom-900"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
                        <p class="text-4xl font-extrabold text-white mt-1"><?php echo $student['points']; ?> <span class="text-lg font-normal">pts</span></p>
                    </div>
                     <?php else: ?>
                        <div class="podium-base w-full rounded-t-xl p-8 text-center h-48 flex items-center justify-center"><span class="text-slate-custom-700">Position Available</span></div>
                    <?php endif; ?>
                </div>

                <!-- 3rd Place -->
                <div class="flex flex-col items-center podium-item rank-3 order-3">
                    <?php if (isset($top3_students[2])): $student = $top3_students[2]; ?>
                    <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student['firstname']); ?>" class="w-20 h-20 rounded-full border-4 border-bronze object-cover mb-[-2.5rem] z-10 relative shadow-md">
                    <div class="podium-base w-full rounded-t-xl pt-12 p-5 text-center shadow-2xl">
                        <h3 class="text-xl font-semibold text-slate-custom-900"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
                        <p class="text-2xl font-bold text-white mt-1"><?php echo $student['points']; ?> <span class="text-xs font-normal">pts</span></p>
                    </div>
                    <?php else: ?>
                         <div class="podium-base w-full rounded-t-xl p-5 text-center h-28 flex items-center justify-center"><span class="text-slate-custom-700">Position Available</span></div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Full Leaderboard Table & Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <section class="lg:col-span-2 bg-slate-custom-800/70 backdrop-blur-sm p-6 rounded-xl shadow-xl border border-slate-custom-700">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-slate-custom-100">Full Rankings</h2>
                    <button onclick="openAdjustPointsModal(null)" class="bg-sky-500 hover:bg-sky-600 text-white font-medium py-2 px-4 rounded-lg transition duration-150 text-sm flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i> Adjust Points
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-custom-700">
                            <tr>
                                <th class="p-3 text-left text-slate-custom-400">Rank</th>
                                <th class="p-3 text-left text-slate-custom-400">Student</th>
                                <th class="p-3 text-left text-slate-custom-400">ID No.</th>
                                <th class="p-3 text-center text-slate-custom-400">Points</th>
                                <th class="p-3 text-center text-slate-custom-400">Sessions Left</th>
                                <th class="p-3 text-center text-slate-custom-400">Total Duration</th>
                                <th class="p-3 text-center text-slate-custom-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-custom-700/50">
                            <?php
                            $rank = 1;
                            mysqli_data_seek($students_result, 0); // Reset pointer for the main loop
                            while($student = mysqli_fetch_assoc($students_result)):
                                $profile_pic = !empty($student['profile_picture']) ? $student['profile_picture'] : 'default_avatar.png';
                            ?>
                            <tr class="hover:bg-slate-custom-700/50 transition-colors duration-150">
                                <td class="p-3">
                                    <span class="font-bold text-lg <?php
                                        if ($rank == 1) echo 'text-gold';
                                        elseif ($rank == 2) echo 'text-silver';
                                        elseif ($rank == 3) echo 'text-bronze';
                                        else echo 'text-slate-custom-300';
                                    ?>">
                                        #<?php echo $rank; ?>
                                    </span>
                                </td>
                                <td class="p-3 flex items-center">
                                    <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student['firstname']); ?>" class="w-10 h-10 rounded-full mr-3 object-cover border-2 border-slate-custom-600">
                                    <div>
                                        <div class="font-medium text-slate-custom-100"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></div>
                                        <div class="text-xs text-slate-custom-400">ID: <?php echo htmlspecialchars($student['id']); ?></div>
                                    </div>
                                </td>
                                <td class="p-3 text-slate-custom-300 font-mono"><?php echo htmlspecialchars($student['idno']); ?></td>
                                <td class="p-3 text-center font-semibold text-sky-400"><?php echo $student['points']; ?></td>
                                <td class="p-3 text-center text-slate-custom-300"><?php echo $student['remaining_sessions']; ?></td>
                                <td class="p-3 text-center text-slate-custom-300 font-mono"><?php echo $student['total_duration']; ?></td>
                                <td class="p-3 text-center">
                                     <button onclick="openAdjustPointsModal('<?php echo $student['id']; ?>', '<?php echo htmlspecialchars(addslashes($student['firstname'] . ' ' . $student['lastname'])); ?>', '<?php echo $student['points']; ?>')"
                                        class="text-sky-400 hover:text-sky-300 transition text-xs" title="Adjust Points">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                            <?php if(mysqli_num_rows($students_result) == 0): ?>
                                <tr><td colspan="7" class="p-4 text-center text-slate-custom-400">No students found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-slate-custom-800/70 backdrop-blur-sm p-6 rounded-xl shadow-xl border border-slate-custom-700">
                <h2 class="text-2xl font-semibold text-slate-custom-100 mb-6">Recent Point Activities</h2>
                <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                    <?php
                    mysqli_data_seek($logs_result, 0); // Reset pointer
                    while($log = mysqli_fetch_assoc($logs_result)):
                        $log_profile_pic = !empty($log['student_profile_picture']) ? $log['student_profile_picture'] : 'default_avatar.png';
                        $points_class = $log['points_earned'] >= 0 ? 'text-green-400' : 'text-red-400';
                        $points_prefix = $log['points_earned'] >= 0 ? '+' : '';
                    ?>
                    <div class="flex items-start p-3 bg-slate-custom-700/30 rounded-lg hover:bg-slate-custom-700/50 transition-colors">
                        <img src="uploads/<?php echo htmlspecialchars($log_profile_pic); ?>" onerror="this.src='assets/default_avatar.png'" alt="" class="w-10 h-10 rounded-full mr-3 object-cover border-2 border-slate-custom-600">
                        <div class="flex-1">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-slate-custom-200"><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></span>
                                <span class="text-xs text-slate-custom-400"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></span>
                            </div>
                            <p class="text-sm text-slate-custom-300 mt-1">
                                Action: <span class="font-semibold"><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></span>
                                <?php if(!empty($log['description'])): ?>
                                    <span class="text-xs text-slate-custom-400 block truncate">Desc: <?php echo htmlspecialchars($log['description']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="ml-3 text-lg font-bold <?php echo $points_class; ?>"><?php echo $points_prefix . $log['points_earned']; ?></span>
                    </div>
                    <?php endwhile; ?>
                     <?php if(mysqli_num_rows($logs_result) == 0): ?>
                        <p class="p-4 text-center text-slate-custom-400">No recent activities.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Adjust Points Modal -->
    <div id="adjustPointsModal" class="modal fixed inset-0 bg-black/70 backdrop-blur-sm items-center justify-center p-4 z-[100]">
        <div class="bg-slate-custom-800 p-6 rounded-xl shadow-2xl w-full max-w-md border border-slate-custom-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-slate-custom-100">Adjust Student Points</h3>
                <button onclick="closeAdjustPointsModal()" class="text-slate-custom-400 hover:text-slate-custom-200 text-2xl">×</button>
            </div>
            <form method="POST" action="admin_leaderboard.php">
                <input type="hidden" name="adjust_points" value="1">
                <div class="mb-4">
                    <label for="student_id_to_adjust" class="block text-sm font-medium text-slate-custom-300 mb-1">Student</label>
                    <select name="student_id_to_adjust" id="student_id_to_adjust" required class="w-full bg-slate-custom-700 border border-slate-custom-600 text-slate-custom-200 p-2 rounded-md focus:ring-sky-500 focus:border-sky-500">
                        <option value="">Select Student</option>
                        <?php
                        mysqli_data_seek($students_result, 0); // Reset pointer again
                        while($s = mysqli_fetch_assoc($students_result)) {
                            echo "<option value='{$s['id']}' data-current-points='{$s['points']}'>" . htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) . " (ID: {$s['idno']})</option>";
                        }
                        ?>
                    </select>
                    <p class="text-xs text-slate-custom-400 mt-1">Current Points: <span id="current_student_points_display">-</span></p>
                </div>
                <div class="mb-4">
                    <label for="points_to_add" class="block text-sm font-medium text-slate-custom-300 mb-1">Points to Add/Subtract</label>
                    <input type="number" name="points_to_add" id="points_to_add" required class="w-full bg-slate-custom-700 border border-slate-custom-600 text-slate-custom-200 p-2 rounded-md focus:ring-sky-500 focus:border-sky-500" placeholder="e.g., 10 or -5">
                </div>
                <div class="mb-6">
                    <label for="reason" class="block text-sm font-medium text-slate-custom-300 mb-1">Reason/Description</label>
                    <textarea name="reason" id="reason" rows="2" class="w-full bg-slate-custom-700 border border-slate-custom-600 text-slate-custom-200 p-2 rounded-md focus:ring-sky-500 focus:border-sky-500" placeholder="Reason for adjustment"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAdjustPointsModal()" class="px-4 py-2 text-sm font-medium text-slate-custom-300 bg-slate-custom-600 hover:bg-slate-custom-500 rounded-lg transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Script for Top Navigation Bar (from admin_upload_resources.php)
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        if(mobileMenuButton) mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));

        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');
         if(userMenuButton) userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) notificationDropdown.classList.add('hidden');
        });

        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.querySelector('.notification-badge');
        const markAllReadBtn = document.getElementById('markAllRead');

        if(notificationButton) notificationButton.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            if (userDropdown && !userDropdown.classList.contains('hidden')) userDropdown.classList.add('hidden');
            if (!notificationDropdown.classList.contains('hidden')) loadNotifications();
        });
        
        if(notificationDropdown) notificationDropdown.addEventListener('click', e => e.stopPropagation());
        if(userDropdown) userDropdown.addEventListener('click', e => e.stopPropagation());


        document.addEventListener('click', () => {
            if(userDropdown) userDropdown.classList.add('hidden');
            if(notificationDropdown) notificationDropdown.classList.add('hidden');
        });

        function loadNotifications() { /* ... Your existing loadNotifications ... */ }
        function markAllNotificationsAsRead() { /* ... Your existing markAllNotificationsAsRead ... */ }
        if (notificationButton) {
           // loadNotifications(); // Initial load
           // setInterval(loadNotifications, 30000); // Periodic refresh
           // if(markAllReadBtn) markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);
        }
        // Placeholder for notification functions - integrate your actual ones
        function loadNotifications() {
            // console.log("Loading notifications...");
            // Dummy data for now if get_notifications.php is not ready
            const notificationList = document.getElementById('notificationList');
            const badge = document.querySelector('.notification-badge');
            if(notificationList) notificationList.innerHTML = '<div class="p-4 text-center text-secondary">No new notifications.</div>';
            if(badge) badge.classList.add('hidden');
        }
        if(markAllReadBtn) markAllReadBtn.addEventListener('click', loadNotifications); // just to simulate
        loadNotifications();


        // Modal script
        const modal = document.getElementById('adjustPointsModal');
        const studentSelect = document.getElementById('student_id_to_adjust');
        const pointsDisplay = document.getElementById('current_student_points_display');

        function openAdjustPointsModal(studentId = null, studentName = '', currentPoints = '-') {
            modal.classList.add('active');
            if (studentId) {
                studentSelect.value = studentId;
                const option = studentSelect.querySelector(`option[value="${studentId}"]`);
                if(option) pointsDisplay.textContent = option.dataset.currentPoints || '-';
                else pointsDisplay.textContent = currentPoints; // Fallback if option not found quickly
            } else {
                studentSelect.value = ''; // Clear for general adjustment
                pointsDisplay.textContent = '-';
            }
            document.getElementById('points_to_add').value = '';
            document.getElementById('reason').value = '';
        }

        function closeAdjustPointsModal() {
            modal.classList.remove('active');
        }

        if (studentSelect) {
            studentSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    pointsDisplay.textContent = selectedOption.dataset.currentPoints || '-';
                } else {
                    pointsDisplay.textContent = '-';
                }
            });
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('active')) {
                closeAdjustPointsModal();
            }
        });
         // Close modal if clicked outside of it
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeAdjustPointsModal();
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