<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Set character set to handle special characters correctly
$conn->set_charset("utf8mb4");

// Check if ID is provided
if (!isset($_GET['id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'No ID provided'
    ]));
}

// Sanitize the input
$id = $conn->real_escape_string($_GET['id']);

// Prepare and execute query
$sql = "SELECT * FROM ganado WHERE id = '$id'";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        // Fetch the data
        $animal = $result->fetch_assoc();
        
        // Convert any NULL values to empty strings to avoid JSON issues
        foreach ($animal as $key => $value) {
            if ($value === null) {
                $animal[$key] = '';
            }
        }

        // Format dates to Y-m-d format for HTML date inputs
        $dateFields = [
            'nacimiento', 'peso_fecha', 'racion_fecha', 'vacuna_fecha', 'bano_fecha',
            'parasitos_fecha', 'destete_fecha', 'prenez_fecha', 'parto_fecha', 'inseminacion_fecha'
        ];
        foreach ($dateFields as $field) {
            if (!empty($animal[$field])) {
                $animal[$field] = date('Y-m-d', strtotime($animal[$field]));
            }
        }

        // Return the data as JSON
        echo json_encode($animal);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No animal found with the provided ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . $conn->error
    ]);
}

// Close connection
$conn->close();
?> 