<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the data from the AJAX request
$id = $_POST['id'];
$racion_nombre = $_POST['racion_nombre'];
$racion_peso = $_POST['racion_peso'];
$racion_fecha = $_POST['racion_fecha'];
$racion_costo = $_POST['racion_costo']; // Corrected variable name

// Update the ganado table
$updateQuery = "UPDATE ganado SET racion_nombre = ?, racion_peso = ?, racion_fecha = ?, racion_costo = ? WHERE id = ?";
$stmt = $conn->prepare($updateQuery);
if (!$stmt) {
    throw new Exception("Error preparing update statement: " . $conn->error);
}
$stmt->bind_param("ssssi", $racion_nombre, $racion_peso, $racion_fecha, $racion_costo, $id);

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
$tagid = $rowSelect['tagid']; // No need to escape since using prepared statements
$stmtSelect->close();

$response = [];
if ($stmt->execute()) {
    // Insert into h_racion table
    $insertQuery = "INSERT INTO h_racion (tagid, racion_nombre, racion_peso, racion_fecha, racion_costo) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    if (!$insertStmt) {
        throw new Exception("Error preparing insert statement: " . $conn->error);
    }
    $insertStmt->bind_param("ssssi", $tagid, $racion_nombre, $racion_peso, $racion_fecha, $racion_costo);
    
    if ($insertStmt->execute()) {
        $response['success'] = true;
    } else {
        $response['success'] = false;
        $response['message'] = "Error inserting into h_racion: " . $conn->error;
    }
    $insertStmt->close();
} else {
    $response['success'] = false;
    $response['message'] = "Error updating ganado: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>