<?php
require_once 'config/database.php';

// Reset admin password
$username = 'admin';
$password = 'admin123';
$role = 'admin';

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    // Update existing admin
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashedPassword, $username]);
    echo "Admin password updated successfully!";
} else {
    // Create new admin
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $role]);
    echo "Admin user created successfully!";
}

// Verify the password
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    echo "<br>Password verification successful!";
} else {
    echo "<br>Password verification failed!";
}
?>