<?php
session_start();

// Set session timeout to 30 minutes
$session_timeout = 1800; // 30 minutes in seconds

// Check if user is logged in and session is still valid
function isLoggedIn() {
    global $session_timeout;
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check if session has expired
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        // Session has expired
        session_unset();
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ./login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ./home.php');
        exit;
    }
}

// Function to check if user has access to a specific page
function checkPageAccess($page) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Admin has access to all pages
    if (isAdmin()) {
        return true;
    }
    
    // Define access rules for different pages
    $customer_pages = ['home.php', 'orders.php', 'payments.php', 'cart.php'];
    $admin_pages = ['customer_registration.php', 'home.php', 'orders.php', 'payments.php','process_payments.php', 'create_products.php', 'edit_products.php', 'cart.php'];
    
    if (in_array($page, $customer_pages)) {
        return true;
    }
    
    if (in_array($page, $admin_pages)) {
        return false;
    }
    
    // Default deny access for unknown pages
    return false;
}

// Function to get current user information
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['customer_id'] ?? null,
        'name' => $_SESSION['fullName'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}
?> 