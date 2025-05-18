<?php
session_start();
include 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch student info
$user_query = "SELECT firstname, lastname, profile_picture FROM users WHERE idno = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $profile_picture);
$stmt->fetch();
$stmt->close();

// Set default profile picture if none exists
if (empty($profile_picture)) {
    $profile_picture = "default_avatar.png";
}

// Fetch resources available to students
$resources_query = "SELECT * FROM resources WHERE available_to = 'students' OR available_to = 'all' ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);

$page_title = "Lab Resources";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Resources - CCS SIT Monitoring System</title>
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
        main {
            background-color: #F1EFEC;
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
        
        .file-card {
            transition: all 0.2s ease;
            border-left: 4px solid #123458;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .file-icon {
            transition: all 0.3s ease;
        }
        
        .file-card:hover .file-icon {
            transform: scale(1.1);
        }
        
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
                        <span class="text-xl font-semibold text-light">Lab System</span>
                    </div>
                </div>
                
                <!-- Desktop Menu -->
                <div class="desktop-menu hidden md:flex items-center space-x-1">
                    <a href="student_dashboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_dashboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Profile</a>
                    <a href="announcements.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Announcements</a>
                    <a href="reservation.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Reservation</a>
                    <a href="sit_in_history.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                    <a href="student_leaderboard.php" class="px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'student_leaderboard.php' ? 'bg-slate-700/20 text-white' : ''; ?>">Leaderboard</a>
                    
                    <!-- Rules Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-secondary hover:bg-primary/20">
                            Rules
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="sit-in-rules.php" <?php echo basename($_SERVER['PHP_SELF']) === 'sit-in-rules.php' ? 'class="bg-primary-700"' : ''; ?>>Sit-in Rules</a>
                            <a href="lab-rules.php" <?php echo basename($_SERVER['PHP_SELF']) === 'lab-rules.php' ? 'class="bg-primary-700"' : ''; ?>>Lab Rules & Regulations</a>
                        </div>
                    </div>
                    
                    <!-- Lab Dropdown -->
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-btn px-3 py-2 rounded-md text-sm font-medium text-light hover:bg-primary/20 <?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' || basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'bg-slate-700/20 text-white' : ''; ?>">
                            Lab
                        </button>
                        <div class="nav-dropdown-content">
                            <a href="upload_resources.php" <?php echo basename($_SERVER['PHP_SELF']) === 'upload_resources.php' ? 'class="bg-primary-700"' : ''; ?>>Lab Resources</a>
                            <a href="student_lab_schedule.php" <?php echo basename($_SERVER['PHP_SELF']) === 'student_lab_schedule.php' ? 'class="bg-primary-700"' : ''; ?>>Lab Schedule</a>
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
                <a href="announcements.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Announcements</a>
                <a href="reservation.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Reservation</a>
                <a href="sit_in_history.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in History</a>
                <a href="student_leaderboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Leaderboard</a>
                <a href="sit-in-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Sit-in Rules</a>
                <a href="lab-rules.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Rules</a>
                <a href="upload_resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20 bg-primary/50">Lab Resources</a>
                <a href="student_lab_schedule.php" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Lab Schedule</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')" class="block px-3 py-2 rounded-md text-base font-medium text-secondary hover:bg-primary/20">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-16 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white">
            <!-- Page header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-book-open mr-2 text-primary"></i>Lab Resources
                        </h1>
                        <p class="mt-2 text-sm text-gray-500">Access learning materials, guides, and resources for your lab sessions</p>
                    </div>
                    
                    <!-- Search and Filter -->
                    <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-grow">
                            <input type="text" placeholder="Search resources..." 
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            <option value="">All Types</option>
                            <option value="pdf">PDF</option>
                            <option value="doc">Word</option>
                            <option value="xls">Excel</option>
                            <option value="ppt">PowerPoint</option>
                            <option value="image">Images</option>
                            <option value="archive">Archives</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- Resource Cards Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
                <!-- Resource Grid Header -->
                <div class="grid grid-cols-12 bg-gray-50 p-4 border-b border-gray-200 hidden md:grid">
                    <div class="col-span-6 font-medium text-gray-700">Resource</div>
                    <div class="col-span-2 font-medium text-gray-700 text-center">Type</div>
                    <div class="col-span-2 font-medium text-gray-700 text-center">Size</div>
                    <div class="col-span-2 font-medium text-gray-700 text-center">Actions</div>
                </div>

                <!-- Resource List -->
                <div class="divide-y divide-gray-200">
                    <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                        <?php
                        $file_ext = pathinfo($resource['file_name'], PATHINFO_EXTENSION);
                        $icon = 'fa-file';
                        $icon_color = 'text-blue-500';
                        
                        $type_classes = [
                            'pdf' => ['fa-file-pdf', 'text-red-500', 'PDF'],
                            'doc' => ['fa-file-word', 'text-blue-600', 'Word'],
                            'docx' => ['fa-file-word', 'text-blue-600', 'Word'],
                            'xls' => ['fa-file-excel', 'text-green-600', 'Excel'],
                            'xlsx' => ['fa-file-excel', 'text-green-600', 'Excel'],
                            'ppt' => ['fa-file-powerpoint', 'text-orange-500', 'PPT'],
                            'pptx' => ['fa-file-powerpoint', 'text-orange-500', 'PPT'],
                            'zip' => ['fa-file-archive', 'text-yellow-500', 'Archive'],
                            'rar' => ['fa-file-archive', 'text-yellow-500', 'Archive'],
                            'jpg' => ['fa-file-image', 'text-purple-500', 'Image'],
                            'jpeg' => ['fa-file-image', 'text-purple-500', 'Image'],
                            'png' => ['fa-file-image', 'text-purple-500', 'Image'],
                            'gif' => ['fa-file-image', 'text-purple-500', 'Image']
                        ];
                        
                        if (array_key_exists($file_ext, $type_classes)) {
                            list($icon, $icon_color, $type_name) = $type_classes[$file_ext];
                        } else {
                            $type_name = strtoupper($file_ext);
                        }
                        ?>
                        
                        <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <!-- Resource Name and Info (Mobile) -->
                                <div class="col-span-6 flex items-center space-x-4">
                                    <div class="bg-gray-100 p-3 rounded-lg">
                                        <i class="fas <?php echo $icon; ?> text-2xl <?php echo $icon_color; ?>"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($resource['title']); ?></h3>
                                        <p class="text-sm text-gray-500 md:hidden">
                                            <?php echo $type_name; ?> • <?php echo formatSizeUnits($resource['file_size']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Uploaded: <?php echo date('M d, Y', strtotime($resource['upload_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Type (Desktop) -->
                                <div class="col-span-2 text-center hidden md:block">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $icon_color; ?> bg-opacity-20 <?php echo str_replace('text', 'bg', $icon_color); ?>">
                                        <?php echo $type_name; ?>
                                    </span>
                                </div>
                                
                                <!-- Size (Desktop) -->
                                <div class="col-span-2 text-center text-gray-600 hidden md:block">
                                    <?php echo formatSizeUnits($resource['file_size']); ?>
                                </div>
                                
                                <!-- Actions -->
                                <div class="col-span-2 flex justify-end space-x-2">
                                    <a href="download_resource.php?id=<?php echo $resource['id']; ?>" 
                                       class="px-3 py-1 bg-primary hover:bg-primary-dark text-white rounded-md text-sm flex items-center transition-colors">
                                        <i class="fas fa-download mr-2"></i>
                                        <span class="hidden md:inline">Download</span>
                                    </a>
                                    <button class="p-2 text-gray-500 hover:text-primary rounded-full transition-colors" 
                                            onclick="showResourceInfo(<?php echo htmlspecialchars(json_encode($resource)); ?>)">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if(mysqli_num_rows($resources_result) == 0): ?>
                        <div class="p-12 text-center">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-folder-open text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700">No resources available</h3>
                            <p class="text-gray-500 mt-1">Check back later for new learning materials</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">20</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-primary border-primary text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Resource Info Modal -->
    <div id="resourceModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                Resource Information
                            </h3>
                            <div class="mt-4">
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <p class="mt-1 text-sm text-gray-900" id="modalTitleText"></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Description</label>
                                        <p class="mt-1 text-sm text-gray-900" id="modalDescription"></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">File Type</label>
                                            <p class="mt-1 text-sm text-gray-900" id="modalFileType"></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">File Size</label>
                                            <p class="mt-1 text-sm text-gray-900" id="modalFileSize"></p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Uploaded On</label>
                                        <p class="mt-1 text-sm text-gray-900" id="modalUploadDate"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="downloadBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                        Download
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Helper function to format file sizes
    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
    ?>

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
                        notificationList.innerHTML = '<div class="p-4 text-center text-secondary">No notifications</div>';
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
        
        // Resource Modal Functions
        function showResourceInfo(resource) {
            document.getElementById('modalTitleText').textContent = resource.title;
            document.getElementById('modalDescription').textContent = resource.description || 'No description provided';
            
            const fileExt = resource.file_name.split('.').pop().toUpperCase();
            document.getElementById('modalFileType').textContent = fileExt;
            document.getElementById('modalFileSize').textContent = formatSizeUnits(resource.file_size);
            document.getElementById('modalUploadDate').textContent = new Date(resource.upload_date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('downloadBtn').onclick = function() {
                window.location.href = `download_resource.php?id=${resource.id}`;
            };
            
            document.getElementById('resourceModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('resourceModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('resourceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Helper function to format file sizes for JS
        function formatSizeUnits(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else if (bytes > 1) {
                return bytes + ' bytes';
            } else if (bytes == 1) {
                return bytes + ' byte';
            } else {
                return '0 bytes';
            }
        }
    </script>
</body>
</html>