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
                unlink($file_path);
            }
            $stmt->close();
        } else {
            $upload_error = "Error uploading file";
        }
    }
}

// Fetch all resources
$resources_query = "SELECT * FROM resources ORDER BY upload_date DESC";
$resources_result = mysqli_query($conn, $resources_query);
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
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($firstname ?? ''); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Upload Resources</p>
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
                    <a href="admin_upload_resources.php" class="flex items-center px-5 py-3 bg-slate-700/20 text-white">
                        <span>Upload Resources</span>
                    </a>
                </li>
                <li>
                    <a href="admin_leaderboard.php" class="flex items-center px-5 py-3 text-slate-300 hover:bg-slate-700/20 hover:text-white transition-all duration-200">
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

    <!-- Main Content -->
    <div class="ml-64 p-6">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl shadow-lg border border-white/5 p-6 hover:shadow-xl transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-white">
                    <i class="fas fa-folder-open mr-2 text-blue-400"></i> Resource Management
                </h2>
            </div>

            <!-- Breadcrumbs -->
            <div class="flex items-center text-sm text-slate-400 mb-6">
                <span class="hover:text-white cursor-pointer">My Drive</span>
                <span class="mx-2">/</span>
                <span class="text-blue-400">Shared Resources</span>
            </div>

            <!-- File Upload Section -->
            <div class="bg-slate-700/30 rounded-lg p-6 mb-6 border border-dashed border-slate-600 upload-dropzone"
                 id="uploadDropzone">
                <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?php if(isset($upload_success)): ?>
                        <div class="bg-green-600/20 text-green-400 p-3 rounded-md mb-4">
                            <?php echo $upload_success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($upload_error)): ?>
                        <div class="bg-red-600/20 text-red-400 p-3 rounded-md mb-4">
                            <?php echo $upload_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <i class="fas fa-cloud-upload-alt text-4xl text-blue-400 mb-2"></i>
                        <p class="text-slate-300 mb-4">Drag & drop files here or click to browse</p>
                        <input type="file" name="resource_file" id="resourceFile" class="hidden" required>
                        <button type="button" onclick="document.getElementById('resourceFile').click()" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-md text-white transition-all duration-200">
                            <i class="fas fa-upload mr-2"></i> Select File
                        </button>
                        <p class="text-xs text-slate-500 mt-2">Max file size: 50MB</p>
                        <p id="fileName" class="text-sm text-slate-300 mt-2 hidden"></p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Title *</label>
                            <input type="text" name="title" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Available To *</label>
                            <select name="available_to" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white">
                                <option value="all">All Users</option>
                                <option value="students">Students Only</option>
                                <option value="admins">Admins Only</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-1">Description</label>
                            <textarea name="description" rows="2" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white"></textarea>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full flex items-center justify-center p-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-md hover:from-blue-700 hover:to-blue-600 transition-all duration-200 shadow-md">
                            <i class="fas fa-upload mr-2"></i> Upload Resource
                        </button>
                    </div>
                </form>
            </div>

            <!-- Resource Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php while($resource = mysqli_fetch_assoc($resources_result)): ?>
                    <?php
                    // Get file icon based on type
                    $file_ext = pathinfo($resource['file_name'], PATHINFO_EXTENSION);
                    $icon = 'fa-file';
                    
                    if (in_array($file_ext, ['pdf'])) {
                        $icon = 'fa-file-pdf';
                    } elseif (in_array($file_ext, ['doc', 'docx'])) {
                        $icon = 'fa-file-word';
                    } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                        $icon = 'fa-file-excel';
                    } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                        $icon = 'fa-file-powerpoint';
                    } elseif (in_array($file_ext, ['zip', 'rar', '7z'])) {
                        $icon = 'fa-file-archive';
                    } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $icon = 'fa-file-image';
                    }
                    ?>
                    <div class="file-card bg-slate-700/50 rounded-lg p-4 border border-slate-600 hover:border-blue-500 transition-all duration-200">
                        <div class="flex flex-col items-center text-center">
                            <div class="file-icon bg-slate-600/50 rounded-full w-16 h-16 flex items-center justify-center mb-3">
                                <i class="fas <?php echo $icon; ?> text-2xl text-blue-400"></i>
                            </div>
                            <h4 class="font-medium text-sm mb-1 truncate w-full"><?php echo htmlspecialchars($resource['title']); ?></h4>
                            <p class="text-xs text-slate-400 mb-2"><?php echo formatSizeUnits($resource['file_size']); ?></p>
                            <div class="flex space-x-2">
                                <a href="download_resource.php?id=<?php echo $resource['id']; ?>" class="text-xs bg-blue-600/50 hover:bg-blue-600 px-2 py-1 rounded">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                                <a href="delete_resource.php?id=<?php echo $resource['id']; ?>" class="text-xs bg-red-600/50 hover:bg-red-600 px-2 py-1 rounded" onclick="return confirm('Are you sure you want to delete this resource?')">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if(mysqli_num_rows($resources_result) == 0): ?>
                <div class="text-center py-10 text-slate-400">
                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                    <p>No resources uploaded yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle file input change
        document.getElementById('resourceFile').addEventListener('change', function(e) {
            const fileNameElement = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileNameElement.textContent = this.files[0].name;
                fileNameElement.classList.remove('hidden');
            } else {
                fileNameElement.classList.add('hidden');
            }
        });

        // Drag and drop functionality
        const dropzone = document.getElementById('uploadDropzone');
        const fileInput = document.getElementById('resourceFile');

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
                const fileNameElement = document.getElementById('fileName');
                fileNameElement.textContent = e.dataTransfer.files[0].name;
                fileNameElement.classList.remove('hidden');
            }
        });
    </script>

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
</body>
</html>