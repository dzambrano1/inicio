<?php
require_once './auth.php';
requireLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: catalog.php');
    exit;
}

require_once './conexion.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user information
$user_id = $_SESSION['customer_id'];
$user_name = $_SESSION['fullName'];
$user_role = $_SESSION['role'];

// Initialize variables for form
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$code = '';
$category = '';
$make = '';
$model = '';
$year = '';
$price = '';
$stock = '';
$image = '';
$success_message = '';
$error_message = '';

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = intval($_POST['id']);
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $make = mysqli_real_escape_string($conn, $_POST['make']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    
    // Check if a new image was uploaded
    $image_path = '';
    $keep_current_image = isset($_POST['keep_current_image']) ? $_POST['keep_current_image'] : '';
    
    if (!empty($_FILES['image']['name'])) {
        // A new image was uploaded
        $target_dir = "images/products/";
        
        // Create the directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = "product_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is valid
        $valid_extensions = array("jpg", "jpeg", "png", "gif");
        if (in_array($file_extension, $valid_extensions)) {
            // Upload file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $error_message = "Error al subir la imagen.";
            }
        } else {
            $error_message = "Solo se permiten archivos JPG, JPEG, PNG & GIF.";
        }
    } else if ($keep_current_image === 'yes') {
        // Keep the current image
        $query = "SELECT image FROM products WHERE id = $id";
        $result = mysqli_query($conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            $image_path = $row['image'];
        }
    }
    
    if (empty($error_message)) {
        // Update the product in the database
        if (!empty($image_path)) {
            $stmt = $conn->prepare("UPDATE products SET code = ?, category = ?, make = ?, model = ?, year = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param("sssssddsi", $code, $category, $make, $model, $year, $price, $stock, $image_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET code = ?, category = ?, make = ?, model = ?, year = ?, price = ?, stock = ? WHERE id = ?");
            $stmt->bind_param("sssssddi", $code, $category, $make, $model, $year, $price, $stock, $id);
        }
        
        if ($stmt->execute()) {
            $success_message = "Producto actualizado correctamente.";
        } else {
            $error_message = "Error al actualizar el producto: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get product details if id is provided
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $code = $product['code'];
        $category = $product['category'];
        $make = $product['make'];
        $model = $product['model'];
        $year = $product['year'];
        $price = $product['price'];
        $stock = $product['stock'];
        $image = $product['image'];
    } else {
        die("Producto no encontrado.");
    }
    
    $stmt->close();
} else {
    die("ID de producto no válido.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="durafrenos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --border-color: #dee2e6;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .navbar {
            background-color: var(--dark-bg);
            padding: 1rem 0;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .nav-item {
            margin: 0 15px;
        }
        
        .nav-item a:hover {
            transform: translateY(-2px);
            transition: var(--transition);
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }
        
        .card-header h5 {
            color: var(--dark-bg);
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Form styling */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-color: var(--primary-color);
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: 1px solid var(--border-color);
            color: var(--secondary-color);
        }
        
        /* Image preview styling */
        .img-preview {
            max-width: 100%;
            max-height: 250px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.25rem;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }
        
        .img-preview:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .preview-container {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: var(--light-bg);
            border-radius: 6px;
            text-align: center;
        }
        
        .preview-container p {
            font-size: 1rem;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
        }
        
        /* Button styling */
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.25);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Custom file input styling */
        .form-control[type="file"] {
            padding: 0.375rem 0.75rem;
        }
        
        .form-text {
            color: var(--secondary-color);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        /* Form sections */
        .form-section {
            background-color: var(--light-bg);
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .form-section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        /* Alerts */
        .alert {
            border-radius: 6px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        /* Checkbox styling */
        .form-check-input {
            width: 1.1em;
            height: 1.1em;
            margin-top: 0.25em;
            margin-right: 0.5rem;
            background-color: #fff;
            border: 1px solid var(--border-color);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            font-weight: 500;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.25rem;
            }
            
            .form-section {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
<header>
    <nav class="container navbar">
        <div class="nav-item text-center">
            <a href="./home.php" title="Inicio" style="color: white;">
                <i class="fa-solid fa-home fa-2xl"></i>
                <div class="nav-label text-center">Inicio</div>
            </a>
        </div>
        <div class="nav-item text-center">
            <a href="./catalog.php" title="Regresar al Catálogo" style="color: white;">
                <i class="fa-solid fa-arrow-left fa-2xl"></i>
                <div class="nav-label text-center">Regresar</div>
            </a>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Producto #<?php echo $id; ?></h5>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($code); ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="edit_product.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-info-circle me-2"></i>Información Básica
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Código:</label>
                                        <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($code); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Categoría:</label>
                                        <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-tag me-2"></i>Detalles del Producto
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="make" class="form-label">Marca:</label>
                                        <input type="text" class="form-control" id="make" name="make" value="<?php echo htmlspecialchars($make); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="model" class="form-label">Modelo:</label>
                                        <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($model); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="year" class="form-label">Año:</label>
                                        <input type="text" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Precio:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Existencia:</label>
                                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($stock); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-image me-2"></i>Imagen del Producto
                            </div>
                            
                            <div class="row">
                                <?php if (!empty($image)): ?>
                                <div class="col-md-6">
                                    <div class="preview-container mb-3">
                                        <p><strong>Imagen Actual</strong></p>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Imagen actual" class="img-preview">
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="keep_current_image" name="keep_current_image" value="yes" checked>
                                        <label class="form-check-label" for="keep_current_image">
                                            Mantener imagen actual
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-md-<?php echo !empty($image) ? '6' : '12'; ?>">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Subir nueva imagen:</label>
                                        <input type="file" class="form-control" id="image" name="image">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i> Formatos permitidos: JPG, JPEG, PNG, GIF.
                                        </div>
                                    </div>
                                    
                                    <div class="preview-container" id="newImagePreview" style="display:none;">
                                        <p><strong>Vista Previa</strong></p>
                                        <img id="newImagePreviewImg" class="img-preview">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="catalog.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Catálogo
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Image Preview Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('image');
    const newImagePreview = document.getElementById('newImagePreview');
    const newImagePreviewImg = document.getElementById('newImagePreviewImg');
    const keepCurrentImage = document.getElementById('keep_current_image');
    
    // Add animation class to form sections
    const formSections = document.querySelectorAll('.form-section');
    formSections.forEach((section, index) => {
        setTimeout(() => {
            section.style.opacity = '0';
            section.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            section.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                section.style.opacity = '1';
                section.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
    
    // Show preview when new image is selected
    imageInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                newImagePreviewImg.src = e.target.result;
                newImagePreview.style.display = 'none';
                
                // Use setTimeout for a nice fade-in effect
                setTimeout(() => {
                    newImagePreview.style.display = 'block';
                    newImagePreview.style.opacity = '0';
                    
                    setTimeout(() => {
                        newImagePreview.style.opacity = '1';
                        newImagePreview.style.transition = 'opacity 0.3s ease';
                    }, 10);
                }, 100);
                
                // Uncheck keep current image if it exists
                if (keepCurrentImage) {
                    keepCurrentImage.checked = false;
                }
            }
            
            reader.readAsDataURL(this.files[0]);
        } else {
            newImagePreview.style.display = 'none';
        }
    });
    
    // Toggle file input based on checkbox
    if (keepCurrentImage) {
        keepCurrentImage.addEventListener('change', function() {
            if (this.checked) {
                if (newImagePreview.style.display === 'block') {
                    newImagePreview.style.opacity = '0';
                    setTimeout(() => {
                        newImagePreview.style.display = 'none';
                    }, 300);
                }
                imageInput.value = ''; // Clear file input
            }
        });
    }
    
    // Form validation with visual feedback
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
    
    form.addEventListener('submit', function(event) {
        let valid = true;
        
        inputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                valid = false;
            }
        });
        
        if (!valid) {
            event.preventDefault();
            
            // Scroll to the first invalid input
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
    });
});
</script>
</body>
</html>