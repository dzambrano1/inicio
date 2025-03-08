<?php
require_once './auth.php';
requireLogin(); // This will redirect to login.php if not logged in

require_once './conexion.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user information
$user_id = $_SESSION['customer_id'];
$user_name = $_SESSION['fullName'];
$user_role = $_SESSION['role'];

// Fetch products for all users
$products = [];
$stmt = $conn->prepare("SELECT * FROM products ORDER BY category, make, model");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="durafrenos.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="container navbar">
            <div class="nav-item text-center">
                <a href="./catalog.php" title="Catalog" style="color: white;">
                    <i class="fa-solid fa-home fa-2xl"></i>
                    <div class="nav-label text-center">Catalogo</div>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./orders.php" title="Orders" style="color: white;">
                    <i class="fa-solid fa-clipboard-list fa-2xl"></i>
                    <div class="nav-label text-center">Pedidos</div>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./payments.php" title="Payments" style="color: white;">
                    <i class="fa-solid fa-money-bill-wave fa-2xl"></i>
                    <div class="nav-label text-center">Pagos</div>
                </a>
            </div>
            <?php if ($user_role === 'admin'): ?>
            <div class="nav-item text-center">
                <a href="./customer_registration.php" title="Registro" style="color: white;">
                    <i class="fa-solid fa-user-plus fa-2xl"></i>
                    <div class="nav-label text-center">Registro</div>
                </a>
            </div>
            <?php endif; ?>
            <div class="nav-item text-center">
                <a href="./cart.php" title="Shopping Cart" style="color: white; position: relative;">
                    <i class="fa-solid fa-cart-shopping fa-2xl"></i>
                    <div class="nav-label text-center">Carrito</div>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo count($_SESSION['cart']); ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./logout.php" title="Cerrar Sesión" style="color: white;">
                    <i class="fa-solid fa-sign-out-alt fa-2xl"></i>
                    <div class="nav-label text-center">Salir</div>
                </a>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Bienvenido(a), <?php echo htmlspecialchars($user_name); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Información del Usuario</h5>
                                        <p class="card-text">
                                            <strong>Identificacion:</strong> <?php echo htmlspecialchars($user_id); ?><br>
                                            <strong>Accesso:</strong> <?php echo $user_role === 'admin' ? 'Admin' : 'Customer'; ?><br>
                                            <strong>Nombre:</strong> <?php echo htmlspecialchars($user_name); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Enlaces Rapidos</h5>
                                        <div class="list-group">
                                            <a href="./orders.php" class="list-group-item list-group-item-action">
                                                <i class="fas fa-clipboard-list"></i> Ver Pedidos
                                            </a>
                                            <a href="./payments.php" class="list-group-item list-group-item-action">
                                                <i class="fas fa-money-bill-wave"></i> Ver Pagos
                                            </a>
                                            <?php if ($user_role === 'admin'): ?>
                                            <a href="./customer_registration.php" class="list-group-item list-group-item-action">
                                                <i class="fas fa-user-plus"></i> Registrar Cliente
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>




    <!-- Add/Edit Product Modal (Admin Only) -->
    <?php if ($user_role === 'admin'): ?>
    <style>
        /* Product Modal Styling */
        .product-modal .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-modal .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
            border-radius: 12px 12px 0 0;
            padding: 16px 24px;
        }
        
        .product-modal .modal-title {
            font-weight: 600;
            color: #3a3a3a;
        }
        
        .product-modal .modal-body {
            padding: 24px;
        }
        
        .product-modal .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #eaeaea;
            border-radius: 0 0 12px 12px;
            padding: 16px 24px;
        }
        
        /* Image Preview Container */
        .image-preview-container {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px dashed #ddd;
            transition: all 0.3s ease;
        }
        
        .image-preview-container:hover {
            border-color: #aaa;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .image-preview-container img {
            max-height: 200px;
            border-radius: 6px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .image-preview-container img:hover {
            transform: scale(1.02);
        }
        
        .image-preview-title {
            color: #6c757d;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        /* Form Styling */
        .product-form label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 6px;
        }
        
        .product-form .form-control {
            border-radius: 6px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        
        .product-form .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .product-form .file-upload {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Buttons */
        .product-modal .btn-cancel {
            background-color: #f1f2f3;
            color: #495057;
            border: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .product-modal .btn-cancel:hover {
            background-color: #e2e6ea;
        }
        
        .product-modal .btn-save {
            background-color: #0d6efd;
            color: white;
            border: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .product-modal .btn-save:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
    
    <div class="modal fade product-modal" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Image Preview Section -->
                    <div id="imagePreviewContainer" class="image-preview-container text-center">
                        <h6 class="image-preview-title" id="imagePreviewTitle">Imagen del Producto</h6>
                        <img id="currentProductImage" src="./images/default_image.png" alt="Preview" class="img-fluid img-thumbnail">
                    </div>
                    
                    <form id="addProductForm" class="product-form" enctype="multipart/form-data">
                        <div class="file-upload">
                            <label for="image" class="form-label">Seleccionar Imagen</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                        <input type="hidden" id="product_id" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Código:</label>
                                    <input type="text" class="form-control" id="code" name="code" required>
                                </div>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Categoría:</label>
                                    <input type="text" class="form-control" id="category" name="category" required>
                                </div>
                                <div class="mb-3">
                                    <label for="make" class="form-label">Marca:</label>
                                    <input type="text" class="form-control" id="make" name="make" required>
                                </div>
                                <div class="mb-3">
                                    <label for="model" class="form-label">Modelo:</label>
                                    <input type="text" class="form-control" id="model" name="model" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="year" class="form-label">Año:</label>
                                    <input type="text" class="form-control" id="year" name="year" required>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label">Precio:</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Existencia:</label>
                                    <input type="number" class="form-control" id="stock" name="stock" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-save" id="saveProductBtn" onclick="submitProduct()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($user_role === 'admin'): ?>
    <script>
        // Product modal instance
        let productModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            productModal = new bootstrap.Modal(document.getElementById('addProductModal'));
            
            // Add image preview functionality
            document.getElementById('image').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    const imagePreview = document.getElementById('currentProductImage');
                    const imageTitle = document.getElementById('imagePreviewTitle');
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imageTitle.textContent = 'Vista previa';
                        
                        // Add a badge to indicate this is a preview of a new image
                        const previewBadge = document.createElement('span');
                        previewBadge.className = 'badge bg-info position-absolute top-0 end-0 m-2';
                        previewBadge.textContent = 'Nueva imagen';
                        imagePreview.parentElement.style.position = 'relative';
                        
                        // Remove any existing badge
                        const existingBadge = imagePreview.parentElement.querySelector('.badge');
                        if (existingBadge) {
                            existingBadge.remove();
                        }
                        
                        imagePreview.parentElement.appendChild(previewBadge);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        });
        
        function submitProduct() {
            const formData = new FormData(document.getElementById('addProductForm'));
            const productId = document.getElementById('product_id').value;
            const url = productId ? './edit_products.php' : './create_products.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: productId ? 'Producto actualizado correctamente' : 'Producto agregado correctamente',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud'
                });
            });
        }

        function editProduct(id) {
            // Fetch product data and populate the modal
            fetch(`./get_product.php?id=${id}`)
                .then(response => response.json())
                .then(product => {
                    // Set modal title
                    document.getElementById('productModalTitle').textContent = 'Editar Producto';
                    
                    // Fill form fields
                    document.getElementById('product_id').value = product.id;
                    document.getElementById('code').value = product.code;
                    document.getElementById('category').value = product.category;
                    document.getElementById('make').value = product.make;
                    document.getElementById('model').value = product.model;
                    document.getElementById('year').value = product.year;
                    document.getElementById('price').value = product.price;
                    document.getElementById('stock').value = product.stock;
                    
                    // Handle image preview
                    const imagePreviewTitle = document.getElementById('imagePreviewTitle');
                    const currentProductImage = document.getElementById('currentProductImage');
                    
                    // Remove any existing badge
                    const existingBadge = currentProductImage.parentElement.querySelector('.badge');
                    if (existingBadge) {
                        existingBadge.remove();
                    }
                    
                    if (product.image && product.image.trim() !== '') {
                        currentProductImage.src = product.image;
                        imagePreviewTitle.textContent = 'Imagen Actual';
                    } else {
                        currentProductImage.src = './images/default_image.png';
                        imagePreviewTitle.textContent = 'Sin imagen';
                    }
                    
                    // Change button text
                    document.getElementById('saveProductBtn').textContent = 'Actualizar';
                    
                    // Show modal
                    productModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar los datos del producto'
                    });
                });
        }
        
        // Function to open modal for new product
        function openNewProductModal() {
            // Reset form
            document.getElementById('addProductForm').reset();
            document.getElementById('product_id').value = '';
            
            // Reset title and button
            document.getElementById('productModalTitle').textContent = 'Nuevo Producto';
            document.getElementById('saveProductBtn').textContent = 'Guardar';
            
            // Set default image
            const currentProductImage = document.getElementById('currentProductImage');
            currentProductImage.src = './images/default_image.png';
            document.getElementById('imagePreviewTitle').textContent = 'Imagen predeterminada';
            
            // Remove any existing badge
            const existingBadge = currentProductImage.parentElement.querySelector('.badge');
            if (existingBadge) {
                existingBadge.remove();
            }
            
            // Show modal
            productModal.show();
        }

        function deleteProduct(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_products.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: 'El producto ha sido eliminado',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la solicitud'
                        });
                    });
                }
            });
        }

        
    </script>
    <?php else: ?>
    <script>
        function updateQuantity(productId, action) {
            const input = document.getElementById(`qty_${productId}`);
            let currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            
            if (action === 'increase' && currentValue < maxValue) {
                input.value = currentValue + 1;
            } else if (action === 'decrease' && currentValue > 1) {
                input.value = currentValue - 1;
            }
        }

        function addToCart(productId) {
            const quantity = document.getElementById(`qty_${productId}`).value;
            
            fetch('./add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Producto agregado al carrito',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud'
                });
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
