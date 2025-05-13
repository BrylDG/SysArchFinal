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
    $profile_picture = "default_avatar.png";
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["resource_file"])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $available_to = $_POST['available_to'] ?? 'all';
    $file = $_FILES['resource_file'];

    // Validate inputs
    if (empty($title) || empty($file['name'])) {
        $upload_error = "Title and file are required";
    } else {
        $upload_dir = 'resources/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = basename($file['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name;
        $file_size = $file['size'];
        $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // Allowed file types
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];

        if (!in_array($file_type, $allowed_types)) {
            $upload_error = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
        } elseif ($file['size'] > 50 * 1024 * 1024) { // 50MB max
            $upload_error = "File size exceeds 50MB limit";
        } elseif (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO resources (title, description, file_name, file_path, file_size, file_type, available_to, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $title, $description, $file_name, $file_path, $file_size, $file_type, $available_to, $idno);

            if ($stmt->execute()) {
                $upload_success = "Resource uploaded successfully!";
            } else {
                $upload_error = "Database error: " . $conn->error;
                // Remove the uploaded file if DB insert failed
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            $stmt->close();
        } else {
            $upload_error = "Error uploading file. Check permissions or server configuration.";
            if ($file['error'] !== UPLOAD_ERR_OK) {
                 $upload_error .= " PHP Upload Error Code: " . $file['error'];
            }
        }
    }
}

// Fetch all resources
$resources_query = "SELECT * FROM resources ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);

// Function to format file size
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

// The main database connection $conn will be closed at the end of the script.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resources - Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        /* Active class for dropdown items as per original snippet's pattern */
        .nav-dropdown-content a.bg-primary-700 { /* Example from original for active dropdown item */
             background-color: #123458; /* Assuming bg-primary-700 is a darker shade or specific primary variant */
             /* Or if it's meant to be like the main nav active: */
             /* background-color: rgba(255,255,255,0.15); */
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

        .upload-dropzone {
            transition: all 0.3s ease;
        }

        .upload-dropzone.dragover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#123458', // Main primary color
                        'primary-dark': '#0f2a48', // Darker shade for hover
                        'primary-darker': '#0c223a', // Even darker
                        secondary: '#D4C9BE',
                        light: '#F1EFEC',
                        dark: '#030303',
                        // For dropdown item active state, if bg-primary-700 is intended as a specific shade
                        // 'primary-700': '#your_specific_shade_for_bg-primary-700',
                    }
                },
            },
        }
    </script>
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
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-folder-open mr-2 text-primary"></i> Resource Management
                        </h1>
                        <p class="mt-2 text-sm text-gray-500">Upload and manage lab resources for students and faculty</p>
                    </div>
                </div>
                <div class="mt-4 border-b border-gray-200"></div>
            </div>

            <!-- File Upload Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 mb-8">
                <div class="p-6 upload-dropzone" id="uploadDropzone">
                    <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <?php if(isset($upload_success)): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                                <?php echo $upload_success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($upload_error)): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                                <?php echo $upload_error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center border-2 border-dashed border-gray-300 rounded-lg p-8">
                            <i class="fas fa-cloud-upload-alt text-4xl text-primary mb-2"></i>
                            <p class="text-gray-600 mb-4">Drag & drop files here or click to browse</p>
                            <input type="file" name="resource_file" id="resourceFile" class="hidden" required>
                            <button type="button" onclick="document.getElementById('resourceFile').click()"
                                    class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-md transition-all duration-200">
                                <i class="fas fa-upload mr-2"></i> Select File
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Max file size: 50MB</p>
                            <p id="fileName" class="text-sm text-gray-700 mt-2 hidden"></p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                                <input type="text" name="title" class="w-full p-2 border border-gray-300 rounded-md" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Available To *</label>
                                <select name="available_to" class="w-full p-2 border border-gray-300 rounded-md">
                                    <option value="all">All Users</option>
                                    <option value="students">Students Only</option>
                                    <option value="admins">Admins Only</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea name="description" rows="2" class="w-full p-2 border border-gray-300 rounded-md"></textarea>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full flex items-center justify-center p-2 bg-gradient-to-r from-primary to-primary-dark text-white rounded-md hover:from-primary-dark hover:to-primary-darker transition-all duration-200 shadow-md">
                                <i class="fas fa-upload mr-2"></i> Upload Resource
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resource Grid -->
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
                    <?php if ($resources_result && mysqli_num_rows($resources_result) > 0): ?>
                        <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                            <?php
                            $file_ext = strtolower(pathinfo($resource['file_name'], PATHINFO_EXTENSION));
                            $icon = 'fa-file'; // Default icon
                            $icon_color = 'text-gray-500'; // Default color
                            $type_name = strtoupper($file_ext); // Default type name

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
                                'gif' => ['fa-file-image', 'text-purple-500', 'Image'],
                                'txt' => ['fa-file-alt', 'text-gray-600', 'Text']
                            ];

                            if (array_key_exists($file_ext, $type_classes)) {
                                list($icon, $icon_color, $type_name) = $type_classes[$file_ext];
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
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $icon_color; ?> bg-opacity-20 <?php echo str_replace('text-', 'bg-', $icon_color); ?>">
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
                                        <a href="delete_resource.php?id=<?php echo $resource['id']; ?>"
                                           class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center transition-colors"
                                           onclick="return confirm('Are you sure you want to delete this resource?')">
                                            <i class="fas fa-trash mr-2"></i>
                                            <span class="hidden md:inline">Delete</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                         <div class="p-12 text-center">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-folder-open text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-700">No resources available</h3>
                            <p class="text-gray-500 mt-1">Upload your first resource using the form above</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination (Static example, would need dynamic implementation for actual pagination logic) -->
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
                                Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo ($resources_result) ? min(10, mysqli_num_rows($resources_result)) : 0; ?></span> of <span class="font-medium"><?php echo ($resources_result) ? mysqli_num_rows($resources_result) : 0; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <!-- Example active page -->
                                <a href="#" aria-current="page" class="z-10 bg-primary border-primary text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <!-- Example other pages (if more than one page and pagination is implemented) -->
                                <?php if ($resources_result && mysqli_num_rows($resources_result) > 10): // Basic check for >1 page ?>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </a>
                                <?php endif; ?>
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
            // Hide notification dropdown if open
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // Notification functionality
        const notificationButton = document.getElementById('notificationButton');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBadge = document.querySelector('.notification-badge');
        const markAllReadButton = document.getElementById('markAllRead');


        if (notificationButton) {
            notificationButton.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('hidden');
                // Hide user dropdown if open
                if (userDropdown && !userDropdown.classList.contains('hidden')) {
                    userDropdown.classList.add('hidden');
                }
                if (!notificationDropdown.classList.contains('hidden')) {
                    loadNotifications();
                }
            });
        }
        
        if (notificationDropdown) {
             notificationDropdown.addEventListener('click', function(e) { // Prevent dropdown from closing when clicking inside
                e.stopPropagation();
            });
        }

        if (userDropdown) {
            userDropdown.addEventListener('click', function(e) { // Prevent user dropdown from closing when clicking inside
                e.stopPropagation();
            });
        }


        if (markAllReadButton) {
            markAllReadButton.addEventListener('click', function() {
                markAllNotificationsAsRead();
            });
        }
        

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            if (userDropdown) userDropdown.classList.add('hidden');
            if (notificationDropdown) notificationDropdown.classList.add('hidden');
        });

        // Function to load notifications
        function loadNotifications() {
            // Check if elements exist before trying to use them
            const notificationList = document.getElementById('notificationList');
            if (!notificationList || !notificationBadge) return;


            fetch('get_notifications.php') // Assumes get_notifications.php is in the same directory
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error('Error from server:', data.error);
                        notificationList.innerHTML = `<div class="p-4 text-center text-red-500">Error: ${data.error}</div>`;
                        return;
                    }

                    if (data.length === 0) {
                        notificationList.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                        notificationBadge.classList.add('hidden');
                        return;
                    }

                    notificationList.innerHTML = '';
                    let unreadCount = 0;

                    data.forEach(notification => {
                        const notificationItem = document.createElement('div');
                        notificationItem.className = `p-3 border-b border-gray-100 notification-item ${notification.is_read == 0 ? 'text-gray-800 bg-blue-50 font-semibold' : 'text-gray-600'}`;
                        
                        let date = new Date(notification.created_at.replace(' ', 'T') + 'Z'); // Adjust for UTC if stored as such
                        let formattedDate = 'Invalid Date';
                        if (!isNaN(date.getTime())) {
                             formattedDate = date.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                        }


                        notificationItem.innerHTML = `
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-sm">${notification.message}</p>
                                    <p class="text-xs text-gray-400 mt-1">${formattedDate}</p>
                                </div>
                                ${notification.is_read == 0 ? '<span class="w-2 h-2 rounded-full bg-primary ml-2 mt-1 flex-shrink-0"></span>' : ''}
                            </div>
                        `;
                        notificationList.appendChild(notificationItem);

                        if (notification.is_read == 0) {
                            unreadCount++;
                        }
                    });

                    if (unreadCount > 0) {
                        notificationBadge.textContent = unreadCount;
                        notificationBadge.classList.remove('hidden');
                    } else {
                        notificationBadge.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    if (notificationList) {
                        notificationList.innerHTML = '<div class="p-4 text-center text-red-500">Could not load notifications.</div>';
                    }
                });
        }

        // Function to mark all notifications as read
        function markAllNotificationsAsRead() {
            fetch('mark_notifications_read.php', { // Assumes mark_notifications_read.php is in the same directory
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                } else {
                    console.error('Failed to mark notifications as read:', data.message);
                }
            })
            .catch(error => console.error('Error marking notifications as read:', error));
        }

        // Load notifications on page load if notification system is present
        if (document.getElementById('notificationButton')) {
            loadNotifications();
            // Check for new notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        }


        // Handle file input change
        const fileInput = document.getElementById('resourceFile');
        const fileNameElement = document.getElementById('fileName');

        if (fileInput && fileNameElement) {
            fileInput.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    fileNameElement.textContent = this.files[0].name;
                    fileNameElement.classList.remove('hidden');
                } else {
                    fileNameElement.textContent = '';
                    fileNameElement.classList.add('hidden');
                }
            });
        }


        // Drag and drop functionality
        const dropzone = document.getElementById('uploadDropzone');

        if (dropzone && fileInput) {
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    // Trigger change event for fileInput to update fileNameElement
                    const changeEvent = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(changeEvent);
                }
            });
        }

    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>