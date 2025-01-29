<?php
header('Content-Type: application/json');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (
    !isset($data['id']) ||
    !isset($data['destete_peso']) ||
    !isset($data['destete_fecha'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$destete_peso = $conn->real_escape_string($data['destete_peso']);
$destete_fecha = $conn->real_escape_string($data['destete_fecha']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET destete_peso = ?, destete_fecha = ? WHERE id = ?");
    $stmtUpdate->bind_param("dsi", $destete_peso, $destete_fecha, $id);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar la actualización: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Retrieve the current tagid for the animal
    $stmtTag = $conn->prepare("SELECT tagid FROM ganado WHERE id = ?");
    $stmtTag->bind_param("i", $id);
    $stmtTag->execute();
    $resultTag = $stmtTag->get_result();
    if ($resultTag->num_rows === 0) {
        throw new Exception("Animal con ID $id no encontrado.");
    }
    $rowTag = $resultTag->fetch_assoc();
    $tagid = $rowTag['tagid'];
    $stmtTag->close();

    // Insert into h_destete table
    $stmtInsert = $conn->prepare("INSERT INTO h_destete (tagid, destete_peso, destete_fecha) VALUES (?, ?, ?)");
    $stmtInsert->bind_param("ids", $tagid, $destete_peso, $destete_fecha);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error al insertar en h_destete: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Destete actualizado correctamente.'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 