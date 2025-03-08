<?php
session_start();

// Output all session data
echo '<h1>SESSION DATA VERIFICATION</h1>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

// Check if test value exists
if (isset($_SESSION['test_value'])) {
    echo '<p style="color:green">Session is working correctly! Test value: ' . $_SESSION['test_value'] . '</p>';
} else {
    echo '<p style="color:red">Session is NOT working correctly! Test value is missing.</p>';
}

// Add a link to go back 
echo '<p><a href="test_session.php">Go back to test page</a></p>';
?>