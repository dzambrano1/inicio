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
    !isset($data['parto_numero']) ||
    !isset($data['parto_fecha'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$parto_numero = $conn->real_escape_string($data['parto_numero']);
$parto_fecha = $conn->real_escape_string($data['parto_fecha']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET parto_numero = ?, parto_fecha = ? WHERE id = ?");
    $stmtUpdate->bind_param("ssi", $parto_numero, $parto_fecha, $id);
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

    // Insert into h_parto table
    $stmtInsert = $conn->prepare("INSERT INTO h_parto (tagid, parto_numero, parto_fecha) VALUES (?, ?, ?)");
    $stmtInsert->bind_param("iss", $tagid, $parto_numero, $parto_fecha);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error al insertar en h_parto: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Datos de parto actualizados exitosamente.'
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