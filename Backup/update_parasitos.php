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
    !isset($data['parasitos']) ||
    !isset($data['parasitos_fecha']) ||
    !isset($data['parasitos_costo'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$parasitos = $conn->real_escape_string($data['parasitos']);
$parasitos_fecha = $conn->real_escape_string($data['parasitos_fecha']);
$parasitos_costo = floatval($data['parasitos_costo']);

try {
    // Begin transaction
    $conn->begin_transaction();

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

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET parasitos = ?, parasitos_fecha = ?, parasitos_costo = ? WHERE id = ?");
    $stmtUpdate->bind_param("ssdi", $parasitos, $parasitos_fecha, $parasitos_costo, $id);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar la actualización: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Insert into h_parasitos table
    $stmtInsert = $conn->prepare("INSERT INTO h_parasitos (tagid, parasitos, parasitos_fecha, parasitos_costo) VALUES (?, ?, ?, ?)");
    $stmtInsert->bind_param("sssd", $tagid, $parasitos, $parasitos_fecha, $parasitos_costo);
    if (!$stmtInsert->execute()) {
        throw new Exception("Error al insertar en h_parasitos: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Parásitos actualizados correctamente.'
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