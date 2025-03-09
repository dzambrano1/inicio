<?php
session_start();
require_once './conexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ./login.php');
    exit;
}

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get orders with payment status - all orders for admin, only customer's orders for regular users 
if ($is_admin) {
    // Admin sees all customer orders
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_date, o.total, o.customer_id,
               c.fullName as customer_name, c.email as customer_email,
               COALESCE(SUM(p.amount), 0) as paid_amount,
               o.total - COALESCE(SUM(p.amount), 0) as pending_amount
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
} else {
    // Regular customer only sees their own orders
    $customer_id = $_SESSION['customer_id'];
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_date, o.total, o.customer_id,
               COALESCE(SUM(p.amount), 0) as paid_amount,
               o.total - COALESCE(SUM(p.amount), 0) as pending_amount
        FROM orders o
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.customer_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
}

$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="./durafrenos.css" rel="stylesheet">
    <style>
        /* Professional styling for pagosTable */
        #pagosTable {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        #pagosTable thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 14px 10px;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }
        
        #pagosTable tbody tr {
            transition: all 0.2s ease;
        }
        
        #pagosTable tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        #pagosTable tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        #pagosTable .badge {
            padding: 6px 10px;
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 6px;
        }
        
        .badge.bg-success {
            background-color: #10b981 !important;
        }
        
        .badge.bg-warning {
            background-color: #f59e0b !important;
            color: #fff;
        }
        
        .btn-group .btn {
            margin: 0 2px;
            border-radius: 6px;
        }
        
        /* DataTables styling */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 6px 12px;
            margin-left: 8px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px;
            padding: 5px 14px;
            margin: 0 3px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd;
            border-color: #0d6efd;
            color: white !important;
        }
        
        .dataTables_info {
            font-size: 0.9rem;
            color: #6c757d;
            padding-top: 15px;
        }
        
        /* Card styling */
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-body {
            padding: 20px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="container navbar">
            <div class="nav-item text-center">
            <a class="navbar-brand" href="catalog.php">
                <img src="/images/durafrenos-logo-removebg-preview.png" alt="Durafrenos" style="height: 80px;">
            </a>
            </div>

        </nav>
    </header>

    <div class="container mt-4">
        <h2 class="mb-4"><?php echo $is_admin ? 'Administración de Pagos' : 'Mis Pagos'; ?></h2>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pedidos y Pagos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pagosTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="fw-bold text-center">Pedido</th>
                                        <?php if ($is_admin): ?>
                                        <th class="fw-bold text-center">Cliente</th>
                                        <?php endif; ?>
                                        <th class="fw-bold text-center">Fecha</th>
                                        <th class="fw-bold text-center">Total</th>
                                        <th class="fw-bold text-center">Pagado</th>
                                        <th class="fw-bold text-center">Pendiente</th>
                                        <th class="fw-bold text-center">Estatus</th>
                                        <th class="fw-bold text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $order['order_id']; ?></td>
                                        <?php if ($is_admin): ?>
                                        <td class="text-center"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <?php endif; ?>
                                        <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td class="text-center">$<?php echo number_format($order['total'], 2); ?></td>
                                        <td class="text-center">$<?php echo number_format($order['paid_amount'], 2); ?></td>
                                        <td class="text-center">$<?php echo number_format($order['pending_amount'], 2); ?></td>
                                        <td class="text-center">
                                            <?php if ($order['pending_amount'] <= 0): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Pagado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class="fas fa-clock me-1"></i> Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($order['pending_amount'] > 0): ?>
                                                <button class="btn btn-primary btn-sm" onclick="realizarPago(<?php echo $order['order_id']; ?>, <?php echo $order['pending_amount']; ?><?php echo $is_admin ? ", '".htmlspecialchars($order['customer_name'], ENT_QUOTES)."'" : ""; ?>)">
                                                    <i class="fas fa-credit-card"></i> Pagar
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-info btn-sm" onclick="verDetallesPago(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-history"></i> Historial
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pagos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="orderId" name="order_id">
                        <?php if ($is_admin): ?>
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="customerName" readonly>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Cantidad a Pagar</label>
                            <input type="text" class="form-control" id="amount" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metodo de Pago</label>
                            <select class="form-select" id="paymentMethod" name="payment_method" required>
                                <option value="">Selecciona un metodo</option>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta de Credito/Debito</option>
                                <option value="transfer">Transferencia</option>
                            </select>
                        </div>
                        <div class="mb-3" id="referenceNumberGroup" style="display: none;">
                            <label class="form-label">Numero de Referencia</label>
                            <input type="text" class="form-control" id="referenceNumber" name="reference_number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="procesarPago()">Procesar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Historial de Pagos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paymentHistory">
                    <!-- Payment history will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let paymentModal;
        let historyModal;

        document.addEventListener('DOMContentLoaded', function() {
            paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
            
            // Initialize DataTables with search and order functionality
            $('#pagosTable').DataTable({
                "language": {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron registros",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "columnDefs": [
                    // Disable ordering and searching for the last column (Acciones)
                    { "orderable": false, "searchable": false, "targets": -1 }
                ],
                "order": [[0, 'desc']], // Default order by order ID (descending)
                "responsive": true,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                "pageLength": 25,
                "dom": '<"row mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                "stateSave": true
            });
        });

        function realizarPago(orderId, amount, customerName) {
            document.getElementById('orderId').value = orderId;
            document.getElementById('amount').value = '$' + amount.toFixed(2);
            
            <?php if ($is_admin): ?>
            // Set customer name for admin users
            if (customerName) {
                document.getElementById('customerName').value = customerName;
            }
            <?php endif; ?>
            
            paymentModal.show();
        }

        function verDetallesPago(orderId) {
            $.ajax({
                url: './get_payment_history.php',
                type: 'POST',
                data: { order_id: orderId },
                success: function(response) {
                    document.getElementById('paymentHistory').innerHTML = response;
                    historyModal.show();
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar el historial de pagos'
                    });
                }
            });
        }

        document.getElementById('paymentMethod').addEventListener('change', function() {
            const referenceGroup = document.getElementById('referenceNumberGroup');
            referenceGroup.style.display = this.value === 'transfer' ? 'block' : 'none';
        });

        function procesarPago() {
            const formData = new FormData(document.getElementById('paymentForm'));
            
            // Show loading indicator
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espera mientras procesamos tu pago',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: './process_payments.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'  // Explicitly mark as AJAX request
                },
                success: function(data) {
                    Swal.close(); // Close loading indicator
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Pago Procesado!',
                            text: 'El pago ha sido registrado exitosamente.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            paymentModal.hide();
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al procesar el pago'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close(); // Close loading indicator
                    
                    console.error("AJAX Error:", status, error);
                    console.log("Status Code:", xhr.status);
                    console.log("Content Type:", xhr.getResponseHeader('Content-Type'));
                    
                    // Log the first 1000 characters of the response for debugging
                    if (xhr.responseText) {
                        console.log("Response Preview:", xhr.responseText.substring(0, 1000));
                    }
                    
                    let errorMsg = 'Error al enviar el pago al servidor';
                    
                    if (xhr.responseText) {
                        if (xhr.responseText.indexOf('<!DOCTYPE') !== -1) {
                            errorMsg = 'El servidor ha devuelto una página HTML en lugar de datos JSON. Verifica tu sesión o contacta al administrador.';
                        } else if (xhr.responseText.indexOf('Fatal error') !== -1) {
                            errorMsg = 'Error fatal en el servidor. Por favor contacte al administrador.';
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                        footer: '<a href="#" onclick="console.log(\'Full Response:\', document.querySelector(\'pre.response-text\').textContent)">Ver detalles del error en consola</a>',
                        didRender: () => {
                            // Add hidden pre element with full response for debugging
                            const footer = document.querySelector('.swal2-footer');
                            if (footer && xhr.responseText) {
                                const pre = document.createElement('pre');
                                pre.className = 'response-text';
                                pre.style.display = 'none';
                                pre.textContent = xhr.responseText;
                                footer.appendChild(pre);
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>
</html> 