<?php
session_start();
require_once './conexion.php';

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

// Verify order belongs to customer
$stmt = $conn->prepare("SELECT o.*, 
                       COALESCE(SUM(p.amount), 0) as paid_amount,
                       o.total - COALESCE(SUM(p.amount), 0) as pending_amount
                       FROM orders o
                       LEFT JOIN payments p ON o.order_id = p.order_id
                       WHERE o.order_id = ? AND o.customer_id = ?
                       GROUP BY o.order_id");
$stmt->bind_param("is", $order_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($order = $result->fetch_assoc()) {
    // Get payment history
    $stmt = $conn->prepare("
        SELECT p.*, 
               CASE 
                   WHEN p.payment_method = 'cash' THEN 'Efectivo'
                   WHEN p.payment_method = 'card' THEN 'Tarjeta de Crédito/Débito'
                   WHEN p.payment_method = 'transfer' THEN 'Transferencia'
                   ELSE p.payment_method
               END as payment_method_name
        FROM payments p
        WHERE p.order_id = ?
        ORDER BY p.payment_date DESC
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $payments = $stmt->get_result();

    // Display order summary
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-primary text-white'>";
    echo "<h5 class='mb-0'>Resumen del Pedido</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Número de Pedido:</strong> " . $order['order_id'] . "</p>";
    echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i', strtotime($order['order_date'])) . "</p>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<p><strong>Total:</strong> $" . number_format($order['total'], 2) . "</p>";
    echo "<p><strong>Pagado:</strong> $" . number_format($order['paid_amount'], 2) . "</p>";
    echo "<p><strong>Pendiente:</strong> $" . number_format($order['pending_amount'], 2) . "</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Display payment history
    echo "<div class='card'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h5 class='mb-0'>Historial de Pagos</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    if ($payments->num_rows > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Fecha</th>";
        echo "<th>Método</th>";
        echo "<th>Referencia</th>";
        echo "<th class='text-end'>Monto</th>";
        echo "<th>Estado</th>";
        echo "<th>Notas</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        while ($payment = $payments->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y H:i', strtotime($payment['payment_date'])) . "</td>";
            echo "<td>" . $payment['payment_method_name'] . "</td>";
            echo "<td>" . ($payment['reference_number'] ?: '-') . "</td>";
            echo "<td class='text-end'>$" . number_format($payment['amount'], 2) . "</td>";
            echo "<td><span class='badge bg-success'>Completado</span></td>";
            echo "<td>" . ($payment['notes'] ?: '-') . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>No hay pagos registrados para este pedido.</div>";
    }

    echo "</div>";
    echo "</div>";

} else {
    echo "<div class='alert alert-danger'>No se encontró el pedido solicitado o no tiene permisos para verlo.</div>";
}

$conn->close();
?> 