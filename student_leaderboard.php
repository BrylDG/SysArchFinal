<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info with points
$user_query = "SELECT id, firstname, lastname, profile_picture, remaining_sessions, points, idno, course, yearlevel, email 
               FROM users 
               WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($user_id, $firstname, $lastname, $profile_picture, $remaining_sessions, $points, $idno, $course, $yearlevel, $email);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Fetch top 3 students for the podium
$top3_query = "SELECT id, firstname, lastname, points, remaining_sessions, profile_picture
              FROM users 
              WHERE role = 'student'
              ORDER BY points DESC, remaining_sessions DESC
              LIMIT 3";
$top3_result = mysqli_query($conn, $top3_query);
$top3 = [];
while ($row = mysqli_fetch_assoc($top3_result)) {
    $top3[] = $row;
}

// Fetch top 10 students for leaderboard table
$leaderboard_query = "SELECT id, firstname, lastname, points, remaining_sessions 
                     FROM users 
                     WHERE role = 'student'
                     ORDER BY points DESC, remaining_sessions DESC
                     LIMIT 10";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);

// Calculate student's rank
$rank_query = "SELECT COUNT(*) + 1 as rank 
              FROM users 
              WHERE role = 'student' AND (points > ? OR (points = ? AND remaining_sessions > ?))";
$stmt = $conn->prepare($rank_query);
$stmt->bind_param("iii", $points, $points, $remaining_sessions);
$stmt->execute();
$stmt->bind_result($student_rank);
$stmt->fetch();
$stmt->close();

// Get all students for accurate ranking
$all_students_query = "SELECT id, firstname, lastname, points, remaining_sessions 
                      FROM users 
                      WHERE role = 'student'
                      ORDER BY points DESC, remaining_sessions DESC";
$all_students_result = mysqli_query($conn, $all_students_query);

// Find actual rank by iterating through all students
$actual_rank = 1;
$found = false;
while ($row = mysqli_fetch_assoc($all_students_result)) {
    if ($row['id'] == $user_id) {
        $found = true;
        break;
    }
    $actual_rank++;
}

// Use the more accurate ranking method
$student_rank = $found ? $actual_rank : $student_rank;

$page_title = "Leaderboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CCS SIT Monitoring System</title>
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
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            border-bottom: 1px solid #D4C9BE;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: #F1EFEC;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0.5rem;
            border: 1px solid #D4C9BE;
        }
        
        .dropdown-menu a {
            color: #030303;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .dropdown-menu a:hover {
            background-color: rgba(212, 201, 190, 0.3);
        }
        
        .show {
            display: block;
        }
        
        body {
            background-color: #F1EFEC;
            color: #030303;
        }
        
        .topnav {
            background-color: #123458;
            color: #F1EFEC;
        }
        
        .card {
            background-color: #F1EFEC;
            border: 1px solid #D4C9BE;
        }
        
        .hover-effect:hover {
            background-color: rgba(212, 201, 190, 0.3);
        }
        
        .mobile-menu {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-menu {
                display: block;
            }
            .desktop-menu {
                display: none;
            }
        }
        
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
        }
        
        .highlight-row {
            background-color: rgba(18, 52, 88, 0.1);
        }
        
        /* Nav dropdown styles */
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
        
        .rank-1 {
            background-color: rgba(253, 230, 138, 0.3);
            border-color: rgba(253, 230, 138, 0.5);
        }
        
        .rank-2 {
            background-color: rgba(209, 213, 219, 0.3);
            border-color: rgba(209, 213, 219, 0.5);
        }
        
        .rank-3 {
            background-color: rgba(253, 186, 116, 0.3);
            border-color: rgba(253, 186, 116, 0.5);
        }
        /* Add these new styles */
        .leaderboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .leaderboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            margin: 40px 0;
        }
        
        .podium-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 120px;
        }
        
        .podium-step.first {
            height: 180px;
            background: linear-gradient(to bottom, #FFD700, #FFC000);
        }
        
        .podium-step.second {
            height: 140px;
            background: linear-gradient(to bottom, #C0C0C0, #A0A0A0);
        }
        
        .podium-step.third {
            height: 100px;
            background: linear-gradient(to bottom, #CD7F32, #B87333);
        }
        
        .podium-step .medal {
            font-size: 32px;
            margin-top: -20px;
            margin-bottom: 10px;
        }
        
        .podium-step .name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .podium-step .points {
            font-size: 14px;
            color: #666;
        }
        
        .leaderboard-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        
        .leaderboard-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f8f9fa;
            color: #123458;
            font-weight: 600;
            border-bottom: 2px solid #D4C9BE;
        }
        
        .leaderboard-table td {
            padding: 16px;
            background: white;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .leaderboard-table tr:first-child td {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .leaderboard-table tr:last-child td {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        
        .leaderboard-table tr:hover td {
            background: #f8f9fa;
        }
        
        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: 600;
            color: white;
        }
        
        .rank-1 .rank-badge {
            background: #FFD700;
        }
        
        .rank-2 .rank-badge {
            background: #C0C0C0;
        }
        
        .rank-3 .rank-badge {
            background: #CD7F32;
        }
        
        .rank-other .rank-badge {
            background: #123458;
        }
        
        .you-badge {
            background: #123458;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: #D4C9BE;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #123458;
            margin: 8px 0;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        
    </style>
</head>
<body class="min-h-screen font-sans pt-16">
    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-lg z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <span class="text-xl font-semibold text-light">Lab System</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Profile</a>
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 bg-slate-700/20 text-white">Leaderboard</a>
                    
                    <!-- Rules Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Rules
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="sit-in-rules.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Sit-in Rules</a>
                            <a href="lab-rules.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Rules & Regulations</a>
                        </div>
                    </div>
                    
                    <!-- Lab Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Lab
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="upload_resources.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Resources</a>
                            <a href="student_lab_schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Schedule</a>
                        </div>
                    </div>
                    
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
                                    <a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-dark hover:bg-secondary/20 transition-colors">
                                        <i class="fas fa-user-edit mr-2 text-primary"></i>
                                        Edit Profile
                                    </a>
                                    <div class="border-t border-secondary/20 my-1"></div>
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
                <a href="student_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Profile</a>
                <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Edit Profile</a>
                <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Announcements</a>
                <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation</a>
                <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Leaderboard</a>
                <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in Rules</a>
                <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Rules</a>
                <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Resources</a>
                <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
            </div>
        </div>
    </nav>

     <!-- Main Content -->
     <div class="container mx-auto p-6">
        <div class="leaderboard-card p-8 mb-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-dark mb-2">
                        Student Leaderboard
                    </h1>
                </div>
                <div class="bg-primary/10 px-4 py-2 rounded-full">
                    <span class="font-medium text-primary">Your Rank: #<?php echo $student_rank; ?></span>
                </div>
            </div>

            <!-- Your Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Your Rank</div>
                    <div class="value">
                        #<?php echo $student_rank; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="label">Your Points</div>
                    <div class="value"><?php echo $points; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Remaining Sessions</div>
                    <div class="value"><?php echo $remaining_sessions; ?></div>
                </div>
            </div>

            <!-- Podium -->
            <h2 class="text-xl font-semibold mb-6 text-dark">Top Performers</h2>
            <div class="podium">
                <?php if (isset($top3[1])): ?>
                <div class="podium-step second rounded-t-lg">
                    <div class="name"><?= htmlspecialchars($top3[1]['firstname']) ?></div>
                    <div class="points"><?= $top3[1]['points'] ?> pts</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($top3[0])): ?>
                <div class="podium-step first rounded-t-lg">
                    <div class="name"><?= htmlspecialchars($top3[0]['firstname']) ?></div>
                    <div class="points"><?= $top3[0]['points'] ?> pts</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($top3[2])): ?>
                <div class="podium-step third rounded-t-lg">
                    <div class="name"><?= htmlspecialchars($top3[2]['firstname']) ?></div>
                    <div class="points"><?= $top3[2]['points'] ?> pts</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Leaderboard Table -->
            <h2 class="text-xl font-semibold mb-6 text-dark">Full Leaderboard</h2>
            <div class="overflow-x-auto">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th style="background: #123458; color:white;">Rank</th>
                            <th style="background: #123458; color:white;">Student</th>
                            <th style="background: #123458; color:white;" class="text-right">Points</th>
                            <th style="background: #123458; color:white;" class="text-right">Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        mysqli_data_seek($leaderboard_result, 0);
                        while($row = mysqli_fetch_assoc($leaderboard_result)): 
                            $highlight = ($row['firstname'] == $firstname && $row['lastname'] == $lastname);
                            $rank_class = '';
                            if ($rank == 1) $rank_class = 'rank-1';
                            elseif ($rank == 2) $rank_class = 'rank-2';
                            elseif ($rank == 3) $rank_class = 'rank-3';
                            else $rank_class = 'rank-other';
                        ?>
                        <tr class="<?php echo $rank_class; ?>">
                            <td>
                                <span class="rank-badge"><?php echo $rank; ?></span>
                            </td>
                            <td>
                                <div class="flex items-center">
                                    <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>
                                    <?php if($highlight): ?>
                                        <span class="you-badge">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-left font-medium"><?php echo $row['points']; ?></td>
                            <td class="text-left font-medium"><?php echo $row['remaining_sessions']; ?></td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
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
        
        // Prevent dropdown from closing when clicking inside
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Mark all as read
        document.getElementById('markAllRead').addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.add('hidden');
            notificationDropdown.classList.add('hidden');
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
                        notificationItem.className = `p-3 notification-item ${notification.is_read ? 'text-secondary' : 'text-dark bg-secondary/50'}`;
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