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
$stmt = $conn->prepare("SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = 'default_avatar.png';
}

if (isset($_GET['action'])) {
    $sit_in_id = $_GET['id'];
    $action = $_GET['action'];
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT student_id, lab, pc_number FROM sit_in_records WHERE id = ?");
        $stmt->bind_param("i", $sit_in_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sit_in = $result->fetch_assoc();
        $student_id = $sit_in['student_id'];
        $lab = $sit_in['lab'];
        $pc_number = $sit_in['pc_number'];
        
        $stmt = $conn->prepare("UPDATE sit_in_records SET end_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $sit_in_id);
        $stmt->execute();
        
        // If PC was assigned, mark it as available again
        if (!empty($pc_number)) {
            $stmt = $conn->prepare("UPDATE lab_pcs SET status = 'Available' WHERE lab_name = ? AND pc_number = ?");
            $stmt->bind_param("si", $lab, $pc_number);
            $stmt->execute();
            $stmt->close();
        }
        
        // Get student info and deduct a session (for both reward and timeout)
        $stmt = $conn->prepare("SELECT points, remaining_sessions, idno FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        
        $current_sessions = $student['remaining_sessions'];
        $student_idno = $student['idno'];
        
        // Deduct one session (only if they have sessions available)
        if ($current_sessions > 0) {
            $current_sessions -= 1;
        } else {
            $current_sessions = 0; // Prevent negative sessions
        }
        
        if ($action === 'reward') {
            $new_points = $student['points'] + 1;
            
            // Add notification for point reward
            if ($new_points == 1) {
                $notification_msg = "You gained 1 point for your sit-in session (Total: $new_points point)";
            } else {
                $notification_msg = "You gained 1 point for your sit-in session (Total: $new_points points)";
            }
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $student_idno, $notification_msg);
            $stmt->execute();
            
            // Check if earned a session (every 3 points)
            $session_added = false;
            if ($new_points % 3 === 0) {
                $current_sessions += 1;
                $session_added = true;
            }
            
            $stmt = $conn->prepare("UPDATE users SET points = ?, remaining_sessions = ? WHERE id = ?");
            $stmt->bind_param("iii", $new_points, $current_sessions, $student_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO rewards_log (user_id, points_earned, action) VALUES (?, 1, 'sit_in_completion')");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            
            if ($session_added) {
                $session_msg = "You earned +1 session for reaching {$new_points} points! (Total: $current_sessions sessions)";
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmt->bind_param("ss", $student_idno, $session_msg);
                $stmt->execute();
                
                $stmt = $conn->prepare("INSERT INTO rewards_log (user_id, points_earned, action) VALUES (?, 1, 'session_reward')");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
            }
            
            $_SESSION['reward_message'] = "Student logged out and rewarded 1 point!" . ($session_added ? " (+1 session awarded)" : "");
        } else {
            // For timeout, just notify and update session count
            $stmt = $conn->prepare("UPDATE users SET remaining_sessions = ? WHERE id = ?");
            $stmt->bind_param("ii", $current_sessions, $student_id);
            $stmt->execute();
            
            $notification_msg = "You logged out without earning points this session. Remaining sessions: $current_sessions";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("ss", $student_idno, $notification_msg);
            $stmt->execute();
            
            $_SESSION['reward_message'] = "Student logged out successfully (1 session deducted)";
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Sit-in management error: " . $e->getMessage());
    }
    
    header("Location: manage_sitins.php");
    exit();
}

$active_sitins_query = "SELECT s.id, u.id as user_id, u.idno, u.firstname, u.lastname, s.purpose, s.lab, s.pc_number, s.start_time 
                        FROM sit_in_records s
                        JOIN users u ON s.student_id = u.id
                        WHERE s.end_time IS NULL
                        ORDER BY s.start_time DESC";
$active_sitins_result = mysqli_query($conn, $active_sitins_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sit-ins | Admin Dashboard</title>
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
                    colors: {
                        primary: '#123458',
                        secondary: '#D4C9BE',
                        light: '#F1EFEC',
                        dark: '#030303',
                    }
                },
            },
        }
        
        function confirmAction(action, sitInId) {
            if (action === 'reward') {
                return confirm("Give this student 1 point for their sit-in? (1 session will be deducted)");
            } else {
                return confirm("Log out this student without giving points? (1 session will be deducted)");
            }
        }
    </script>
    <style>
        .time-cell {
            min-width: 160px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-dropdown {
            position: relative;
        }
        .nav-dropdown-content {
            display: none;
            position: absolute;
            background-color: #123458;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        .nav-dropdown-content a {
            color: #F1EFEC;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.875rem;
        }
        .nav-dropdown-content a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .nav-dropdown:hover .nav-dropdown-content {
            display: block;
        }
        .nav-dropdown-btn {
            display: flex;
            align-items: center;
        }
        .nav-dropdown-btn::after {
            content: "▾";
            margin-left: 5px;
            font-size: 0.8em;
        }
    </style>
</head>
<body class="min-h-screen font-sans pt-16">
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
                                        src="uploads/<?php echo htmlspecialchars($profile_picture); ?>" 
                                        onerror="this.src='assets/default_avatar.png'" 
                                        alt="Profile">
                                </div>
                                <span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname); ?></span>
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
    <main class="pt-16 min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="mb-4 md:mb-0">
                        <h1 class="text-3xl font-bold text-gray-900">Manage Sit-ins</h1>
                        <p class="mt-2 text-sm text-gray-600">View and manage all active sit-in sessions</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo mysqli_num_rows($active_sitins_result); ?> active sessions
                        </span>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- Status Messages -->
            <?php if (isset($_SESSION['reward_message'])): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?php echo $_SESSION['reward_message']; unset($_SESSION['reward_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active Sit-ins Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Active Sit-in Sessions</h3>
                </div>
                
                <?php if ($active_sitins_result && mysqli_num_rows($active_sitins_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PC</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = mysqli_fetch_assoc($active_sitins_result)): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-medium">
                                                    <?php echo strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($row['idno']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($row['purpose']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($row['lab']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 time-cell">
                                            <?php 
                                            if ($row['start_time'] == '0000-00-00 00:00:00' || empty($row['start_time'])) {
                                                echo 'Not recorded';
                                            } else {
                                                $date = new DateTime($row['start_time']);
                                                echo $date->format('M d, Y h:i A');
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="manage_sitins.php?action=reward&id=<?php echo $row['id']; ?>" 
                                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                   onclick="return confirmAction('reward', <?php echo $row['id']; ?>);">
                                                    <i class="fas fa-gift mr-1"></i> Reward
                                                </a>
                                                <a href="manage_sitins.php?action=timeout&id=<?php echo $row['id']; ?>" 
                                                   class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                   onclick="return confirmAction('timeout', <?php echo $row['id']; ?>);">
                                                    <i class="fas fa-sign-out-alt mr-1"></i> Timeout
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-user-clock text-gray-400 text-4xl mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900">No active sit-ins</h3>
                        <p class="mt-1 text-sm text-gray-500">There are currently no active sit-in sessions to manage.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // User dropdown toggle
        document.getElementById('userMenuButton').addEventListener('click', function() {
            const menu = document.getElementById('userDropdown');
            menu.classList.toggle('hidden');
        });

        // Notification functionality
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.querySelector('.notification-badge');
        
        // Toggle notification dropdown
        notificationButton.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            loadNotifications();
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('userDropdown').classList.add('hidden');
            notificationDropdown.classList.add('hidden');
        });
        
        // Prevent dropdown from closing when clicking inside
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Mark all as read
        document.getElementById('markAllRead').addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
        
        // Function to load notifications
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    
                    if (data.length === 0) {
                        notificationList.innerHTML = '<div class="p-3 text-center text-secondary">No notifications</div>';
                        notificationBadge.classList.add('hidden');
                        return;
                    }
                    
                    notificationList.innerHTML = '';
                    let unreadCount = 0;
                    
                    data.forEach(notification => {
                        const notificationItem = document.createElement('div');
                        notificationItem.className = `p-3 border-b border-gray-200 ${notification.is_read ? 'text-secondary' : 'text-dark bg-secondary/50'}`;
                        notificationItem.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-sm">${notification.message}</p>
                                    <p class="text-xs text-secondary mt-1">${notification.created_at}</p>
                                </div>
                                ${notification.is_read ? '' : '<span class="w-2 h-2 rounded-full bg-primary ml-2"></span>'}
                            </div>
                        `;
                        notificationList.appendChild(notificationItem);
                        
                        if (!notification.is_read) {
                            unreadCount++;
                        }
                    });
                    
                    if (unreadCount > 0) {
                        notificationBadge.textContent = unreadCount;
                        notificationBadge.classList.remove('hidden');
                    } else {
                        notificationBadge.classList.add('hidden');
                    }
                });
        }
        
        // Function to mark all notifications as read
        function markAllNotificationsAsRead() {
            fetch('mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            });
        }
        
        // Load notifications on page load
        loadNotifications();

        // Check for new notifications every 30 seconds
        setInterval(loadNotifications, 30000);
    </script>
</body>
</html>