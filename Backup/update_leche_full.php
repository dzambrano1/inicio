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
    echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['id'], $data['leche_peso'], $data['leche_fecha'], $data['leche_precio'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($data['id']);
$leche_peso = floatval($data['leche_peso']);
$leche_fecha = $conn->real_escape_string($data['leche_fecha']);
$leche_precio = floatval($data['leche_precio']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update ganado table
    $sqlUpdate = "UPDATE ganado SET leche_peso = ?, leche_fecha = ?, leche_precio = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception("Error preparing update statement: " . $conn->error);
    }
    $stmtUpdate->bind_param("dsdi", $leche_peso, $leche_fecha, $leche_precio, $id);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error executing update statement: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Retrieve tagid from ganado table
    $sqlSelect = "SELECT tagid FROM ganado WHERE id = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    if (!$stmtSelect) {
        throw new Exception("Error preparing select statement: " . $conn->error);
    }
    $stmtSelect->bind_param("i", $id);
    if (!$stmtSelect->execute()) {
        throw new Exception("Error executing select statement: " . $stmtSelect->error);
    }
    $resultSelect = $stmtSelect->get_result();
    if ($resultSelect->num_rows === 0) {
        throw new Exception("Animal with ID $id not found.");
    }
    $rowSelect = $resultSelect->fetch_assoc();
    $tagid = $conn->real_escape_string($rowSelect['tagid']);
    $stmtSelect->close();

    // Insert into h_leche table
    $sqlInsert = "INSERT INTO h_leche (tagid, leche_peso, leche_fecha, leche_precio) VALUES (?, ?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert) {
        throw new Exception("Error preparing insert statement: " . $conn->error);
    }
    $stmtInsert->bind_param("sdsd", $tagid, $leche_peso, $leche_fecha, $leche_precio);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error executing insert statement: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode(['success' => true, 'message' => 'Leche actualizada exitosamente.']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Log the error message (optional but recommended)
    error_log($e->getMessage());

    // Return error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>