<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['idno']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['student_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$student_id = $_POST['student_id'];
$action = $_POST['action'];

// Start transaction
$conn->begin_transaction();

try {
    // Get current points
    $query = "SELECT points, remaining_sessions FROM users WHERE idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    $current_points = $student['points'];
    $current_sessions = $student['remaining_sessions'];
    $new_points = $current_points;
    $new_sessions = $current_sessions;

    // Adjust points
    if ($action === 'add') {
        $new_points = $current_points + 1;
    } elseif ($action === 'remove' && $current_points > 0) {
        $new_points = $current_points - 1;
    }

    // Check if points reached 3 to convert to session
    if ($new_points >= 3) {
        $sessions_to_add = floor($new_points / 3);
        $remaining_points = $new_points % 3;
        
        $new_sessions = $current_sessions + $sessions_to_add;
        $new_points = $remaining_points;
    }

    // Update user
    $update_query = "UPDATE users 
                    SET points = ?, 
                        remaining_sessions = ?
                    WHERE idno = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iis", $new_points, $new_sessions, $student_id);
    $stmt->execute();

    // Log the adjustment
    $log_query = "INSERT INTO rewards_log (user_id, points_earned, action) 
                 VALUES (?, ?, ?)";
    $stmt = $conn->prepare($log_query);
    $points_earned = ($action === 'add' ? 1 : -1);
    $log_action = 'admin_' . $action;
    $stmt->bind_param("sis", $student_id, $points_earned, $log_action);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Points adjusted successfully. " . 
                     ($new_sessions > $current_sessions ? 
                     'Converted to ' . ($new_sessions - $current_sessions) . ' session(s)!' : '')
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>