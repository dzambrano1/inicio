<?php
// Start session if not already started 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ./login.php");
    exit();
}

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="durafrenos.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
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
            <a href="./payments.php" title="Estado de Cuenta" style="color: white;">
                <i class="fa-solid fa-dollar-sign fa-2xl"></i>
                <div class="nav-label text-center">Pagos</div>
            </a>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Pedidos</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ordenesTable" class="table table-striped table-bordered display responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center">Pedido No</th>
                                    <?php if ($is_admin): ?>
                                    <th class="text-center">Cliente</th>
                                    <?php endif; ?>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once './conexion.php';

                                $conn = mysqli_connect($servername, $username, $password, $dbname);
                                if (!$conn) {
                                    die("Connection failed: " . mysqli_connect_error());
                                }

                                $customer_id = $_SESSION['customer_id'];
                                
                                if ($is_admin) {
                                    // Admin sees all orders
                                    $sql = "SELECT o.order_id, o.order_date, o.status, o.total, c.fullName as customer_name 
                                           FROM orders o
                                           LEFT JOIN customers c ON o.customer_id = c.customer_id
                                           ORDER BY o.order_date DESC";
                                    
                                    $stmt = $conn->prepare($sql);
                                } else {
                                    // Regular customer only sees their own orders
                                    $sql = "SELECT order_id, order_date, status, total 
                                           FROM orders 
                                           WHERE customer_id = ? 
                                           ORDER BY order_date DESC";
                                    
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("s", $customer_id);
                                }
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while ($row = $result->fetch_assoc()) {
                                    $statusClass = '';
                                    $statusIcon = '';
                                    
                                    switch($row['status']) {
                                        case 'pending':
                                            $statusClass = 'warning';
                                            $statusIcon = 'clock';
                                            $statusText = 'Pendiente';
                                            break;
                                        case 'processing':
                                            $statusClass = 'info';
                                            $statusIcon = 'cog';
                                            $statusText = 'Procesando';
                                            break;
                                        case 'completed':
                                            $statusClass = 'success';
                                            $statusIcon = 'check';
                                            $statusText = 'Completado';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'danger';
                                            $statusIcon = 'times';
                                            $statusText = 'Cancelado';
                                            break;
                                        default:
                                            $statusClass = 'secondary';
                                            $statusIcon = 'question';
                                            $statusText = $row['status'];
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
                                    if ($is_admin) {
                                        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                    }
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['order_date'])) . "</td>";
                                    echo "<td><span class='badge bg-{$statusClass}'><i class='fas fa-{$statusIcon} me-1'></i>{$statusText}</span></td>";
                                    echo "<td>$" . number_format($row['total'], 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button class='btn btn-outline-primary' onclick='verDetalles(\"" . $row['order_id'] . "\")'>
                                                <i class='fas fa-eye'></i> Pedido
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for order details -->
    <div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detallesModalLabel">Detalles del Pedido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detallesModalBody">
                    <!-- Los detalles se cargarán dinámicamente aquí -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#ordenesTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger',
                title: 'Orders',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success',
                title: 'Orders',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-info',
                title: 'Orders',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            }
        ],
        language: {
            "decimal": ",",
            "thousands": ".",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "infoEmpty": "Showing 0 to 0 of 0 records",
            "infoFiltered": "(filtered from _MAX_ total records)",
            "lengthMenu": "Show _MENU_ records",
            "loadingRecords": "Loading...",
            "processing": "Processing...",
            "search": "Search:",
            "zeroRecords": "No records found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        },
        order: [[1, 'desc']]
    });
});

function verDetalles(orderId) {
    console.log('Fetching details for order:', orderId);
    $.ajax({
        url: './get_order_details.php',
        type: 'POST',
        data: { order_id: orderId },
        success: function(response) {
            console.log('Response received:', response);
            $('#detallesModalBody').html(response);
            $('#detallesModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.log('Status:', status);
            console.log('Response:', xhr.responseText);
            alert('Error loading order details: ' + error);
        }
    });
}

// Update the modal initialization
$('#detallesModal').on('shown.bs.modal', function () {
    $(this).find('[aria-hidden="true"]').attr('aria-hidden', 'false');
});

$('#detallesModal').on('hidden.bs.modal', function () {
    $(this).find('[aria-hidden="false"]').attr('aria-hidden', 'true');
});
</script>

</body>
</html> 