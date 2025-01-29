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

// Set character set
$conn->set_charset("utf8mb4");

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['id'], $data['preñez_numero'], $data['preñez_fecha'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data.'
    ]);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$preñez_numero = $conn->real_escape_string($data['preñez_numero']);
$preñez_fecha = $conn->real_escape_string($data['preñez_fecha']);

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update ganado table
    $sqlUpdateGanado = "UPDATE ganado SET prenez_numero = '$preñez_numero', prenez_fecha = '$preñez_fecha' WHERE id = '$id'";
    if (!$conn->query($sqlUpdateGanado)) {
        throw new Exception("Error updating ganado: " . $conn->error);
    }

    // Get tagid for the animal
    $sqlGetTagid = "SELECT tagid FROM ganado WHERE id = '$id'";
    $result = $conn->query($sqlGetTagid);
    if ($result->num_rows === 0) {
        throw new Exception("Animal with ID $id not found.");
    }
    $row = $result->fetch_assoc();
    $tagid = $conn->real_escape_string($row['tagid']);

    // Insert into h_prenez table
    $sqlInsertHPrenez = "INSERT INTO h_prenez (h_tagid, h_prenez_numero, h_prenez_fecha) VALUES ('$tagid', '$preñez_numero', '$preñez_fecha')";
    if (!$conn->query($sqlInsertHPrenez)) {
        throw new Exception("Error inserting into h_prenez: " . $conn->error);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Preñez information updated successfully.'
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

// Close connection
$conn->close();
?> 