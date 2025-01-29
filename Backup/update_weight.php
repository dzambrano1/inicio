<?php
// update_weight.php

header('Content-Type: application/json');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Check if POST data is set
if (
    isset($_POST['id']) &&
    isset($_POST['peso']) &&
    isset($_POST['peso_fecha']) &&
    isset($_POST['peso_precio'])
) {
    $id = $_POST['id'];
    $peso = $_POST['peso'];
    $peso_fecha = $_POST['peso_fecha'];
    $peso_precio = $_POST['peso_precio'];

    // Validate input
    if (!is_numeric($peso) || !is_numeric($peso_precio)) {
        echo json_encode(['success' => false, 'message' => 'Peso and Precio must be numeric values.']);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update 'ganado' table
        $updateStmt = $conn->prepare("UPDATE ganado SET peso = ?, peso_fecha = ?, peso_precio = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters: peso (double), peso_fecha (string), peso_precio (double), id (integer)
        $updateStmt->bind_param("ssdi", $peso, $peso_fecha, $peso_precio, $id);
        if (!$updateStmt->execute()) {
            throw new Exception("Execute failed: " . $updateStmt->error);
        }
        
        // Retrieve tagid from ganado table
        $selectStmt = $conn->prepare("SELECT tagid FROM ganado WHERE id = ?");
        if (!$selectStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $selectStmt->bind_param("i", $id);
        if (!$selectStmt->execute()) {
            throw new Exception("Execute failed: " . $selectStmt->error);
        }
        $resultSelect = $selectStmt->get_result();
        if ($resultSelect->num_rows === 0) {
            throw new Exception("Animal with ID $id not found.");
        }
        $rowSelect = $resultSelect->fetch_assoc();
        $tagid = $rowSelect['tagid'];
        $selectStmt->close();

        // Insert into 'h_peso' table
        $insertStmt = $conn->prepare("INSERT INTO h_peso (tagid, peso, peso_fecha, peso_precio) VALUES (?, ?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters: id_ganado (integer), peso (string), peso_fecha (string), peso_precio (double)
        $insertStmt->bind_param("issd", $tagid, $peso, $peso_fecha, $peso_precio);
        if (!$insertStmt->execute()) {
            throw new Exception("Execute failed: " . $insertStmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Peso actualizado exitosamente.']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

    // Close statements
    if (isset($updateStmt)) {
        $updateStmt->close();
    }
    if (isset($insertStmt)) {
        $insertStmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para procesar la solicitud.']);
}

// Close the connection
$conn->close();
?>