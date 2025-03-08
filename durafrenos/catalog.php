<?php
// Start session
session_start();



// Get user role with the CORRECT session variable names 
$user_role = $_SESSION["role"] ?? "customer";
$user_id = $_SESSION["id"] ?? 0;
$user_name = $_SESSION["name"] ?? "Usuario";

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get filter options from database for dropdowns
$categories = [];
$makes = [];
$years = [];

$sql = "SELECT DISTINCT category FROM products ORDER BY category";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['category'])) {
            $categories[] = $row['category'];
        }
    }
}

$sql = "SELECT DISTINCT make FROM products ORDER BY make";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['make'])) {
            $makes[] = $row['make'];
        }
    }
}

$sql = "SELECT DISTINCT year FROM products ORDER BY year DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['year'])) {
            $years[] = $row['year'];
        }
    }
}

// Get filtered products
function getFilteredProducts($conn) {
    $query = "SELECT * FROM products WHERE 1=1";
    
    // Apply filters if provided
    if (!empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
    }
    
    if (!empty($_GET['make'])) {
        $make = mysqli_real_escape_string($conn, $_GET['make']);
        $query .= " AND make = '$make'";
    }
    
    if (!empty($_GET['model'])) {
        $model = mysqli_real_escape_string($conn, $_GET['model']);
        $query .= " AND model LIKE '%$model%'";
    }
    
    if (!empty($_GET['year'])) {
        $year = mysqli_real_escape_string($conn, $_GET['year']);
        $query .= " AND year = '$year'";
    }
    
    // Order the results
    $query .= " ORDER BY category, make, model";
    
    // Execute query
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return [];
    }
    
    // Fetch results
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    return $products;
}

// Get products with any applied filters
$products = getFilteredProducts($conn);

// Get selected filter values for repopulating the form
$selectedCategory = $_GET['category'] ?? '';
$selectedMake = $_GET['make'] ?? '';
$selectedModel = $_GET['model'] ?? '';
$selectedYear = $_GET['year'] ?? '';

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Durafrenos - Catálogo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .product-image {
            height: 50px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .product-image:hover {
            transform: scale(1.1);
        }
        
        /* For the image modal */
        #enlargedImage {
            max-height: 80vh;
            max-width: 100%;
        }
        
        /* Fix for DataTables styling */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }
        
        /* Navbar styling */
        .navbar-brand img {
            height: 40px;
        }
        
        /* Footer styling */
        .footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-top: 40px;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link active" href="catalog.php">Catálogo</a>
                    </li>
                    <?php if ($user_role === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pedidos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Usuarios</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Carrito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Mis Pedidos</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($user_name); ?>
                            <span class="badge <?php echo ($user_role === 'admin') ? 'bg-danger' : 'bg-primary'; ?> ms-1">
                                <?php echo ucfirst(htmlspecialchars($user_role)); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li class="px-3 py-2 text-center">
                                <span class="badge <?php echo ($user_role === 'admin') ? 'bg-danger' : 'bg-primary'; ?> w-100 py-2">
                                    <i class="fas <?php echo ($user_role === 'admin') ? 'fa-shield-alt' : 'fa-user'; ?> me-1"></i>
                                    Rol: <?php echo ucfirst(htmlspecialchars($user_role)); ?>
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
    
    <div class="container mt-4">
        <h1 class="mb-4">Catálogo</h1>
        
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" action="catalog.php" method="GET">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="category" class="form-label">Categoría</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($selectedCategory === $category) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="make" class="form-label">Marca</label>
                            <select class="form-select" id="make" name="make">
                                <option value="">Todas las marcas</option>
                                <?php foreach ($makes as $make): ?>
                                    <option value="<?php echo htmlspecialchars($make); ?>" <?php echo ($selectedMake === $make) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($make); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="model" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($selectedModel); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="year" class="form-label">Año</label>
                            <select class="form-select" id="year" name="year">
                                <option value="">Todos los años</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selectedYear === $year) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Aplicar filtros
                        </button>
                        <a href="catalog.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Admin Add Product Button -->
        <?php if ($user_role === "admin"): ?>
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" type="button" onclick="openNewProductModal()">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Product Listing -->
        <div id="productListing">
            <?php if (empty($products)): ?>
                <div class="alert alert-info">No se encontraron productos con los filtros seleccionados.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="productsTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center">Código</th>
                                <th class="text-center">Marca</th>
                                <th class="text-center">Modelo</th>
                                <th class="text-center">Año</th>
                                <th class="text-center">Precio</th>
                                <th class="text-center">Inventario</th>
                                <th class="text-center">Imagen</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="text-center"><?php echo htmlspecialchars($product['code'] ?? ''); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($product['make'] ?? ''); ?></td>
                                    <td class="text-start"><?php echo htmlspecialchars($product['model'] ?? ''); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($product['year'] ?? ''); ?></td>
                                    <td class="text-center">$<?php echo number_format((float)($product['price'] ?? 0), 2); ?></td>
                                    <td class="text-center"><?php echo (int)($product['stock'] ?? 0); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($product['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                alt="<?php echo htmlspecialchars($product['make'] . ' ' . $product['model']); ?>" 
                                                class="product-image" 
                                                data-full-image="<?php echo htmlspecialchars($product['image']); ?>">
                                        <?php else: ?>
                                            <span class="text-muted">No imagen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($user_role === 'admin'): ?>
                                            <!-- Admin actions -->
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-warning" onclick="editProduct(<?php echo (int)$product['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger" onclick="deleteProduct(<?php echo (int)$product['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <!-- Customer actions -->
                                            <div class="btn-group btn-group-sm">
                                                <?php if ((int)($product['stock'] ?? 0) > 0): ?>
                                                    <button class="btn btn-primary" onclick="addToCart(<?php echo (int)$product['id']; ?>)">
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        <i class="fas fa-cart-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Simple Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Durafrenos</h5>
                    <p>Un frenazo al alto costo con la más alta confiabilidad</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; <?php echo date('Y'); ?> Durafrenos. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Vista ampliada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="enlargedImage" src="" alt="Imagen ampliada" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Product Modal (Only for Admin) -->
    <?php if ($user_role === "admin"): ?>
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="productModalLabel">Agregar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm" enctype="multipart/form-data">
                        <input type="hidden" id="productId" name="id" value="">
                        
                        <!-- Image Preview -->
                        <div class="text-center mb-3" id="imagePreviewContainer">
                            <h6>Vista previa</h6>
                            <img id="currentProductImage" src="./images/default_image.png" alt="Vista previa del producto" 
                                 style="max-height: 150px; max-width: 100%;" class="border p-2 rounded">
                            <div id="newImageBadge" class="d-none">
                                <span class="badge bg-success mt-1">Nueva imagen seleccionada</span>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">Código</label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productCategory" class="form-label">Categoría</label>
                                <select class="form-select" id="productCategory" name="category" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                            <?php echo htmlspecialchars($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="other">Otra categoría</option>
                                </select>
                                <input type="text" class="form-control mt-2 d-none" id="otherCategory" placeholder="Especifique otra categoría">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="productMake" class="form-label">Marca</label>
                                <input type="text" class="form-control" id="productMake" name="make" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="productModel" class="form-label">Modelo</label>
                                <input type="text" class="form-control" id="productModel" name="model" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="productYear" class="form-label">Año</label>
                                <input type="text" class="form-control" id="productYear" name="year">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Precio</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Inventario</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label">Imagen</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveProductBtn">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- jQuery, Bootstrap, and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#productsTable').DataTable({
                responsive: true,
                "language": {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sEmptyTable":     "Ningún dato disponible en esta tabla",
                    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix":    "",
                    "sSearch":         "Buscar:",
                    "sUrl":            "",
                    "sInfoThousands":  ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst":    "Primero",
                        "sLast":     "Último",
                        "sNext":     "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                }
            });
            
            // Image preview on click
            $('.product-image').on('click', function() {
                const fullImage = $(this).data('full-image');
                const alt = $(this).attr('alt');
                
                $('#enlargedImage').attr('src', fullImage);
                $('#enlargedImage').attr('alt', alt);
                $('#imageModalLabel').text(alt);
                
                const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                imageModal.show();
            });
            
            <?php if ($user_role === "admin"): ?>
            // Handle "other" category option
            $('#productCategory').on('change', function() {
                if ($(this).val() === 'other') {
                    $('#otherCategory').removeClass('d-none');
                } else {
                    $('#otherCategory').addClass('d-none');
                }
            });
            
            // Image preview when selecting a file
            $('#image').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#currentProductImage').attr('src', e.target.result);
                        $('#newImageBadge').removeClass('d-none');
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Save product button
            $('#saveProductBtn').on('click', function() {
                const productId = $('#productId').val();
                const formData = new FormData(document.getElementById('productForm'));
                
                // Handle other category if selected
                if ($('#productCategory').val() === 'other' && $('#otherCategory').val().trim() !== '') {
                    formData.set('category', $('#otherCategory').val().trim());
                }
                
                // Send to appropriate endpoint
                const url = productId ? 'edit_product.php' : 'create_product.php';
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert(productId ? 'Producto actualizado correctamente' : 'Producto agregado correctamente');
                        window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            });
            <?php endif; ?>
        });
        
        // Function to add product to cart (for customers)
        function addToCart(productId) {
            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: { product_id: productId, quantity: 1 },
                success: function(response) {
                    alert('Producto agregado al carrito');
                    // Optionally update cart indicator in header
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
        
        <?php if ($user_role === "admin"): ?>
        // Function to open modal for new product
        function openNewProductModal() {
            // Reset form
            $('#productForm')[0].reset();
            $('#productId').val('');
            $('#productModalLabel').text('Agregar Producto');
            $('#saveProductBtn').text('Guardar');
            $('#currentProductImage').attr('src', './images/default_image.png');
            $('#newImageBadge').addClass('d-none');
            $('#otherCategory').addClass('d-none');
            
            // Show modal
            const productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        }
        
        // Function to edit product
        function editProduct(id) {
            // Get product data
            $.ajax({
                url: 'get_product.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(product) {
                    // Populate form
                    $('#productId').val(product.id);
                    $('#code').val(product.code);
                    $('#productCategory').val(product.category);
                    $('#productMake').val(product.make);
                    $('#productModel').val(product.model);
                    $('#productYear').val(product.year);
                    $('#price').val(product.price);
                    $('#stock').val(product.stock);
                    $('#description').val(product.description);
                    
                    // Handle category input
                    if (!$('#productCategory option[value="' + product.category + '"]').length) {
                        $('#productCategory').val('other');
                        $('#otherCategory').removeClass('d-none').val(product.category);
                    }
                    
                    // Set image preview
                    if (product.image) {
                        $('#currentProductImage').attr('src', product.image);
                    } else {
                        $('#currentProductImage').attr('src', './images/default_image.png');
                    }
                    
                    // Reset new image badge
                    $('#newImageBadge').addClass('d-none');
                    
                    // Update modal title and button
                    $('#productModalLabel').text('Editar Producto');
                    $('#saveProductBtn').text('Actualizar');
                    
                    // Show modal
                    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
                    productModal.show();
                },
                error: function(xhr, status, error) {
                    alert('Error: ' + error);
                }
            });
        }
        
        // Function to delete product
        function deleteProduct(id) {
            if (confirm('¿Está seguro que desea eliminar este producto?')) {
                $.ajax({
                    url: 'delete_product.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        alert('Producto eliminado correctamente');
                        window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    }
                });
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>
