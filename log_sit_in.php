<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id']; // This should be the user's `idno`
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $pc_number = isset($_POST['pc_number']) ? $_POST['pc_number'] : null;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if student exists and has an active session
        $stmt = $conn->prepare("SELECT id FROM users WHERE idno = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if (!$student) {
            throw new Exception("Student not found");
        }

        // Check if student already has an active session
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sit_in_records WHERE student_id = ? AND end_time IS NULL");
        $stmt->bind_param("i", $student['id']);
        $stmt->execute();
        $stmt->bind_result($active_count);
        $stmt->fetch();
        $stmt->close();

        if ($active_count > 0) {
            throw new Exception("Student already has an active sit-in session");
        }

        // If PC was specified, verify it's available
        if (!empty($pc_number)) {
            $stmt = $conn->prepare("SELECT status FROM lab_pcs WHERE lab_name = ? AND pc_number = ? FOR UPDATE");
            $stmt->bind_param("si", $lab, $pc_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $pc_status = $result->fetch_assoc();

            if (!$pc_status || $pc_status['status'] !== 'Available') {
                throw new Exception("Selected PC is not available");
            }
        }

        // Insert sit-in record
        $stmt = $conn->prepare("INSERT INTO sit_in_records (student_id, purpose, lab, pc_number, start_time) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $student['id'], $purpose, $lab, $pc_number);
        $stmt->execute();

        // If PC was specified, mark it as used
        if (!empty($pc_number)) {
            $stmt = $conn->prepare("UPDATE lab_pcs SET status = 'Used' WHERE lab_name = ? AND pc_number = ?");
            $stmt->bind_param("si", $lab, $pc_number);
            $stmt->execute();
        }

        $conn->commit();
        
        $_SESSION['success_message'] = "Sit-in recorded successfully!";
        header("Location: manage_sitins.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: admin_dashboard.php");
        exit();
    }
}