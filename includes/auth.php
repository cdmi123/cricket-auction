<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is team
function isTeam() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'team';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Redirect if not team
function requireTeam() {
    requireLogin();
    if (!isTeam()) {
        header('Location: index.php');
        exit;
    }
}

// Get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}
?>