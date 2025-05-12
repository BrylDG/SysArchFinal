<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idno'])) {
    echo json_encode(['success' => false]);
    exit();
}

$idno = $_SESSION['idno'];

// Mark all notifications as read for this user
$query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>