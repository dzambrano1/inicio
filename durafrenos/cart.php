<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    // User is not logged in, redirect to login page
    header("Location: login.php?redirect=cart.php");
    exit;
}

// User is logged in, get user info
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

// Get cart items from database
$cart_query = "SELECT id, product_id, quantity, code, make, model, year, price, image 
               FROM cart_items 
               WHERE user_id = ?";
$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$total = 0;
$item_count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['price'] * $row['quantity'];
        $total += $subtotal;
        $item_count += $row['quantity'];
        
        // Add calculated fields
        $row['subtotal'] = $subtotal;
        $cart_items[] = $row;
    }
}

$stmt->close();
$cart_is_empty = empty($cart_items);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Durafrenos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 56px;
        }
        .cart-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-image {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }
        .dataTables_wrapper .dt-buttons {
            margin-bottom: 1rem;
        }
        .action-btn {
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .action-btn:active {
            transform: scale(0.95);
        }
        .btn-container {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .subtotal {
            font-weight: bold;
        }
        .empty-cart-message {
            text-align: center;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .btn-checkout {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-checkout:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
                        <a class="nav-link" href="catalog.php">Catálogo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">Carrito</a>
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
        <h2 class="mb-4">
            <i class="fas fa-shopping-cart me-2"></i> Tu Carrito de Compras
        </h2>
        
        <?php if ($cart_is_empty): ?>
            <div class="empty-cart-message my-5">
                <i class="fas fa-shopping-cart cart-icon"></i>
                <h3>Tu carrito está vacío</h3>
                <p class="text-muted mb-4">Parece que aún no has agregado productos a tu carrito.</p>
                <a href="catalog.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i> Ir al Catálogo
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-9">
                    <!-- Cart Items Table -->
                    <div class="table-responsive mb-4">
                        <table id="cartTable" class="table table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center">Imagen</th>
                                    <th class="text-center">Código</th>
                                    <th class="text-center">Producto</th>
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Subtotal</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td class="text-center">
                                        <img src="<?php echo !empty($item['image']) ? htmlspecialchars($item['image']) : 'images/default_image.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?>" 
                                             class="product-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['code']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?></strong>
                                        <?php if (!empty($item['year'])): ?>
                                        <br><small>Año: <?php echo htmlspecialchars($item['year']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary update-quantity" 
                                                    data-item-id="<?php echo $item['id']; ?>" 
                                                    data-action="decrease">-</button>
                                            <input type="text" class="form-control text-center" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   readonly>
                                            <button class="btn btn-outline-secondary update-quantity" 
                                                    data-item-id="<?php echo $item['id']; ?>" 
                                                    data-action="increase">+</button>
                                        </div>
                                    </td>
                                    <td class="subtotal">$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger remove-item" 
                                                data-item-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th>$<?php echo number_format($total, 2); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <div class="col-lg-3">
                    <!-- Cart Summary -->
                    <div class="cart-summary mb-4">
                        <h4>Resumen del Pedido</h4>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?php echo $item_count; ?> artículos):</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Impuestos:</span>
                            <span>$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío:</span>
                            <span>Gratis</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Total:</h5>
                            <h5>$<?php echo number_format($total, 2); ?></h5>
                        </div>
                        
                        <button id="checkoutBtn" class="btn btn-success w-100 btn-lg btn-checkout">
                            <i class="fas fa-check-circle me-2"></i> Confirmar Pedido
                        </button>
                        
                        <a href="catalog.php" class="btn btn-outline-secondary w-100 mt-3">
                            <i class="fas fa-arrow-left me-2"></i> Seguir Comprando
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable with export buttons
        <?php if (!$cart_is_empty): ?>
        $('#cartTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-secondary',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    },
                    title: 'Carrito de Compras - Durafrenos',
                    customize: function(doc) {
                        doc.content[1].table.widths = ['20%', '30%', '15%', '15%', '20%'];
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 12;
                        doc.styles.tableHeader.fillColor = '#4e73df';
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-info',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5]
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true,
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: [0, 6] }
            ]
        });
        <?php endif; ?>
        
        // Update quantity
        $('.update-quantity').click(function() {
            const itemId = $(this).data('item-id');
            const action = $(this).data('action');
            
            // Disable button during processing
            $(this).prop('disabled', true);
            
            $.ajax({
                url: 'update_cart.php',
                type: 'POST',
                data: {
                    item_id: itemId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error al actualizar el carrito');
                    location.reload();
                }
            });
        });
        
        // Remove item
        $('.remove-item').click(function() {
            if (!confirm('¿Está seguro que desea eliminar este producto del carrito?')) {
                return;
            }
            
            const itemId = $(this).data('item-id');
            
            // Disable button during processing
            $(this).prop('disabled', true);
            
            $.ajax({
                url: 'update_cart.php',
                type: 'POST',
                data: {
                    item_id: itemId,
                    action: 'remove'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error al eliminar el producto del carrito');
                    location.reload();
                }
            });
        });
        
        // Checkout button
        $('#checkoutBtn').click(function() {
            $(this).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando...');
            $(this).prop('disabled', true);
            
            // Redirect to checkout page
            window.location.href = 'checkout.php';
        });
    });
    </script>
</body>
</html> 