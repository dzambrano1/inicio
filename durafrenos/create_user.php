<?php
// Start session
session_start();



// Include database connection
require_once "./conexion.php";

// Initialize variables 
$form_username = $firstName = $lastName = $company = $mobile = $address = $email = $form_password = $role = "";
$error = $success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Get and sanitize form data
    $form_username = mysqli_real_escape_string($conn, trim($_POST["username"]));
    $firstName = mysqli_real_escape_string($conn, trim($_POST["firstName"]));
    $lastName = mysqli_real_escape_string($conn, trim($_POST["lastName"]));
    $company = mysqli_real_escape_string($conn, trim($_POST["company"]));
    $mobile = mysqli_real_escape_string($conn, trim($_POST["mobile"]));
    $address = mysqli_real_escape_string($conn, trim($_POST["address"]));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $form_password = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirmPassword"]);
    $role = mysqli_real_escape_string($conn, trim($_POST["role"]));

    // Validate inputs
    $valid = true;

    if (empty($form_username)) {
        $error = "Username is required";
        $valid = false;
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $form_username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Username already exists";
            $valid = false;
        }
        mysqli_stmt_close($check_stmt);
    }

    if (empty($firstName) || empty($lastName)) {
        $error = "First and last name are required";
        $valid = false;
    }

    if (empty($email)) {
        $error = "Email is required";
        $valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
        $valid = false;
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email already exists";
            $valid = false;
        }
        mysqli_stmt_close($check_stmt);
    }

    if (empty($form_password)) {
        $error = "Password is required";
        $valid = false;
    } elseif (strlen($form_password) < 8) {
        $error = "Password must be at least 8 characters";
        $valid = false;
    } elseif ($form_password !== $confirmPassword) {
        $error = "Passwords do not match";
        $valid = false;
    }

    if (empty($role)) {
        $error = "Role is required";
        $valid = false;
    }

    // If validation passes, create the user
    if ($valid) {
        // Hash the password
        $hashed_password = password_hash($form_password, PASSWORD_DEFAULT);
        
        // Create a user ID based on role
        $user_id = null;
        if ($role === "customer") {
            // Generate customer ID
            $user_id = "CUST" . sprintf("%03d", rand(1, 999));
            
            // Make sure customer ID is unique
            $check_sql = "SELECT id FROM users WHERE user_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $user_id);
            
            do {
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $user_id = "CUST" . sprintf("%03d", rand(1, 999));
                } else {
                    break;
                }
            } while (true);
            
            mysqli_stmt_close($check_stmt);
        } elseif ($role === "admin") {
            // Generate admin ID
            $user_id = "ADMIN" . sprintf("%03d", rand(1, 999));
            
            // Make sure admin ID is unique
            $check_sql = "SELECT id FROM users WHERE user_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $user_id);
            
            do {
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $user_id = "ADMIN" . sprintf("%03d", rand(1, 999));
                } else {
                    break;
                }
            } while (true);
            
            mysqli_stmt_close($check_stmt);
        }
        
        // Prepare full name
        $fullName = $firstName . " " . $lastName;
        
        // Insert the user into the database
        $insert_sql = "INSERT INTO users (username, firstName, lastName, company, mobile, address, email, role, password, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ssssssssss", 
            $form_username, $firstName, $lastName, $company, $mobile, $address, $email, $role, $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success = "User created successfully!";
            // Clear form fields after successful submission
            $form_username = $firstName = $lastName = $company = $mobile = $address = $email = $form_password = $role = "";
        } else {
            $error = "Error creating user: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($insert_stmt);
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Durafrenos - Crear Usuario</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                Durafrenos
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalog.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pedidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Usuarios</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION["fullName"] ?? "Admin"); ?>
                            <span class="badge bg-danger ms-1">Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li class="px-3 py-2 text-center">
                                <span class="badge bg-danger w-100 py-2">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Rol: Admin
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-12 mb-3">
                                    <label for="username" class="form-label required-field">Nombre de Usuario</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($form_username); ?>" required>
                                    <div class="form-text">Este será el nombre para iniciar sesión</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label required-field">Nombre</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" 
                                           value="<?php echo htmlspecialchars($firstName); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label required-field">Apellido</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" 
                                           value="<?php echo htmlspecialchars($lastName); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="company" class="form-label">Empresa</label>
                                    <input type="text" class="form-control" id="company" name="company" 
                                           value="<?php echo htmlspecialchars($company); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mobile" class="form-label">Teléfono Móvil</label>
                                    <input type="tel" class="form-control" id="mobile" name="mobile" 
                                           value="<?php echo htmlspecialchars($mobile); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección de Envío</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label required-field">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label required-field">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Mínimo 8 caracteres</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirmPassword" class="form-label required-field">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label required-field">Rol</label>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" name="role" id="roleCustomer" 
                                               value="customer" <?php echo ($role === "customer" || $role === "") ? "checked" : ""; ?> required>
                                        <label class="form-check-label" for="roleCustomer">
                                            <span class="badge bg-primary">Cliente</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="role" id="roleAdmin" 
                                               value="admin" <?php echo ($role === "admin") ? "checked" : ""; ?>>
                                        <label class="form-check-label" for="roleAdmin">
                                            <span class="badge bg-danger">Administrador</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="users.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">Los campos marcados con * son obligatorios</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span>Durafrenos &copy; <?php echo date('Y'); ?></span>
                </div>
                <div class="col-md-6 text-md-end">
                    <span>Administración de Usuarios</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Bootstrap & Form Functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms we want to apply validation styles to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    </script>
</body>
</html> 