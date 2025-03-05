<?php
require_once 'conexion.php';

header('Content-Type: application/json');

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
                $etapa = $conn->real_escape_string($_POST['etapa']);
                $producto = $conn->real_escape_string($_POST['producto']);
                $racion = $conn->real_escape_string($_POST['racion']);
                $costo = $conn->real_escape_string($_POST['costo']);
                $fecha = $conn->real_escape_string($_POST['fecha']);
                
                $sql = "UPDATE vh_concentrado 
                        SET vh_concentrado_etapa = '$etapa',
                            vh_concentrado_producto = '$producto',
                            vh_concentrado_racion = '$racion',
                            vh_concentrado_costo = '$costo',
                            vh_concentrado_fecha = '$fecha'
                        WHERE id = '$id'";
                break;
                
            case 'delete':
                // ... existing delete code ...
                break;
        }
        
        if ($conn->query($sql)) {
            $response['success'] = true;
        } else {
            throw new Exception($conn->error);
        }
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit; 