<?php
require 'db.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idno = $_POST['idno'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $middlename = $_POST['middlename'] ?? '';
    $course = $_POST['course'] ?? '';
    $yearlevel = $_POST['yearlevel'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert data into the database
    $sql = "INSERT INTO users (idno, lastname, firstname, middlename, course, yearlevel, email, username, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssssss", $idno, $lastname, $firstname, $middlename, $course, $yearlevel, $email, $username, $hashed_password);

        if ($stmt->execute()) {
            $showModal = true;
            $success = "Registration successful!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: Unable to prepare statement.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-[#F1EFEC] min-h-screen flex items-center justify-center p-4" style="overflow-y: auto;">

    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-lg min-h-[10vh] relative">
        <div class="top-div display flex flex-row items-center">
            <img src="images/CCS.png" alt="CCS Logo" class="w-24 h-24 mb-4" style="width: 170px; height: 170px;">
            <h2 class="text-3xl font-semibold text-black mb-6 text-left">REGISTRATION FORM</h2>
        </div>

        <?php if (!empty($error)): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" class="w-full space-y-6">
            <div>
                <label for="idno" class="text-[#123458] font-medium">ID Number:</label>
                <input type="text" id="idno" name="idno" required class="mt-2 p-3 border border-[#D4C9BE] rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-[#123458]">
            </div>

            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="lastname" class="block text-sm font-medium text-[#123458]">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" class="mt-1 p-2 w-full border border-[#D4C9BE] rounded" required>
                </div>
                <div class="flex-1">
                    <label for="firstname" class="block text-sm font-medium text-[#123458]">First Name:</label>
                    <input type="text" id="firstname" name="firstname" class="mt-1 p-2 w-full border border-[#D4C9BE] rounded" required>
                </div>
                <div class="flex-1">
                    <label for="middlename" class="block text-sm font-medium text-[#123458]">Middle Name:</label>
                    <input type="text" id="middlename" name="middlename" class="mt-1 p-2 w-full border border-[#D4C9BE] rounded">
                </div>
            </div>

            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="course" class="block text-sm font-medium text-[#123458]">Course:</label>
                    <select id="course" name="course" class="mt-1 p-2 w-full border border-[#D4C9BE] rounded" required>
                        <option value="">Select Course</option>
                        <option value="Bachelor of Science in Information Technology">BSIT</option>
                        <option value="Bachelor of Science in Computer Science">BSCS</option>
                        <option value="Bachelor of Science in Computer Engineering">BSCpE</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label for="yearlevel" class="block text-sm font-medium text-[#123458]">Year Level:</label>
                    <select id="yearlevel" name="yearlevel" class="mt-1 p-2 w-full border border-[#D4C9BE] rounded" required>
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="email" class="text-[#123458] font-medium">Email:</label>
                <input type="email" id="email" name="email" required class="mt-2 p-3 border border-[#D4C9BE] rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-[#123458]">
            </div>

            <div class="flex space-x-4">
                <div class="flex-1">
                    <label for="username" class="block text-sm font-medium text-[#123458]">Username:</label>
                    <input type="text" id="username" name="username" required class="mt-1 p-2 w-full border border-[#D4C9BE] rounded">
                </div>
                <div class="flex-1">
                    <label for="password" class="block text-sm font-medium text-[#123458]">Password:</label>
                    <input type="password" id="password" name="password" required class="mt-1 p-2 w-full border border-[#D4C9BE] rounded">
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-[#123458] text-white rounded-lg font-semibold hover:bg-[#030303] transition-colors">Register</button>
        </form>

        <p class="text-[#123458] mt-4 text-center">Already have an account? <a href="login.php" class="text-[#030303] hover:underline">Log in here</a>.</p>
    </div>

    <?php if (isset($showModal) && $showModal): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 animate-fade-in">
            <div class="text-center">
                <div class="topdiv flex flex-row justify-center mb-4">
                    <div class="mr-3 flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-[#123458] mt-3">Registration Successful!</h3>
                </div>
                <div class="mt-4">
                    <a href="login.php" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-[#123458] border border-transparent rounded-md hover:bg-[#030303] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#123458]">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('bg-opacity-50')) {
                window.location.href = 'login.php';
            }
        });
    </script>
    <?php endif; ?>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>

</html>
