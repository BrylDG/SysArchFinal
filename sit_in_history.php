<?php
session_start();
include 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info
$user_query = "SELECT id, firstname, lastname, profile_picture, remaining_sessions FROM users WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($student_id, $firstname, $lastname, $profile_picture, $remaining_sessions);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// List of foul words to check against (can be expanded)
$foul_words = array('fuck', 'shit', 'bitch', 'asshole', 'damn', 'crap', 'piss', 'dick', 'cock', 'pussy', 
                   'fag', 'faggot', 'nigger', 'nigga', 'whore', 'slut', 'bastard', 'motherfucker', 'cunt');

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
    $record_id = $_POST['record_id'];
    $feedback = $_POST['feedback'];
    
    // Check for foul language
    $contains_foul_language = false;
    $feedback_lower = strtolower($feedback);
    $detected_words = array();
    
    foreach ($foul_words as $word) {
        if (strpos($feedback_lower, $word) !== false) {
            $contains_foul_language = true;
            $detected_words[] = $word;
        }
    }
    
    if ($contains_foul_language) {
        $_SESSION['feedback_warning'] = "Your feedback contains inappropriate language (" . implode(', ', $detected_words) . "). It has been flagged.";
    } else {
        $_SESSION['feedback_success'] = "Feedback submitted successfully!";
    }
    
    $feedback_query = "UPDATE sit_in_records SET feedback = ? WHERE id = ? AND student_id = ?";
    $stmt = $conn->prepare($feedback_query);
    $stmt->bind_param("sii", $feedback, $record_id, $student_id);
    $stmt->execute();
    $stmt->close();
    
    // Reload the page to show updated feedback
    header("Location: sit_in_history.php");
    exit();
}

// Fetch the sit-in history for the logged-in user
$sit_in_history_query = "SELECT id, purpose, lab, pc_number, start_time, end_time, feedback 
                         FROM sit_in_records 
                         WHERE student_id = ?
                         ORDER BY start_time DESC";
$stmt = $conn->prepare($sit_in_history_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$sit_in_history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "Sit-in History";
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
        
        function toggleFeedbackForm(rowId, event) {
            if (event) event.stopPropagation();
            const overlay = document.getElementById(`feedback-overlay-${rowId}`);
            
            if (overlay.classList.contains('hidden')) {
                // Close any other open feedback forms first
                closeAllFeedbackForms();
                // Open this one
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function closeAllFeedbackForms() {
            document.querySelectorAll('[id^="feedback-overlay-"]').forEach(overlay => {
                overlay.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }

        // Check for foul language but allow submission
        function checkFoulLanguage(form, recordId) {
            const foulWords = <?php echo json_encode($foul_words); ?>;
            const feedback = form.feedback.value.toLowerCase();
            let containsFoulLanguage = false;
            let detectedWords = [];
            
            foulWords.forEach(word => {
                if (feedback.includes(word.toLowerCase())) {
                    containsFoulLanguage = true;
                    detectedWords.push(word);
                }
            });
            
            if (containsFoulLanguage) {
                const warningDiv = document.getElementById(`feedback-warning-${recordId}`);
                warningDiv.innerHTML = `
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Your feedback contains inappropriate language (${detectedWords.join(', ')}). 
                                    You can still submit it, but it will be flagged.
                                </p>
                            </div>
                        </div>
                    </div>
                `;
                warningDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return true; // Always allow submission
        }

        // Close overlay when clicking outside the form
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.feedback-form-container') && 
                    !e.target.closest('[onclick^="toggleFeedbackForm"]')) {
                    closeAllFeedbackForms();
                }
            });
        });
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
        
        .feedback-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(2px);
        }
        
        .feedback-form-container {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #D4C9BE;
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
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgba(220, 38, 38, 1);
        }
        
        .status-completed {
            background-color: rgba(16, 185, 129, 0.1);
            color: rgba(5, 150, 105, 1);
        }
        
        .foul-feedback {
            color: #ef4444;
            background-color: rgba(239, 68, 68, 0.1);
            padding: 0.25rem;
            border-radius: 0.25rem;
        }
        
        .foul-warning-icon {
            color: #ef4444;
            margin-left: 0.25rem;
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
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 bg-slate-700/20 text-white">Sit-in History</a>
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
        <!-- Display session messages if exists -->
        <?php if (isset($_SESSION['feedback_warning'])): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <?php echo htmlspecialchars($_SESSION['feedback_warning']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['feedback_warning']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['feedback_success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">
                            <?php echo htmlspecialchars($_SESSION['feedback_success']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['feedback_success']); ?>
        <?php endif; ?>

        <div class="card rounded-xl shadow-lg border border-secondary p-6 hover:shadow-xl transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-semibold mb-2 text-dark">Sit-in History</h2>
                    <p class="text-secondary">View your past and current sit-in sessions</p>
                </div>
                <div class="flex items-center bg-primary/10 px-3 py-1 rounded-full">
                    <span class="text-sm font-medium text-primary">Remaining: <?php echo $remaining_sessions; ?> sessions</span>
                </div>
            </div>

            <!-- History Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-secondary/30">
                    <thead class="bg-primary/90">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Lab</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">PC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">End Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/20">
                        <?php if (empty($sit_in_history)) { ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-secondary">No sit-in records found.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sit_in_history as $record) { 
                                // Check if feedback contains foul language
                                $contains_foul = false;
                                if (!empty($record['feedback'])) {
                                    $feedback_lower = strtolower($record['feedback']);
                                    foreach ($foul_words as $word) {
                                        if (strpos($feedback_lower, $word) !== false) {
                                            $contains_foul = true;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <tr class="hover:bg-secondary/10 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['purpose']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($record['lab']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $record['pc_number'] ? 'PC ' . htmlspecialchars($record['pc_number']) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo date("M d, Y h:i A", strtotime($record['start_time'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo $record['end_time'] ? date("M d, Y h:i A", strtotime($record['end_time'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge <?php echo $record['end_time'] ? 'status-completed' : 'status-active'; ?>">
                                            <?php echo $record['end_time'] ? 'Completed' : 'Active'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div id="feedback-display-<?php echo $record['id']; ?>" class="flex items-center">
                                            <?php if (!empty($record['feedback'])) { ?>
                                                <div class="<?php echo $contains_foul ? 'foul-feedback' : ''; ?>">
                                                    <?php echo htmlspecialchars($record['feedback']); ?>
                                                    <?php if ($contains_foul) { ?>
                                                        <i class="fas fa-exclamation-circle foul-warning-icon"></i>
                                                    <?php } ?>
                                                </div>
                                                <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="ml-2 text-primary hover:text-primary/70">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php } else { ?>
                                                <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-primary hover:text-primary/70">
                                                    <i class="fas fa-plus-circle mr-1"></i> Add
                                                </button>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Feedback Overlays -->
    <?php foreach ($sit_in_history as $record) { ?>
        <div id="feedback-overlay-<?php echo $record['id']; ?>" class="feedback-overlay hidden">
            <div class="feedback-form-container">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold"><?php echo empty($record['feedback']) ? 'Add Feedback' : 'Edit Feedback'; ?></h3>
                    <button onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="text-secondary hover:text-dark">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Warning container -->
                <div id="feedback-warning-<?php echo $record['id']; ?>"></div>
                
                <form method="POST" onsubmit="return checkFoulLanguage(this, <?php echo $record['id']; ?>)" class="space-y-4">
                    <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                    <div>
                        <textarea name="feedback" rows="4" class="w-full p-3 bg-white border border-secondary/50 rounded-lg text-dark focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                            placeholder="Enter your feedback about this session..."><?php echo htmlspecialchars($record['feedback'] ?? ''); ?></textarea>
                        <p class="text-xs text-secondary mt-1">Please keep your feedback professional and respectful.</p>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="toggleFeedbackForm(<?php echo $record['id']; ?>, event)" class="px-4 py-2 border border-secondary/50 rounded-lg hover:bg-secondary/10 transition-all duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-all duration-200">
                            Save Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>

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