<?php
session_start();
include 'db.php';

// Ensure only admins can access
if (!isset($_SESSION["idno"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST["student_id"]);

    $stmt = $conn->prepare("SELECT idno, firstname, lastname, remaining_sessions FROM users WHERE idno = ? AND role = 'student'");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($idno, $firstname, $lastname, $remaining_sessions);
        $stmt->fetch();
        $fullname = $firstname . ' ' . $lastname;
        echo "<h3>Student Found: $fullname</h3>";
        echo "<p>Remaining Sessions: $remaining_sessions</p>";
        echo "
            <form action='log_sit_in.php' method='POST'>
                <input type='hidden' name='student_id' value='$idno'>
                <label>Purpose:</label>
                <input type='text' name='purpose' required>
                <label>Lab:</label>
                <input type='text' name='lab' required>
                <button type='submit'>Log Sit-in</button>
            </form>";
    } else {
        echo "Student not found.";
    }
    
    $stmt->close();
}
?>

<form method="POST">
    <label>Enter Student ID:</label>
    <input type="number" name="student_id" required>
    <button type="submit">Search</button>
</form>
