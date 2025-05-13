<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno_session = $_SESSION['idno']; // Logged-in student's IDNO

// Fetch logged-in student's info (for top nav and their stats)
// Ensure all necessary fields for the student's view are fetched
$user_query = "SELECT id, firstname, lastname, profile_picture, remaining_sessions, points, email, course, yearlevel, role 
               FROM users 
               WHERE idno = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("s", $idno_session);
$stmt_user->execute();
$stmt_user->bind_result($user_id_session, $firstname_session, $lastname_session, $profile_picture_session, $remaining_sessions_session, $points_session, $email_session, $course_session, $yearlevel_session, $user_role_session);
$stmt_user->fetch();
$stmt_user->close();

// Use these for the top navigation bar specifically
$firstname_nav = $firstname_session; 
$profile_picture_nav = $profile_picture_session;

// Set default profile picture if none exists or file is missing
if (empty($profile_picture_nav) || !file_exists("uploads/" . $profile_picture_nav)) {
    $profile_picture_nav = "default_avatar.png";
}
// Also for the student's main data
if (empty($profile_picture_session) || !file_exists("uploads/" . $profile_picture_session)) {
    $profile_picture_session = "default_avatar.png";
}


// Fetch top 3 students for the podium
$top3_query = "SELECT id, firstname, lastname, points, remaining_sessions, profile_picture
              FROM users 
              WHERE role = 'student'
              ORDER BY points DESC, remaining_sessions DESC
              LIMIT 3";
$top3_result_q = mysqli_query($conn, $top3_query);
$top3_students = [];
while ($row_top3 = mysqli_fetch_assoc($top3_result_q)) {
    if (empty($row_top3['profile_picture']) || !file_exists("uploads/" . $row_top3['profile_picture'])) {
        $row_top3['profile_picture'] = 'default_avatar.png';
    }
    $top3_students[] = $row_top3;
}

// Fetch all students for leaderboard table (can be limited if performance is an issue, e.g., top 50 or implement pagination)
$leaderboard_query_all = "SELECT id, firstname, lastname, points, remaining_sessions, profile_picture 
                         FROM users 
                         WHERE role = 'student'
                         ORDER BY points DESC, remaining_sessions DESC";
$leaderboard_result_all = mysqli_query($conn, $leaderboard_query_all);


// Calculate student's actual rank more reliably
$student_actual_rank = "N/A"; 
$rank_counter = 0;
$temp_students_for_rank = [];
mysqli_data_seek($leaderboard_result_all, 0); 
while ($student_row_for_rank = mysqli_fetch_assoc($leaderboard_result_all)) {
    $temp_students_for_rank[] = $student_row_for_rank;
}

// Sort by points, then by remaining_sessions (desc) to handle ties correctly
usort($temp_students_for_rank, function($a, $b) {
    if ($b['points'] == $a['points']) {
        return $b['remaining_sessions'] <=> $a['remaining_sessions'];
    }
    return $b['points'] <=> $a['points'];
});

foreach ($temp_students_for_rank as $index => $ranked_student) {
    if ($ranked_student['id'] == $user_id_session) { 
        $student_actual_rank = $index + 1;
        break;
    }
}


$page_title = "Leaderboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - CCS Lab System</title>
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
                        secondary: '#D4C9BE', light: '#F1EFEC', dark: '#030303', // Main text on light bg
                        gold: '#FFD700', silver: '#C0C0C0', bronze: '#CD7F32', // Podium/Rank colors
                    }
                },
            },
        }
    </script>
    <style>
        /* Styles from student_leaderboard.php (top code) for top nav and base */
        .notification-badge { /* ... as in top code ... */ 
            position: absolute; top: -5px; right: -5px; background-color: #ef4444; color: white;
            border-radius: 50%; width: 18px; height: 18px; font-size: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .nav-dropdown { position: relative; }
        .nav-dropdown-content {
            display: none; position: absolute; background-color: #123458; /* Kept from student nav */
            min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1; border-radius: 0 0 0.5rem 0.5rem;
        }
        .nav-dropdown-content a {
            color: #F1EFEC; padding: 12px 16px; text-decoration: none;
            display: block; font-size: 0.875rem;
        }
        .nav-dropdown-content a:hover { background-color: rgba(255,255,255,0.1); }
        .nav-dropdown:hover .nav-dropdown-content { display: block; }
        .nav-dropdown-btn::after { content: "▾"; margin-left: 5px; font-size: 0.8em; }
        
        /* Leaderboard styles from admin_leaderboard.php (bottom code reference), adapted for light theme */
        .podium-item { transition: transform 0.3s ease-out, box-shadow 0.3s ease-out; }
        .podium-item:hover { transform: translateY(-8px); box-shadow: 0 15px 20px -5px rgba(0,0,0,0.1), 0 8px 8px -5px rgba(0,0,0,0.04); }
        .podium-base { clip-path: polygon(10% 0, 90% 0, 100% 100%, 0% 100%); border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        
        .rank-1 .podium-base { background: linear-gradient(135deg, #fde047, #fbbf24); height: 11rem; } /* Gold */
        .rank-2 .podium-base { background: linear-gradient(135deg, #e5e7eb, #d1d5db); height: 9rem;  } /* Silver */
        .rank-3 .podium-base { background: linear-gradient(135deg, #fDBA74, #fb923c); height: 7.5rem; } /* Bronze */

        .you-highlight-row td { background-color: #eff6ff !important; /* Light blue for student's row */ }
        .rank-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%; font-weight: 600;
            color: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light text-dark font-sans pt-16">

    <!-- Top Navigation Bar (Kept from original student_leaderboard.php) -->
    <nav class="fixed top-0 left-0 right-0 topnav shadow-lg z-50 bg-primary">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas <?php echo $user_role_session === 'admin' ? 'fa-user-shield' : 'fa-laptop-house'; ?> text-light mr-2 text-xl"></i>
                    <span class="text-xl font-semibold text-light">Lab System</span>
                </div>
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'text-white bg-primary-darker' : 'text-secondary'; ?>">Profile</a>
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'text-white bg-primary-darker' : ''; ?>">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'text-white bg-primary-darker' : ''; ?>">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_history.php' ? 'text-white bg-primary-darker' : ''; ?>">Sit-in History</a>
                    <a href="student_leaderboard.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-dark <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'text-white bg-primary-darker' : 'text-secondary'; ?>">Leaderboard</a>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Rules</button>
                        <div class="nav-dropdown-content">
                            <a href="sit-in-rules.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Sit-in Rules</a>
                            <a href="lab-rules.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Rules & Regulations</a>
                        </div>
                    </div>
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary-dark">Lab</button>
                        <div class="nav-dropdown-content">
                            <a href="upload_resources.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Resources</a>
                            <a href="student_lab_schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Lab Schedule</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 ml-4">
                        <div class="relative"><button id="notificationButton" class="relative p-2 text-light hover:text-secondary rounded-full hover:bg-white/10"><i class="fas fa-bell text-lg"></i><span class="notification-badge hidden">0</span></button><div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><div class="p-3 bg-primary text-white flex justify-between items-center"><span class="font-semibold">Notifications</span><button id="markAllReadButton" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded">Mark all as read</button></div><div id="notificationList" class="max-h-80 overflow-y-auto"><div class="p-4 text-center text-gray-500">No notifications</div></div></div></div>
                        <div class="relative"><button id="userMenuButton" class="flex items-center gap-2 group"><img class="h-9 w-9 rounded-full border-2 border-white/20 group-hover:border-primary object-cover" src="uploads/<?php echo htmlspecialchars($profile_picture_nav); ?>" onerror="this.src='assets/default_avatar.png'" alt="Profile"><span class="text-light font-medium hidden md:inline-block"><?php echo htmlspecialchars($firstname_nav); ?></span></button><div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50"><a href="edit-profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary <?php echo basename($_SERVER['PHP_SELF']) === 'edit-profile.php' ? 'bg-gray-100 text-primary font-medium' : ''; ?>"><i class="fas fa-user-edit mr-2"></i>Edit Profile</a><div class="border-t border-gray-200"></div><a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary"><i class="fas fa-sign-out-alt mr-2"></i>Log Out</a></div></div>
                    </div>
                </div>
                <div class="mobile-menu md:hidden flex items-center"><button id="mobileMenuButton" class="text-light hover:text-secondary"><i class="fas fa-bars text-xl"></i></button></div>
            </div>
        </div>
        <div id="mobileMenu" class="hidden md:hidden bg-primary border-t border-primary-dark">
             <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="student_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-primary-darker text-white' : 'text-light hover:bg-primary-dark'; ?>">Profile</a>
                <a href="edit-profile.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'edit-profile.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Edit Profile</a>
                <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Announcements</a>
                <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'reservation.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Reservation</a>
                <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'sit_in_history.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Sit-in History</a>
                <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Leaderboard</a>
                <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">View Resources</a>
                <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Lab Schedule</a>
                <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Sit-in Rules</a>
                <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'bg-primary-darker text-white' : 'text-secondary hover:bg-primary-dark'; ?>">Lab Rules</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary-dark">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-xl p-6 md:p-8 border border-gray-200">
            <header class="mb-10 text-center">
                <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary to-blue-600">
                    Student Leaderboard
                </h1>
                <p class="mt-2 text-gray-600 text-lg">See where you stand among your peers!</p>
            </header>

            <!-- Your Stats Section -->
            <section class="mb-12 p-6 bg-gradient-to-r from-primary to-blue-600 text-white rounded-xl shadow-lg">
                <h2 class="text-2xl font-semibold mb-4 text-center">Your Performance</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                    <div>
                        <div class="text-4xl font-bold"><?php echo htmlspecialchars($student_actual_rank); ?></div>
                        <div class="text-sm opacity-80">Your Rank</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold"><?php echo htmlspecialchars($points_session); ?></div>
                        <div class="text-sm opacity-80">Your Points</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold"><?php echo htmlspecialchars($remaining_sessions_session); ?></div>
                        <div class="text-sm opacity-80">Sessions Left</div>
                    </div>
                </div>
            </section>

            <!-- Podium Section (Styled like admin_leaderboard.php) -->
            <section class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center">Top Performers</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 items-end">
                    <!-- 2nd Place -->
                    <div class="flex flex-col items-center podium-item rank-2 order-2 md:order-1">
                        <?php if (isset($top3_students[1])): $student_podium = $top3_students[1]; ?>
                        <img src="uploads/<?php echo htmlspecialchars($student_podium['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student_podium['firstname']); ?>" class="w-24 h-24 md:w-28 md:h-28 rounded-full border-4 border-white object-cover mb-[-3.5rem] z-10 relative shadow-lg">
                        <div class="podium-base w-full pt-16 p-5 text-center">
                            <h3 class="text-xl md:text-2xl font-semibold text-gray-700"><?php echo htmlspecialchars($student_podium['firstname'] . ' ' . $student_podium['lastname']); ?></h3>
                            <p class="text-2xl md:text-3xl font-bold text-gray-600 mt-1"><?php echo $student_podium['points']; ?> <span class="text-sm font-normal">pts</span></p>
                        </div>
                        <?php else: ?> <div class="podium-base w-full p-6 text-center h-36 flex items-center justify-center bg-gray-100 rounded-t-lg"><span class="text-gray-400">N/A</span></div> <?php endif; ?>
                    </div>

                    <!-- 1st Place -->
                    <div class="flex flex-col items-center podium-item rank-1 order-1 md:order-2">
                         <?php if (isset($top3_students[0])): $student_podium = $top3_students[0]; ?>
                        <img src="uploads/<?php echo htmlspecialchars($student_podium['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student_podium['firstname']); ?>" class="w-28 h-28 md:w-36 md:h-36 rounded-full border-4 border-white object-cover mb-[-4.5rem] z-10 relative shadow-xl">
                        <div class="podium-base w-full pt-20 p-6 text-center">
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($student_podium['firstname'] . ' ' . $student_podium['lastname']); ?></h3>
                            <p class="text-3xl md:text-4xl font-extrabold text-gray-700 mt-1"><?php echo $student_podium['points']; ?> <span class="text-lg font-normal">pts</span></p>
                        </div>
                         <?php else: ?> <div class="podium-base w-full p-8 text-center h-48 flex items-center justify-center bg-gray-100 rounded-t-lg"><span class="text-gray-400">N/A</span></div> <?php endif; ?>
                    </div>

                    <!-- 3rd Place -->
                    <div class="flex flex-col items-center podium-item rank-3 order-3">
                        <?php if (isset($top3_students[2])): $student_podium = $top3_students[2]; ?>
                        <img src="uploads/<?php echo htmlspecialchars($student_podium['profile_picture']); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student_podium['firstname']); ?>" class="w-20 h-20 md:w-24 md:h-24 rounded-full border-4 border-white object-cover mb-[-3rem] z-10 relative shadow-md">
                        <div class="podium-base w-full pt-12 p-4 text-center">
                            <h3 class="text-lg md:text-xl font-semibold text-gray-700"><?php echo htmlspecialchars($student_podium['firstname'] . ' ' . $student_podium['lastname']); ?></h3>
                            <p class="text-xl md:text-2xl font-bold text-gray-600 mt-1"><?php echo $student_podium['points']; ?> <span class="text-xs font-normal">pts</span></p>
                        </div>
                        <?php else: ?> <div class="podium-base w-full p-5 text-center h-28 flex items-center justify-center bg-gray-100 rounded-t-lg"><span class="text-gray-400">N/A</span></div> <?php endif; ?>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-2xl font-semibold text-gray-700 mb-6">Full Leaderboard</h2>
                <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rank</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Points</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Sessions Left</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $current_rank_display_table = 1;
                            mysqli_data_seek($leaderboard_result_all, 0); // Reset for display
                            while($student_lb_row = mysqli_fetch_assoc($leaderboard_result_all)):
                                $is_current_user_in_table = ($student_lb_row['id'] == $user_id_session);
                                $profile_pic_lb_table = !empty($student_lb_row['profile_picture']) && file_exists("uploads/" . $student_lb_row['profile_picture']) ? $student_lb_row['profile_picture'] : 'default_avatar.png';
                            ?>
                            <tr class="<?php echo $is_current_user_in_table ? 'you-highlight-row' : 'hover:bg-gray-50'; ?> transition-colors duration-100">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="rank-badge <?php
                                        if ($current_rank_display_table == 1) echo 'bg-gold text-gray-800';
                                        elseif ($current_rank_display_table == 2) echo 'bg-silver text-gray-700';
                                        elseif ($current_rank_display_table == 3) echo 'bg-bronze text-white';
                                        else echo 'bg-primary text-light';
                                    ?>">
                                        <?php echo $current_rank_display_table; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img src="uploads/<?php echo htmlspecialchars($profile_pic_lb_table); ?>" onerror="this.src='assets/default_avatar.png'" alt="<?php echo htmlspecialchars($student_lb_row['firstname']); ?>" class="w-10 h-10 rounded-full mr-3 object-cover border-2 <?php echo $is_current_user_in_table ? 'border-primary' : 'border-gray-200'; ?>">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student_lb_row['firstname'] . ' ' . $student_lb_row['lastname']); ?></div>
                                            <?php if ($is_current_user_in_table): ?>
                                                <span class="text-xs text-primary font-semibold">(You)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-primary"><?php echo $student_lb_row['points']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700"><?php echo $student_lb_row['remaining_sessions']; ?></td>
                            </tr>
                            <?php $current_rank_display_table++; endwhile; ?>
                            <?php if(mysqli_num_rows($leaderboard_result_all) == 0): ?>
                                <tr><td colspan="4" class="p-4 text-center text-gray-500">Leaderboard is empty.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        // --- Top Navigation Bar Scripts (from student_leaderboard.php top code) ---
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        const userMenuButton = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadButton'); // Corrected ID from student nav

        if(mobileMenuButton && mobileMenu) mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
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

        function loadNotifications() { /* Kept from student_leaderboard.php top code */
            fetch('get_notifications.php') // Assuming this exists and is correct for students
                .then(response => response.json())
                .then(data => {
                    const notificationList = document.getElementById('notificationList');
                    const badge = document.querySelector('.notification-badge');
                    if (!notificationList || !badge) return;

                    if (data.length === 0) {
                        notificationList.innerHTML = '<div class="p-3 text-center text-gray-500">No notifications</div>'; // Adjusted text color for white bg
                        badge.classList.add('hidden');
                        return;
                    }
                    
                    notificationList.innerHTML = '';
                    let unreadCount = 0;
                    data.forEach(notification => {
                        const item = document.createElement('div');
                        // Adjusted classes for better readability on white dropdown background
                        item.className = `p-3 border-b border-gray-100 ${!notification.is_read ? 'bg-blue-50 font-medium' : 'text-gray-600'}`;
                        item.innerHTML = `
                            <div class="flex justify-between items-start">
                                <p class="text-sm flex-1">${notification.message}</p>
                                ${!notification.is_read ? '<span class="w-2 h-2 mt-1 rounded-full bg-primary ml-2 flex-shrink-0"></span>' : ''}
                            </div>
                            <p class="text-xs text-gray-400 mt-1">${new Date(notification.created_at).toLocaleString()}</p>
                        `;
                        notificationList.appendChild(item);
                        if (!notification.is_read) unreadCount++;
                    });
                    
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(err => {
                     const notificationList = document.getElementById('notificationList');
                     if(notificationList) notificationList.innerHTML = '<div class="p-3 text-center text-red-500">Error loading.</div>';
                     console.error("Error loading notifications:", err);
                });
        }
        function markAllNotificationsAsRead() { /* Kept from student_leaderboard.php top code */
            fetch('mark_notifications_read.php', { method: 'POST' }) // Assuming this exists
                .then(response => response.json())
                .then(data => { if (data.success) loadNotifications(); })
                .catch(err => console.error("Error marking notifications as read:", err));
        }
        if(markAllReadBtn) markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);
        loadNotifications(); // Initial load
        // setInterval(loadNotifications, 30000); // Optional: refresh periodically
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>