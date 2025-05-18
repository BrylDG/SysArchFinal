<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

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

// List of foul language words to detect
$foul_words = array('shit', 'fuck', 'asshole', 'bitch', 'damn', 'crap', 'piss', 'dick', 'cock', 
                   'pussy', 'fag', 'bastard', 'slut', 'whore', 'douche', 'faggot', 
                   'motherfucker', 'bullshit', 'bollocks', 'arsehole', 'wanker', 'twat', 
                   'cunt', 'prick', 'hell', 'darn', 'fucking', 'shitty', 'ass', 'dickhead');

// Function to check for foul language
function containsFoulLanguage($text, $foul_words) {
    foreach ($foul_words as $word) {
        if (preg_match("/\b" . preg_quote($word, '/') . "\b/i", $text)) {
            return true;
        }
    }
    return false;
}

// Fetch all sit-in records (both with and without feedback)
$records_query = "SELECT sr.id, sr.purpose, sr.lab, sr.start_time, sr.end_time, sr.feedback,
                         u.firstname, u.lastname, u.idno
                  FROM sit_in_records sr
                  JOIN users u ON sr.student_id = u.id
                  ORDER BY sr.start_time DESC";

$result = $conn->query($records_query);
$all_records = $result->fetch_all(MYSQLI_ASSOC);

// Count feedback with foul language
$foul_language_count = 0;
foreach ($all_records as $record) {
    if (!empty($record['feedback']) && containsFoulLanguage($record['feedback'], $foul_words)) {
        $foul_language_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports | Admin Dashboard</title>
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
    </script>
    <style>
        .foul-language {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 3px solid #b91c1c;
            padding-left: 8px;
        }
        
        .feedback-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .feedback-cell:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 10;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        .active-now {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
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
        .sidebar-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .sidebar-item:hover {
            background-color: rgba(18, 52, 88, 0.05);
            border-left-color: #123458;
        }
        .sidebar-item.active {
            background-color: rgba(18, 52, 88, 0.1);
            border-left-color: #123458;
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
                        <h1 class="text-3xl font-bold text-gray-900">Feedback Reports</h1>
                        <p class="mt-2 text-sm text-gray-600">View all student feedback from sit-in sessions</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <i class="fas fa-comment-alt mr-1"></i>
                            <?php 
                                $feedback_count = 0;
                                foreach ($all_records as $record) {
                                    if (!empty($record['feedback'])) $feedback_count++;
                                }
                                echo $feedback_count . ' feedback entries';
                            ?>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <?php echo $foul_language_count; ?> foul language
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($all_records); ?> total records
                        </span>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- Feedback Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <i class="fas fa-comment text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Feedback</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900"><?php echo $feedback_count; ?></div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">Foul Language</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php echo $foul_language_count; ?>
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <i class="fas fa-percentage text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">Feedback Rate</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php 
                                            $rate = count($all_records) > 0 ? round(($feedback_count / count($all_records)) * 100) : 0;
                                            echo $rate . '%';
                                        ?>
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <i class="fas fa-laptop-code text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">Labs with Feedback</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?php
                                            $labs_with_feedback = [];
                                            foreach ($all_records as $record) {
                                                if (!empty($record['feedback']) && !in_array($record['lab'], $labs_with_feedback)) {
                                                    $labs_with_feedback[] = $record['lab'];
                                                }
                                            }
                                            echo count($labs_with_feedback);
                                        ?>
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Records Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">All Feedback</h3>
                    <p class="text-sm text-gray-500">
                        Showing <?php echo count($all_records); ?> records
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($all_records)) { ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No sit-in records found.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($all_records as $record) { ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-medium">
                                                    <?php echo strtoupper(substr($record['firstname'], 0, 1) . substr($record['lastname'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['idno']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($record['lab']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['purpose']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date("M d, Y", strtotime($record['start_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 feedback-cell">
                                            <?php if (!empty($record['feedback'])) { ?>
                                                <div class="flex items-center <?php echo containsFoulLanguage($record['feedback'], $foul_words) ? 'foul-language' : ''; ?>">
                                                    <i class="fas fa-comment-alt <?php echo containsFoulLanguage($record['feedback'], $foul_words) ? 'text-red-500' : 'text-blue-500'; ?> mr-2"></i>
                                                    <?php echo htmlspecialchars($record['feedback']); ?>
                                                    <?php if (containsFoulLanguage($record['feedback'], $foul_words)) { ?>
                                                    <?php } ?>
                                                </div>
                                            <?php } else { ?>
                                                <span class="text-gray-400 italic">No feedback</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($all_records)) { ?>
                    <div class="px-6 py-4 border-t border-gray-200 text-sm text-gray-500">
                        Showing <?php echo count($all_records); ?> records
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileMenu = document.getElementById('mobileMenu');
            
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
            
            // Profile dropdown toggle
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');

            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
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
        });
    </script>
</body>
</html>