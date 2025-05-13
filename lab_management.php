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

// Use fetched admin details for the nav bar
$firstname_nav = $admin_firstname_nav ?? 'Admin'; // Fallback if query fails or no record
$profile_picture_nav = $admin_profile_picture_nav ?? 'default_avatar.png';
if (empty($profile_picture_nav) || !file_exists("uploads/" . $profile_picture_nav)) {
    $profile_picture_nav = 'default_avatar.png';
}


// Handle PC status update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_status"])) {
        $pc_id = $_POST["pc_id"];
        $new_status = $_POST["status"];
        $lab_name_posted = $_POST["lab_name"]; // Renamed to avoid conflict with $lab_name in foreach
        
        $stmt_update_pc = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE id = ? AND lab_name = ?");
        $stmt_update_pc->bind_param("sis", $new_status, $pc_id, $lab_name_posted);
        $stmt_update_pc->execute();
        $stmt_update_pc->close();
        
        $action = "Updated PC $pc_id in $lab_name_posted to status: $new_status";
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $admin_idno_for_nav, $action); // Use $admin_idno_for_nav
        $log_stmt->execute();
        $log_stmt->close();
        $_SESSION['pc_update_message'] = "PC #{$pc_id} in {$lab_name_posted} updated to {$new_status}.";
        $_SESSION['message_type'] = 'success';
        header("Location: lab_management.php#" . str_replace(' ', '-', strtolower($lab_name_posted)));
        exit();

    } elseif (isset($_POST["bulk_update"])) {
        $lab_name_bulk = $_POST["lab_name"]; // Renamed
        $bulk_status = $_POST["bulk_status"];
        
        $stmt_bulk = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE lab_name = ?");
        $stmt_bulk->bind_param("ss", $bulk_status, $lab_name_bulk);
        $stmt_bulk->execute();
        $stmt_bulk->close();
        
        $action = "Updated ALL PCs in $lab_name_bulk to status: $bulk_status";
        $log_stmt_bulk = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt_bulk->bind_param("ss", $admin_idno_for_nav, $action); // Use $admin_idno_for_nav
        $log_stmt_bulk->execute();
        $log_stmt_bulk->close();
        
        $_SESSION['pc_update_message'] = "All PCs in $lab_name_bulk have been marked as $bulk_status.";
        $_SESSION['message_type'] = 'success';
        header("Location: lab_management.php#" . str_replace(' ', '-', strtolower($lab_name_bulk)));
        exit();
    }
}

// Get all labs
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];
$lab_pcs = [];

foreach ($labs as $current_lab_name) { // Use $current_lab_name to avoid conflict
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM lab_pcs WHERE lab_name = ?");
    $stmt_count->bind_param("s", $current_lab_name);
    $stmt_count->execute();
    $stmt_count->bind_result($pc_count);
    $stmt_count->fetch();
    $stmt_count->close();
    
    if ($pc_count == 0) {
        for ($i = 1; $i <= 48; $i++) {
            $insert_stmt = $conn->prepare("INSERT INTO lab_pcs (lab_name, pc_number, status) VALUES (?, ?, 'Available')");
            $insert_stmt->bind_param("si", $current_lab_name, $i);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
    
    $stmt_fetch_pcs = $conn->prepare("SELECT id, pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number ASC");
    $stmt_fetch_pcs->bind_param("s", $current_lab_name);
    $stmt_fetch_pcs->execute();
    $result_pcs = $stmt_fetch_pcs->get_result();
    $lab_pcs[$current_lab_name] = $result_pcs->fetch_all(MYSQLI_ASSOC);
    $stmt_fetch_pcs->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Lab Management - Lab System</title>
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
        /* Styles for Top Nav Dropdowns */
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
        /* PC Card specific style */
        .pc-card { transition: all 0.2s ease-in-out; }
        .pc-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-light text-dark font-sans pt-16">

    <!-- Top Navigation Bar -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-md z-40 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center"><div class="flex-shrink-0 flex items-center"><i class="fas fa-laptop-house text-light mr-2 text-xl"></i><span class="text-xl font-semibold text-light">Lab System - Admin</span></div></div>
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
                        <div class="relative"><button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10"><i class="fas fa-bell text-lg"></i><span class="notification-badge hidden">0</span></button><div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><div class="p-3 bg-primary text-white flex justify-between items-center"><span class="font-semibold">Notifications</span><button id="markAllReadButton" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded">Mark all as read</button></div><div id="notificationList" class="max-h-80 overflow-y-auto"><div class="p-4 text-center text-gray-500">No notifications</div></div></div></div>
                        <div class="relative"><button id="userMenuButton" class="flex items-center gap-2 group"><img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary object-cover" src="uploads/<?php echo htmlspecialchars($profile_picture_nav); ?>" onerror="this.src='assets/default_avatar.png'" alt="Profile"><span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname_nav); ?></span></button><div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-user-edit mr-2"></i>Edit Profile</a><div class="border-t border-gray-200"></div><a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-sign-out-alt mr-2"></i>Log Out</a></div></div>
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
            <header class="mb-8 pb-6 border-b border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                    <h1 class="text-3xl font-bold text-primary">
                        </i>Computer Lab Management
                    </h1>
                    <div class="flex items-center space-x-4 text-xs text-gray-600">
                        <div class="flex items-center"><span class="h-3 w-3 rounded-full bg-green-500 mr-1.5"></span>Available</div>
                        <div class="flex items-center"><span class="h-3 w-3 rounded-full bg-red-500 mr-1.5"></span>Used</div>
                        <div class="flex items-center"><span class="h-3 w-3 rounded-full bg-yellow-500 mr-1.5"></span>Maintenance</div>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['pc_update_message'])): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-50 border-green-300 text-green-700' : 'bg-red-50 border-red-300 text-red-700'; ?>" role="alert">
                    <strong class="font-bold"><?php echo ucfirst($_SESSION['message_type']); ?>!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['pc_update_message']); unset($_SESSION['pc_update_message']); unset($_SESSION['message_type']); ?></span>
                </div>
            <?php endif; ?>


            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-4 overflow-x-auto pb-px" id="labTabs" role="tablist">
                        <?php foreach ($labs as $index => $lab_tab_name): ?>
                            <button 
                                class="lab-tab-button whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors duration-150 focus:outline-none <?php echo $index === 0 ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>" 
                                id="<?php echo str_replace(' ', '-', strtolower($lab_tab_name)); ?>-tab" 
                                data-tabs-target="#<?php echo str_replace(' ', '-', strtolower($lab_tab_name)); ?>" 
                                type="button" role="tab" aria-controls="<?php echo str_replace(' ', '-', strtolower($lab_tab_name)); ?>" 
                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                <?php echo htmlspecialchars($lab_tab_name); ?>
                            </button>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>

            <div id="labContent">
                <?php foreach ($labs as $index => $current_lab_content_name): ?>
                    <div class="lab-tab-content <?php echo $index === 0 ? '' : 'hidden'; ?> p-1" 
                         id="<?php echo str_replace(' ', '-', strtolower($current_lab_content_name)); ?>" 
                         role="tabpanel" aria-labelledby="<?php echo str_replace(' ', '-', strtolower($current_lab_content_name)); ?>-tab">
                        
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 flex flex-wrap justify-between items-center gap-4">
                            <h3 class="text-xl font-semibold text-primary"><?php echo htmlspecialchars($current_lab_content_name); ?> Overview</h3>
                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-700">
                                <?php
                                    $total_pcs = count($lab_pcs[$current_lab_content_name]);
                                    $available_count = count(array_filter($lab_pcs[$current_lab_content_name], fn($pc) => $pc['status'] === 'Available'));
                                    $used_count = count(array_filter($lab_pcs[$current_lab_content_name], fn($pc) => $pc['status'] === 'Used'));
                                    $maintenance_count = count(array_filter($lab_pcs[$current_lab_content_name], fn($pc) => $pc['status'] === 'Maintenance'));
                                ?>
                                <span><strong class="font-medium">Total:</strong> <?php echo $total_pcs; ?></span>
                                <span class="text-green-600"><strong class="font-medium">Available:</strong> <?php echo $available_count; ?></span>
                                <span class="text-red-600"><strong class="font-medium">In Use:</strong> <?php echo $used_count; ?></span>
                                <span class="text-yellow-600"><strong class="font-medium">Maintenance:</strong> <?php echo $maintenance_count; ?></span>
                            </div>
                        </div>

                        <form method="POST" action="lab_management.php#<?php echo str_replace(' ', '-', strtolower($current_lab_content_name)); ?>" class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="hidden" name="lab_name" value="<?php echo htmlspecialchars($current_lab_content_name); ?>">
                            <div class="flex flex-col sm:flex-row items-end gap-4">
                                <div class="flex-grow">
                                    <label for="bulk_status_<?php echo str_replace(' ', '', $current_lab_content_name); ?>" class="block text-sm font-medium text-gray-700 mb-1">Update All PCs in <?php echo htmlspecialchars($current_lab_content_name); ?></label>
                                    <select name="bulk_status" id="bulk_status_<?php echo str_replace(' ', '', $current_lab_content_name); ?>" class="w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm text-dark" required>
                                        <option value="Available">Set to Available</option>
                                        <option value="Used">Set to Used</option>
                                        <option value="Maintenance">Set to Maintenance</option>
                                    </select>
                                </div>
                                <button type="submit" name="bulk_update" class="w-full sm:w-auto px-4 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark">
                                    Update All
                                </button>
                            </div>
                        </form>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                            <?php foreach ($lab_pcs[$current_lab_content_name] as $pc): ?>
                                <?php
                                    $status_bg_color = 'bg-gray-100 border-gray-300'; $status_text_color = 'text-gray-800'; $icon_color = 'text-gray-500';
                                    if ($pc['status'] === 'Available') { $status_bg_color = 'bg-green-50 border-green-300'; $status_text_color = 'text-green-700'; $icon_color = 'text-green-500';}
                                    elseif ($pc['status'] === 'Used') { $status_bg_color = 'bg-red-50 border-red-300'; $status_text_color = 'text-red-700'; $icon_color = 'text-red-500';}
                                    elseif ($pc['status'] === 'Maintenance') { $status_bg_color = 'bg-yellow-50 border-yellow-300'; $status_text_color = 'text-yellow-700'; $icon_color = 'text-yellow-500';}
                                ?>
                                <div class="pc-card p-4 rounded-lg border <?php echo $status_bg_color; ?> cursor-pointer shadow-sm hover:shadow-md"
                                    onclick="openPcModal('<?php echo $pc['id']; ?>', '<?php echo htmlspecialchars($current_lab_content_name); ?>', '<?php echo $pc['pc_number']; ?>', '<?php echo htmlspecialchars($pc['status']); ?>')">
                                    <div class="flex flex-col items-center text-center">
                                        <div class="text-lg font-semibold text-gray-800 mb-1">PC <?php echo $pc['pc_number']; ?></div>
                                        <div class="text-xs px-2.5 py-0.5 rounded-full font-medium <?php echo $status_bg_color; ?> <?php echo $status_text_color; ?> border <?php echo $status_bg_color; ?>">
                                            <?php echo htmlspecialchars($pc['status']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- PC Status Modal -->
    <div id="pcModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50 p-4 transition-opacity duration-300 opacity-0">
        <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto transform transition-transform duration-300 scale-95">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-primary" id="pcModalTitle">Update PC Status</h3>
                <button onclick="closePcModal()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            
            <form id="pcStatusForm" method="POST">
                <input type="hidden" name="pc_id" id="modalPcId">
                <input type="hidden" name="lab_name" id="modalLabName">
                
                <div class="mb-4">
                    <label for="modalPcNumber" class="block text-sm font-medium text-gray-700 mb-1">PC Number</label>
                    <input type="text" id="modalPcNumber" class="w-full p-2 border-gray-300 rounded-md shadow-sm bg-gray-100 text-dark" readonly>
                </div>
                <div class="mb-4">
                    <label for="modalCurrentStatus" class="block text-sm font-medium text-gray-700 mb-1">Current Status</label>
                    <input type="text" id="modalCurrentStatus" class="w-full p-2 border-gray-300 rounded-md shadow-sm bg-gray-100 text-dark" readonly>
                </div>
                <div class="mb-6">
                    <label for="modalStatusSelect" class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                    <select name="status" id="modalStatusSelect" class="w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-dark" required>
                        <option value="Available">Available</option>
                        <option value="Used">Used</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePcModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md shadow-sm transition duration-150">Cancel</button>
                    <button type="submit" name="update_status" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white font-medium rounded-md shadow-sm transition duration-150">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Top Navigation Bar Scripts ---
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadButton');

        if(mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        }
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

        function loadNotifications() { /* Placeholder */ const list = document.getElementById('notificationList'); if(list) list.innerHTML = '<div class="p-4 text-center text-gray-500">No new notifications.</div>'; document.querySelector('.notification-badge')?.classList.add('hidden'); }
        if(markAllReadBtn) markAllReadBtn.addEventListener('click', loadNotifications);
        loadNotifications();

        // --- Lab Management Tab & Modal Scripts ---
        const labTabs = document.querySelectorAll('.lab-tab-button');
        const labContents = document.querySelectorAll('.lab-tab-content');
        const pcModal = document.getElementById('pcModal');
        const pcModalContent = pcModal ? pcModal.querySelector('div') : null;


        labTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetId = this.dataset.tabsTarget;
                const targetContent = document.querySelector(targetId);

                labContents.forEach(content => content.classList.add('hidden'));
                if(targetContent) targetContent.classList.remove('hidden');
                
                labTabs.forEach(t => {
                    t.classList.remove('border-primary', 'text-primary');
                    t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    t.setAttribute('aria-selected', 'false');
                });
                this.classList.add('border-primary', 'text-primary');
                this.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                this.setAttribute('aria-selected', 'true');

                // Update URL hash without page jump
                if (history.pushState) {
                    history.pushState(null, null, targetId);
                } else {
                    location.hash = targetId;
                }
            });
        });
        
        // Activate tab based on URL hash on page load
        document.addEventListener('DOMContentLoaded', function() {
            let activeTabId = labTabs[0]?.id; // Default to first tab
            if (window.location.hash) {
                const hash = window.location.hash;
                const tabForHash = document.querySelector(`.lab-tab-button[data-tabs-target="${hash}"]`);
                if (tabForHash) {
                    activeTabId = tabForHash.id;
                }
            }
            if (activeTabId) {
                 document.getElementById(activeTabId)?.click();
            }
        });


        function openPcModal(pcId, labName, pcNumber, currentStatus) {
            if (!pcModal) return;
            document.getElementById('modalPcId').value = pcId;
            document.getElementById('modalLabName').value = labName;
            document.getElementById('modalPcNumber').value = 'PC ' + pcNumber;
            document.getElementById('modalCurrentStatus').value = currentStatus;
            document.getElementById('modalStatusSelect').value = currentStatus;
            document.getElementById('pcModalTitle').textContent = labName + ' - PC ' + pcNumber;
            
            pcModal.classList.remove('hidden');
            pcModal.classList.add('opacity-0'); // Start transparent for fade-in
            pcModalContent.classList.add('scale-95');
            
            requestAnimationFrame(() => { // Ensures transition classes are applied after display:flex
                 pcModal.classList.remove('opacity-0');
                 pcModalContent.classList.remove('scale-95');
                 pcModalContent.classList.add('scale-100');
            });
        }

        function closePcModal() {
            if (!pcModal) return;
            pcModal.classList.add('opacity-0');
            pcModalContent.classList.add('scale-95');
            pcModalContent.classList.remove('scale-100');

            setTimeout(() => {
                pcModal.classList.add('hidden');
            }, 300); // Match transition duration
        }

        if (pcModal) {
            pcModal.addEventListener('click', function(e) {
                if (e.target === this) closePcModal();
            });
        }
         // Close modal on ESC key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && pcModal && !pcModal.classList.contains('hidden')) {
                closePcModal();
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