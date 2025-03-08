<?php
session_start();
require_once './conexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized');
}

if (!isset($_POST['order_id'])) {
    die('Order ID is required');
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=durafrenos", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    $order_id = $_POST['order_id'];
    $customer_id = $_SESSION['customer_id'];
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    // Debug output
    error_log("Searching for order: " . $order_id . " for customer: " . $customer_id . ", is_admin: " . ($is_admin ? 'yes' : 'no'));

    // For admin users, allow access to any order
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT o.*, c.fullName as customer_name, c.email as customer_email 
                               FROM orders o 
                               LEFT JOIN customers c ON o.customer_id = c.customer_id
                               WHERE o.order_id = ?");
        $stmt->execute([$order_id]);
    } else {
        // Regular users can only view their own orders
        $stmt = $conn->prepare("SELECT o.* 
                               FROM orders o 
                               WHERE o.order_id = ? AND o.customer_id = ?");
        $stmt->execute([$order_id, $customer_id]);
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        error_log("Order found: " . print_r($order, true));
        
        // Get order details with product information
        $stmt = $conn->prepare("
            SELECT od.*, p.code, p.make, p.model, p.year, p.category, p.price as product_price
            FROM order_details od
            JOIN products p ON od.code = p.code
            WHERE od.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Number of details found: " . count($details));
        
        if (empty($details)) {
            echo "<div class='alert alert-warning'>";
            echo "Debug Info:<br>";
            echo "Order ID: " . htmlspecialchars($order_id) . "<br>";
            if ($is_admin) {
                echo "Admin viewing order<br>";
            } else {
                echo "Customer ID: " . htmlspecialchars($customer_id) . "<br>";
            }
            echo "Order exists but no details found.";
            echo "</div>";
            
            // Debug query
            $debug_stmt = $conn->prepare("SELECT * FROM order_details WHERE order_id = ?");
            $debug_stmt->execute([$order_id]);
            $debug_result = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Direct order_details query result count: " . count($debug_result));
            error_log("Direct order_details query result: " . print_r($debug_result, true));
        }

        // Order header information
        echo "<div class='card mb-3'>";
        echo "<div class='card-header bg-primary text-white'>";
        echo "<h5 class='mb-0'>Order Information</h5>";
        echo "</div>";
        echo "<div class='card-body'>";
        echo "<div class='row'>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Order Number:</strong> " . htmlspecialchars($order['order_id']) . "</p>";
        
        // Show customer information for admin users
        if ($is_admin) {
            echo "<p><strong>Customer:</strong> " . htmlspecialchars($order['customer_name']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($order['customer_email']) . "</p>";
        }
        
        echo "<p><strong>Customer ID:</strong> " . htmlspecialchars($order['customer_id']) . "</p>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<p><strong>Date:</strong> " . date('d/m/Y H:i', strtotime($order['order_date'])) . "</p>";
        echo "<p><strong>Status:</strong> ";
        
        // Status badge
        switch($order['status']) {
            case 'pending':
                echo "<span class='badge bg-warning'><i class='fas fa-clock me-1'></i>Pending</span>";
                break;
            case 'processing':
                echo "<span class='badge bg-info'><i class='fas fa-cog me-1'></i>Processing</span>";
                break;
            case 'completed':
                echo "<span class='badge bg-success'><i class='fas fa-check me-1'></i>Completed</span>";
                break;
            case 'cancelled':
                echo "<span class='badge bg-danger'><i class='fas fa-times me-1'></i>Cancelled</span>";
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
        echo "<th>Code</th>";
        echo "<th>Make</th>";
        echo "<th>Model</th>";
        echo "<th>Year</th>";
        echo "<th>Category</th>";
        echo "<th class='text-center'>Quantity</th>";
        echo "<th class='text-end'>Price</th>";
        echo "<th class='text-end'>Subtotal</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($details as $detail) {
            $subtotal = $detail['quantity'] * $detail['price'];
            echo "<tr>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['code']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['make']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['model']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['year']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['category']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($detail['quantity']) . "</td>";
            echo "<td class='text-center'>$" . number_format($detail['price'], 2) . "</td>";
            echo "<td class='text-center'>$" . number_format($subtotal, 2) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "<tfoot class='table-light'>";
        echo "<tr>";
        echo "<td colspan='6' class='text-end'><strong>Total:</strong></td>";
        echo "<td class='text-end'><strong>$" . number_format($order['total'], 2) . "</strong></td>";
        echo "</tr>";
        echo "</tfoot>";
        echo "</table>";
        echo "</div>";

        // Additional information or notes
        if (!empty($order['notes'])) {
            echo "<div class='mt-3'>";
            echo "<h6>Notes:</h6>";
            echo "<p>" . nl2br(htmlspecialchars($order['notes'])) . "</p>";
            echo "</div>";
        }
    } else {
        error_log("No order found for ID: " . $order_id . " and customer: " . $customer_id);
        echo "<div class='alert alert-danger'>No order found for ID: " . $order_id . " and customer: " . $customer_id . "</div>";
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error getting order details.</div>";
}
?> 