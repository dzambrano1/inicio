<?php
session_start();

// Set a test value
$_SESSION['test_value'] = 'This is a test at ' . date('Y-m-d H:i:s');

// Output all session data
echo '<h1>SESSION DATA</h1>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

// Add a link to verify persistence
echo '<p><a href="test_session_verify.php">Click here to verify session persistence</a></p>';
?>