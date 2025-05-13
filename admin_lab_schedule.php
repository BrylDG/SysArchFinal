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
$admin_idno_for_nav = $_SESSION["idno"];
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
$start_time_calc = strtotime('7:30 AM');
$end_time_calc = strtotime('9:00 PM');

while ($start_time_calc < $end_time_calc) {
    $end_slot_calc = $start_time_calc + (90 * 60); // 1.5 hours in seconds
    $time_slots[] = array(
        'start' => date('h:i A', $start_time_calc), // e.g., "07:30 AM"
        'end' => date('h:i A', $end_slot_calc),     // e.g., "09:00 AM"
        // REMOVED 'start_time_db' and 'end_time_db' as they are not in your schema
        'display' => date('g:iA', $start_time_calc) . '-' . date('g:iA', $end_slot_calc) // For display e.g., "7:30AM-9:00AM"
    );
    $start_time_calc = $end_slot_calc;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab'], $_POST['time_slot'], $_POST['day_group'], $_POST['status'])) {
    $lab_posted = $_POST['lab'];
    $time_slot_posted_key = $_POST['time_slot']; // This is "h:i A - h:i A" string from the form
    $day_group_posted = $_POST['day_group'];
    $status_posted = $_POST['status']; // 'available' or 'occupied'
    
    // Check if record exists using the time_slot_posted_key (the display string "h:i A - h:i A")
    // This 'time_slot' column in your DB stores the "h:i A - h:i A" string.
    $check_query = "SELECT id FROM static_lab_schedules WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("sss", $lab_posted, $day_group_posted, $time_slot_posted_key);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        // Update existing record
        // The 'time_slot' column is part of the WHERE clause, not updated here.
        $update_query = "UPDATE static_lab_schedules SET status = ? WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("ssss", $status_posted, $lab_posted, $day_group_posted, $time_slot_posted_key);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        // Insert new record
        // The 'time_slot' column will store the "h:i A - h:i A" string.
        $insert_query = "INSERT INTO static_lab_schedules (lab_name, day_group, time_slot, status) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_query);
        $stmt_insert->bind_param("ssss", $lab_posted, $day_group_posted, $time_slot_posted_key, $status_posted);
        $stmt_insert->execute(); // This was line 97 where the error occurred
        $stmt_insert->close();
    }
    $stmt_check->close();
    
    $_SESSION['schedule_message'] = "Schedule updated: {$lab_posted} on {$day_group_posted} at {$time_slot_posted_key} is now {$status_posted}.";
    $_SESSION['message_type'] = 'success';
    
    header("Location: admin_lab_schedule.php#table-" . $day_group_posted); // Redirect back to the same tab
    exit();
}

// Fetch all lab availability from database
$lab_availability = array();
$query_fetch = "SELECT lab_name, day_group, time_slot, status FROM static_lab_schedules";
$result_fetch = $conn->query($query_fetch);

if($result_fetch){
    while ($row = $result_fetch->fetch_assoc()) {
        // The time_slot stored in DB is "h:i A - h:i A"
        $lab_availability[$row['lab_name']][$row['day_group']][$row['time_slot']] = ($row['status'] === 'available');
    }
}

// Default to available if no record exists in database
foreach ($labs as $lab_item) {
    foreach ($day_groups as $group_code_item => $group_name_item) {
        foreach ($time_slots as $slot_item) {
            // The key for lab_availability should be "start - end" (e.g., "07:30 AM-09:00 AM")
            // This matches the format of time_slot in your database
            $time_slot_key = $slot_item['start'] . '-' . $slot_item['end'];
            if (!isset($lab_availability[$lab_item][$group_code_item][$time_slot_key])) {
                $lab_availability[$lab_item][$group_code_item][$time_slot_key] = true; // Default to available
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Lab Schedule - Lab System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        'primary-dark': '#0f2a48',
                        'primary-darker': '#0c223a',
                        secondary: '#D4C9BE',
                        light: '#F1EFEC', // Page background
                        dark: '#030303', // Text color on light backgrounds
                    }
                },
            },
        }
    </script>
    <style>
        /* Styles for Top Nav Dropdowns */
        .nav-dropdown { position: relative; }
        .nav-dropdown-content {
            display: none; position: absolute; background-color: white; /* Dropdown BG */
            min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); /* Softer shadow */
            z-index: 50; border-radius: 0.375rem; /* Rounded corners */
            border: 1px solid #e5e7eb; /* Light border for dropdown */
            overflow: hidden; /* Ensures rounded corners apply to children */
        }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-dropdown-content a {
            color: #374151; /* Darker gray for text for better contrast on white */
            padding: 10px 15px; /* Adjusted padding */
            text-decoration: none; display: block; font-size: 0.875rem;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
        }
        .nav-dropdown-content a:hover { background-color: #f3f4f6; /* Lighter gray for hover */ color: #123458; /* Primary color for text on hover */ }
        .nav-dropdown-content a.active-dropdown-item { /* Active item in dropdown */
            background-color: #e0e7ff; /* Light blue/indigo for active */
            color: #123458; font-weight: 500;
        }
        .nav-dropdown-btn::after { content: "▾"; margin-left: 5px; font-size: 0.8em; }
         .notification-badge {
            position: absolute; top: -5px; right: -5px; background-color: #ef4444; color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Sticky table headers and first column */
        .schedule-table th.time-cell, .schedule-table td.time-cell-body {
            position: sticky; left: 0; z-index: 5;
        }
        .schedule-table thead th { position: sticky; top: 0; z-index: 10; }
        /* Ensure the top-left corner cell (Time Slot header) is above both sticky row and column headers */
         .schedule-table thead th.time-cell { z-index: 15; }
    </style>
</head>
<body class="bg-light text-dark font-sans pt-16">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-md z-40 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-laptop-house text-light mr-2 text-xl"></i>
                        <span class="text-xl font-semibold text-light">Lab System - Admin</span>
                    </div>
                </div>
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
                         <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-dark <?php echo (basename($_SERVER['PHP_SELF']) === 'studentlist.php' || basename($_SERVER['PHP_SELF']) === 'manage_reservation.php' || basename($_SERVER['PHP_SELF']) === 'reservation_logs.php' || basename($_SERVER['PHP_SELF']) === 'admin_upload_resources.php' || basename($_SERVER['PHP_SELF']) === 'admin_leaderboard.php' || basename($_SERVER['PHP_SELF']) === 'admin_lab_schedule.php' || basename($_SERVER['PHP_SELF']) === 'lab_management.php') ? 'text-white bg-primary-darker' : 'text-secondary'; ?>">
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
                    <a href="create_announcement.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'create_announcement.php' ? 'bg-primary-darker text-white' : ''; ?>">Announcements</a>
                    <div class="flex items-center gap-4 ml-4">
                        <div class="relative">
                            <button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10"><i class="fas fa-bell text-lg"></i><span class="notification-badge hidden">0</span></button>
                            <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <div class="p-3 bg-primary text-white flex justify-between items-center"><span class="font-semibold">Notifications</span><button id="markAllReadButton" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded">Mark all as read</button></div>
                                <div id="notificationList" class="max-h-80 overflow-y-auto"><div class="p-4 text-center text-gray-500">No notifications</div></div>
                            </div>
                        </div>
                        <div class="relative">
                            <button id="userMenuButton" class="flex items-center gap-2 group">
                                <img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary object-cover" src="uploads/<?php echo htmlspecialchars($profile_picture_nav); ?>" onerror="this.src='assets/default_avatar.png'" alt="Profile">
                                <span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname_nav); ?></span>
                            </button>
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-user-edit mr-2"></i>Edit Profile</a>
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
            <header class="mb-8 border-b border-gray-200 pb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <h1 class="text-3xl font-bold text-primary mb-2 md:mb-0">
                        <i class="fas fa-calendar-check mr-3"></i>Static Lab Schedule
                    </h1>
                    <div class="text-sm text-gray-500 italic">
                        <i class="fas fa-info-circle mr-1"></i> This schedule reflects fixed lab availability for the semester.
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['schedule_message'])): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-50 border border-green-300 text-green-700' : 'bg-red-50 border border-red-300 text-red-700'; ?>" role="alert">
                    <strong class="font-bold"><?php echo ucfirst($_SESSION['message_type']); ?>!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['schedule_message']); unset($_SESSION['schedule_message']); unset($_SESSION['message_type']); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-4 overflow-x-auto pb-px" aria-label="Tabs">
                        <?php 
                        $first_group_tab = true;
                        foreach ($day_groups as $group_code => $group_name): 
                        ?>
                            <button 
                                onclick="showDayGroup('<?php echo $group_code; ?>')" 
                                id="tab-<?php echo $group_code; ?>"
                                class="day-group-tab whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-150 focus:outline-none
                                <?php echo $first_group_tab ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>"
                            >
                                <?php echo htmlspecialchars($group_name); ?>
                            </button>
                        <?php 
                        $first_group_tab = false;
                        endforeach; 
                        ?>
                    </nav>
                </div>
            </div>

            <?php 
            $first_group_table_display = true;
            foreach ($day_groups as $group_code => $group_name): 
            ?>
                <div id="table-<?php echo $group_code; ?>" class="day-group-table <?php echo !$first_group_table_display ? 'hidden' : ''; ?>">
                    <h3 class="text-xl font-semibold text-gray-700 mb-5"><?php echo htmlspecialchars($group_name); ?> Schedule</h3>
                    <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 schedule-table">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider time-cell bg-gray-100 w-44">
                                        Time Slot
                                    </th>
                                    <?php foreach ($labs as $lab_header): ?>
                                        <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            <?php echo htmlspecialchars($lab_header); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($time_slots as $slot): 
                                    $time_slot_key_for_lookup = $slot['start'] . '-' . $slot['end'];
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-100">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 time-cell-body bg-white">
                                            <?php echo htmlspecialchars($slot['display']); ?>
                                        </td>
                                        <?php foreach ($labs as $lab_cell): 
                                            $is_available = $lab_availability[$lab_cell][$group_code][$time_slot_key_for_lookup] ?? true;
                                            $opposite_status = $is_available ? 'occupied' : 'available';
                                            $icon_color_class = $is_available ? 'text-green-600 hover:bg-green-100 focus:ring-green-500' : 'text-red-600 hover:bg-red-100 focus:ring-red-500';
                                            $icon_class = $is_available ? 'fa-check-circle' : 'fa-times-circle';
                                            $tooltip_text = $is_available ? 'Mark as Occupied' : 'Mark as Available';
                                        ?>
                                            <td class="px-6 py-4 text-center">
                                                <form method="POST" action="admin_lab_schedule.php#table-<?php echo $group_code; ?>" class="inline-block">
                                                    <input type="hidden" name="lab" value="<?php echo htmlspecialchars($lab_cell); ?>">
                                                    <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($time_slot_key_for_lookup); ?>">
                                                    <input type="hidden" name="day_group" value="<?php echo htmlspecialchars($group_code); ?>">
                                                    <button 
                                                        type="submit" 
                                                        name="status" 
                                                        value="<?php echo $opposite_status; ?>"
                                                        title="<?php echo $tooltip_text; ?>"
                                                        class="p-2 rounded-full transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1 <?php echo $icon_color_class; ?>"
                                                    >
                                                        <i class="fas <?php echo $icon_class; ?> text-xl"></i>
                                                        <span class="sr-only"><?php echo $is_available ? 'Available' : 'Occupied'; ?></span>
                                                    </button>
                                                </form>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php 
            $first_group_table_display = false;
            endforeach; 
            ?>

            <div class="mt-10 bg-gray-50 rounded-lg p-6 border border-gray-200">
                <h4 class="text-lg font-semibold text-gray-700 mb-3">Legend</h4>
                <div class="flex flex-col sm:flex-row sm:flex-wrap gap-x-8 gap-y-3">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-xl mr-2"></i>
                        <span class="text-sm text-gray-700">Indicates the lab slot is <strong class="font-medium">Available</strong>.</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-red-600 text-xl mr-2"></i>
                        <span class="text-sm text-gray-700">Indicates the lab slot is <strong class="font-medium">Occupied</strong>.</span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-500">
                    Click an icon to toggle its status. Changes are saved immediately.
                </p>
            </div>
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
        const markAllReadBtn = document.getElementById('markAllReadButton'); // Updated ID

        if(mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        if(userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
                if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }

        if(notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
                if (userDropdown && !userDropdown.classList.contains('hidden')) {
                    userDropdown.classList.add('hidden');
                }
                if (!notificationDropdown.classList.contains('hidden')) {
                    loadNotifications(); // Changed from loadNotificationsNav to a generic name
                }
            });
        }
        
        if(notificationDropdown) {
            notificationDropdown.addEventListener('click', e => e.stopPropagation());
        }
        if(userDropdown) {
            userDropdown.addEventListener('click', e => e.stopPropagation());
        }

        document.addEventListener('click', (event) => {
            if(userDropdown && !userDropdown.contains(event.target) && userMenuButton && !userMenuButton.contains(event.target) && !userMenuButton.querySelector('*').contains(event.target) ) {
                userDropdown.classList.add('hidden');
            }
            if(notificationDropdown && !notificationDropdown.contains(event.target) && notificationButton && !notificationButton.contains(event.target) && !notificationButton.querySelector('*').contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });

        function loadNotifications() {
            const notificationList = document.getElementById('notificationList');
            const badge = document.querySelector('.notification-badge');
            // Placeholder: Replace with actual AJAX call to fetch notifications
            if(notificationList) {
                notificationList.innerHTML = '<div class="p-4 text-center text-gray-500">No new notifications.</div>';
            }
            if(badge) {
                badge.classList.add('hidden');
                badge.textContent = '0';
            }
        }
        if(markAllReadBtn) { // Use the corrected ID
            markAllReadBtn.addEventListener('click', () => {
                // Placeholder: Add AJAX call to mark all as read on server
                loadNotifications(); // Refresh list
            });
        }
        loadNotifications(); // Initial load


        // --- Lab Schedule Tab Script ---
        function showDayGroup(groupCode) {
            document.querySelectorAll('.day-group-table').forEach(table => {
                table.classList.add('hidden');
            });
            document.querySelectorAll('.day-group-tab').forEach(tab => {
                tab.classList.remove('border-primary', 'text-primary');
                tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            
            const activeTable = document.getElementById(`table-${groupCode}`);
            if (activeTable) activeTable.classList.remove('hidden');
            
            const activeTab = document.getElementById(`tab-${groupCode}`);
            if (activeTab) {
                activeTab.classList.add('border-primary', 'text-primary');
                activeTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Activate first tab by default if no hash
            let activeGroupCode = 'MW'; // Default first tab
            if (window.location.hash) {
                const hash = window.location.hash.substring(1); // Remove #
                const targetTable = document.getElementById(hash);
                if (targetTable && targetTable.classList.contains('day-group-table')) {
                    activeGroupCode = hash.replace('table-', '');
                }
            }
            showDayGroup(activeGroupCode);
        });
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>