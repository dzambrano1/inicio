<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: login.php?redirect=orders.php");
    exit;
}

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

// Get orders for this user
// If admin, get all orders
$orders_query = $user_role === "admin" 
    ? "SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.order_date DESC" 
    : "SELECT o.* FROM orders o WHERE o.customer_id = ? ORDER BY o.order_date DESC";

$stmt = $conn->prepare($orders_query);
if ($user_role !== "admin") {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$orders_result = $stmt->get_result();
$stmt->close();

// Check for success message
$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Pedido realizado con éxito.';
    if (isset($_GET['order_id'])) {
        $success_message .= ' Pedido #' . $_GET['order_id'] . ' ha sido creado.';
    }
}

// Check for error message
$error_message = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Durafrenos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 56px;
        }
        .order-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .order-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .order-body {
            padding: 15px;
        }
        .order-footer {
            background-color: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #dee2e6;
        }
        .empty-orders {
            text-align: center;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
        }
        .product-image {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }
        .details-table th, .details-table td {
            vertical-align: middle;
        }
        .btn-view-details {
            background-color: #007bff;
            color: white;
        }
        .btn-view-details:hover {
            background-color: #0069d9;
            color: white;
        }
        .badge-order-id {
            font-size: 1em;
            font-weight: normal;
            background-color: #6c757d;
        }
        .alert-success {
            animation: fadeOut 5s forwards;
            animation-delay: 3s;
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; visibility: hidden; }
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
                        <a class="nav-link" href="cart.php">Carrito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Mis Pedidos</a>
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
            <i class="fas fa-clipboard-list me-2"></i> Mis Pedidos
        </h2>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($orders_result->num_rows === 0): ?>
        <div class="empty-orders">
            <i class="fas fa-box-open fa-4x mb-3 text-muted"></i>
            <h3>No tienes pedidos aún</h3>
            <p class="text-muted mb-4">Todos tus pedidos aparecerán aquí cuando realices uno.</p>
            <a href="catalog.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i> Ir al Catálogo
            </a>
        </div>
        <?php else: ?>
        <!-- Orders Table -->
        <div class="table-responsive">
            <table id="ordersTable" class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Pedido #</th>
                        <?php if ($user_role === "admin"): ?>
                        <th>Cliente</th>
                        <?php endif; ?>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <?php if ($user_role === "admin"): ?>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <?php endif; ?>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                        <td>$<?php echo number_format($order['total'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-view-details view-order-details" 
                                    data-order-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-eye me-1"></i> Ver Detalles
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Detalles del Pedido #<span id="modalOrderId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center" id="orderDetailsLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando detalles...</p>
                    </div>
                    <div id="orderDetailsContent" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Fecha:</strong> <span id="orderDate"></span>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Total:</strong> <span id="orderTotal"></span>
                            </div>
                        </div>
                        <table class="table table-striped details-table">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Código</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="orderDetailsTableBody">
                                <!-- Order details will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#ordersTable').DataTable({
            order: [[0, 'desc']], // Sort by order ID descending
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true
        });
        
        // View Order Details
        $('.view-order-details').click(function() {
            const orderId = $(this).data('order-id');
            
            // Reset and show modal
            $('#modalOrderId').text(orderId);
            $('#orderDetailsLoading').show();
            $('#orderDetailsContent').hide();
            $('#orderDetailsTableBody').empty();
            $('#orderDetailsModal').modal('show');
            
            // Load order details via AJAX
            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: { order_id: orderId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Fill order info
                        $('#orderDate').text(response.order.date);
                        $('#orderTotal').text('$' + parseFloat(response.order.total).toFixed(2));
                        
                        // Fill details table
                        response.details.forEach(function(item) {
                            const subtotal = parseFloat(item.price) * parseInt(item.quantity);
                            let row = `
                                <tr>
                                    <td>
                                        <img src="${item.image || './images/default_image.png'}" 
                                             alt="${item.make} ${item.model}" 
                                             class="product-image">
                                    </td>
                                    <td>
                                        <strong>${item.make} ${item.model}</strong>
                                        ${item.year ? '<br><small>Año: ' + item.year + '</small>' : ''}
                                    </td>
                                    <td>${item.code}</td>
                                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                                    <td>${item.quantity}</td>
                                    <td>$${subtotal.toFixed(2)}</td>
                                </tr>
                            `;
                            $('#orderDetailsTableBody').append(row);
                        });
                        
                        // Hide loading, show content
                        $('#orderDetailsLoading').hide();
                        $('#orderDetailsContent').show();
                    } else {
                        alert('Error: ' + response.message);
                        $('#orderDetailsModal').modal('hide');
                    }
                },
                error: function() {
                    alert('Error al cargar los detalles del pedido');
                    $('#orderDetailsModal').modal('hide');
                }
            });
        });
        
        // Print Order
        $('#printOrderBtn').click(function() {
            const orderId = $('#modalOrderId').text();
            window.open('print_order.php?order_id=' + orderId, '_blank');
        });
    });
    </script>
</body>
</html>
