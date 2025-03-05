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
    <title>Carrito de Compras</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="delivery.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <a href="./ordenes.php" title="Órdenes" style="color: white;">
                <i class="fa-regular fa-file-powerpoint fa-2xl"></i>
                <div class="nav-label text-center">Pedidos</div>
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

<div class="container mt-3">
    <div class="row mt-4">
        <div class="col-12">
            <div class="thank-you-section text-center p-4 bg-light rounded shadow-sm">
                <div class="thank-you-icon mb-3">
                    <i class="fa-regular fa-thumbs-up fa-3x text-primary"></i>
                </div>
                <h2 class="display-6 mb-3 text-primary">¡Gracias por su pedido!</h2>
                <div class="instructions-container bg-white p-3 rounded">      
                    <p>Por favor revise su pedido y confirmelo con el boton correspondiente, solo así se procesara inmediatamente. Gracias !</p>

                </div>
            </div>
        </div>
    </div>
    
    <?php
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'success') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Pedido procesado exitosamente. Se ha enviado el PDF por correo.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        } else if ($_GET['message'] === 'error') {
            $error = isset($_GET['error']) ? $_GET['error'] : 'Error desconocido';
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error al procesar el pedido: ' . htmlspecialchars($error) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
    }
    ?>
    
    <div class="table-responsive">
        <table id="carritoTable" class="table table-striped table-bordered display responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th class="text-center">Código</th>
                    <th class="text-center">Marca</th>
                    <th class="text-center">Modelo</th>
                    <th class="text-center">Precio</th>
                    <th class="text-center">Existencia</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once '../conexion_delivery.php';

                $conn = mysqli_connect($servername, $username, $password, $dbname);
                if (!$conn) {
                    die("Connection failed: " . mysqli_connect_error());
                }

                $total = 0;
                
                if (!empty($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $productId => $item) {
                        if ($item['cantidad'] > 0) {
                            $sql = "SELECT numero_parte, marca, modelo, precio, existencia 
                                   FROM productos 
                                   WHERE id = ?";
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $productId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($row = $result->fetch_assoc()) {
                                $subtotal = $item['cantidad'] * $row['precio'];
                                $total += $subtotal;
                                
                                echo "<tr>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['numero_parte']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['marca']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['modelo']) . "</td>";
                                echo "<td class='text-center'>$" . number_format($row['precio'], 2) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($row['existencia']) . "</td>";
                                echo "<td class='text-center'>" . htmlspecialchars($item['cantidad']) . "</td>";
                                echo "<td class='text-center'>$" . number_format($subtotal, 2) . "</td>";
                                echo "<td class='text-center'>
                                        <button class='btn btn-sm btn-danger' onclick='removeFromCart(\"$productId\")'>
                                            <i class='fas fa-trash'></i>
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        }
                    }
                }
                $conn->close();
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end"><strong>Total:</strong></td>
                    <td colspan="2"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                </tr>                
            </tfoot>
        </table>
    </div>
    
    <div class="d-flex justify-content-center mt-3">
        <button class="btn btn-secondary me-2" onclick="window.location.href='./inicio.php'">
            <i class="fas fa-arrow-left"></i> Seguir Comprando
        </button>
        <button class="btn btn-primary" onclick="confirmarPedido()">
            <i class="fas fa-check"></i> Confirmar Pedido
        </button>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#carritoTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger',
                title: 'Carrito de Compras',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success',
                title: 'Carrito de Compras',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn btn-info',
                title: 'Carrito de Compras',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
        language: {
            "decimal": ",",
            "thousands": ".",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros en total)",
            "infoThousands": ",",
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": ordenar de manera ascendente",
                "sortDescending": ": ordenar de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad",
                "collection": "Colección",
                "colvisRestore": "Restaurar visibilidad",
                "copyKeys": "Presione ctrl o u2318 + C para copiar los datos de la tabla al portapapeles del sistema.<br><br>Para cancelar, haga clic en este mensaje o presione escape.",
                "copySuccess": {
                    "1": "Copiada 1 fila al portapapeles",
                    "_": "Copiadas %ds filas al portapapeles"
                },
                "copyTitle": "Copiar al portapapeles",
                "csv": "CSV",
                "excel": "Excel",
                "pageLength": {
                    "-1": "Mostrar todas las filas",
                    "_": "Mostrar %d filas"
                },
                "pdf": "PDF",
                "print": "Imprimir"
            },
            "autoFill": {
                "cancel": "Cancelar",
                "fill": "Llenar las celdas con <i>%d</i>",
                "fillHorizontal": "Llenar las celdas horizontalmente",
                "fillVertical": "Llenar las celdas verticalmente"
            },
            "searchBuilder": {
                "add": "Añadir condición",
                "clearAll": "Borrar todo",
                "condition": "Condición",
                "data": "Datos",
                "button": {
                    "_": "Búsqueda avanzada (%d)",
                    "0": "Búsqueda avanzada"
                },
                "deleteTitle": "Eliminar regla de filtrado",
                "leftTitle": "Criterios anidados",
                "logicAnd": "Y",
                "logicOr": "O",
                "rightTitle": "Criterios externos",
                "title": {
                    "_": "Búsqueda avanzada (%d)",
                    "0": "Búsqueda avanzada"
                },
                "value": "Valor"
            },
            "searchPanes": {
                "clearMessage": "Borrar todo",
                "collapse": {
                    "_": "Paneles de búsqueda (%d)",
                    "0": "Paneles de búsqueda"
                },
                "count": "{total}",
                "countFiltered": "{shown} ({total})",
                "emptyPanes": "Sin paneles de búsqueda",
                "loadMessage": "Cargando paneles de búsqueda",
                "title": "Filtros activos - %d",
                "showMessage": "Mostrar todos",
                "collapseMessage": "Colapsar todos"
            },
            "select": {
                "1": "%d fila seleccionada",
                "_": "%d filas seleccionadas",
                "cells": {
                    "1": "1 celda seleccionada",
                    "_": "%d celdas seleccionadas"
                },
                "columns": {
                    "1": "1 columna seleccionada",
                    "_": "%d columnas seleccionadas"
                }
            },
            "datetime": {
                "previous": "Anterior",
                "next": "Proximo",
                "hours": "Horas",
                "minutes": "Minutos",
                "seconds": "Segundos",
                "unknown": "-",
                "amPm": [
                    "am",
                    "pm"
                ],
                "weekdays": [
                    "Dom",
                    "Lun",
                    "Mar",
                    "Mie",
                    "Jue",
                    "Vie",
                    "Sab"
                ],
                "months": [
                    "Enero",
                    "Febrero",
                    "Marzo",
                    "Abril",
                    "Mayo",
                    "Junio",
                    "Julio",
                    "Agosto",
                    "Septiembre",
                    "Octubre",
                    "Noviembre",
                    "Diciembre"
                ]
            },
            "editor": {
                "close": "Cerrar",
                "create": {
                    "button": "Nuevo",
                    "title": "Crear nueva entrada",
                    "submit": "Crear"
                },
                "edit": {
                    "button": "Editar",
                    "title": "Editar entrada",
                    "submit": "Actualizar"
                },
                "remove": {
                    "button": "Eliminar",
                    "title": "Eliminar",
                    "submit": "Eliminar",
                    "confirm": {
                        "_": "¿Está seguro que desea eliminar %d filas?",
                        "1": "¿Está seguro que desea eliminar 1 fila?"
                    }
                },
                "error": {
                    "system": "Ha ocurrido un error en el sistema (<a target=\"\\\" rel=\"nofollow\" href=\"\\\">Más información&lt;/a&gt;)."
                },
                "multi": {
                    "title": "Múltiples valores",
                    "info": "Los elementos seleccionados contienen diferentes valores para esta entrada.",
                    "restore": "Deshacer cambios",
                    "noMulti": "Esta entrada puede ser editada individualmente, pero no como parte de un grupo."
                }
            },
            "stateRestore": {
                "creationModal": {
                    "button": "Crear",
                    "title": "Crear nuevo estado",
                    "order": "Orden",
                    "scroller": "Posición de desplazamiento",
                    "search": "Búsqueda",
                    "select": "Seleccionar",
                    "columns": {
                        "search": "Búsqueda de columna",
                        "visible": "Visibilidad de columna"
                    }
                },
                "duplicateError": "Ya existe un estado con este nombre.",
                "emptyError": "El nombre no puede estar vacío.",
                "emptyStates": "No hay estados guardados",
                "removeConfirm": "¿Seguro que desea eliminar %s?",
                "removeError": "Error al eliminar el estado.",
                "removeJoiner": "y",
                "removeSubmit": "Eliminar",
                "renameButton": "Renombrar",
                "renameLabel": "Nuevo nombre para %s:",
                "duplicateButton": "Duplicar",
                "format": "Formato"
            }
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        order: [[0, 'asc']]
    });
});

function removeFromCart(productId) {
    if (confirm('¿Está seguro de que desea eliminar este producto del carrito?')) {
        $.ajax({
            url: './remove_from_cart.php',
            type: 'POST',
            data: {
                product_id: productId
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr, status, error) {
                alert('Error al eliminar el producto del carrito');
            }
        });
    }
}

function confirmarPedido() {
    // Get cart data from PHP session
    let cartData = <?php echo json_encode($_SESSION['cart'] ?? []); ?>;
    
    // Debug logging
    console.log('Cart data:', cartData);

    // Check if cart is empty
    if (!cartData || Object.keys(cartData).length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Carrito Vacío',
            text: 'Por favor agregue productos al carrito antes de confirmar el pedido.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Show confirmation dialog
    Swal.fire({
        title: '¿Confirmar Pedido?',
        text: "¿Está seguro que desea confirmar su pedido?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, confirmar pedido',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send order to server
            $.ajax({
                url: 'procesar_pedido.php',
                type: 'POST',
                data: { 
                    cart: JSON.stringify(cartData)
                },
                success: function(response) {
                    console.log('Server response:', response);
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Pedido Confirmado!',
                                text: 'Su pedido ha sido registrado exitosamente.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = './ordenes.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Hubo un error al confirmar el pedido.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    } catch (e) {
                        console.error('Parse error:', e);
                        console.log('Raw response:', response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un error al procesar la respuesta del servidor.',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.log('XHR:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al enviar el pedido al servidor.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    });
}
</script>

</body>
</html>