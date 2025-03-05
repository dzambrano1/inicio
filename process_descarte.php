<?php
// Start with a clean slate
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'conexion.php';

// Set JSON header
header('Content-Type: application/json');

// Basic response structure
$response = array(
    'success' => false,
    'error' => null
);

try {
    // Verify POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Process based on action
    switch ($action) {
        case 'create':
            if (empty($_POST['tagid']) || empty($_POST['peso']) || empty($_POST['fecha'])) {
                throw new Exception('Missing required fields');
            }
            
            $tagid = $conn->real_escape_string($_POST['tagid']);
            $peso = $conn->real_escape_string($_POST['peso']);
            $fecha = $conn->real_escape_string($_POST['fecha']);
            
            $sql = "INSERT INTO vh_descarte (vh_descarte_tagid, vh_descarte_peso, vh_descarte_fecha) 
                    VALUES ('$tagid', '$peso', '$fecha')";
            break;

        case 'update':
            if (empty($_POST['id']) || empty($_POST['peso']) || empty($_POST['fecha'])) {
                throw new Exception('Missing required fields');
            }
            
            $id = $conn->real_escape_string($_POST['id']);
            $peso = $conn->real_escape_string($_POST['peso']);
            $fecha = $conn->real_escape_string($_POST['fecha']);
            
            $sql = "UPDATE vh_descarte 
                    SET vh_descarte_peso = '$peso',
                        vh_descarte_fecha = '$fecha'
                    WHERE id = '$id'";
            break;

        case 'delete':
            if (empty($_POST['id'])) {
                throw new Exception('Missing ID');
            }
            
            $id = $conn->real_escape_string($_POST['id']);
            $sql = "DELETE FROM vh_descarte WHERE id = '$id'";
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Execute query
    if ($conn->query($sql)) {
        $response['success'] = true;
    } else {
        throw new Exception($conn->error);
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Send JSON response
echo json_encode($response);
exit;