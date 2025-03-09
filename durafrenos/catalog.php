<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

// Get user info from session
$user_id = $_SESSION["id"];
$user_name = $_SESSION["name"] ?? "Usuario";
$user_role = $_SESSION["role"] ?? "customer";

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get unique categories for filter
$category_query = "SELECT DISTINCT category FROM products ORDER BY category";
$category_result = mysqli_query($conn, $category_query);
$categories = [];
while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row['category'];
}

// Get unique makes for filter
$make_query = "SELECT DISTINCT make FROM products ORDER BY make";
$make_result = mysqli_query($conn, $make_query);
$makes = [];
while ($row = mysqli_fetch_assoc($make_result)) {
    $makes[] = $row['make'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - Durafrenos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 56px;
        }
        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
        .product-image-modal {
            max-height: 150px;
            max-width: 100%;
        }
        .filters-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-upload {
            border: 2px dashed #0d6efd;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        .btn-upload:hover {
            background-color: #e2e6ea;
            border-color: #0a58ca;
        }
        .modal-header {
            background-color: #0d6efd;
            color: white;
        }
        .modal-footer {
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }
        .image-magnifier {
            cursor: zoom-in;
            transition: transform 0.2s;
        }
        .image-magnifier:hover {
            transform: scale(1.5);
        }
        .selected-file {
            font-size: 0.9rem;
            margin-top: 5px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        #productModal .modal-content {
            animation: fadeIn 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="catalog.php">
                <img src="/images/durafrenos-logo-removebg-preview.png" alt="Durafrenos" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="catalog.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Carrito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Mis Pedidos</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Bienvenido, <?php echo htmlspecialchars($user_name); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 pt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Catálogo de Productos</h2>
            <?php if ($user_role === "admin"): ?>
            <button class="btn btn-primary" type="button" onclick="openNewProductModal()">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <h5>Filtros</h5>
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-select" id="category">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="make" class="form-label">Marca</label>
                    <select class="form-select" id="make">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($makes as $make): ?>
                        <option value="<?php echo htmlspecialchars($make); ?>"><?php echo htmlspecialchars($make); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priceRange" class="form-label">Precio máximo</label>
                    <input type="number" class="form-control" id="priceRange" placeholder="Precio máximo">
                </div>
                <div class="col-md-3">
                    <label for="stockFilter" class="form-label">Stock mínimo</label>
                    <input type="number" class="form-control" id="stockFilter" placeholder="Stock mínimo">
                </div>
                <div class="col-12 mt-3">
                    <button type="button" class="btn btn-primary" id="applyFilters">
                        <i class="fas fa-filter"></i> Aplicar filtros
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                        <i class="fas fa-undo"></i> Reiniciar
                    </button>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="table-responsive">
            <table id="productsTable" class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Imagen</th>
                        <th>Código</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Products will be loaded here by AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Update Product Modal (Only for Admin) -->
    <?php if ($user_role === "admin"): ?>
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Crear Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm" enctype="multipart/form-data">
                        <input type="hidden" id="productId" name="id" value="">
                        
                        <!-- Image Preview -->
                        <div class="image-upload-container text-center mb-3">
                            <h6>Vista previa</h6>
                            <img id="currentProductImage" src="./images/default_image.png" alt="Vista previa del producto" 
                                 class="product-image-modal border p-2 rounded mb-3">
                            <div id="newImageBadge" class="d-none">
                                <span class="badge bg-success mt-1 mb-2">Nueva imagen seleccionada</span>
                            </div>
                            
                            <!-- Hidden original file input -->
                            <input type="file" class="form-control d-none" id="image" name="image" accept="image/*">
                            
                            <!-- Styled upload button -->
                            <button type="button" class="btn btn-upload d-block w-100" onclick="document.getElementById('image').click()">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Seleccionar archivo
                            </button>
                            
                            <!-- File name display -->
                            <div class="selected-file mt-2" id="selectedFileName">
                                <small class="text-muted">Ningún archivo seleccionado</small>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        const productsTable = $('#productsTable').DataTable({
            processing: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            columns: [
                { data: 'image', orderable: false },
                { data: 'code' },
                { data: 'category' },
                { data: 'make' },
                { data: 'model' },
                { data: 'year' },
                { data: 'price' },
                { data: 'stock' },
                { data: 'actions', orderable: false }
            ],
            columnDefs: [
                {
                    targets: 0, // Image column
                    render: function(data, type, row) {
                        return `<img src="${data || './images/default_image.png'}" alt="${row.make} ${row.model}" class="product-image image-magnifier">`;
                    }
                },
                {
                    targets: 6, // Price column
                    render: function(data) {
                        return '$' + parseFloat(data).toFixed(2);
                    }
                },
                {
                    targets: 8, // Actions column
                    render: function(data, type, row) {
                        const userRole = "<?php echo $user_role; ?>";
                        let actions = '';
                        
                        if (userRole === "admin") {
                            actions = `
                                <button class="btn btn-sm btn-primary me-1" onclick="editProduct(${row.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${row.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            `;
                        } else {
                            actions = `
                                <button class="btn btn-sm btn-primary" onclick="addToCart(${row.id})">
                                    <i class="fas fa-cart-plus"></i> Agregar
                                </button>
                            `;
                        }
                        
                        return actions;
                    }
                }
            ],
            responsive: true,
            autoWidth: false
        });
        
        // Load filtered products
        function loadFilteredProducts() {
            const category = $('#category').val();
            const make = $('#make').val();
            const maxPrice = $('#priceRange').val();
            const minStock = $('#stockFilter').val();
            
            // Build query string for filters
            let queryParams = [];
            if (category) queryParams.push(`category=${encodeURIComponent(category)}`);
            if (make) queryParams.push(`make=${encodeURIComponent(make)}`);
            if (maxPrice) queryParams.push(`max_price=${encodeURIComponent(maxPrice)}`);
            if (minStock) queryParams.push(`min_stock=${encodeURIComponent(minStock)}`);
            
            const queryString = queryParams.length > 0 ? '?' + queryParams.join('&') : '';
            
            // Fetch products with filters
            $.ajax({
                url: 'get_products.php' + queryString,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Clear existing data and add new data
                    productsTable.clear();
                    productsTable.rows.add(data);
                    productsTable.draw();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching products:", error);
                    alert("Error al cargar los productos. Por favor, intente nuevamente.");
                }
            });
        }
        
        // Initial load of products
        loadFilteredProducts();
        
        // Apply filters button
        $('#applyFilters').click(function() {
            loadFilteredProducts();
        });
        
        // Reset filters button
        $('#resetFilters').click(function() {
            $('#filterForm')[0].reset();
            loadFilteredProducts();
        });
        
        // Image preview when selecting a file
        $('#image').change(function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#currentProductImage').attr('src', e.target.result);
                    $('#newImageBadge').removeClass('d-none');
                    $('#selectedFileName').html('<small>' + $('#image')[0].files[0].name + '</small>');
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Category dropdown change - show/hide other category input
        $('#productCategory').change(function() {
            if ($(this).val() === 'other') {
                $('#otherCategory').removeClass('d-none');
            } else {
                $('#otherCategory').addClass('d-none');
            }
        });
        
        // Save product button
        $('#saveProductBtn').click(function() {
            const productId = $('#productId').val();
            const formData = new FormData(document.getElementById('productForm'));
            
            // Handle other category if selected
            if ($('#productCategory').val() === 'other' && $('#otherCategory').val().trim() !== '') {
                formData.set('category', $('#otherCategory').val().trim());
            }
            
            // Send to appropriate endpoint
            const url = productId ? 'edit_product.php' : 'create_product.php';
            
            // Show loading indicator
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
            $(this).prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log("Response:", response);
                    
                    if (response.success) {
                        alert(productId ? 'Producto actualizado correctamente' : 'Producto agregado correctamente');
                        $('#productModal').modal('hide');
                        loadFilteredProducts(); // Reload products table
                    } else {
                        alert('Error: ' + (response.message || 'Ocurrió un error al procesar la solicitud'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Status:", status);
                    console.error("Error:", error);
                    alert('Error: ' + error);
                },
                complete: function() {
                    $('#saveProductBtn').html(productId ? 'Actualizar' : 'Guardar');
                    $('#saveProductBtn').prop('disabled', false);
                }
            });
        });
        
        // Add to cart function
        window.addToCart = function(productId) {
            // Show loading indicator
            const addBtn = document.getElementById('addBtn_' + productId);
            if (addBtn) {
                addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                addBtn.disabled = true;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('add_to_cart', 'true');
            
            // Send AJAX request
            $.ajax({
                url: 'add_to_cart.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Producto agregado al carrito');
                    } else {
                        alert('Error: ' + (response.message || 'Ocurrió un error al agregar al carrito'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    alert('Error al agregar producto al carrito');
                },
                complete: function() {
                    // Restore button
                    if (addBtn) {
                        addBtn.innerHTML = '<i class="fas fa-cart-plus"></i> Agregar';
                        addBtn.disabled = false;
                    }
                }
            });
        };
        
        <?php if ($user_role === "admin"): ?>
        // Function to open modal for new product
        window.openNewProductModal = function() {
            // Reset form
            $('#productForm')[0].reset();
            $('#productId').val('');
            $('#currentProductImage').attr('src', './images/default_image.png');
            $('#newImageBadge').addClass('d-none');
            $('#selectedFileName').html('<small class="text-muted">Ningún archivo seleccionado</small>');
            $('#otherCategory').addClass('d-none');
            
            // Set modal title and button text
            $('#productModalLabel').text('Crear Producto');
            $('#saveProductBtn').text('Guardar');
            
            // Show modal
            $('#productModal').modal('show');
        };
        
        // Function to edit product
        window.editProduct = function(id) {
            // Get product data
            $.ajax({
                url: 'get_product.php',
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        // Fill form fields
                        $('#productId').val(data.id);
                        $('#code').val(data.code);
                        $('#productCategory').val(data.category);
                        $('#productMake').val(data.make);
                        $('#productModel').val(data.model);
                        $('#productYear').val(data.year);
                        $('#price').val(data.price);
                        $('#stock').val(data.stock);
                        
                        // Show image if available
                        if (data.image) {
                            $('#currentProductImage').attr('src', data.image);
                        } else {
                            $('#currentProductImage').attr('src', './images/default_image.png');
                        }
                        
                        // Reset new image badge
                        $('#newImageBadge').addClass('d-none');
                        $('#selectedFileName').html('<small class="text-muted">Ningún archivo seleccionado</small>');
                        
                        // If category is not in list, select 'other'
                        let categoryFound = false;
                        $('#productCategory option').each(function() {
                            if ($(this).val() === data.category) {
                                categoryFound = true;
                                return false; // Break the loop
                            }
                        });
                        
                        if (!categoryFound && data.category) {
                            $('#productCategory').val('other');
                            $('#otherCategory').removeClass('d-none');
                            $('#otherCategory').val(data.category);
                        } else {
                            $('#otherCategory').addClass('d-none');
                        }
                        
                        // Set modal title and button text
                        $('#productModalLabel').text('Editar Producto');
                        $('#saveProductBtn').text('Actualizar');
                        
                        // Show modal
                        $('#productModal').modal('show');
                    } else {
                        alert('No se pudo cargar la información del producto');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching product:", error);
                    alert('Error al cargar el producto');
                }
            });
        };
        
        // Function to delete product
        window.deleteProduct = function(id) {
            if (confirm('¿Está seguro que desea eliminar este producto?')) {
                $.ajax({
                    url: 'delete_product.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Producto eliminado correctamente');
                            loadFilteredProducts(); // Reload products table
                        } else {
                            alert('Error: ' + (response.message || 'Ocurrió un error al eliminar el producto'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error deleting product:", error);
                        alert('Error al eliminar el producto');
                    }
                });
            }
        };
        <?php endif; ?>
    });
    </script>
</body>
</html> 