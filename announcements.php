<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info
$user_query = "SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

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
    } else {
        $profile_picture = "default_avatar.png";
    }
}

// Fetch all announcements
$announcement_query = "SELECT * FROM announcements ORDER BY created_at DESC";
$announcement_result = mysqli_query($conn, $announcement_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
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
        
        .announcement-card {
            background: rgba(241, 239, 236, 0.9);
            border: 1px solid #D4C9BE;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
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
        
        .show {
            display: block;
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

        /* New dropdown styles */
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
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen font-sans">
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
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                    <a href="student_leaderboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Leaderboard</a>
                    
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


    <!-- Mobile Menu (hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden fixed top-16 left-0 right-0 bg-white shadow-lg z-40">
        <div class="container mx-auto px-4 py-2">
            <a href="student_dashboard.php" class="block py-2 px-4 text-dark hover:bg-secondary/20">Profile</a>
            <a href="announcements.php" class="block py-2 px-4 bg-secondary/20 text-dark">Announcements</a>
            <a href="reservation.php" class="block py-2 px-4 text-dark hover:bg-secondary/20">Reservation</a>
            <a href="sit_in_history.php" class="block py-2 px-4 text-dark hover:bg-secondary/20">Sit-in History</a>
            <a href="upload_resources.php" class="block py-2 px-4 text-dark hover:bg-secondary/20">Lab Resources</a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block py-2 px-4 text-dark hover:bg-secondary/20">Log Out</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="pt-20 pb-6 px-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-dark">Announcements</h1>
            </div>

            <?php if (mysqli_num_rows($announcement_result) > 0): ?>
                <div class="space-y-6">
                    <?php while ($announcement = mysqli_fetch_assoc($announcement_result)): ?>
                        <div class="announcement-card p-6 rounded-lg shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-semibold text-dark"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <span class="text-xs text-secondary"><?php echo $announcement['created_at']; ?></span>
                            </div>
                            <div class="text-dark">
                                <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-secondary text-lg">No announcements available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

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
                userDropdown.classList.toggle('hidden'); // Changed from 'show' to 'hidden'
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                userDropdown.classList.add('hidden'); // Changed from removing 'show' to adding 'hidden'
                notificationDropdown.classList.add('hidden');
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
        });
    </script>
</body>
</html>