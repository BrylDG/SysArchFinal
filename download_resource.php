<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

// Check if resource ID is provided
if (!isset($_GET['id'])) {
    header("Location: upload_resources.php");
    exit();
}

$resource_id = $_GET['id'];
$user_idno = $_SESSION['idno'];
$user_role = $_SESSION['role'] ?? 'student';

// Get resource info
$query = "SELECT r.*, u.idno FROM resources r 
          JOIN users u ON r.uploaded_by = u.idno 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Resource not found");
}

$resource = $result->fetch_assoc();

// Check if user has access
if ($resource['available_to'] === 'admins' && $user_role !== 'admin') {
    die("Access denied");
}

if ($resource['available_to'] === 'students' && $user_role === 'admin') {
    // Admins can access student resources
}

// Check if file exists
if (!file_exists($resource['file_path'])) {
    die("File not found");
}

// Try to record download in database (optional - won't fail if table doesn't exist)
try {
    $download_query = "INSERT INTO resource_downloads (resource_id, downloaded_by, download_date) 
                      VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($download_query);
    if ($stmt) {
        $stmt->bind_param("is", $resource_id, $user_idno);
        $stmt->execute();
    }
} catch (Exception $e) {
    // Silently fail if table doesn't exist
    error_log("Could not record download: " . $e->getMessage());
}

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($resource['file_name']).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($resource['file_path']));
flush(); // Flush system output buffer
readfile($resource['file_path']);
exit;
?>