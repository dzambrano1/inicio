<?php
// Start the session 
session_start();


// Include database connection
require_once "./conexion.php";

// Initialize variables
$email = $user_password = "";
$email_err = $password_err = $login_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if email is empty
    if (empty(trim($_POST["email"]))) {
        $email_err = "Por favor ingrese su correo electrónico.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Por favor ingrese su contraseña.";
    } else {
        $user_password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($email_err) && empty($password_err)) {
        // Create database connection
        $conn = mysqli_connect($servername, $username, $password, $dbname);
        
        // Check connection
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        
        // Prepare a select statement
        $sql = "SELECT id, username, firstName, lastName, email, password, role, user_id FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if (mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $firstName, $lastName, $email, $hashed_password, $role, $user_id);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($user_password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["logged_in"] = 1;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["fullName"] = $firstName . " " . $lastName;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["last_activity"] = time();
                            
                            // Redirect user to appropriate page based on role
                            if ($role === "admin") {
                                header("location: catalog.php");
                            } else {
                                header("location: catalog.php");
                            }
                            exit;
                        } else {
                            // Password is not valid
                            $login_err = "Correo electrónico o contraseña incorrectos.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = "Correo electrónico o contraseña incorrectos.";
                }
            } else {
                $login_err = "Ocurrió un error. Por favor intente más tarde.";
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
        
        // Close connection
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Durafrenos - Iniciar Sesión</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand-name {
            font-size: 28px;
            font-weight: 700;
            color: #0d6efd;
        }
        .card-title {
            text-align: center;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .form-floating {
            margin-bottom: 15px;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
        }
        .input-group-text {
            background-color: transparent;
            border-left: none;
            cursor: pointer;
        }
        .password-toggle {
            border-left: 0;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
        .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <div class="brand-name">Durafrenos</div>
                <p class="text-muted">Sistema de gestión</p>
            </div>
            
            <h4 class="card-title">Iniciar Sesión</h4>
            
            <?php if (!empty($login_err)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $login_err; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" placeholder="nombre@ejemplo.com" value="<?php echo $email; ?>">
                    <label for="email">Correo Electrónico</label>
                    <?php if (!empty($email_err)): ?>
                        <div class="invalid-feedback"><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                           id="password" name="password" placeholder="Contraseña">
                    <label for="password">Contraseña</label>
                    <?php if (!empty($password_err)): ?>
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-muted">¿No tiene una cuenta? <a href="register.php">Regístrese aquí</a></p>
                    <p><a href="recover-password.php">¿Olvidó su contraseña?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap and JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            
            // Add the eye icon to toggle password visibility
            const passwordField = passwordInput.parentElement;
            const toggleButton = document.createElement('div');
            toggleButton.className = 'position-absolute top-50 end-0 translate-middle-y me-3';
            toggleButton.innerHTML = '<i class="far fa-eye-slash"></i>';
            toggleButton.style.cursor = 'pointer';
            toggleButton.style.zIndex = '5';
            passwordField.appendChild(toggleButton);
            
            toggleButton.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                this.querySelector('i').className = type === 'password' ? 'far fa-eye-slash' : 'far fa-eye';
            });
        });
    </script>
</body>
</html>
