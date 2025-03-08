<?php
require_once './auth.php';
requireAdmin(); // This will redirect non-admin users

require_once './conexion.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
$messageType = '';
$success = false;
$customer_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $compania = $_POST['compania'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'customer'; // Default to customer if not specified

    // Generate customer ID
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(customer_id, 5) AS UNSIGNED)) as max_id FROM customers");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
    $customer_id = 'CUST' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $message = "El correo electrónico ya está registrado.";
        $messageType = "danger";
    } else {
        // Insert new customer
        $stmt = $conn->prepare("INSERT INTO customers (customer_id, fullName, email, telefono, direccion, company, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $customer_id, $nombre, $email, $telefono, $direccion, $compania, $password, $role);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $message = "Error al registrar el cliente: " . $stmt->error;
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="durafrenos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <nav class="container navbar">
            <div class="nav-item text-center">
                <a href="./home.php" title="Inicio" style="color: white;">
                    <i class="fa-solid fa-home fa-2xl"></i>
                    <div class="nav-label text-center">Regresar</div>
                </a>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Registro de Nuevo Cliente</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="compania" class="form-label">Compañía</label>
                                <input type="text" class="form-control" id="compania" name="compania" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Celular (04xx)xxxxxxx</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección corta</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Clave</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Rol</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="customer">Cliente</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Registrar Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: '¡Registro Exitoso!',
                text: 'Cliente registrado exitosamente. ID: <?php echo $customer_id; ?>',
                showConfirmButton: false,
                timer: 2000
            }).then(() => {
                window.location.href = './home.php';
            });
        });
    </script>
    <?php endif; ?>
</body>
</html> 