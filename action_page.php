<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["uname"];
    $password = $_POST["psw"];

    $sql = "SELECT idno, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($idno, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_start();
            $_SESSION["idno"] = $idno;
            $_SESSION["username"] = $username;
            echo "<script type='text/javascript'>
                    alert('Login successful! Redirecting...');
                    window.location.href = 'dashboard.php';
                  </script>";
        } else {
            echo "<script type='text/javascript'>
                    alert('Password is incorrect.');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script type='text/javascript'>
                alert('Username not found.');
                window.history.back();
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>
