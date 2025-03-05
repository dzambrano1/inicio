<?php
$dbname = 'ganagram';
$username = 'root';
$password = '';

// Conexion a la base de datos
$pdo = new PDO('mysql:host=localhost;dbname='.$dbname, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Verificar la conexión
if (!$pdo) {
  die("Connection failed: " . $e->getMessage());
}
echo "Connected successfully";
?>
