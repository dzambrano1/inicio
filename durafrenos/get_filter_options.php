<?php
// Include database connection 
require_once './conexion.php';

// Set header to return JSON
header('Content-Type: application/json');

// Connect to the database
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die(json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]));
}

// Get filter type from request
$type = $_GET['type'] ?? '';
$response = [];

// Based on filter type, build the query
if ($type === 'make') {
    // Get unique makes
    $query = "SELECT DISTINCT make FROM products WHERE make IS NOT NULL";
    
    // Add category filter if provided
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
    }
    
    $query .= " ORDER BY make";
    
} elseif ($type === 'model') {
    // Get unique models
    $query = "SELECT DISTINCT model FROM products WHERE model IS NOT NULL";
    
    // Add make filter if provided
    if (isset($_GET['make']) && !empty($_GET['make'])) {
        $make = mysqli_real_escape_string($conn, $_GET['make']);
        $query .= " AND make = '$make'";
    }
    
    // Add category filter if provided
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
    }
    
    $query .= " ORDER BY model";
    
} elseif ($type === 'year') {
    // Get unique years
    $query = "SELECT DISTINCT year FROM products WHERE year IS NOT NULL";
    
    // Add category filter if provided
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
    }
    
    // Add make filter if provided
    if (isset($_GET['make']) && !empty($_GET['make'])) {
        $make = mysqli_real_escape_string($conn, $_GET['make']);
        $query .= " AND make = '$make'";
    }
    
    // Add model filter if provided
    if (isset($_GET['model']) && !empty($_GET['model'])) {
        $model = mysqli_real_escape_string($conn, $_GET['model']);
        $query .= " AND model = '$model'";
    }
    
    $query .= " ORDER BY year DESC";
    
} else {
    // If invalid type is requested
    echo json_encode(['error' => 'Invalid filter type']);
    exit;
}

// Execute the query
$result = mysqli_query($conn, $query);

if ($result) {
    // Build the response array
    while ($row = mysqli_fetch_assoc($result)) {
        // Only add non-empty values
        if (!empty($row[$type])) {
            $response[] = $row[$type];
        }
    }
} else {
    $response = ['error' => 'Query failed: ' . mysqli_error($conn)];
}

// Return the result as JSON
echo json_encode($response);

// Close the database connection
mysqli_close($conn);
?>