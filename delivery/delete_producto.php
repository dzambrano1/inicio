<?php
require_once '../conexion_delivery.php';


$conn = mysqli_connect($servername, $username, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Get the POST data
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = intval($_POST['id']);

    // Prepare and bind
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $id);

    // Execute the statement
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Registro borrado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el registro con el ID proporcionado.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
}

// Close the connection
$conn->close();
?> 