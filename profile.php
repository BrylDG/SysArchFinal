<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['idno'])) {
    header("Location: login.php");
    exit();
}

$idno = $_SESSION['idno']; // Use 'idno' as set in the session during login
$query = mysqli_query($conn, "SELECT idno, course, yearlevel, email, firstname, lastname, middlename, username, profile_picture, password FROM users WHERE idno='$idno'") or die(mysqli_error($conn));
$row = mysqli_fetch_array($query);

if (isset($_POST['submit'])) {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $middlename = mysqli_real_escape_string($conn, $_POST['middlename']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    $profile_picture = $row['profile_picture']; // Keep the existing profile picture by default

    // Handle Profile Picture Upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB
        $upload_dir = "uploads/";

        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file type and size
        if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
            $new_file_name = $idno . "_" . time() . "." . $file_ext; // Ensure unique name
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_picture = $new_file_name; // Set new profile picture
            } else {
                echo "<script>alert('Error uploading the file. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type or file too large (max 2MB).');</script>";
        }
    }

    // Update user information
    $update_query = "UPDATE users SET firstname = '$firstname', lastname = '$lastname', middlename = '$middlename', profile_picture = '$profile_picture' WHERE idno = '$idno'";
    mysqli_query($conn, $update_query) or die(mysqli_error($conn));

    // Change Password if required
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_new_password)) {
        if ($new_password === $confirm_new_password) {
            if (password_verify($current_password, $row['password'])) {
                $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                mysqli_query($conn, "UPDATE users SET password = '$hashed_new_password' WHERE idno = '$idno'") or die(mysqli_error($conn));
                echo "<script>alert('Password updated successfully.');</script>";
            } else {
                echo "<script>alert('Current password is incorrect.');</script>";
            }
        } else {
            echo "<script>alert('New passwords do not match.');</script>";
        }
    }

    echo "<script>alert('updated successfully.'); window.location.href = 'profile.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/profile.css">
    <script>
        function togglePasswordVisibility(inputId, toggleId) {
            var input = document.getElementById(inputId);
            var toggle = document.getElementById(toggleId);
            if (input.type === "password") {
                input.type = "text";
                toggle.classList.remove("fa-eye");
                toggle.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                toggle.classList.remove("fa-eye-slash");
                toggle.classList.add("fa-eye");
            }
        }

        function confirmLogout(event) {
            event.preventDefault();
            var confirmation = confirm("Are you sure you want to log out?");
            if (confirmation) {
                window.location.href = 'login.php';
            }
        }

        function toggleEditSection() {
            var editSection = document.getElementById('edit-section');
            if (editSection.style.display === 'none') {
                editSection.style.display = 'block';
            } else {
                editSection.style.display = 'none';
            }
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="navbar">
        <a href="student_dashboard.php">
            <div class="logo">
                <img src="images/CCS.png" alt="Logo">
            </div>
        </a>
        <ul>
            <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
            <li><a href="announcement.php"><i class="fa-solid fa-bullhorn"></i> View Announcement</a></li>
            <li><a href="#"><i class="fa-solid fa-calendar-check"></i> Reservation</a></li>
            <li><a href="labrules.php"><i class="fa-solid fa-book"></i> Lab Rules</a></li>
            <li><a href="sit_in_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Sit-in History</a></li>
            <li><a href="remaining_sessions.php"><i class="fa-solid fa-hourglass-half"></i> Remaining Session</a></li>
            <li><a href="logout.php" onclick="confirmLogout(event)"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <div class="profile-container">
        <div class="profile-display-field">
            <h3>USER PROFILE</h3>
            <div class="profile-picture-container">
                <?php if (!empty($row['profile_picture'])): ?>
                    <img src="uploads/<?php echo $row['profile_picture']; ?>" alt="Profile Picture" class="profile-picture">
                <?php else: ?>
                    <img src="images/default-profile.png" alt="Default Profile Picture" class="profile-picture">
                <?php endif; ?>
                <div class="profile-fullname">
                    <?php echo $row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']; ?>
                </div>
            </div>
            <div class="form-column">
                <div class="form-group">
                    <label>ID Number</label>
                    <p><?php echo $row['idno']; ?></p>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <p><?php echo $row['course']; ?></p>
                </div>
                <div class="form-group">
                    <label>Year Level</label>
                    <p><?php echo $row['yearlevel']; ?></p>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <p><?php echo $row['email']; ?></p>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <p><?php echo $row['username']; ?></p>
                </div>
            </div>
            <center><button class="btn btn-primary" onclick="toggleEditSection()">Edit Information</button></center>
        </div>

        <div class="profile-input-field" id="edit-section" style="display: none;">
            <h3>Edit Your Information</h3>
            <form method="post" action="profile.php" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <input type="file" class="form-control" name="profile_picture" accept="image/*" />
                    </div>
                    <div class="form-group">
                        <label>ID Number</label>
                        <input type="text" class="form-control" name="idno" placeholder="Enter your ID Number" value="<?php echo $row['idno']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <input type="text" class="form-control" name="course" placeholder="Enter your Course" value="<?php echo $row['course']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>Year Level</label>
                        <input type="text" class="form-control" name="yearlevel" placeholder="Enter your Year Level" value="<?php echo $row['yearlevel']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter your Email" value="<?php echo $row['email']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-control" name="firstname" placeholder="Enter your First Name" value="<?php echo $row['firstname']; ?>" required />
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-control" name="lastname" placeholder="Enter your Last Name" value="<?php echo $row['lastname']; ?>" required />
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" class="form-control" name="middlename" placeholder="Enter your Middle Name" value="<?php echo $row['middlename']; ?>" />
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Enter your Username" value="<?php echo $row['username']; ?>" readonly />
                    </div>
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter your Current Password" />
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePasswordVisibility('current_password', 'toggleCurrentPassword')">
                                    <i id="toggleCurrentPassword" class="fa fa-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter your New Password" />
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePasswordVisibility('new_password', 'toggleNewPassword')">
                                    <i id="toggleNewPassword" class="fa fa-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm your New Password" />
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePasswordVisibility('confirm_new_password', 'toggleConfirmNewPassword')">
                                    <i id="toggleConfirmNewPassword" class="fa fa-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <center><input type="submit" name="submit" class="btn btn-primary"><br><br></center>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
