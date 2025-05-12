<?php
session_start();
include 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno'];

// Fetch the remaining sessions for the logged-in user
$stmt = $conn->prepare("SELECT remaining_sessions FROM users WHERE idno = ?");
$stmt->bind_param("s", $idno);
$stmt->execute();
$stmt->bind_result($remaining_sessions);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remaining Sessions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            transition: background-color 0.3s, color 0.3s;
        }
        .dark-mode {
            background-color: #343a40;
            color: #f8f9fa;
        }
        .dark-mode .card {
            background-color: #495057;
            color: white;
        }
        .dark-mode .navbar {
            background-color: #212529 !important;
        }
        .card {
            max-width: 400px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-toggle {
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sit-in Monitoring</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="student_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn-toggle nav-link text-light" onclick="toggleDarkMode()">
                            <img id="theme-icon" src="https://cdn-icons-png.flaticon.com/512/1164/1164954.png" width="20" alt="Toggle Theme">
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-5">
        <div class="card p-4 text-center">
            <h3 class="mb-3">Remaining Sessions</h3>
            <p class="fs-5">You have <strong><?php echo $remaining_sessions; ?></strong> remaining sessions.</p>
            <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <!-- Dark Mode Script -->
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");

            // Change navbar theme
            const navbar = document.querySelector(".navbar");
            navbar.classList.toggle("navbar-dark");
            navbar.classList.toggle("bg-dark");

            // Change icon based on theme
            const icon = document.getElementById("theme-icon");
            if (document.body.classList.contains("dark-mode")) {
                icon.src = "https://cdn-icons-png.flaticon.com/512/6714/6714978.png"; // Moon icon
            } else {
                icon.src = "https://cdn-icons-png.flaticon.com/512/1164/1164954.png"; // Sun icon
            }

            // Save preference in localStorage
            localStorage.setItem("dark-mode", document.body.classList.contains("dark-mode"));
        }

        // Apply saved theme preference
        if (localStorage.getItem("dark-mode") === "true") {
            document.body.classList.add("dark-mode");
        }
    </script>

</body>
</html>
