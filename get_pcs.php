<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Verify it's an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Verify user is logged in
if (!isset($_SESSION['idno'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit();
}

// Validate lab parameter
if (empty($_GET['lab'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No lab specified']);
    exit();
}

$allowed_labs = ['Lab 517', 'Lab 524', 'Lab 526', 'Lab 528', 'Lab 530', 'Lab 542', 'Lab 544'];
$lab = trim($_GET['lab']);

if (!in_array($lab, $allowed_labs)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid lab specified']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT pc_number, status FROM lab_pcs WHERE lab_name = ? ORDER BY pc_number");
    $stmt->bind_param("s", $lab);
    $stmt->execute();
    $result = $stmt->get_result();

    $pcs = [];
    while ($row = $result->fetch_assoc()) {
        $pcs[] = [
            'pc_number' => $row['pc_number'],
            'status' => $row['status'],
            'status_class' => $row['status'] === 'Available' ? 'status-available' : 
                            ($row['status'] === 'Used' ? 'status-used' : 'status-maintenance'),
            'status_text_class' => $row['status'] === 'Available' ? 'text-green-400' : 
                                 ($row['status'] === 'Used' ? 'text-red-400' : 'text-yellow-400')
        ];
    }

    echo json_encode([
        'success' => true,
        'pcs' => $pcs,
        'count' => count($pcs),
        'available_count' => array_reduce($pcs, function($carry, $pc) {
            return $carry + ($pc['status'] === 'Available' ? 1 : 0);
        }, 0)
    ]);
    
} catch (Exception $e) {
    error_log("PC loading error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
}