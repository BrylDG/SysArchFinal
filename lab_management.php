<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION["idno"];
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($firstname, $lastname);
$stmt->fetch();
$stmt->close();

// Handle PC status update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_status"])) {
        $pc_id = $_POST["pc_id"];
        $new_status = $_POST["status"];
        $lab_name = $_POST["lab_name"];
        
        $stmt = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE id = ? AND lab_name = ?");
        $stmt->bind_param("sis", $new_status, $pc_id, $lab_name);
        $stmt->execute();
        $stmt->close();
        
        // Log the change
        $action = "Updated PC $pc_id in $lab_name to status: $new_status";
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $idno, $action);
        $log_stmt->execute();
        $log_stmt->close();
    } 
    // Handle bulk update
    elseif (isset($_POST["bulk_update"])) {
        $lab_name = $_POST["lab_name"];
        $bulk_status = $_POST["bulk_status"];
        
        $stmt = $conn->prepare("UPDATE lab_pcs SET status = ? WHERE lab_name = ?");
        $stmt->bind_param("ss", $bulk_status, $lab_name);
        $stmt->execute();
        $stmt->close();
        
        // Log the change
        $action = "Updated ALL PCs in $lab_name to status: $bulk_status";
        $log_stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("ss", $idno, $action);
        $log_stmt->execute();
        $log_stmt->close();
        
        $_SESSION['bulk_message'] = "All PCs in $lab_name have been marked as $bulk_status";
    }
}

// Get all labs
$labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];

// Initialize PC data array
$lab_pcs = [];

foreach ($labs as $lab) {
    // Check if PCs exist for this lab
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lab_pcs WHERE lab_name = ?");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $stmt->bind_result($pc_count);
    $stmt->fetch();
    $stmt->close();
    
    // If no PCs exist, initialize them
    if ($pc_count == 0) {
        for ($i = 1; $i <= 48; $i++) {
            $insert_stmt = $conn->prepare("INSERT INTO lab_pcs (lab_name, pc_number, status) VALUES (?, ?, 'Available')");
            $insert_stmt->bind_param("si", $lab, $i);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
    }
    
    // Get all PCs for this lab
    $stmt = $conn->prepare("SELECT id, pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();
    $lab_pcs[$lab] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Lab Management</title>
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
                },
            },
        }
    </script>
    <style>
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
                <h2 class="text-xl font-semibold text-white">Admin <?php echo htmlspecialchars($firstname); ?></h2>
            </div>
            <p class="text-sm text-slate-400 mt-2">Lab Management</p>
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
                    <a href="lab_management.php" class="flex items-center px-5 py-3 bg-blue-600/20 text-white transition-all duration-200">
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
                <h2 class="text-2xl font-semibold text-white">Computer Lab Management</h2>
                <div class="flex items-center space-x-2">
                    <span class="h-3 w-3 rounded-full bg-green-500"></span>
                    <span class="text-sm text-slate-300">Available</span>
                    <span class="h-3 w-3 rounded-full bg-red-500 ml-2"></span>
                    <span class="text-sm text-slate-300">Used</span>
                    <span class="h-3 w-3 rounded-full bg-yellow-500 ml-2"></span>
                    <span class="text-sm text-slate-300">Maintenance</span>
                </div>
            </div>

            <?php if (isset($_SESSION['bulk_message'])): ?>
                <div class="bg-blue-600/20 text-blue-400 p-3 rounded-md mb-4">
                    <?php echo $_SESSION['bulk_message']; unset($_SESSION['bulk_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Lab Tabs -->
            <div class="mb-6 border-b border-white/10">
                <ul class="flex flex-wrap -mb-px" id="labTabs" role="tablist">
                    <?php foreach ($labs as $index => $lab): ?>
                        <li class="mr-2" role="presentation">
                            <button 
                                class="inline-block p-3 border-b-2 rounded-t-lg <?php echo $index === 0 ? 'border-blue-500 text-blue-500' : 'border-transparent hover:text-slate-300 hover:border-slate-300'; ?>" 
                                id="<?php echo str_replace(' ', '-', strtolower($lab)); ?>-tab" 
                                data-tabs-target="#<?php echo str_replace(' ', '-', strtolower($lab)); ?>" 
                                type="button" 
                                role="tab" 
                                aria-controls="<?php echo str_replace(' ', '-', strtolower($lab)); ?>" 
                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            >
                                <?php echo $lab; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Lab Content -->
            <div id="labContent">
                <?php foreach ($labs as $index => $lab): ?>
                    <div class="<?php echo $index === 0 ? 'block' : 'hidden'; ?> p-4 rounded-lg bg-slate-800/30" 
                         id="<?php echo str_replace(' ', '-', strtolower($lab)); ?>" 
                         role="tabpanel" 
                         aria-labelledby="<?php echo str_replace(' ', '-', strtolower($lab)); ?>-tab">
                        
                        <div class="mb-4 flex justify-between items-center">
                            <h3 class="text-xl font-semibold"><?php echo $lab; ?></h3>
                            <div class="flex items-center space-x-4">
                                <div class="text-sm">
                                    <span class="font-medium">Total PCs:</span> 
                                    <span class="text-slate-300">48</span>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium">Available:</span> 
                                    <span class="text-green-400">
                                        <?php 
                                            $available = array_filter($lab_pcs[$lab], function($pc) { 
                                                return $pc['status'] === 'Available'; 
                                            });
                                            echo count($available);
                                        ?>
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium">In Use:</span> 
                                    <span class="text-red-400">
                                        <?php 
                                            $used = array_filter($lab_pcs[$lab], function($pc) { 
                                                return $pc['status'] === 'Used'; 
                                            });
                                            echo count($used);
                                        ?>
                                    </span>
                                </div>
                                <div class="text-sm">
                                    <span class="font-medium">Maintenance:</span> 
                                    <span class="text-yellow-400">
                                        <?php 
                                            $maintenance = array_filter($lab_pcs[$lab], function($pc) { 
                                                return $pc['status'] === 'Maintenance'; 
                                            });
                                            echo count($maintenance);
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Update Form -->
                        <form method="POST" class="mb-6 bg-slate-700/30 p-4 rounded-lg border border-white/10">
                            <input type="hidden" name="lab_name" value="<?php echo $lab; ?>">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Update All PCs in <?php echo $lab; ?></label>
                                    <select name="bulk_status" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required>
                                        <option value="Available">Available</option>
                                        <option value="Used">Used</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" name="bulk_update" class="mt-6 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-all duration-200">
                                        Update All
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- PC Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <?php foreach ($lab_pcs[$lab] as $pc): ?>
                                <div class="pc-card relative p-3 rounded-lg border cursor-pointer 
                                    <?php 
                                        if ($pc['status'] === 'Available') echo 'status-available';
                                        elseif ($pc['status'] === 'Used') echo 'status-used';
                                        else echo 'status-maintenance';
                                    ?>"
                                    onclick="openPcModal('<?php echo $pc['id']; ?>', '<?php echo $lab; ?>', '<?php echo $pc['pc_number']; ?>', '<?php echo $pc['status']; ?>')">
                                    
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-desktop text-3xl mb-2 
                                            <?php 
                                                if ($pc['status'] === 'Available') echo 'text-green-400';
                                                elseif ($pc['status'] === 'Used') echo 'text-red-400';
                                                else echo 'text-yellow-400';
                                            ?>">
                                        </i>
                                        <div class="text-lg font-semibold mb-1">PC <?php echo $pc['pc_number']; ?></div>
                                        <div class="text-xs px-2 py-1 rounded-full 
                                            <?php 
                                                if ($pc['status'] === 'Available') echo 'bg-green-900/50 text-green-400';
                                                elseif ($pc['status'] === 'Used') echo 'bg-red-900/50 text-red-400';
                                                else echo 'bg-yellow-900/50 text-yellow-400';
                                            ?>">
                                            <?php echo $pc['status']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- PC Status Modal -->
    <div id="pcModal" class="fixed inset-0 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-slate-800/90 backdrop-blur-md rounded-xl shadow-2xl border border-white/10 p-6 w-full max-w-md transform transition-all duration-300 scale-95 opacity-0">
            <h3 class="text-xl font-semibold mb-4" id="pcModalTitle">Update PC Status</h3>
            
            <form id="pcStatusForm" method="POST">
                <input type="hidden" name="pc_id" id="modalPcId">
                <input type="hidden" name="lab_name" id="modalLabName">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-1">PC Number</label>
                    <input type="text" id="modalPcNumber" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" readonly>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-1">Current Status</label>
                    <input type="text" id="modalCurrentStatus" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white" readonly>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-1">Update Status</label>
                    <select name="status" id="modalStatusSelect" class="w-full p-2 bg-slate-700/50 border border-slate-600 rounded-md text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" required>
                        <option value="Available">Available</option>
                        <option value="Used">Used</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closePcModal()" class="px-4 py-2 bg-slate-600/50 hover:bg-slate-600 text-white rounded-md transition-all duration-200 border border-white/10">
                        Cancel
                    </button>
                    <button type="submit" name="update_status" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-all duration-200">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('[data-tabs-target]');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const target = document.querySelector(this.dataset.tabsTarget);
                    
                    // Hide all tab content
                    document.querySelectorAll('#labContent > div').forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    // Show selected tab content
                    target.classList.remove('hidden');
                    
                    // Update active tab styling
                    tabs.forEach(t => {
                        t.classList.remove('border-blue-500', 'text-blue-500');
                        t.classList.add('border-transparent');
                    });
                    
                    this.classList.add('border-blue-500', 'text-blue-500');
                    this.classList.remove('border-transparent');
                });
            });
        });

        // PC Modal functions
        function openPcModal(pcId, labName, pcNumber, currentStatus) {
            document.getElementById('modalPcId').value = pcId;
            document.getElementById('modalLabName').value = labName;
            document.getElementById('modalPcNumber').value = 'PC ' + pcNumber;
            document.getElementById('modalCurrentStatus').value = currentStatus;
            document.getElementById('modalStatusSelect').value = currentStatus;
            
            document.getElementById('pcModalTitle').textContent = labName + ' - PC ' + pcNumber;
            
            document.getElementById('pcModal').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('pcModal').querySelector('div').classList.remove('scale-95', 'opacity-0');
                document.getElementById('pcModal').querySelector('div').classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closePcModal() {
            document.getElementById('pcModal').querySelector('div').classList.remove('scale-100', 'opacity-100');
            document.getElementById('pcModal').querySelector('div').classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                document.getElementById('pcModal').classList.add('hidden');
            }, 300);
        }

        // Close modal when clicking outside
        document.getElementById('pcModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePcModal();
            }
        });
    </script>
</body>
</html>