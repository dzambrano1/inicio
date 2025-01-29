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

// Enable detailed error reporting (for debugging purposes only. Disable in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$requiredFields = ['id', 'bano', 'bano_fecha', 'bano_costo'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Missing field: $field"
        ]);
        exit;
    }
    // Additional validation for each field
    if ($field === 'bano_costo' && !is_numeric($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => "Invalid value for $field. It must be a number."
        ]);
        exit;
    }
}

$animalId = $conn->real_escape_string($data['id']);
$bano = $conn->real_escape_string($data['bano']);
$banoFecha = $conn->real_escape_string($data['bano_fecha']);
$banoCosto = floatval($data['bano_costo']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Retrieve the current tagid for the animal
    $stmtTag = $conn->prepare("SELECT tagid FROM ganado WHERE id = ?");
    if (!$stmtTag) {
        throw new Exception("Preparation failed: " . $conn->error);
    }
    $stmtTag->bind_param("i", $animalId);
    $stmtTag->execute();
    $resultTag = $stmtTag->get_result();
    if ($resultTag->num_rows === 0) {
        throw new Exception("Animal with ID $animalId not found.");
    }
    $rowTag = $resultTag->fetch_assoc();
    $tagid = $rowTag['tagid'];
    $stmtTag->close();

    // Update the ganado table
    $stmtUpdate = $conn->prepare("UPDATE ganado SET bano = ?, bano_fecha = ?, bano_costo = ? WHERE id = ?");
    if (!$stmtUpdate) {
        throw new Exception("Preparation failed: " . $conn->error);
    }
    $stmtUpdate->bind_param("ssdi", $bano, $banoFecha, $banoCosto, $animalId);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Insert into h_bano table with corrected bind_param types
    $stmtInsert = $conn->prepare("INSERT INTO h_bano (h_tagid, h_bano, h_bano_fecha, h_bano_costo) VALUES (?, ?, ?, ?)");
    if (!$stmtInsert) {
        throw new Exception("Preparation failed: " . $conn->error);
    }
    // Corrected bind_param types: "sssd"
    $stmtInsert->bind_param("sssd", $tagid, $bano, $banoFecha, $banoCosto);
    $stmtInsert->execute();
    $stmtInsert->close();

    // Commit the transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Baño information updated successfully.'
    ]);
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}

// Close the connection
$conn->close();
?> 