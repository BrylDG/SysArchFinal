<?php
session_start();
include 'db.php';

// Check if user is logged in as student
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Set page title
$page_title = "Reservation";

// Get student info
$idno = $_SESSION['idno'];
$stmt = $conn->prepare("SELECT id, firstname, lastname, remaining_sessions, profile_picture, course, yearlevel, email FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $remaining_sessions, $profile_picture, $course, $yearlevel, $email);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Check if student has completed the satisfaction survey
$survey_completed = false;
$stmt = $conn->prepare("SELECT survey_completed FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($survey_completed);
$stmt->fetch();
$stmt->close();

// Get total sit-ins count
$total_sitins = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($total_sitins);
$stmt->fetch();
$stmt->close();

// Check for pending reservations
$pending_reservations = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE student_id = ? AND status = 'pending'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->bind_result($pending_reservations);
$stmt->fetch();
$stmt->close();

// Get PC availability data
$lab_pcs = [];
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];

foreach ($labs as $lab) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
                           SUM(CASE WHEN status = 'Used' THEN 1 ELSE 0 END) as used,
                           SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance
                           FROM lab_pcs WHERE lab_name = ?");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    $lab_pcs[$lab] = $result->fetch_assoc();
    $stmt->close();
}

// Handle survey submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_survey'])) {
    $satisfaction = isset($_POST['satisfaction']) ? intval($_POST['satisfaction']) : 0;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    
    if ($satisfaction > 0) {
        // Insert survey response
        $stmt = $conn->prepare("INSERT INTO satisfaction_surveys (student_id, satisfaction, comments) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $student_id, $satisfaction, $comments);
        $stmt->execute();
        $stmt->close();
        
        // Mark survey as completed
        $stmt = $conn->prepare("UPDATE users SET survey_completed = 1 WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
        
        $survey_completed = true;
    }
}

// Handle reservation form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Check if student has pending reservations
    if ($pending_reservations > 0) {
        $error = 'You already have a pending reservation. Please wait for it to be processed before making a new one.';
    } else {
        // Initialize variables with empty values
        $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
        $lab_room = isset($_POST['lab_room']) ? trim($_POST['lab_room']) : '';
        $reservation_date = isset($_POST['reservation_date']) ? trim($_POST['reservation_date']) : '';
        $time_in = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
        $pc_number = isset($_POST['pc_number']) ? trim($_POST['pc_number']) : '';
        
        // Validate inputs
        if (empty($purpose) || empty($lab_room) || empty($reservation_date) || empty($time_in)) {
            $error = 'All fields are required!';
        } elseif ($remaining_sessions <= 0) {
            $error = 'You have no remaining sessions left!';
        } else {
            // Check if selected PC is available (if one was selected)
            if (!empty($pc_number)) {
                $stmt = $conn->prepare("SELECT status FROM lab_pcs WHERE lab_name = ? AND pc_number = ?");
                $stmt->bind_param("si", $lab_room, $pc_number);
                $stmt->execute();
                $stmt->bind_result($pc_status);
                $stmt->fetch();
                $stmt->close();
                
                if ($pc_status !== 'Available') {
                    $error = 'The selected PC is not available!';
                }
            }
            
            if (empty($error)) {
                // Insert reservation
                $stmt = $conn->prepare("INSERT INTO reservations (student_id, purpose, lab_room, pc_number, reservation_date, time_in) VALUES (?, ?, ?, ?, ?, ?)");
                $pc_number = empty($pc_number) ? NULL : $pc_number;
                $stmt->bind_param("isssss", $student_id, $purpose, $lab_room, $pc_number, $reservation_date, $time_in);
                
                if ($stmt->execute()) {
                    $success = 'Reservation submitted successfully! Waiting for admin approval.';
                    // Clear form values after successful submission
                    $purpose = $lab_room = $reservation_date = $time_in = $pc_number = '';
                    // Update pending reservations count
                    $pending_reservations = 1;
                } else {
                    $error = 'Error submitting reservation: ' . $conn->error;
                }
                $stmt->close();
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
            background-color: white;
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
        
        /* PC Card Styles */
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
        .star-rating {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 30px;
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
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
        /* PC Card Styles */
    .pc-card {
        position: relative;
        height: 4rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-radius: 0.375rem;
        border: 1px solid;
        cursor: pointer;
        transition: all 0.2s ease;
        overflow: hidden;
    }
    
    .pc-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .pc-card.selected {
        border: 2px solid #123458;
        background-color: rgba(18, 52, 88, 0.05);
        box-shadow: 0 0 0 3px rgba(18, 52, 88, 0.1);
    }
    
    .pc-card .pc-number {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.1rem;
    }
    
    .pc-card .pc-status {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.15rem 0.35rem;
        border-radius: 9999px;
    }
    
    .pc-card.available {
        border-color: rgba(16, 185, 129, 0.3);
        background-color: rgba(16, 185, 129, 0.05);
    }
    
    .pc-card.available .pc-status {
        background-color: rgba(16, 185, 129, 0.1);
        color: rgba(5, 150, 105, 1);
    }
    
    .pc-card.used {
        border-color: rgba(239, 68, 68, 0.3);
        background-color: rgba(239, 68, 68, 0.05);
    }
    
    .pc-card.used .pc-status {
        background-color: rgba(239, 68, 68, 0.1);
        color: rgba(220, 38, 38, 1);
    }
    
    .pc-card.maintenance {
        border-color: rgba(245, 158, 11, 0.3);
        background-color: rgba(245, 158, 11, 0.05);
    }
    
    .pc-card.maintenance .pc-status {
        background-color: rgba(245, 158, 11, 0.1);
        color: rgba(217, 119, 6, 1);
    }
    
    /* Filter Button Styles */
    .pc-filter-btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
        border-radius: 0.25rem;
        background-color: rgba(212, 201, 190, 0.2);
        color: #030303;
        border: 1px solid rgba(212, 201, 190, 0.3);
        transition: all 0.2s ease;
    }
    
    .pc-filter-btn:hover {
        background-color: rgba(212, 201, 190, 0.3);
    }
    
    .pc-filter-btn.active {
        background-color: #123458;
        color: white;
        border-color: #123458;
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
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 bg-slate-700/20 text-white">Reservation</a>
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

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <div class="card rounded-xl shadow-lg border border-secondary p-6 hover:shadow-xl transition-all duration-300">
            <h2 class="text-2xl font-semibold mb-6 text-dark border-b border-secondary pb-2">Lab Reservation</h2>
            
            <!-- Display error/success messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Survey reminder -->
            <?php if ($total_sitins >= 10 && !$survey_completed): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span>Please complete the satisfaction survey to continue making reservations.</span>
                    </div>
                    <button onclick="document.getElementById('surveyModal').classList.remove('hidden')" 
                            class="mt-2 inline-flex items-center px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-all duration-200">
                        <i class="fas fa-clipboard-check mr-1"></i> Complete Survey
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Reservation Form -->
                <div class="card p-6 rounded-lg border border-secondary">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-dark">New Reservation</h3>
                        <div class="flex items-center bg-primary/10 px-3 py-1 rounded-full">
                            <span class="text-sm font-medium text-primary">Remaining: <?php echo $remaining_sessions; ?> sessions</span>
                        </div>
                    </div>
                    
                    <?php if ($pending_reservations > 0): ?>
                        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>You have a pending reservation. Please wait for it to be processed before making a new one.</span>
                            </div>
                        </div>
                    <?php elseif ($total_sitins >= 10 && !$survey_completed): ?>
                        <!-- Survey reminder already shown above -->
                    <?php else: ?>
                        <form method="POST" class="space-y-5">
                            <!-- Student Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-secondary mb-1">Student ID</label>
                                    <div class="p-2 bg-secondary/10 rounded-md border border-secondary/30">
                                        <p class="text-dark"><?php echo htmlspecialchars($idno); ?></p>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-secondary mb-1">Name</label>
                                    <div class="p-2 bg-secondary/10 rounded-md border border-secondary/30">
                                        <p class="text-dark"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Purpose and Lab Selection -->
                            <div>
                                <label class="block text-sm font-medium text-secondary mb-1">Purpose *</label>
                                <select name="purpose" class="w-full p-3 bg-white border border-secondary/50 rounded-lg text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 appearance-none" required>
                                    <option value="">Select Purpose</option>
                                    <option value="C Programming" <?php echo (isset($purpose) && $purpose == 'C Programming' ? 'selected' : ''); ?>>C Programming</option>
                                    <option value="Java Programming" <?php echo (isset($purpose) && $purpose == 'Java Programming' ? 'selected' : ''); ?>>Java Programming</option>
                                    <option value="C# Programming" <?php echo (isset($purpose) && $purpose == 'C# Programming' ? 'selected' : ''); ?>>C# Programming</option>
                                    <option value="Systems Integration & Architecture" <?php echo (isset($purpose) && $purpose == 'Systems Integration & Architecture' ? 'selected' : ''); ?>>Systems Integration & Architecture</option>
                                    <option value="Embedded Systems & IoT" <?php echo (isset($purpose) && $purpose == 'Embedded Systems & IoT' ? 'selected' : ''); ?>>Embedded Systems & IoT</option>
                                    <option value="Computer Application" <?php echo (isset($purpose) && $purpose == 'Computer Application' ? 'selected' : ''); ?>>Computer Application</option>
                                    <option value="Database" <?php echo (isset($purpose) && $purpose == 'Database' ? 'selected' : ''); ?>>Database</option>
                                    <option value="Project Management" <?php echo (isset($purpose) && $purpose == 'Project Management' ? 'selected' : ''); ?>>Project Management</option>
                                    <option value="Python Programming" <?php echo (isset($purpose) && $purpose == 'Python Programming' ? 'selected' : ''); ?>>Python Programming</option>
                                    <option value="Mobile Application" <?php echo (isset($purpose) && $purpose == 'Mobile Application' ? 'selected' : ''); ?>>Mobile Application</option>
                                    <option value="Web Design" <?php echo (isset($purpose) && $purpose == 'Web Design' ? 'selected' : ''); ?>>Web Design</option>
                                    <option value="Php Programming" <?php echo (isset($purpose) && $purpose == 'Php Programming' ? 'selected' : ''); ?>>Php Programming</option>
                                    <option value="Other" <?php echo (isset($purpose) && $purpose == 'Other' ? 'selected' : ''); ?>>Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-secondary mb-1">Laboratory Room *</label>
                                <select name="lab_room" id="lab_room" class="w-full p-3 bg-white border border-secondary/50 rounded-lg text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 appearance-none" required onchange="updatePcAvailability()">
                                    <option value="">Select Lab</option>
                                    <?php foreach ($labs as $lab): ?>
                                        <option value="<?php echo $lab; ?>" <?php echo (isset($lab_room) && $lab_room == $lab) ? 'selected' : ''; ?>>
                                            <?php echo $lab; ?> 
                                            (<?php echo $lab_pcs[$lab]['available']; ?> available)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- PC Selection Section -->
                            <div id="pcSelectionContainer" class="hidden">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-secondary">Select PC (Optional)</label>
                                    <span id="pcCountBadge" class="text-xs bg-primary/10 text-primary px-2 py-1 rounded-full">
                                        Loading PCs...
                                    </span>
                                </div>
                                
                                <div class="relative">
                                    <!-- Search Box -->
                                    <div class="mb-3 relative">
                                        <input type="text" id="pcSearch" placeholder="Search PCs..." 
                                            class="w-full p-2 pl-8 bg-white border border-secondary/30 rounded-md text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200">
                                        <i class="fas fa-search absolute left-2.5 top-2.5 text-secondary/60"></i>
                                    </div>
                                    
                                    <!-- Status Filter -->
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="pc-filter-btn active" data-status="all">
                                            <i class="fas fa-layer-group mr-1"></i> All
                                        </button>
                                        <button type="button" class="pc-filter-btn" data-status="available">
                                            <i class="fas fa-check-circle mr-1 text-green-500"></i> Available
                                        </button>
                                        <button type="button" class="pc-filter-btn" data-status="used">
                                            <i class="fas fa-times-circle mr-1 text-red-500"></i> In Use
                                        </button>
                                        <button type="button" class="pc-filter-btn" data-status="maintenance">
                                            <i class="fas fa-tools mr-1 text-yellow-500"></i> Maintenance
                                        </button>
                                    </div>
                                    
                                    <!-- PC Grid -->
                                    <div id="pcGrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-h-60 overflow-y-auto p-2 bg-secondary/5 rounded-lg border border-secondary/20">
                                        <!-- PCs will be loaded here via AJAX -->
                                        <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                                        <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                                        <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                                    </div>
                                    
                                    <!-- Selected PC Indicator -->
                                    <div id="selectedPcIndicator" class="hidden mt-3 p-3 bg-primary/5 border border-primary/20 rounded-md transition-all duration-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-primary">Selected:</span>
                                                <span id="selectedPcNumber" class="ml-2 font-semibold">PC 12</span>
                                            </div>
                                            <button type="button" id="clearPcSelection" class="text-xs text-red-500 hover:text-red-700">
                                                <i class="fas fa-times mr-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-xs text-secondary mt-2">
                                    <i class="fas fa-info-circle mr-1"></i> Leave unselected for any available PC
                                </p>
                                
                                <input type="hidden" name="pc_number" id="selectedPc">
                            </div>
                            
                            <!-- Date and Time -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-secondary mb-1">Date *</label>
                                    <div class="relative">
                                        <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($reservation_date) ? htmlspecialchars($reservation_date) : ''; ?>" 
                                            class="w-full p-3 bg-white border border-secondary/50 rounded-lg text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200" required>
                                        <i class="fas fa-calendar absolute right-3 top-3 text-secondary/70"></i>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-secondary mb-1">Time In *</label>
                                    <div class="relative">
                                        <input type="time" name="time_in" value="<?php echo isset($time_in) ? htmlspecialchars($time_in) : ''; ?>" 
                                            class="w-full p-3 bg-white border border-secondary/50 rounded-lg text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200" required>
                                        <i class="fas fa-clock absolute right-3 top-3 text-secondary/70"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button type="submit" name="submit" class="w-full flex items-center justify-center p-4 bg-gradient-to-r from-primary to-primary/90 text-white rounded-lg hover:from-primary/90 hover:to-primary transition-all duration-200 shadow-md hover:shadow-lg">
                                    <i class="fa-solid fa-paper-plane mr-2"></i> Submit Reservation Request
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Reservation History -->
                <div class="card p-6 rounded-lg border border-secondary">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-dark">Your Reservations</h3>
                        <span class="text-sm bg-secondary/10 px-2 py-1 rounded-full">Total: <?php echo $total_sitins; ?></span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-secondary/30">
                            <thead class="bg-primary/90">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Lab</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">PC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-secondary/20">
                                <?php
                                $reservations = $conn->prepare("SELECT reservation_date, lab_room, pc_number, time_in, status FROM reservations WHERE student_id = ? ORDER BY reservation_date DESC, time_in DESC");
                                $reservations->bind_param("i", $student_id);
                                $reservations->execute();
                                $result = $reservations->get_result();
                                
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $status_color = '';
                                        $status_icon = '';
                                        if ($row['status'] == 'approved') {
                                            $status_color = 'text-green-600 bg-green-100';
                                            $status_icon = 'fa-check-circle';
                                        } elseif ($row['status'] == 'disapproved') {
                                            $status_color = 'text-red-600 bg-red-100';
                                            $status_icon = 'fa-times-circle';
                                        } else {
                                            $status_color = 'text-yellow-600 bg-yellow-100';
                                            $status_icon = 'fa-clock';
                                        }
                                        
                                        echo "<tr class='hover:bg-secondary/10 transition-colors'>";
                                        echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['reservation_date']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['lab_room']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm'>" . ($row['pc_number'] ? 'PC ' . htmlspecialchars($row['pc_number']) : '-') . "</td>";
                                        echo "<td class='px-4 py-3 text-sm'>" . htmlspecialchars($row['time_in']) . "</td>";
                                        echo "<td class='px-4 py-3 text-sm'><span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $status_color'><i class='fas $status_icon mr-1'></i>" . ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='px-4 py-6 text-center text-sm text-secondary'>No reservations found</td></tr>";
                                }
                                
                                $reservations->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to update PC availability when lab is selected
        function updatePcAvailability() {
            const labSelect = document.getElementById('lab_room');
            const labName = labSelect.value;
            const pcContainer = document.getElementById('pcSelectionContainer');
            const pcGrid = document.getElementById('pcGrid');
            
            if (labName) {
                pcGrid.innerHTML = `
                    <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                    <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                    <div class="pc-card-skeleton h-16 rounded-md bg-secondary/10 animate-pulse"></div>
                `;
                pcContainer.classList.remove('hidden');
                
                fetch(`get_pcs.php?lab=${encodeURIComponent(labName)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    if (data.pcs.length === 0) {
                        pcGrid.innerHTML = `
                            <div class="col-span-full text-center py-4 text-secondary">
                                <i class="fas fa-desktop text-2xl mb-2 opacity-50"></i>
                                <p>No PCs found in this lab</p>
                            </div>
                        `;
                        return;
                    }
                    
                    pcGrid.innerHTML = '';
                    data.pcs.forEach(pc => {
                        const pcCard = document.createElement('div');
                        pcCard.className = `pc-card p-2 rounded-md border text-center cursor-pointer ${pc.status_class}`;
                        pcCard.dataset.pcNumber = pc.pc_number;
                        pcCard.innerHTML = `
                            PC ${pc.pc_number}<br>
                            <span class="text-xs ${pc.status_text_class}">${pc.status}</span>
                        `;
                        pcCard.addEventListener('click', () => selectPc(pc.pc_number));
                        pcGrid.appendChild(pcCard);
                    });
                    
                    document.getElementById('pcCountBadge').textContent = 
                        `${data.available_count}/${data.count} available`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    pcGrid.innerHTML = `
                        <div class="col-span-full text-center py-4 text-red-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            ${error.message}
                        </div>
                    `;
                });
            } else {
                pcContainer.classList.add('hidden');
            }
        }
        
        // Function to select a PC
        function selectPc(pcNumber) {
            const pcCards = document.querySelectorAll('#pcGrid .pc-card');
            const selectedPc = document.getElementById('selectedPc');
            const selectedPcIndicator = document.getElementById('selectedPcIndicator');
            const selectedPcNumber = document.getElementById('selectedPcNumber');
            
            pcCards.forEach(card => {
                if (parseInt(card.dataset.pcNumber) === pcNumber) {
                    card.classList.add('selected');
                    selectedPc.value = pcNumber;
                    selectedPcNumber.textContent = `PC ${pcNumber}`;
                    selectedPcIndicator.classList.remove('hidden');
                } else {
                    card.classList.remove('selected');
                }
            });
        }
        // Clear PC selection
        document.getElementById('clearPcSelection')?.addEventListener('click', function() {
            document.getElementById('selectedPc').value = '';
            document.getElementById('selectedPcIndicator').classList.add('hidden');
            document.querySelectorAll('#pcGrid .pc-card').forEach(card => {
                card.classList.remove('selected');
            });
        });
        
        // Initialize PC filters
        function initPcFilters() {
            const filterButtons = document.querySelectorAll('.pc-filter-btn');
            const pcSearch = document.getElementById('pcSearch');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    filterPcs();
                });
            });
            
            pcSearch.addEventListener('input', filterPcs);
        }
        
        // Filter PCs based on search and status
        function filterPcs() {
            const searchTerm = document.getElementById('pcSearch').value.toLowerCase();
            const activeFilter = document.querySelector('.pc-filter-btn.active').dataset.status;
            
            document.querySelectorAll('#pcGrid .pc-card').forEach(card => {
                const pcNumber = card.dataset.pcNumber;
                const status = card.dataset.status;
                const matchesSearch = `pc ${pcNumber}`.includes(searchTerm);
                const matchesFilter = activeFilter === 'all' || status === activeFilter;
                
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
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