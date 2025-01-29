<?php
header('Content-Type: application/json');

// Habilitar la visualización de errores (solo para desarrollo)
// **Nota:** Deshabilita esto en producción para evitar exponer información sensible.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Parámetros de conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Conexión fallida: ' . $conn->connect_error]);
    exit;
}

// Obtener datos JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
if (!isset($data['id'], $data['destete_peso'], $data['destete_fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
    exit;
}

$id = $conn->real_escape_string($data['id']);
$destete_peso = $conn->real_escape_string($data['destete_peso']);
$destete_fecha = $conn->real_escape_string($data['destete_fecha']);

// Iniciar transacción
$conn->begin_transaction();

try {
    // Actualizar destete_peso y destete_fecha en la tabla ganado
    $sqlUpdate = "UPDATE ganado SET destete_peso = ?, destete_fecha = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        throw new Exception("Error en la preparación de la consulta de actualización: " . $conn->error);
    }
    $stmtUpdate->bind_param("dsi", $destete_peso, $destete_fecha, $id);
    if (!$stmtUpdate->execute()) {
        throw new Exception("Error al ejecutar la consulta de actualización: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // Obtener tagid del animal para registrar en h_destete
    $sqlTag = "SELECT tagid FROM ganado WHERE id = ?";
    $stmtTag = $conn->prepare($sqlTag);
    if (!$stmtTag) {
        throw new Exception("Error en la preparación de la consulta de selección de tagid: " . $conn->error);
    }
    $stmtTag->bind_param("i", $id);
    if (!$stmtTag->execute()) {
        throw new Exception("Error al ejecutar la consulta de selección de tagid: " . $stmtTag->error);
    }
    $resultTag = $stmtTag->get_result();
    if ($resultTag->num_rows === 0) {
        throw new Exception("No se encontró el animal con ID proporcionado.");
    }
    $rowTag = $resultTag->fetch_assoc();
    $tagid = $rowTag['tagid'];
    $stmtTag->close();

    // Insertar en la tabla h_destete
    $sqlInsert = "INSERT INTO h_destete (h_tagid, h_destete_peso, h_destete_fecha) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert) {
        throw new Exception("Error en la preparación de la consulta de inserción en h_destete: " . $conn->error);
    }
    $stmtInsert->bind_param("ids", $id, $destete_peso, $destete_fecha);

    if (!$stmtInsert->execute()) {
        throw new Exception("Error al ejecutar la consulta de inserción en h_destete: " . $stmtInsert->error);
    }
    $stmtInsert->close();

    // Confirmar transacción
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Destete actualizado exitosamente']);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>