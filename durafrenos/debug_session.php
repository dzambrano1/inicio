<?php
// Start session
session_start();

// Output session information
echo "<h1>Session Debug Information</h1>";
echo "<p>Session ID: " . session_id() . "</p>";

echo "<h2>All Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cart Contents:</h2>";
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<pre>";
    print_r($_SESSION['cart']);
    echo "</pre>";
} else {
    echo "<p>Cart is empty or not set.</p>";
}

echo "<p><a href='catalog.php'>Return to Catalog</a> | <a href='cart.php'>Go to Cart</a></p>";
?> 