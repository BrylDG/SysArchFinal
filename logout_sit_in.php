<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sit_in_id = $_POST['sit_in_id'];

    $stmt = $conn->prepare("UPDATE sit_in_records SET end_time = NOW() WHERE id = ?");
    $stmt->bind_param("i", $sit_in_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Sit-out recorded"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to record sit-out"]);
    }
}
?>
