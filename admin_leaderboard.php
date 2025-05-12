<?php
session_start();
include 'db.php';

if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Modified query to include profile pictures
$students_query = "SELECT 
                    u.id, 
                    u.idno, 
                    u.firstname, 
                    u.lastname, 
                    u.points, 
                    u.remaining_sessions,
                    u.profile_picture,
                    SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(s.end_time, s.start_time)))) AS total_duration
                  FROM users u
                  LEFT JOIN sit_in_records s ON u.id = s.student_id
                  WHERE u.role = 'student'
                  GROUP BY u.id
                  ORDER BY u.points DESC, u.remaining_sessions DESC
                  LIMIT 50";
$students_result = mysqli_query($conn, $students_query);

// Get top 3 students separately for the podium
$top3_query = "SELECT 
                u.id, 
                u.idno, 
                u.firstname, 
                u.lastname, 
                u.points, 
                u.remaining_sessions,
                u.profile_picture
              FROM users u
              WHERE u.role = 'student'
              ORDER BY u.points DESC, u.remaining_sessions DESC
              LIMIT 3";
$top3_result = mysqli_query($conn, $top3_query);
$top3 = [];
while ($row = mysqli_fetch_assoc($top3_result)) {
    $top3[] = $row;
}

// Recent activity logs - simplified to show points only
$logs_query = "SELECT r.*, u.firstname, u.lastname 
              FROM rewards_log r
              JOIN users u ON r.user_id = u.id
              ORDER BY r.created_at DESC
              LIMIT 50";
$logs_result = mysqli_query($conn, $logs_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    // ... (keep your existing point adjustment logic) ...
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Leaderboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            overflow-x: hidden;
            min-height: 100vh;
            height: 100%;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
        }
        .podium-item {
            transition: all 0.3s ease;
        }
        .podium-item:hover {
            transform: translateY(-5px);
        }
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
            <p class="text-sm text-slate-400 mt-2">Leaderboard</p>
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
                    <a href="admin_leaderboard.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white transition-all duration-200">
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_lab_schedule.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
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

    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-600/20 text-green-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-600/20 text-red-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <h2 class="text-2xl font-semibold mb-6 text-white border-b border-white/10 pb-2">
                <i class="fas fa-trophy mr-2 text-yellow-400"></i> Leaderboard
            </h2>

            <!-- Podium for Top 3 Students -->
            <div class="mb-12">
                <h3 class="text-xl font-semibold mb-6 text-center">Top Performers</h3>
                <div class="flex items-end justify-center gap-4 h-64">
                    <!-- 2nd Place -->
                    <?php if (isset($top3[1])): ?>
                    <div class="podium-item flex flex-col items-center w-1/4">
                        <div class="bg-gray-400 w-full rounded-t-lg h-40 flex items-center justify-center relative">
                            <span class="text-4xl">🥈</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[1]['firstname'].' '.$top3[1]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[1]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-gray-300">2nd</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 1st Place -->
                    <?php if (isset($top3[0])): ?>
                    <div class="podium-item flex flex-col items-center w-1/3">
                        <div class="bg-yellow-400 w-full rounded-t-lg h-56 flex items-center justify-center relative">
                            <span class="text-4xl">🥇</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[0]['firstname'].' '.$top3[0]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[0]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-yellow-400">1st</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- 3rd Place -->
                    <?php if (isset($top3[2])): ?>
                    <div class="podium-item flex flex-col items-center w-1/4">
                        <div class="bg-amber-600 w-full rounded-t-lg h-32 flex items-center justify-center relative">
                            <span class="text-4xl">🥉</span>
                            <div class="absolute -bottom-6 w-full text-center">
                                <div class="text-lg font-bold"><?= htmlspecialchars($top3[2]['firstname'].' '.$top3[2]['lastname']) ?></div>
                                <div class="text-sm"><?= $top3[2]['points'] ?> points</div>
                            </div>
                        </div>
                        <div class="text-center mt-8">
                            <div class="text-xl font-bold text-amber-600">3rd</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Full Leaderboard Table -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">All Students</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left p-3">Rank</th>
                                <th class="text-left p-3">Student</th>
                                <th class="text-left p-3">ID</th>
                                <th class="text-right p-3">Points</th>
                                <th class="text-right p-3">Sessions</th>
                                <th class="text-right p-3">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            mysqli_data_seek($students_result, 0); // Reset pointer
                            while($student = mysqli_fetch_assoc($students_result)): 
                                // Skip top 3 since we already displayed them
                                if ($rank <= 3) {
                                    $rank++;
                                    continue;
                                }
                            ?>
                            <tr class="border-b border-white/5 hover:bg-slate-700/50">
                                <td class="p-3 font-medium"><?= $rank ?></td>
                                <td class="p-3"><?= htmlspecialchars($student['firstname'].' '.$student['lastname']) ?></td>
                                <td class="p-3 font-mono text-sm"><?= htmlspecialchars($student['idno']) ?></td>
                                <td class="p-3 text-right font-medium"><?= $student['points'] ?></td>
                                <td class="p-3 text-right font-medium"><?= $student['remaining_sessions'] ?></td>
                                <td class="p-3 text-right font-mono text-sm">
                                    <?= $student['total_duration'] ?: '00:00:00' ?>
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Reward Activities - simplified to show points only -->
            <div>
                <h3 class="text-xl font-semibold mb-4">Recent Reward Activities</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/10">
                                <th class="text-left p-3">Date</th>
                                <th class="text-left p-3">Student</th>
                                <th class="text-right p-3">Points</th>
                                <th class="text-left p-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($logs_result, 0); // Reset pointer
                            while($log = mysqli_fetch_assoc($logs_result)): 
                            ?>
                            <tr class="border-b border-white/5 hover:bg-slate-700/50">
                                <td class="p-3 text-sm"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                                <td class="p-3"><?= htmlspecialchars($log['firstname'].' '.$log['lastname']) ?></td>
                                <td class="p-3 text-right font-mono">
                                    <span class="<?= ($log['points_earned'] > 0 ? 'text-green-400' : 'text-red-400') ?>">
                                        <?= ($log['points_earned'] > 0 ? '+' : '').$log['points_earned'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-1 bg-slate-700/50 rounded-full text-xs">
                                        <?= ucfirst(str_replace('_', ' ', $log['action'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>