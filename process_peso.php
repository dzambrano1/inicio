<?php
header('Content-Type: application/json');
require_once './conexion.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);

$response = ['success' => false];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update':
                if (empty($_POST['id'])) {
                    throw new Exception('ID is required');
                }
                
                $id = $conn->real_escape_string($_POST['id']);
                $peso = $conn->real_escape_string($_POST['peso']);
                $precio = $conn->real_escape_string($_POST['precio']);
                $fecha = $conn->real_escape_string($_POST['fecha']);
                
                $sql = "UPDATE vh_peso 
                        SET vh_peso_animal = '$peso',
                            vh_peso_precio = '$precio',
                            vh_peso_fecha = '$fecha'
                        WHERE id = '$id'";
                break;
                
            case 'delete':
                if (empty($_POST['id'])) {
                    throw new Exception('ID is required for deletion');
                }
                
                $id = $conn->real_escape_string($_POST['id']);
                
                // Verify record exists before deletion
                $check_sql = "SELECT id FROM vh_peso WHERE id = '$id'";
                $check_result = $conn->query($check_sql);
                
                if ($check_result->num_rows === 0) {
                    throw new Exception('Record not found');
                }
                
                $sql = "DELETE FROM vh_peso WHERE id = '$id'";
                break;
                
            default:
                throw new Exception('Invalid action specified');
        }
        
        if ($conn->query($sql)) {
            $response['success'] = true;
            
            // Add additional info for delete action
            if ($action === 'delete') {
                $response['message'] = 'Record deleted successfully';
            }
        } else {
            throw new Exception($conn->error);
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response);
exit; 