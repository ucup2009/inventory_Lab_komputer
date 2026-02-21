<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is petugas
function isPetugas() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'petugas';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Get user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Get user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get username
function getUsername() {
    return $_SESSION['username'] ?? null;
}
?>




