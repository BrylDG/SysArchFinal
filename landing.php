<?php
require 'db.php'; // Database connection

$error = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['user'] ?? '');
    $password = trim($_POST['pass'] ?? '');

    // Check if the user is an admin
    if ($username === "admin") {
        $admin_password_hash = password_hash("admin123", PASSWORD_DEFAULT); // Hardcoded admin password
        if (password_verify($password, $admin_password_hash)) {
            $_SESSION['admin'] = [
                'username' => $username,
                'logged_in' => true
            ];

            // Ensure remaining_sessions is set to 30 for all users (admin-specific action)
            $stmt = $conn->prepare("UPDATE users SET remaining_sessions = 30");
            $stmt->execute();
            $stmt->close();

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid Username or Password!";
        }
    } else {
        // Check for regular users
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $db_username, $db_password, $role);
                $stmt->fetch();

                if (password_verify($password, $db_password)) {
                    $_SESSION['user_id'] = $id; 
                    $_SESSION['username'] = $db_username; 
                    $_SESSION['role'] = $role; 

                    if ($role === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid Username or Password!";
                }
            } else {
                $error = "Invalid Username or Password!";
            }
            $stmt->close();
        } else {
            $error = "Database error: Unable to prepare statement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS SIT Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background: url('login.png') no-repeat center center/cover;
            color: #fff;
            font-family: Arial, sans-serif;
            height: 100vh;
        }

        /* Animation for the welcome text */
        .welcome-text {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 1.5s ease-out forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <div class="navbar absolute top-0 right-0 p-5">
        <a href="login.php" class="text-white border px-4 py-2 rounded hover:bg-white hover:text-black">Login</a>
        <a href="register.php" class="text-white border px-4 py-2 rounded hover:bg-white hover:text-black">Signup</a>
    </div>

    <!-- Main Content -->
    <div class="container h-screen flex flex-col justify-center items-center text-center">
        <!-- Animated Welcome Text -->
        <h1 class="text-5xl font-bold mb-10 welcome-text">Welcome to CCS SIT-IN Monitoring System</h1>
        <div class="btn-container flex gap-5">
            <a href="login.php" class="px-6 py-3 border rounded-lg text-white hover:bg-white hover:text-black">Get Started</a>
            <a href="about.php" class="px-6 py-3 border rounded-lg text-white hover:bg-white hover:text-black">Learn More</a>
        </div>
    </div>

</body>

</html>