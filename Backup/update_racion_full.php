<?php
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log incoming data for debugging
file_put_contents('update_racion_log.txt', "Received data: " . print_r(file_get_contents('php://input'), true) . "\n", FILE_APPEND);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Get JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Log decoded data
file_put_contents('update_racion_log.txt', "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);

// Validate required fields
if (
    !isset($data['id'], $data['racion_nombre'], $data['racion_peso'], $data['racion_costo'], $data['racion_fecha']) ||
    empty($data['id']) ||
    empty($data['racion_nombre']) ||
    !is_numeric($data['racion_peso']) ||
    !is_numeric($data['racion_costo']) ||
    empty($data['racion_fecha'])
) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
    file_put_contents('update_racion_log.txt', "Validation failed.\n", FILE_APPEND);
    exit;
}

// Sanitize and assign variables
$id = $conn->real_escape_string($data['id']);
$racion_nombre = $conn->real_escape_string($data['racion_nombre']);
$racion_peso = floatval($data['racion_peso']);
$racion_costo = floatval($data['racion_costo']);
$racion_fecha = $conn->real_escape_string($data['racion_fecha']);

// Begin transaction
$conn->begin_transaction();

try {
    // Retrieve the current tagid for the animal
    $stmtTag = $conn->prepare("SELECT tagid FROM ganado WHERE id = ?");
    if (!$stmtTag) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    $stmtTag->bind_param("i", $id);
    if (!$stmtTag->execute()) {
        throw new Exception("Error al ejecutar la consulta de tagid: " . $stmtTag->error);
    }
    $resultTag = $stmtTag->get_result();
    if ($resultTag->num_rows === 0) {
        throw new Exception("Animal con ID $id no encontrado.");
    }
    $rowTag = $resultTag->fetch_assoc();
    $tagid = $rowTag['tagid'];
    $stmtTag->close();

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET racion_nombre = ?, racion_peso = ?, racion_costo = ?, racion_fecha = ? WHERE id = ?");
    if (!$stmtUpdate) {
        throw new Exception("Error en la preparación de la actualización: " . $conn->error);
    }
    $stmtUpdate->bind_param("sdssi", $racion_nombre, $racion_peso, $racion_costo, $racion_fecha, $id);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar la actualización: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Insert into h_racion table
    $stmtInsert = $conn->prepare("INSERT INTO h_racion (h_tagid, h_racion_nombre, h_racion_peso, h_racion_costo, h_racion_fecha) VALUES (?, ?, ?, ?, ?)");
    if (!$stmtInsert) {
        throw new Exception("Error en la preparación de la inserción: " . $conn->error);
    }
    $stmtInsert->bind_param("ssdds", $tagid, $racion_nombre, $racion_peso, $racion_costo, $racion_fecha);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error al ejecutar la inserción en h_racion: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Log success
    file_put_contents('update_racion_log.txt', "Ración actualizada exitosamente para ID $id.\n", FILE_APPEND);

    // Return success response
    echo json_encode(['success' => true, 'message' => 'Ración actualizada exitosamente']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Log error
    file_put_contents('update_racion_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);

    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn->close();
?> 