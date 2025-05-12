<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get admin info
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

// Initialize students array
$students = [];

// Handle reset all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_all_sessions"])) {
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE role = 'student'";
    if ($conn->query($reset_query)) {
        $_SESSION['success_message'] = "All student sessions have been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting sessions: " . $conn->error;
    }
}

// Handle individual session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_student_session"])) {
    $student_id = $_POST["student_id"];
    $reset_query = "UPDATE users SET remaining_sessions = 30 WHERE idno = ?";
    $stmt = $conn->prepare($reset_query);
    $stmt->bind_param("s", $student_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Session for student ID $student_id has been reset!";
    } else {
        $_SESSION['error_message'] = "Error resetting session: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch all students from the database (exclude admins)
$sql = "SELECT idno, firstname, lastname, middlename, course, yearlevel, email, profile_picture, remaining_sessions FROM users WHERE role = 'student' ORDER BY lastname, firstname ASC";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    $_SESSION['error_message'] = "Error fetching students: " . $conn->error;
    $students = []; // Ensure it's an empty array even if there's an error
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Students | Admin Dashboard</title>
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
        
        function filterStudents() {
            const input = document.getElementById('search').value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? 'block' : 'none';
            });
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
        .student-card {
            transition: all 0.3s ease;
        }
        .student-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
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
                        <h1 class="text-3xl font-bold text-gray-900">List of Students</h1>
                        <p class="mt-2 text-sm text-gray-600">View and manage all registered students</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($students); ?> students
                        </span>
                        <form method="POST">
                            <button type="submit" name="reset_all_sessions" 
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors"
                                    onclick="return confirm('Are you sure you want to reset ALL student sessions?')">
                                <i class="fas fa-sync-alt mr-1"></i> Reset All Sessions
                            </button>
                        </form>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- Status Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <input 
                        type="text" 
                        id="search" 
                        class="w-full p-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200" 
                        placeholder="Search by name, ID, or course..."
                        onkeyup="filterStudents()"
                    >
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>

            <!-- Student Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="studentGrid">
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                        <div class="student-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                            <!-- Student Header -->
                            <div class="bg-primary p-4 text-white flex items-center">
                                <div class="flex-shrink-0 mr-4">
                                    <img 
                                        src="uploads/<?php echo htmlspecialchars($student['profile_picture'] ?? 'default_avatar.png'); ?>" 
                                        alt="Profile Picture" 
                                        class="w-12 h-12 rounded-full border-2 border-white object-cover"
                                        onerror="this.src='assets/default_avatar.png'"
                                    >
                                </div>
                                <div>
                                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
                                    <p class="text-sm text-primary-100"><?php echo htmlspecialchars($student['idno']); ?></p>
                                </div>
                            </div>
                            
                            <!-- Student Details -->
                            <div class="p-4">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Course</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($student['course']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Year Level</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($student['yearlevel']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Email</p>
                                        <p class="font-medium truncate"><?php echo htmlspecialchars($student['email']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider">Sessions</p>
                                        <p class="font-medium <?php echo $student['remaining_sessions'] <= 0 ? 'text-red-500' : 'text-green-500'; ?>">
                                            <?php echo htmlspecialchars($student['remaining_sessions']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Reset Session Button -->
                                <form method="POST">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['idno']); ?>">
                                    <button type="submit" name="reset_student_session" 
                                            class="w-full flex items-center justify-center px-4 py-2 bg-gradient-to-r from-primary to-primary-600 text-white rounded-md hover:from-primary-600 hover:to-primary-700 transition-all duration-200 shadow-sm"
                                            onclick="return confirm('Reset sessions for <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>?')">
                                        <i class="fas fa-sync-alt mr-2"></i> Reset Session
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10">
                        <i class="fas fa-user-graduate text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">No students found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

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
    </script>
</body>
</html>