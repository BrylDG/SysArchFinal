<?php
session_start();
include 'db.php';

// Set the timezone for Philippines
date_default_timezone_set('Asia/Manila');

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
    $profile_picture = "default_avatar.png";
}

// List of available labs
$labs = array('Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544');

// Define day groups
$day_groups = array(
    'MW' => 'Monday/Wednesday',
    'TTh' => 'Tuesday/Thursday',
    'Fri' => 'Friday',
    'Sat' => 'Saturday'
);

// Generate time slots from 7:30 AM to 9:00 PM in 1.5 hour increments
$time_slots = array();
$start_time = strtotime('7:30 AM');
$end_time = strtotime('9:00 PM');

while ($start_time < $end_time) {
    $end_slot = $start_time + (90 * 60); // 1.5 hours in seconds
    $time_slots[] = array(
        'start' => date('h:i A', $start_time),
        'end' => date('h:i A', $end_slot),
        'display' => date('g:iA', $start_time) . '-' . date('g:iA', $end_slot)
    );
    $start_time = $end_slot;
}

// Get current day of week to determine which schedule to show by default
$current_day = date('w'); // 0 (Sunday) to 6 (Saturday)
$default_group = 'MW'; // Default to MW

if ($current_day == 1 || $current_day == 3) { // Monday or Wednesday
    $default_group = 'MW';
} elseif ($current_day == 2 || $current_day == 4) { // Tuesday or Thursday
    $default_group = 'TTh';
} elseif ($current_day == 5) { // Friday
    $default_group = 'Fri';
} elseif ($current_day == 6) { // Saturday
    $default_group = 'Sat';
}

// Check if a specific day group was requested
$selected_group = isset($_GET['group']) ? $_GET['group'] : $default_group;

// Fetch lab availability from database
$lab_availability = array();
$query = "SELECT lab_name, time_slot, status FROM static_lab_schedules WHERE day_group = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_group);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $lab_availability[$row['lab_name']][$row['time_slot']] = ($row['status'] === 'available');
}

$stmt->close();

// Default to available if no record exists in database
foreach ($labs as $lab) {
    foreach ($time_slots as $slot) {
        $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
        if (!isset($lab_availability[$lab][$time_slot_str])) {
            $lab_availability[$lab][$time_slot_str] = true;
        }
    }
}

$page_title = "Lab Schedule";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedule - CCS SIT Monitoring System</title>
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
        
        // Define day groups mapping for JavaScript
        const dayGroups = {
            'MW': 'Monday/Wednesday',
            'TTh': 'Tuesday/Thursday',
            'Fri': 'Friday',
            'Sat': 'Saturday'
        };
        
        function showDayGroup(groupCode) {
            // Hide all tables and deactivate all tabs
            document.querySelectorAll('.day-group-table').forEach(table => {
                table.classList.add('hidden');
            });
            document.querySelectorAll('.day-group-tab').forEach(tab => {
                tab.classList.remove('bg-primary', 'text-light');
                tab.classList.add('bg-secondary', 'text-dark');
            });
            
            // Show selected table and activate tab
            document.getElementById(`table-${groupCode}`).classList.remove('hidden');
            document.getElementById(`tab-${groupCode}`).classList.add('bg-primary', 'text-light');
            document.getElementById(`tab-${groupCode}`).classList.remove('bg-secondary', 'text-dark');
            
            // Update the title
            document.getElementById('schedule-title').textContent = `Lab Schedule - ${dayGroups[groupCode]}`;
            
            // Update URL without reloading
            history.pushState(null, '', `?group=${groupCode}`);
        }
    </script>
    <style>
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
        
        .status-available {
            background-color: rgba(74, 222, 128, 0.2);
            color: #16a34a;
        }
        
        .status-occupied {
            background-color: rgba(248, 113, 113, 0.2);
            color: #dc2626;
        }

        /* Ensure dropdowns appear above other content */
        .nav-dropdown-content {
            z-index: 1000;
        }

        #userDropdown {
            z-index: 1001;
        }

        #notificationDropdown {
            z-index: 1002;
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
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-primary/50 text-white' : ''; ?>">Profile</a>
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">Sit-in History</a>
                    <a href="student_leaderboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">Leaderboard</a>
                    
                    <!-- Rules Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">
                            Rules
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="sit-in-rules.php">Sit-in Rules</a>
                            <a href="lab-rules.php">Lab Rules & Regulations</a>
                        </div>
                    </div>
                    
                    <!-- Lab Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20">
                            Lab
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="upload_resources.php">Lab Resources</a>
                            <a href="student_lab_schedule.php" class="bg-primary/50">Lab Schedule</a>
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

                        <!-- User Profile Dropdown - Fixed Structure -->
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
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-secondary/20 overflow-hidden transition-all duration-200">
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
                <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Edit Profile</a>
                <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Announcements</a>
                <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Reservation</a>
                <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Sit-in History</a>
                <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Leaderboard</a>
                <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Sit-in Rules</a>
                <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Lab Rules</a>
                <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Lab Resources</a>
                <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20 bg-primary/50">Lab Schedule</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-light hover:bg-primary/20">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div>
            <h1 id="schedule-title" class="text-3xl font-bold text-primary">
                <i class="fas mr-2"></i>Lab Schedule - <?php echo htmlspecialchars($day_groups[$selected_group]); ?>
            </h1>
        </div>

    <!-- Info Section -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Legend Card -->
            <div class="bg-light p-5 rounded-lg border border-secondary shadow-sm">
                <h3 class="text-lg font-semibold text-primary mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> Legend
                </h3>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-3"></span>
                        <span class="text-dark">Available - Lab is vacant</span>
                    </li>
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-3"></span>
                        <span class="text-dark">Occupied - Lab in use</span>
                    </li>
                </ul>
            </div>

            <!-- Current Time Card -->
            <div class="bg-light p-5 rounded-lg border border-secondary shadow-sm">
                <h3 class="text-lg font-semibold text-primary mb-3 flex items-center">
                    <i class="fas fa-clock mr-2"></i> Current Time
                </h3>
                <div class="text-2xl font-bold text-dark" id="current-time">
                    <?php echo date('h:i A'); ?>
                </div>
                <div class="text-secondary mt-1">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Notes Card -->
            <div class="bg-light p-5 rounded-lg border border-secondary shadow-sm">
                <h3 class="text-lg font-semibold text-primary mb-3 flex items-center">
                    <i class="fas fa-clipboard mr-2"></i> Notes
                </h3>
                <p class="text-dark mb-2">
                    This schedule shows regular lab availability. Special events may cause temporary changes.
                </p>
                <p class="text-secondary text-sm">
                    Last updated: <?php echo date('m/d/Y'); ?>
                </p>
            </div>
        </div>

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 mt-10">
            <!-- Day Group Selector -->
            <div class="flex flex-wrap gap-2 bg-light p-2 rounded-lg border border-secondary">
                <?php foreach ($day_groups as $group_code => $group_name): ?>
                    <button 
                        onclick="showDayGroup('<?php echo $group_code; ?>')" 
                        id="tab-<?php echo $group_code; ?>"
                        class="px-4 py-2 rounded-md transition-all duration-200 day-group-tab 
                            <?php echo $group_code === $selected_group ? 
                            'bg-primary text-light shadow-md' : 
                            'bg-secondary/30 text-dark hover:bg-secondary/50'; ?>"
                    >
                        <?php echo htmlspecialchars($group_name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Schedule Card -->
        <div class="bg-light rounded-xl shadow-md overflow-hidden border border-secondary">
            <!-- Current Schedule Table -->
            <div id="table-<?php echo $selected_group; ?>" class="day-group-table">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-primary text-left">
                                <th class="p-4 font-medium text-light w-48 min-w-[12rem]">Time Slot</th>
                                <?php foreach ($labs as $lab): ?>
                                    <th class="p-4 font-medium text-light text-center"><?php echo htmlspecialchars($lab); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($time_slots as $index => $slot): 
                                $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                            ?>
                                <tr class="<?php echo $index % 2 === 0 ? 'bg-light' : 'bg-secondary/10'; ?> hover:bg-secondary/20 transition-colors">
                                    <td class="p-4 font-medium text-dark whitespace-nowrap border-r border-secondary/30">
                                        <div class="flex items-center">
                                            <i class="fas fa-clock mr-2 text-primary/80"></i>
                                            <?php echo htmlspecialchars($slot['display']); ?>
                                        </div>
                                    </td>
                                    <?php foreach ($labs as $lab): 
                                        $is_available = $lab_availability[$lab][$time_slot_str];
                                    ?>
                                        <td class="p-4 text-center border-r border-secondary/30 last:border-r-0">
                                            <div class="flex justify-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 
                                                    <?php echo $is_available ? 
                                                    'status-available bg-green-100 text-green-800' : 
                                                    'status-occupied bg-red-100 text-red-800'; ?>">
                                                    <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1.5"></i>
                                                    <?php echo $is_available ? 'Available' : 'Occupied'; ?>
                                                </span>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Hidden tables for other day groups -->
            <?php foreach ($day_groups as $group_code => $group_name): ?>
                <?php if ($group_code !== $selected_group): ?>
                    <?php
                    // Fetch data for this day group
                    $query = "SELECT lab_name, time_slot, status FROM static_lab_schedules WHERE day_group = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $group_code);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $group_availability = array();
                    while ($row = $result->fetch_assoc()) {
                        $group_availability[$row['lab_name']][$row['time_slot']] = ($row['status'] === 'available');
                    }
                    $stmt->close();
                    
                    // Default to available if no record exists in database
                    foreach ($labs as $lab) {
                        foreach ($time_slots as $slot) {
                            $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                            if (!isset($group_availability[$lab][$time_slot_str])) {
                                $group_availability[$lab][$time_slot_str] = true;
                            }
                        }
                    }
                    ?>
                    <div id="table-<?php echo $group_code; ?>" class="day-group-table hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-primary text-left">
                                        <th class="p-4 font-medium text-light w-48 min-w-[12rem]">Time Slot</th>
                                        <?php foreach ($labs as $lab): ?>
                                            <th class="p-4 font-medium text-light text-center"><?php echo htmlspecialchars($lab); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $index => $slot): 
                                        $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                                    ?>
                                        <tr class="<?php echo $index % 2 === 0 ? 'bg-light' : 'bg-secondary/10'; ?> hover:bg-secondary/20 transition-colors">
                                            <td class="p-4 font-medium text-dark whitespace-nowrap border-r border-secondary/30">
                                                <div class="flex items-center">
                                                    <i class="fas fa-clock mr-2 text-primary/80"></i>
                                                    <?php echo htmlspecialchars($slot['display']); ?>
                                                </div>
                                            </td>
                                            <?php foreach ($labs as $lab): 
                                                $is_available = $group_availability[$lab][$time_slot_str];
                                            ?>
                                                <td class="p-4 text-center border-r border-secondary/30 last:border-r-0">
                                                    <div class="flex justify-center">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 
                                                            <?php echo $is_available ? 
                                                            'status-available bg-green-100 text-green-800' : 
                                                            'status-occupied bg-red-100 text-red-800'; ?>">
                                                            <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1.5"></i>
                                                            <?php echo $is_available ? 'Available' : 'Occupied'; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    // Mobile menu toggle
    document.getElementById('mobileMenuButton').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // Profile dropdown toggle - fixed version
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    userMenuButton.addEventListener('click', function(e) {
        e.stopPropagation();
        // Close notification dropdown if open
        notificationDropdown.classList.add('hidden');
        // Toggle user dropdown
        userDropdown.classList.toggle('hidden');
    });

    // Notification dropdown toggle - fixed version
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');

    notificationButton.addEventListener('click', function(e) {
        e.stopPropagation();
        // Close user dropdown if open
        userDropdown.classList.add('hidden');
        // Toggle notification dropdown
        notificationDropdown.classList.toggle('hidden');
        // Load notifications
        loadNotifications();
    });

    // Mark all as read
    document.getElementById('markAllRead').addEventListener('click', function(e) {
        e.stopPropagation();
        markAllNotificationsAsRead();
    });

    // Close dropdowns when clicking outside - fixed version
    document.addEventListener('click', function(e) {
        // Check if click is outside both dropdowns
        if (!userDropdown.contains(e.target) && e.target !== userMenuButton && !userMenuButton.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
        if (!notificationDropdown.contains(e.target) && e.target !== notificationButton && !notificationButton.contains(e.target)) {
            notificationDropdown.classList.add('hidden');
        }
    });

    // Function to load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notificationList');
                const notificationBadge = document.querySelector('.notification-badge');
                
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
    
    // Load notifications on page load
    loadNotifications();

    // Check for new notifications every 30 seconds
    setInterval(loadNotifications, 30000);

    // Update current time every second
    function updateCurrentTime() {
        const now = new Date();
        document.getElementById('current-time').textContent = 
            now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime(); // Initial call
</script>
</body>
</html>