<?php
session_destroy(); // Destroy session
header("Location: login.php"); // Redirect to login page
exit();
?>