<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables with default values
$firstname = '';
$profile_picture = 'default_avatar.png';

// Fetch student info if session exists
if (isset($_SESSION['idno'])) {
    $idno = $_SESSION['idno'];
    
    $user_query = "SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $idno);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $profile_picture);
    $stmt->fetch();
    $stmt->close();

    // Set default profile picture if none exists
    if (empty($profile_picture)) {
        $profile_picture = "default_avatar.png";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Rules - CCS SIT Monitoring System</title>
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
        
        .rule-card {
            border-left: 4px solid #123458;
            transition: all 0.2s ease;
        }
        
        .rule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Profile</a>
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                    <a href="student_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200 <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Leaderboard</a>
                    
                    <!-- Rules Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20 bg-slate-700/20 text-white">
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
                <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20 bg-primary/50">Sit-in Rules</a>
                <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Rules</a>
                <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Resources</a>
                <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-primary mb-2">Sit-in Rules & Guidelines</h1>
            <p class="text-secondary text-lg">Essential policies for all students participating in sit-in sessions</p>
        </div>
        <div class="bg-primary/10 px-4 py-2 rounded-full flex items-center">
            <i class="fas fa-info-circle text-primary mr-2"></i>
            <span class="text-primary font-medium">Last updated: June 2023</span>
        </div>
    </div>

    <!-- Rules Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- General Rules Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border-l-4 border-primary hover:shadow-lg transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-primary/10 p-3 rounded-lg mr-4">
                        <i class="fas fa-clipboard-check text-primary text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark">General Rules</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center">
                                <i class="fas fa-check text-primary text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Register before attending any sit-in session</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center">
                                <i class="fas fa-check text-primary text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Follow proper dress code at all times</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center">
                                <i class="fas fa-check text-primary text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Latecomers (15+ minutes) won't be admitted</p>
                    </li>
                </ul>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                <div class="flex items-center text-sm text-primary">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>Mandatory for all students</span>
                </div>
            </div>
        </div>

        <!-- Behavior Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border-l-4 border-blue-500 hover:shadow-lg transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark">Behavior & Conduct</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-check text-blue-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Maintain respect and discipline at all times</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-check text-blue-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">No mobile phones or electronic devices</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-check text-blue-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Report absences in advance</p>
                    </li>
                </ul>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                <div class="flex items-center text-sm text-blue-500">
                    <i class="fas fa-user-shield mr-2"></i>
                    <span>Professional conduct expected</span>
                </div>
            </div>
        </div>

        <!-- Consequences Card -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border-l-4 border-red-500 hover:shadow-lg transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark">Consequences</h3>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Suspension of sit-in privileges</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Possible permanent ban for repeat violations</p>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-5 w-5 rounded-full bg-red-100 flex items-center justify-center">
                                <i class="fas fa-times text-red-500 text-xs"></i>
                            </div>
                        </div>
                        <p class="ml-3 text-secondary">Disciplinary action may be taken</p>
                    </li>
                </ul>
            </div>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                <div class="flex items-center text-sm text-red-500">
                    <i class="fas fa-gavel mr-2"></i>
                    <span>Strictly enforced</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information Section -->
    <div class="mt-12 bg-gradient-to-r from-primary to-blue-700 rounded-xl shadow-lg overflow-hidden">
        <div class="p-8 md:p-10 text-white">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-2/3 mb-6 md:mb-0">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">Need More Information?</h2>
                    <p class="text-white/90 mb-4">For any questions or clarifications regarding sit-in rules and procedures, please contact the lab administrator or your course instructor.</p>
                    <div class="flex flex-wrap gap-3">
                        <div class="bg-white/10 px-4 py-2 rounded-full flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>labadmin@ccs.edu</span>
                        </div>
                        <div class="bg-white/10 px-4 py-2 rounded-full flex items-center">
                            <i class="fas fa-phone-alt mr-2"></i>
                            <span>(02) 1234-5678</span>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/3 flex justify-center">
                    <div class="bg-white/20 p-6 rounded-full">
                        <i class="fas fa-question-circle text-5xl text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-dark mb-6">Frequently Asked Questions</h2>
        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-toggle w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-medium text-lg text-dark">Can I attend sit-in sessions without prior registration?</span>
                    <i class="fas fa-chevron-down transition-transform duration-200"></i>
                </button>
                <div class="faq-content px-6 pb-4 hidden">
                    <p class="text-secondary">No, all students must register for sit-in sessions at least 24 hours in advance. Walk-ins are not permitted to ensure proper lab capacity management.</p>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-toggle w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-medium text-lg text-dark">What happens if I violate the dress code?</span>
                    <i class="fas fa-chevron-down transition-transform duration-200"></i>
                </button>
                <div class="faq-content px-6 pb-4 hidden">
                    <p class="text-secondary">First violation will result in a warning. Subsequent violations may lead to suspension of sit-in privileges for one week. Repeated violations may result in permanent ban from sit-in sessions.</p>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-toggle w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <span class="font-medium text-lg text-dark">How can I appeal a sit-in suspension?</span>
                    <i class="fas fa-chevron-down transition-transform duration-200"></i>
                </button>
                <div class="faq-content px-6 pb-4 hidden">
                    <p class="text-secondary">Submit a written appeal to the lab administrator within 3 days of the suspension. Include your explanation and any supporting documents. The appeals committee will review your case within 5 working days.</p>
                </div>
            </div>
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
                        notificationList.innerHTML = '<div class="p-4 text-center text-secondary">No notifications</div>';
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

    // FAQ Toggle Functionality
    document.querySelectorAll('.faq-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const content = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            // Toggle content visibility
            content.classList.toggle('hidden');
            
            // Rotate icon
            icon.classList.toggle('rotate-180');
            
            // Close other open FAQs
            document.querySelectorAll('.faq-toggle').forEach(otherButton => {
                if (otherButton !== button) {
                    otherButton.nextElementSibling.classList.add('hidden');
                    otherButton.querySelector('i').classList.remove('rotate-180');
                }
            });
        });
    });

        
        // Load notifications on page load
        loadNotifications();

        // Check for new notifications every 30 seconds
        setInterval(loadNotifications, 30000);
    </script>
</body>
</html>