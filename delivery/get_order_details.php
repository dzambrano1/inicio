<?php
session_start();
require_once '../conexion_delivery.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

if (!isset($_POST['order_id'])) {
    die('Order ID is required');
}

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$order_id = $_POST['order_id'];
$customer_id = $_SESSION['customer_id'];

// Debug output
error_log("Searching for order: " . $order_id . " for customer: " . $customer_id);

// Verify the order belongs to the customer
$stmt = $conn->prepare("SELECT o.* 
                       FROM orders o 
                       WHERE o.order_id = ? AND o.customer_id = ?");
$stmt->bind_param("is", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($order = $result->fetch_assoc()) {
    error_log("Order found: " . print_r($order, true));
    
    // Get order details with product information
    $stmt = $conn->prepare("
        SELECT od.*, p.numero_parte, p.marca, p.modelo, p.precio as product_price
        FROM order_details od
        JOIN productos p ON od.product_id = p.id
        WHERE od.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $details = $stmt->get_result();
    
    error_log("Number of details found: " . $details->num_rows);
    
    if ($details->num_rows === 0) {
        echo "<div class='alert alert-warning'>";
        echo "Debug Info:<br>";
        echo "Order ID: " . htmlspecialchars($order_id) . "<br>";
        echo "Customer ID: " . htmlspecialchars($customer_id) . "<br>";
        echo "Order exists but no details found.";
        echo "</div>";
        
        // Debug query
        $debug_stmt = $conn->prepare("SELECT * FROM order_details WHERE order_id = ?");
        $debug_stmt->bind_param("s", $order_id);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        error_log("Direct order_details query result count: " . $debug_result->num_rows);
        
        // Show the actual SQL for debugging
        $debug_sql = "SELECT od.*, p.numero_parte, p.marca, p.modelo, p.precio
                     FROM order_details od
                     JOIN productos p ON od.product_id = p.id
                     WHERE od.order_id = '$order_id'";
        error_log("Debug SQL: " . $debug_sql);
    }

    // Order header information
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-primary text-white'>";
    echo "<h5 class='mb-0'>Información del Pedido</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Número de Pedido:</strong> " . htmlspecialchars($order['order_id']) . "</p>";
    echo "<p><strong>Cliente ID:</strong> " . htmlspecialchars($order['customer_id']) . "</p>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i', strtotime($order['order_date'])) . "</p>";
    echo "<p><strong>Estado:</strong> ";
    
    // Status badge
    switch($order['status']) {
        case 'pending':
            echo "<span class='badge bg-warning'><i class='fas fa-clock me-1'></i>Pendiente</span>";
            break;
        case 'processing':
            echo "<span class='badge bg-info'><i class='fas fa-cog me-1'></i>Procesando</span>";
            break;
        case 'completed':
            echo "<span class='badge bg-success'><i class='fas fa-check me-1'></i>Completado</span>";
            break;
        case 'cancelled':
            echo "<span class='badge bg-danger'><i class='fas fa-times me-1'></i>Cancelado</span>";
            break;
        default:
            echo "<span class='badge bg-secondary'>" . htmlspecialchars($order['status']) . "</span>";
    }
    echo "</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Order details table
    echo "<div class='table-responsive'>";
    echo "<table class='table table-striped table-bordered'>";
    echo "<thead class='table-light'>";
    echo "<tr>";
    echo "<th>Código</th>";
    echo "<th>Marca</th>";
    echo "<th>Modelo</th>";
    echo "<th class='text-center'>Cantidad</th>";
    echo "<th class='text-end'>Precio Unit.</th>";
    echo "<th class='text-end'>Subtotal</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($detail = $details->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($detail['numero_parte']) . "</td>";
        echo "<td>" . htmlspecialchars($detail['marca']) . "</td>";
        echo "<td>" . htmlspecialchars($detail['modelo']) . "</td>";
        echo "<td class='text-center'>" . htmlspecialchars($detail['quantity']) . "</td>";
        echo "<td class='text-end'>$" . number_format($detail['product_price'], 2) . "</td>";
        echo "<td class='text-end'>$" . number_format($detail['subtotal'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "<tfoot class='table-light'>";
    echo "<tr>";
    echo "<td colspan='5' class='text-end'><strong>Total:</strong></td>";
    echo "<td class='text-end'><strong>$" . number_format($order['total'], 2) . "</strong></td>";
    echo "</tr>";
    echo "</tfoot>";
    echo "</table>";
    echo "</div>";

    // Additional information or notes
    if (!empty($order['notes'])) {
        echo "<div class='mt-3'>";
        echo "<h6>Notas:</h6>";
        echo "<p>" . nl2br(htmlspecialchars($order['notes'])) . "</p>";
        echo "</div>";
    }
} else {
    error_log("No order found for ID: " . $order_id . " and customer: " . $customer_id);
    echo "<div class='alert alert-danger'>No se encontró el pedido solicitado o no tiene permisos para verlo.</div>";
}

$conn->close();
?> 