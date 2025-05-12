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

// Initialize filter variables
$lab_filter = isset($_GET['lab_filter']) ? $_GET['lab_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$purpose_filter = isset($_GET['purpose_filter']) ? $_GET['purpose_filter'] : '';

// Build the main query for displaying records
$query = "SELECT sr.id, sr.purpose, sr.lab, sr.start_time, sr.end_time,
                 u.firstname, u.lastname, u.idno
          FROM sit_in_records sr
          JOIN users u ON sr.student_id = u.id";

// Add filters if they exist
$where_clauses = [];
if (!empty($lab_filter)) {
    $where_clauses[] = "sr.lab = '" . $conn->real_escape_string($lab_filter) . "'";
}
if (!empty($date_filter)) {
    $where_clauses[] = "DATE(sr.start_time) = '" . $conn->real_escape_string($date_filter) . "'";
}
if (!empty($purpose_filter)) {
    $where_clauses[] = "sr.purpose = '" . $conn->real_escape_string($purpose_filter) . "'";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY sr.start_time DESC";
$result = $conn->query($query);
$sit_in_history = $result->fetch_all(MYSQLI_ASSOC);

// Handle export actions
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Reuse the same query with filters for export
    $export_query = $query;
    $export_result = $conn->query($export_query);
    $export_data = $export_result->fetch_all(MYSQLI_ASSOC);
    
    switch ($export_type) {
        case 'csv':
            exportToCSV($export_data);
            break;
        case 'excel':
            exportToExcel($export_data);
            break;
        case 'pdf':
            exportToPDF($export_data);
            break;
        case 'print':
            generatePrintView($export_data);
            break;
    }
}

/**
 * Export sit-in records to CSV format
 */
function exportToCSV($records) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Add title headers
    fputcsv($output, ['University of Cebu-Main']);
    fputcsv($output, ['College of Computer Studies']);
    fputcsv($output, ['Computer Laboratory Sitin Monitoring System Report']);
    fputcsv($output, []); // Empty line
    
    // Add column headers
    fputcsv($output, ['Student Name', 'Student ID', 'Purpose', 'Lab', 'Start Time', 'End Time']);
    
    // Add data rows
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        fputcsv($output, [
            $record['firstname'] . ' ' . $record['lastname'],
            $record['idno'],
            $record['purpose'],
            $record['lab'],
            date("F d, Y h:i A", strtotime($record['start_time'])),
            $end_time
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export sit-in records to Excel format (HTML table that Excel can open)
 */
function exportToExcel($records) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sit_in_records_' . date('Y-m-d') . '.xls"');
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">
    <head>
        <meta charset="UTF-8">
        <style>
            .title { font-weight: bold; text-align: center; font-size: 16px; }
            .subtitle { text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f2f2f2; font-weight: bold; text-align: left; }
            th, td { border: 1px solid #dddddd; padding: 8px; }
        </style>
    </head>
    <body>
        <div class="title">University of Cebu-Main</div>
        <div class="title">College of Computer Studies</div>
        <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        <br>
        
        <table>
            <tr>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Purpose</th>
                <th>Lab</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>';
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        echo '<tr>
            <td>' . htmlspecialchars($record['firstname'] . ' ' . $record['lastname']) . '</td>
            <td>' . htmlspecialchars($record['idno']) . '</td>
            <td>' . htmlspecialchars($record['purpose']) . '</td>
            <td>' . htmlspecialchars($record['lab']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($record['start_time'])) . '</td>
            <td>' . htmlspecialchars($end_time) . '</td>
        </tr>';
    }
    
    echo '</table>
    </body>
    </html>';
    exit;
}

/**
 * Export sit-in records to PDF format (returns JSON for client-side generation)
 */
function exportToPDF($records) {
    // Prepare data for JavaScript
    $jsData = [
        'title' => 'Sit-in Records',
        'headers' => ['University of Cebu-Main', 'College of Computer Studies', 
                     'Computer Laboratory Sitin Monitoring System Report',
                     ], 
        'columns' => ['Student Name', 'Student ID', 'Purpose', 'Lab', 'Start Time', 'End Time'],
        'rows' => []
    ];
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        $jsData['rows'][] = [
            htmlspecialchars($record['firstname'] . ' ' . $record['lastname']),
            htmlspecialchars($record['idno']),
            htmlspecialchars($record['purpose']),
            htmlspecialchars($record['lab']),
            date("F d, Y h:i A", strtotime($record['start_time'])),
            htmlspecialchars($end_time)
        ];
    }
    
    // Return JSON data for client-side processing
    header('Content-Type: application/json');
    echo json_encode($jsData);
    exit;
}

/**
 * Generate printable HTML view
 */
function generatePrintView($records) {
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Sit-in Records - Print View</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-weight: bold; font-size: 18px; }
            .subtitle { font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                @page { size: A4 landscape; margin: 10mm; }
                body { margin: 0; padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">University of Cebu-Main</div>
            <div class="title">College of Computer Studies</div>
            <div class="subtitle">Computer Laboratory Sitin Monitoring System Report</div>
        </div>
        
        <button class="no-print" onclick="window.print()" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Print Report
        </button>
        <button class="no-print" onclick="window.close()" style="padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Close Window
        </button>
        
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($records as $record) {
        $end_time = $record['end_time'] ? 
            date("F d, Y h:i A", strtotime($record['end_time'])) : 
            'Still Active';
            
        echo '<tr>
            <td>' . htmlspecialchars($record['firstname'] . ' ' . $record['lastname']) . '</td>
            <td>' . htmlspecialchars($record['idno']) . '</td>
            <td>' . htmlspecialchars($record['purpose']) . '</td>
            <td>' . htmlspecialchars($record['lab']) . '</td>
            <td>' . date("F d, Y h:i A", strtotime($record['start_time'])) . '</td>
            <td>' . htmlspecialchars($end_time) . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records | Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .feedback-cell {
            max-width: 200px;
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
                        <h1 class="text-3xl font-bold text-gray-900">Sit-in Records</h1>
                        <p class="mt-2 text-sm text-gray-600">View and manage all student sit-in sessions</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <i class="fas fa-calendar-day mr-1"></i>
                            <?php echo date("l, F j, Y"); ?>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($sit_in_history); ?> records
                        </span>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- Filter and Export Section -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Filters & Export</h3>
                </div>
                <div class="px-6 py-4">
                    <!-- Filter Form -->
                    <form id="filter-form" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Date Filter -->
                        <div>
                            <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input 
                                type="date" 
                                name="date_filter" 
                                id="date_filter"
                                class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                value="<?php echo htmlspecialchars($date_filter); ?>"
                            >
                        </div>
                        
                        <!-- Lab Filter -->
                        <div>
                            <label for="lab_filter" class="block text-sm font-medium text-gray-700 mb-1">Lab Room</label>
                            <select 
                                name="lab_filter" 
                                id="lab_filter"
                                class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                            >
                                <option value="">All Labs</option>
                                <option value="Lab 517" <?php echo $lab_filter == 'Lab 517' ? 'selected' : ''; ?>>Lab 517</option>
                                <option value="Lab 524" <?php echo $lab_filter == 'Lab 524' ? 'selected' : ''; ?>>Lab 524</option>
                                <option value="Lab 526" <?php echo $lab_filter == 'Lab 526' ? 'selected' : ''; ?>>Lab 526</option>
                                <option value="Lab 528" <?php echo $lab_filter == 'Lab 528' ? 'selected' : ''; ?>>Lab 528</option>
                                <option value="Lab 530" <?php echo $lab_filter == 'Lab 530' ? 'selected' : ''; ?>>Lab 530</option>
                                <option value="Lab 542" <?php echo $lab_filter == 'Lab 542' ? 'selected' : ''; ?>>Lab 542</option>
                                <option value="Lab 544" <?php echo $lab_filter == 'Lab 544' ? 'selected' : ''; ?>>Lab 544</option>
                            </select>
                        </div>
                        
                        <!-- Purpose Filter -->
                        <div>
                            <label for="purpose_filter" class="block text-sm font-medium text-gray-700 mb-1">Purpose</label>
                            <select 
                                name="purpose_filter" 
                                id="purpose_filter"
                                class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                            >
                                <option value="">All Purposes</option>
                                <option value="C Programming" <?php echo $purpose_filter == 'C Programming' ? 'selected' : ''; ?>>C Programming</option>
                                <option value="Java Programming" <?php echo $purpose_filter == 'Java Programming' ? 'selected' : ''; ?>>Java Programming</option>
                                <option value="C# Programming" <?php echo $purpose_filter == 'C# Programming' ? 'selected' : ''; ?>>C# Programming</option>
                                <option value="Systems Integration & Architecture" <?php echo $purpose_filter == 'Systems Integration & Architecture' ? 'selected' : ''; ?>>Systems Integration & Architecture</option>
                                <option value="Embedded Systems & IoT" <?php echo $purpose_filter == 'Embedded Systems & IoT' ? 'selected' : ''; ?>>Embedded Systems & IoT</option>
                                <option value="Computer Application" <?php echo $purpose_filter == 'Computer Application' ? 'selected' : ''; ?>>Computer Application</option>
                                <option value="Database" <?php echo $purpose_filter == 'Database' ? 'selected' : ''; ?>>Database</option>
                                <option value="Project Management" <?php echo $purpose_filter == 'Project Management' ? 'selected' : ''; ?>>Project Management</option>
                                <option value="Python Programming" <?php echo $purpose_filter == 'Python Programming' ? 'selected' : ''; ?>>Python Programming</option>
                                <option value="Mobile Application" <?php echo $purpose_filter == 'Mobile Application' ? 'selected' : ''; ?>>Mobile Application</option>
                                <option value="Web Design" <?php echo $purpose_filter == 'Web Design' ? 'selected' : ''; ?>>Web Design</option>
                                <option value="Php Programming" <?php echo $purpose_filter == 'Php Programming' ? 'selected' : ''; ?>>Php Programming</option>
                                <option value="Other" <?php echo $purpose_filter == 'Other' ? 'selected' : ''; ?>>Others...</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-3 flex justify-end space-x-3">
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                            >
                                Apply Filters
                            </button>
                            <button 
                                type="button" 
                                onclick="window.location.href='sit_in_records.php'"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </form>
                    
                    <!-- Export Buttons -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Export Records:</h4>
                        <div class="flex flex-wrap gap-2">
                            <a href="?export=csv<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-file-csv mr-2 text-green-600"></i> CSV
                            </a>
                            <a href="?export=excel<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-file-excel mr-2 text-green-600"></i> Excel
                            </a>
                            <button onclick="exportToPDF(event)" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF
                            </button>
                            <a href="?export=print<?php echo !empty($lab_filter) ? '&lab_filter=' . urlencode($lab_filter) : ''; ?><?php echo !empty($date_filter) ? '&date_filter=' . urlencode($date_filter) : ''; ?><?php echo !empty($purpose_filter) ? '&purpose_filter=' . urlencode($purpose_filter) : ''; ?>" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-print mr-2 text-blue-600"></i> Print
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Records Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Sit-in Records</h3>
                    <p class="text-sm text-gray-500">
                        Showing <?php echo count($sit_in_history); ?> records
                        <?php 
                            if (!empty($date_filter)) echo "for " . date("F d, Y", strtotime($date_filter));
                            if (!empty($lab_filter)) echo " in " . htmlspecialchars($lab_filter);
                            if (!empty($purpose_filter)) echo " with purpose: " . htmlspecialchars($purpose_filter);
                        ?>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($sit_in_history)) { ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No sit-in records found with the current filters.</td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($sit_in_history as $record) { ?>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($record['purpose']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($record['lab']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date("M d, Y h:i A", strtotime($record['start_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $record['end_time'] ? date("M d, Y h:i A", strtotime($record['end_time'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php 
                                                if ($record['end_time']) {
                                                    echo '<span class="text-gray-500">Completed</span>';
                                                } else {
                                                    echo '<span class="text-green-600 active-now">Active Now</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($sit_in_history)) { ?>
                    <div class="px-6 py-4 border-t border-gray-200 text-sm text-gray-500">
                        Showing <?php echo count($sit_in_history); ?> records
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <!-- PDF Generation Script -->
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

        // Function to handle PDF export
        function exportToPDF(event) {
            event.preventDefault();
            
            // Get current filters
            const labFilter = document.getElementById('lab_filter').value;
            const dateFilter = document.getElementById('date_filter').value;
            const purposeFilter = document.getElementById('purpose_filter').value;
            
            // Build export URL
            let url = '?export=pdf';
            if (labFilter) url += '&lab_filter=' + encodeURIComponent(labFilter);
            if (dateFilter) url += '&date_filter=' + encodeURIComponent(dateFilter);
            if (purposeFilter) url += '&purpose_filter=' + encodeURIComponent(purposeFilter);
            
            // Check if jsPDF is already loaded
            if (window.jspdf) {
                fetchAndGeneratePDF(url);
            } else {
                // Load jsPDF dynamically
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = function() {
                    const autoTableScript = document.createElement('script');
                    autoTableScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                    autoTableScript.onload = function() {
                        fetchAndGeneratePDF(url);
                    };
                    document.head.appendChild(autoTableScript);
                };
                document.head.appendChild(script);
            }
        }

        function fetchAndGeneratePDF(url) {
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    generatePDF(data);
                })
                .catch(error => {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF. Please try again.');
                });
        }

        // Function to generate PDF from data
        function generatePDF(data) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape'
            });

            // Add headers
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text(data.headers[0], doc.internal.pageSize.width / 2, 15, { align: 'center' });
            doc.text(data.headers[1], doc.internal.pageSize.width / 2, 22, { align: 'center' });
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.text(data.headers[2], doc.internal.pageSize.width / 2, 29, { align: 'center' });

            // AutoTable configuration
            doc.autoTable({
                head: [data.columns],
                body: data.rows,
                startY: 40,
                theme: 'grid',
                headStyles: {
                    fillColor: [61, 71, 79], // Dark gray color
                    textColor: 255, // White text
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 2,
                    overflow: 'linebreak'
                },
                margin: { left: 10, right: 10 }
            });

            // Save the PDF
            doc.save('sit_in_records_' + new Date().toISOString().slice(0, 10) + '.pdf');
        }
    </script>
</body>
</html>