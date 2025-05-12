<?php
session_start();
include 'db.php';

// Only admins can delete
if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_upload_resources.php");
    exit();
}

$resource_id = $_GET['id'];

// Get resource info
$query = "SELECT file_path FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $resource_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Resource not found");
}

$resource = $result->fetch_assoc();

// Delete file from server
if (file_exists($resource['file_path'])) {
    unlink($resource['file_path']);
}

// Delete from database
$delete_query = "DELETE FROM resources WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $resource_id);

if ($stmt->execute()) {
    header("Location: admin_upload_resources.php?delete_success=1");
} else {
    header("Location: admin_upload_resources.php?delete_error=1");
}
exit;
?>