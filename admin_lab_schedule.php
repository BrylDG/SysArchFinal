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

// Get user info from session
$firstname = $_SESSION['firstname'] ?? 'Admin';
$profile_picture = $_SESSION['profile_picture'] ?? 'default_avatar.png';

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
        'start_time' => date('H:i:s', $start_time),
        'end_time' => date('H:i:s', $end_slot),
        'display' => date('g:iA', $start_time) . '-' . date('g:iA', $end_slot)
    );
    $start_time = $end_slot;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab'], $_POST['time_slot'], $_POST['day_group'], $_POST['status'])) {
    $lab = $_POST['lab'];
    $time_slot = $_POST['time_slot'];
    $day_group = $_POST['day_group'];
    $status = $_POST['status']; // 'available' or 'occupied'
    
    // Check if record exists
    $check_query = "SELECT id FROM static_lab_schedules WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("sss", $lab, $day_group, $time_slot);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE static_lab_schedules SET status = ? WHERE lab_name = ? AND day_group = ? AND time_slot = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssss", $status, $lab, $day_group, $time_slot);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO static_lab_schedules (lab_name, day_group, time_slot, status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssss", $lab, $day_group, $time_slot, $status);
    }
    
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_lab_schedule.php");
    exit();
}

// Fetch all lab availability from database
$lab_availability = array();
$query = "SELECT lab_name, day_group, time_slot, status FROM static_lab_schedules";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $lab_availability[$row['lab_name']][$row['day_group']][$row['time_slot']] = ($row['status'] === 'available');
}

// Default to available if no record exists in database
foreach ($labs as $lab) {
    foreach ($day_groups as $group_code => $group_name) {
        foreach ($time_slots as $slot) {
            $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
            if (!isset($lab_availability[$lab][$group_code][$time_slot_str])) {
                $lab_availability[$lab][$group_code][$time_slot_str] = true;
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
    <title>Admin Lab Schedule</title>
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
                },
            },
        }
    </script>
    <style>
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #1e293b;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-800 to-slate-900 min-h-screen font-sans text-white">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-slate-900/80 backdrop-blur-md border-r border-white/10 shadow-xl z-50 flex flex-col">
        <!-- Fixed header -->
        <div class="p-5 border-b border-white/10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white"></i>
                </div>
                <h2 class="text-xl font-semibold text-white">Admin</h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Lab Schedule</p>
        </div>
        
        <!-- Scrollable navigation -->
        <nav class="mt-5 flex-1 overflow-y-auto sidebar-scroll">
            <ul>
                <li>
                    <a href="admin_dashboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="todays_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Current Sit-in Records</span>
                    </a>
                </li>
                <li>
                    <a href="sit_in_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Sit-in Reports</span>
                    </a>
                </li>
                <li>
                    <a href="feedback_records.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Feedback Reports</span>
                    </a>
                </li>
                <li>
                    <a href="manage_sitins.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Manage Sit-ins</span>
                    </a>
                </li>
                <li>
                    <a href="create_announcement.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="studentlist.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>List of Students</span>
                    </a>
                </li>
                <li>
                    <a href="manage_reservation.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservations Requests</span>
                    </a>
                </li>
                <li>
                    <a href="reservation_logs.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Reservation Logs</span>
                    </a>
                </li>
                <li>
                    <a href="admin_upload_resources.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Upload Resources</span>
                    </a>
                </li>
                <li>
                    <a href="admin_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_lab_schedule.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white transition-all duration-200">
                        <span>Lab Schedule</span>
                    </a>
                </li>
                <li>
                    <a href="lab_management.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
                        <span>Lab Management</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Fixed footer with logout -->
        <div class="p-5 border-t border-white/10 flex-shrink-0">
            <a href="logout.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-red-600/20 hover:text-red-400 transition-all duration-200">
                <span>Log Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-white">Lab Schedule</h2>
                <div class="text-slate-300">
                    <i class="fas fa-info-circle mr-1"></i> This schedule applies for the entire semester
                </div>
            </div>

            <!-- Day Group Tabs -->
            <div class="mb-6">
                <ul class="flex flex-wrap -mb-px">
                    <?php foreach ($day_groups as $group_code => $group_name): ?>
                        <li class="mr-2">
                            <button 
                                onclick="showDayGroup('<?php echo $group_code; ?>')" 
                                id="tab-<?php echo $group_code; ?>"
                                class="inline-block p-4 border-b-2 rounded-t-lg hover:text-white hover:border-blue-500 transition-all duration-200 day-group-tab <?php echo $group_code === 'MW' ? 'border-blue-500 text-white' : 'border-transparent text-slate-400'; ?>"
                            >
                                <?php echo htmlspecialchars($group_name); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Schedule Tables for each Day Group -->
            <?php foreach ($day_groups as $group_code => $group_name): ?>
                <div id="table-<?php echo $group_code; ?>" class="day-group-table mb-8 <?php echo $group_code !== 'MW' ? 'hidden' : ''; ?>">
                    <h3 class="text-xl font-semibold text-white mb-4"><?php echo htmlspecialchars($group_name); ?> Schedule</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-slate-700/50 rounded-lg overflow-hidden">
                            <thead>
                                <tr class="bg-slate-700/80 text-left">
                                    <th class="p-4 font-medium text-slate-300 w-48">Time Slot</th>
                                    <?php foreach ($labs as $lab): ?>
                                        <th class="p-4 font-medium text-slate-300"><?php echo htmlspecialchars($lab); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $index => $slot): 
                                    $time_slot_str = $slot['start'] . ' - ' . $slot['end'];
                                ?>
                                    <tr class="<?php echo $index % 2 === 0 ? 'bg-slate-700/30' : 'bg-slate-700/10'; ?> hover:bg-slate-700/40 transition-all duration-150">
                                        <td class="p-4 font-medium text-slate-200 w-48 whitespace-nowrap">
                                            <?php echo htmlspecialchars($slot['display']); ?>
                                        </td>
                                        <?php foreach ($labs as $lab): 
                                            $is_available = $lab_availability[$lab][$group_code][$time_slot_str];
                                            $opposite_status = $is_available ? 'occupied' : 'available';
                                        ?>
                                            <td class="p-4">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="lab" value="<?php echo htmlspecialchars($lab); ?>">
                                                    <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($time_slot_str); ?>">
                                                    <input type="hidden" name="day_group" value="<?php echo htmlspecialchars($group_code); ?>">
                                                    <button 
                                                        type="submit" 
                                                        name="status" 
                                                        value="<?php echo $opposite_status; ?>"
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-all duration-200 <?php echo $is_available ? 'bg-green-600/20 text-green-400 hover:bg-green-600/30' : 'bg-red-600/20 text-red-400 hover:bg-red-600/30'; ?>"
                                                    >
                                                        <i class="fas <?php echo $is_available ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                                        <?php echo $is_available ? 'Available' : 'Occupied'; ?>
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
            <?php endforeach; ?>

            <div class="mt-6 bg-slate-700/30 rounded-lg p-4 border border-white/5">
                <h4 class="text-lg font-semibold text-white mb-2">Legend</h4>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-sm text-slate-300">Available - Lab is vacant during this time</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                        <span class="text-sm text-slate-300">Occupied - Lab is in use during this time</span>
                    </div>
                </div>
                <div class="mt-3 text-sm text-slate-400">
                    <p>Click on the status buttons to toggle between Available and Occupied. Changes apply to the entire semester.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDayGroup(groupCode) {
            // Hide all tables and deactivate all tabs
            document.querySelectorAll('.day-group-table').forEach(table => {
                table.classList.add('hidden');
            });
            document.querySelectorAll('.day-group-tab').forEach(tab => {
                tab.classList.remove('border-blue-500', 'text-white');
                tab.classList.add('border-transparent', 'text-slate-400');
            });
            
            // Show selected table and activate tab
            document.getElementById(`table-${groupCode}`).classList.remove('hidden');
            document.getElementById(`tab-${groupCode}`).classList.add('border-blue-500', 'text-white');
            document.getElementById(`tab-${groupCode}`).classList.remove('border-transparent', 'text-slate-400');
        }
    </script>
</body>
</html>