<?php
// Admin credentials configuration
define('ADMIN_EMAIL', 'admin@durafrenos.com');
define('ADMIN_PASSWORD', 'Durafrenos2024!'); // Change this to a strong password

// Function to verify admin credentials
function verifyAdminCredentials($email, $password) {
    return $email === ADMIN_EMAIL && $password === ADMIN_PASSWORD;
}

// Set this to false when you're done creating admin users!
$allow_admin_creation = true;

if (!$allow_admin_creation) {
    die("Admin creation is disabled for security reasons.");
}

require_once "./conexion.php";  // Your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $fullName = $_POST["fullName"] ?? "";
    $email = $_POST["email"] ?? "";
    
    // Validate input
    if (empty($username) || empty($password) || empty($fullName) || empty($email)) {
        $error = "All fields are required.";
    } else {
        // Create connection
        $conn = mysqli_connect($servername, $username, $password, $dbname);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Username already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin user
            $insert_sql = "INSERT INTO users (username, password, fullName, email, role) VALUES (?, ?, ?, ?, 'admin')";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $hashed_password, $fullName, $email);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = "Admin user created successfully!";
            } else {
                $error = "Error creating admin user: " . mysqli_error($conn);
            }
        }
        
        mysqli_close($conn);
    }
}
?> 