<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = trim($_POST["idno"]);
    $course = trim($_POST["course"]);
    $yearlevel = trim($_POST["yearlevel"]);
    $email = trim($_POST["email"]);
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $middlename = trim($_POST["middlename"]);
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = "student"; // Default role
    $remaining_sessions = 30; // Grant 30 sessions upon registration

    // Basic Input Validation
    if (empty($idno) || empty($course) || empty($yearlevel) || empty($email) || 
        empty($firstname) || empty($lastname) || empty($username) || empty($password)) {
        echo json_encode(["success" => false, "error" => "All fields are required"]);
        exit();
    }

    // Email Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "error" => "Invalid email format"]);
        exit();
    }

    // Check if username already exists
    $checkUser = "SELECT * FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $checkUser);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["success" => false, "error" => "Username already taken"]);
        exit();
    }

    // Check if ID number already exists
    $checkIdno = "SELECT * FROM users WHERE idno = ?";
    $stmt = mysqli_prepare($conn, $checkIdno);
    mysqli_stmt_bind_param($stmt, "s", $idno);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["success" => false, "error" => "ID number already taken"]);
        exit();
    }

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $checkEmail);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(["success" => false, "error" => "Email already taken"]);
        exit();
    }

    // Secure Password Hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert User into Database with 30 sessions
    $sql = "INSERT INTO users (idno, course, yearlevel, email, firstname, lastname, middlename, username, password, role, remaining_sessions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssi", $idno, $course, $yearlevel, $email, 
                            $firstname, $lastname, $middlename, $username, $hashed_password, $role, $remaining_sessions);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Registration failed"]);
    }
}
?>
