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

// Get total registered students
$student_count_query = "SELECT COUNT(*) AS student_count FROM users WHERE role = 'student'";
$student_count_result = mysqli_query($conn, $student_count_query);
$student_count_row = mysqli_fetch_assoc($student_count_result);
$student_count = $student_count_row['student_count'];

// Get active sit-ins
$active_sitins_query = "SELECT COUNT(*) AS active_sit_ins FROM sit_in_records WHERE end_time IS NULL";
$active_sitins_result = mysqli_query($conn, $active_sitins_query);
$active_sitins_row = mysqli_fetch_assoc($active_sitins_result);
$active_sit_ins = $active_sitins_row['active_sit_ins'];

$search_result = ""; 
$search_open = false; // Track if the form should stay open

// Handle Student Search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search_query"])) {
    $search_query = trim($_POST["search_query"]);
    
    // Search by ID or name
    $stmt = $conn->prepare("SELECT id, idno, firstname, lastname, profile_picture FROM users WHERE (idno LIKE ? OR CONCAT(firstname, ' ', lastname) LIKE ?) AND role = 'student'");
    $search_param = "%$search_query%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $student_idno, $student_firstname, $student_lastname, $profile_picture);
        $stmt->fetch();
        $fullname = htmlspecialchars($student_firstname . ' ' . $student_lastname);
        
        // Get total sit-ins for this student
        $sitins_count = 0;
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ?");
        $count_stmt->bind_param("i", $id);
        $count_stmt->execute();
        $count_stmt->bind_result($sitins_count);
        $count_stmt->fetch();
        $count_stmt->close();
        
        // Check survey completion status
        $survey_completed = false;
        $survey_stmt = $conn->prepare("SELECT survey_completed FROM users WHERE id = ?");
        $survey_stmt->bind_param("i", $id);
        $survey_stmt->execute();
        $survey_stmt->bind_result($survey_completed);
        $survey_stmt->fetch();
        $survey_stmt->close();
        
        // Check if student already has an active session
        $active_session_check = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ? AND end_time IS NULL");
        $active_session_check->bind_param("i", $id);
        $active_session_check->execute();
        $active_session_check->bind_result($active_sessions);
        $active_session_check->fetch();
        $active_session_check->close();
        
        // Default profile picture if not set
        $profile_pic = $profile_picture ? 'uploads/'.$profile_picture : 'assets/default_avatar.png';
        
        if ($active_sessions > 0) {
            $search_result = "
                <div class='flex items-start space-x-4'>
                    <div class='flex-shrink-0'>
                        <img src='$profile_pic' alt='Profile Picture' class='w-16 h-16 rounded-full border-2 border-secondary object-cover' onerror=\"this.src='assets/default_avatar.png'\">
                    </div>
                    <div class='flex-1 min-w-0'>
                        <h4 class='text-xl font-semibold text-primary'>$fullname</h4>
                        <p class='text-sm text-dark'><strong>ID:</strong> $student_idno</p>
                        <p class='text-sm text-secondary mt-1'>Sit-ins completed: $sitins_count</p>
                    </div>
                </div>
                
                <div class='mt-6 p-4 bg-red-100 border border-red-300 rounded-md'>
                    <div class='flex items-center'>
                        <i class='fas fa-exclamation-circle text-red-500 mr-2'></i>
                        <span class='font-medium text-red-700'>This student already has an active sit-in session.</span>
                    </div>
                    <p class='text-sm text-red-600 mt-2'>Please end the current session before logging a new one.</p>
                </div>";
        } else {
            $search_result = "
                <div class='flex items-start space-x-4'>
                    <div class='flex-shrink-0'>
                        <img src='$profile_pic' alt='Profile Picture' class='w-16 h-16 rounded-full border-2 border-secondary object-cover' onerror=\"this.src='assets/default_avatar.png'\">
                    </div>
                    <div class='flex-1 min-w-0'>
                        <h4 class='text-xl font-semibold text-primary'>$fullname</h4>
                        <p class='text-sm text-dark'><strong>ID:</strong> $student_idno</p>
                        <p class='text-sm text-secondary mt-1'>Sit-ins completed: $sitins_count</p>
                    </div>
                </div>";
            
            // Add survey notification if applicable
            if ($sitins_count >= 10 && !$survey_completed) {
                $search_result .= "
                <div class='mt-4 p-4 bg-yellow-100 border border-yellow-300 rounded-md'>
                    <div class='flex items-center'>
                        <i class='fas fa-exclamation-triangle text-yellow-600 mr-2'></i>
                        <span class='font-medium text-yellow-700'>This student hasn't completed their satisfaction survey yet.</span>
                    </div>
                    <p class='text-sm text-yellow-600 mt-2'>They must complete the survey before any new sit-ins can be logged.</p>
                </div>
                <div class='mt-4 p-4 bg-secondary/20 rounded-md border border-secondary'>
                    <p class='text-dark text-center'>Cannot log sit-in - Survey required</p>
                </div>";
            } else {
                // Only show the sit-in form if survey is completed or not required yet
                $search_result .= "
                    <form action='log_sit_in.php' method='POST' class='mt-6 space-y-4' id='sitInForm'>
                        <input type='hidden' name='student_id' value='$student_idno'>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                            <div>
                                <label class='block text-sm font-medium text-dark mb-1'>Purpose</label>
                                <select name='purpose' class='w-full p-2 bg-light border border-secondary rounded-md text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200' required>
                                    <option value='' disabled selected>Select Purpose</option>
                                    <option value='C Programming'>C Programming</option>
                                    <option value='Java Programming'>Java Programming</option>
                                    <option value='C# Programming'>C# Programming</option>
                                    <option value='Systems Integration & Architecture'>Systems Integration & Architecture</option>
                                    <option value='Embedded Systems & IoT'>Embedded Systems & IoT</option>
                                    <option value='Computer Application'>Computer Application</option>
                                    <option value='Database'>Database</option>
                                    <option value='Project Management'>Project Management</option>
                                    <option value='Python Programming'>Python Programming</option>
                                    <option value='Mobile Appilication'>Mobile Appilication</option>
                                    <option value='Web Design'>Web Design</option>
                                    <option value='Php Programming'>Php Programming</option>
                                    <option value='Other'>Others...</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class='block text-sm font-medium text-dark mb-1'>Lab</label>
                                <select name='lab' id='lab_room' class='w-full p-2 bg-light border border-secondary rounded-md text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200' required onchange='updatePcAvailability()'>
                                    <option value='' disabled selected>Select Lab</option>
                                    <option value='Lab 517'>Lab 517</option>
                                    <option value='Lab 524'>Lab 524</option>
                                    <option value='Lab 526'>Lab 526</option>
                                    <option value='Lab 528'>Lab 528</option>
                                    <option value='Lab 530'>Lab 530</option>
                                    <option value='Lab 542'>Lab 542</option>
                                    <option value='Lab 544'>Lab 544</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id='pcSelectionContainer' class='hidden'>
                            <label class='block text-sm font-medium text-dark mb-1'>Select PC (Optional)</label>
                            <div id='pcGrid' class='grid grid-cols-4 gap-2 mb-2 max-h-40 overflow-y-auto p-2 bg-secondary/20 rounded-md'>
                                <!-- PCs will be loaded here via AJAX -->
                            </div>
                            <input type='hidden' name='pc_number' id='selectedPc'>
                        </div>
                        
                        <div class='pt-2'>
                            <button type='submit' class='w-full flex items-center justify-center p-2 bg-gradient-to-r from-primary to-primary/90 text-white rounded-md hover:from-primary/90 hover:to-primary transition-all duration-200 shadow-md'>
                                <i class='fa-solid fa-check mr-2'></i> Log Sit-in
                            </button>
                        </div>
                    </form>";
            }
        }
        $search_open = true;
    } else {
        $search_result = "
            <div class='text-center py-6'>
                <i class='fas fa-user-slash text-4xl text-red-500 mb-3'></i>
                <p class='text-red-500 text-lg'>Student not found</p>
                <p class='text-secondary text-sm mt-1'>Please check the ID or name and try again</p>
            </div>";
        $search_open = true;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        .pc-card {
            transition: all 0.2s ease;
        }
        .pc-card:hover {
            transform: translateY(-2px);
        }
        .status-available {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
        }
        .status-used {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .status-maintenance {
            background-color: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
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
                    <span class="text-xl font-semibold text-light">Lab System - Admin</span>
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
                            <a href="todays_sitins.php">Current Sit-ins</a>
                            <a href="sit_in_records.php">Sit-in Reports</a>
                            <a href="feedback_records.php">Feedback Reports</a>
                            <a href="manage_sitins.php">Manage Sit-ins</a>
                        </div>
                    </div>
                    
                    <!-- Management Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Management
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="studentlist.php">List of Students</a>
                            <a href="manage_reservation.php">Reservation Requests</a>
                            <a href="reservation_logs.php">Reservation Logs</a>
                            <a href="admin_upload_resources.php">Upload Resources</a>
                            <a href="admin_leaderboard.php">Leaderboard</a>
                            <a href="admin_lab_schedule.php">Lab Schedule</a>
                            <a href="lab_management.php">Lab Management</a>
                        </div>
                    </div>
                    
                    <a href="create_announcement.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Announcements</a>
                    
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
       <div class="container mx-auto px-4 py-6">
        <!-- Welcome Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($firstname . " " . $lastname); ?></h1>
                <p class="text-gray-600 mt-2">Admin Dashboard Overview</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="create_announcement.php" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-bullhorn mr-2 text-primary"></i>
                    New Announcement
                </a>
                <a href="studentlist.php" class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-lg shadow-sm text-sm font-medium text-white hover:bg-primary-700">
                    <i class="fas fa-users mr-2"></i>
                    View Students
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Students Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-primary-50">
                            <i class="fas fa-users text-primary text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <h3 class="text-lg font-medium text-gray-500">Registered Students</h3>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $student_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4">
                    <a href="studentlist.php" class="text-sm font-medium text-primary hover:text-primary-700 flex items-center">
                        View all students
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- Active Sit-ins Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-50">
                            <i class="fas fa-laptop-code text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <h3 class="text-lg font-medium text-gray-500">Active Sit-ins</h3>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $active_sit_ins; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4">
                    <a href="todays_sitins.php" class="text-sm font-medium text-primary hover:text-primary-700 flex items-center">
                        View current sessions
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-50">
                            <i class="fas fa-bolt text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5">
                            <h3 class="text-lg font-medium text-gray-500">Quick Actions</h3>
                            <div class="mt-3 flex space-x-3">
                                <a href="manage_reservation.php" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">Reservations</a>
                                <a href="lab_management.php" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">Labs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-search text-primary mr-2"></i>
                    Student Search
                </h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input 
                            type="text" 
                            name="search_query" 
                            class="focus:ring-primary focus:border-primary block w-full pl-10 p-3 sm:text-sm border-gray-300 rounded-md" 
                            placeholder="Search by ID or name..."
                            required
                            autocomplete="off"
                        >
                    </div>
                    <button type="submit" class="w-full flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-700">
                        <i class="fas fa-search mr-2"></i>
                        Search Student
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-clock text-primary mr-2"></i>
                    Recent Activity
                </h2>
            </div>
            <div class="p-6">
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-history text-4xl mb-3"></i>
                    <p>No recent activity to display</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Result Modal -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300"></div>
    <div id="searchResultModal" class="fixed inset-0 flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0">
            <div class="p-6">
                <div id="searchResultContent">
                    <?php echo $search_result; ?>
                </div>
                <button onclick="closeSearchResultModal()" class="w-full mt-6 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700">
                    Close
                </button>
            </div>
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
                userDropdown.classList.add('hidden');
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

            // Open search result modal with animation
            function openSearchResultModal() {
                document.getElementById("overlay").classList.remove("hidden");
                document.getElementById("searchResultModal").classList.remove("hidden");
                
                setTimeout(() => {
                    document.getElementById("searchResultModal").querySelector('div').classList.remove('scale-95', 'opacity-0');
                    document.getElementById("searchResultModal").querySelector('div').classList.add('scale-100', 'opacity-100');
                }, 10);
            }

            // Close search result modal with animation
            function closeSearchResultModal() {
                document.getElementById("searchResultModal").querySelector('div').classList.remove('scale-100', 'opacity-100');
                document.getElementById("searchResultModal").querySelector('div').classList.add('scale-95', 'opacity-0');
                
                setTimeout(() => {
                    document.getElementById("overlay").classList.add("hidden");
                    document.getElementById("searchResultModal").classList.add("hidden");
                }, 300);
            }

            // Close modal when clicking outside
            document.getElementById('overlay').addEventListener('click', closeSearchResultModal);

            // Open modal if search was performed
            <?php if ($search_open) { ?>
                window.onload = function() {
                    openSearchResultModal();
                };
            <?php } ?>

            // Function to update PC availability when lab is selected
            function updatePcAvailability() {
                const labSelect = document.getElementById('lab_room');
                const labName = labSelect.value;
                const pcContainer = document.getElementById('pcSelectionContainer');
                const pcGrid = document.getElementById('pcGrid');
                const selectedPc = document.getElementById('selectedPc');
                
                if (labName) {
                    // Show loading state
                    pcGrid.innerHTML = '<div class="col-span-4 text-center py-4"><i class="fas fa-spinner fa-spin mr-2"></i> Loading PCs...</div>';
                    pcContainer.classList.remove('hidden');
                    
                    // Load PCs via AJAX
                    fetch(`get_pcs.php?lab=${encodeURIComponent(labName)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(data => {
                        pcGrid.innerHTML = data;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        pcGrid.innerHTML = `
                            <div class="col-span-4 text-center py-4 text-red-500">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Failed to load PCs. Please try again.
                            </div>
                        `;
                    });
                } else {
                    pcContainer.classList.add('hidden');
                    selectedPc.value = '';
                }
            }
            
            // Function to select a PC
            function selectPc(pcNumber) {
                const pcCards = document.querySelectorAll('#pcGrid .pc-card');
                const selectedPc = document.getElementById('selectedPc');
                
                pcCards.forEach(card => {
                    if (parseInt(card.dataset.pcNumber) === pcNumber) {
                        card.classList.add('border-primary', 'bg-primary/20');
                        selectedPc.value = pcNumber;
                    } else {
                        card.classList.remove('border-primary', 'bg-primary/20');
                    }
                });
            }

            // Handle form submission
            document.getElementById('sitInForm')?.addEventListener('submit', function(e) {
                // You can add any additional form validation here if needed
            });
        });
    </script>
</body>
</html>