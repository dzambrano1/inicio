<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if ID was provided
if (!isset($_POST['id'])) {
    die(json_encode(['success' => false, 'message' => 'No ID provided']));
}

$id = intval($_POST['id']);

// Prepare and execute the delete query
$stmt = $conn->prepare("DELETE FROM ganado WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 