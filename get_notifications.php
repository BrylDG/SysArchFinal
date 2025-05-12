<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idno'])) {
    echo json_encode([]);
    exit();
}

$idno = $_SESSION['idno'];

// Fetch notifications for this user
$query = "SELECT id, message, is_read, created_at FROM notifications 
          WHERE user_id = ? 
          ORDER BY created_at DESC 
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $idno);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => date('M j, Y g:i A', strtotime($row['created_at']))
    ];
}

echo json_encode($notifications);
?>