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
    !isset($data['inseminacion']) ||
    !isset($data['inseminacion_fecha']) ||
    !isset($data['inseminacion_costo'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$inseminacion = $conn->real_escape_string($data['inseminacion']);
$inseminacion_fecha = $conn->real_escape_string($data['inseminacion_fecha']);
$inseminacion_costo = floatval($data['inseminacion_costo']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET inseminacion = ?, inseminacion_fecha = ?, inseminacion_costo = ? WHERE id = ?");
    $stmtUpdate->bind_param("ssdi", $inseminacion, $inseminacion_fecha, $inseminacion_costo, $id);
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

    // Insert into h_inseminacion table
    $stmtInsert = $conn->prepare("INSERT INTO h_inseminacion (tagid, inseminacion, inseminacion_fecha, inseminacion_costo) VALUES (?, ?, ?, ?)");
    $stmtInsert->bind_param("sssd", $tagid, $inseminacion, $inseminacion_fecha, $inseminacion_costo);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error al insertar en h_inseminacion: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Inseminación actualizada correctamente.'
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