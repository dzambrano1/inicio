<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ./login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="delivery.css">
    
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
            <a href="./inicio.php" title="Inicio" style="color: white;">
                <i class="fa-solid fa-home fa-2xl"></i>
                <div class="nav-label text-center">Inicio</div>
            </a>
        </div>
        <div class="nav-item text-center">
            <a href="./carrito.php" title="Carrito" style="color: white;">
                <i class="fa-solid fa-shopping-cart fa-2xl"></i>
                <div class="nav-label text-center">Carrito</div>
            </a>
        </div>
        <div class="nav-item text-center">
            <a href="./estado_de_cuenta.php" title="Estado de Cuenta" style="color: white;">
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
                    <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Mis Pedidos</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="ordenesTable" class="table table-striped table-bordered display responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Orden ID</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once '../conexion_delivery.php';

                                $conn = mysqli_connect($servername, $username, $password, $dbname);
                                if (!$conn) {
                                    die("Connection failed: " . mysqli_connect_error());
                                }

                                $customer_id = $_SESSION['customer_id'];
                                
                                $sql = "SELECT order_id, order_date, status, total 
                                       FROM orders 
                                       WHERE customer_id = ? 
                                       ORDER BY order_date DESC";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $customer_id);
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
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['order_date'])) . "</td>";
                                    echo "<td><span class='badge bg-{$statusClass}'><i class='fas fa-{$statusIcon} me-1'></i>{$statusText}</span></td>";
                                    echo "<td>$" . number_format($row['total'], 2) . "</td>";
                                    echo "<td class='text-center'>
                                            <button class='btn btn-sm btn-info' onclick='verDetalles(\"" . $row['order_id'] . "\")'>
                                                <i class='fas fa-eye'></i> Ver Detalles
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

    <!-- Modal para detalles del pedido -->
    <div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detallesModalLabel">Detalles del Pedido</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detallesModalBody">
                    <!-- Los detalles se cargarán aquí dinámicamente -->
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
                title: 'Mis Pedidos',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success',
                title: 'Mis Pedidos',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn btn-info',
                title: 'Mis Pedidos',
                exportOptions: {
                    columns: [0, 1, 2, 3]
                }
            }
        ],
        language: {
            "decimal": ",",
            "thousands": ".",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros en total)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        order: [[1, 'desc']]
    });
});

function verDetalles(orderId) {
    console.log('Fetching details for order:', orderId);
    $.ajax({
        url: 'get_order_details.php',
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
            alert('Error al cargar los detalles del pedido: ' + error);
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