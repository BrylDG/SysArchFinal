<?php
$admin_password = "admin123"; // Change this to your actual password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

echo "New Hashed Password: " . $hashed_password;
?>
INSERT INTO users (idno, username, email, password, role) 
VALUES ('ADMIN001', 'admin', 'admin@example.com', '$2y$10$wJu01nHFOeffP6vcOdF/r.R0iJ0TeEyK3.wkoY8BakPQDmIUBf/nq', 'admin');