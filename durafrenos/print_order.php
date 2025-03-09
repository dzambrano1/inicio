<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$user_role = $_SESSION["role"] ?? "customer";

// Check if order ID is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die("ID de pedido no proporcionado");
}

$order_id = intval($_GET['order_id']);

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// First verify the order belongs to this user (unless admin)
if ($user_role !== "admin") {
    $check_query = "SELECT id FROM orders WHERE id = ? AND customer_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows === 0) {
        die("Pedido no encontrado o no autorizado");
    }
}

// Get order information with customer details
$order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
               FROM orders o 
               JOIN users u ON o.customer_id = u.id 
               WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    die("Pedido no encontrado");
}

// Get order details
$details_query = "SELECT id, product_id, quantity, code, make, model, year, price, image 
                 FROM order_details 
                 WHERE order_id = ?";
$stmt = $conn->prepare($details_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$details_result = $stmt->get_result();
$stmt->close();

// Close database connection
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $order_id; ?> - Durafrenos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .order-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .company-logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .customer-info, .order-details {
            width: 48%;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .order-table th, .order-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        .order-table th {
            background-color: #f8f9fa;
        }
        .product-image {
            max-width: 60px;
            max-height: 60px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .print-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9em;
            color: #6c757d;
        }
        .print-only {
            display: none;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
            body {
                padding: 0;
                font-size: 12pt;
            }
            .container {
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print mb-3 text-end">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <button class="btn btn-secondary" onclick="window.close();">
                <i class="fas fa-times me-1"></i> Cerrar
            </button>
        </div>
        
        <div class="order-header">
            <img src="/images/durafrenos-logo-removebg-preview.png" alt="Durafrenos" class="company-logo">
            <h2>Comprobante de Pedido</h2>
            <h3>Pedido #<?php echo $order_id; ?></h3>
        </div>
        
        <div class="order-info">
            <div class="customer-info">
                <h4>Información del Cliente</h4>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <p><strong>ID Cliente:</strong> <?php echo $order['customer_id']; ?></p>
            </div>
            
            <div class="order-details">
                <h4>Detalles del Pedido</h4>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                <p><strong>Número de Pedido:</strong> <?php echo $order_id; ?></p>
                <p><strong>Estado:</strong> Completado</p>
            </div>
        </div>
        
        <h4>Productos</h4>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                while ($item = $details_result->fetch_assoc()):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td>
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
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-end">Total:</td>
                    <td>$<?php echo number_format($order['total'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="print-footer">
            <p>Este documento es un comprobante de pedido. Gracias por su compra.</p>
            <p>Durafrenos &copy; <?php echo date('Y'); ?></p>
        </div>
        
        <div class="print-only">
            <p style="text-align: center; margin-top: 30px; font-size: 0.8em; color: #999;">
                Impreso el <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 