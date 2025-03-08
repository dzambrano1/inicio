<?php
require_once './auth.php';
requireLogin();

require_once './conexion.php';

$conn = new PDO("mysql:host=localhost;dbname=durafrenos", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->exec("SET NAMES utf8");

// Get user information 
$user_id = $_SESSION['customer_id'];
$user_name = $_SESSION['fullName'];
$user_role = $_SESSION['role'];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito Compras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="durafrenos.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="container navbar">
            <div class="nav-item text-center">
                <a href="./home.php" title="home" style="color: white;">
                    <i class="fa-solid fa-home fa-2xl"></i>
                    <div class="nav-label text-center">Inicio</div>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./orders.php" title="Orders" style="color: white;">
                    <i class="fa-solid fa-clipboard-list fa-2xl"></i>
                    <div class="nav-label text-center">Pedidos</div>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./payments.php" title="Payments" style="color: white;">
                    <i class="fa-solid fa-money-bill-wave fa-2xl"></i>
                    <div class="nav-label text-center">Pagos</div>
                </a>
            </div>
            <?php if ($user_role === 'admin'): ?>
            <div class="nav-item text-center">
                    <a href="./customer_registration.php" title="Registration" style="color: white;">
                    <i class="fa-solid fa-user-plus fa-2xl"></i>
                    <div class="nav-label text-center">Registro</div>
                </a>
            </div>
            <?php endif; ?>
            <div class="nav-item text-center">
                <a href="./cart.php" title="Shopping Cart" style="color: white; position: relative;">
                    <i class="fa-solid fa-cart-shopping fa-2xl"></i>
                    <div class="nav-label text-center">Carrito</div>
                    <?php if (count($_SESSION['cart']) > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo count($_SESSION['cart']); ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./logout.php" title="Logout" style="color: white;">
                    <i class="fa-solid fa-sign-out-alt fa-2xl"></i>
                    <div class="nav-label text-center">Salir</div>
                </a>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Carrito Compras</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tu carrito está vacío</p>
                                <a href="./home.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag"></i> Seguir Comprando
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Modelo</th>
                                            <th class="text-center">Codigo</th>
                                            <th class="text-center">Precio</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Sub-total</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                                            <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                            <tr>
                                                <td class="text-center"><?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($item['code']); ?></td>
                                                <td class="text-center">$<?php echo number_format($item['price'], 2); ?></td>
                                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                <td class="text-center">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-danger btn-sm" onclick="removeItem(<?php echo $index; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                            <td colspan="2"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <a href="./home.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Seguir Comprando
                                </a>
                                <button class="btn btn-primary" onclick="proceedToCheckout()">
                                    <i class="fas fa-check"></i> Procesar Pedido
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateQuantity(index, action) {
            const input = document.getElementById(`qty_${index}`);
            let currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            
            if (action === 'increase' && currentValue < maxValue) {
                currentValue = currentValue + 1;
            } else if (action === 'decrease' && currentValue > 1) {
                currentValue = currentValue - 1;
            }
            
            input.value = currentValue;
            updateCartItem(index, currentValue);
        }

        function updateCartItem(index, quantity) {
            fetch('./update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `index=${index}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al actualizar el cart'
                });
            });
        }

        function removeItem(index) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Este producto será eliminado del cart. Si necesitas cambiar la cantidad, puedes volver a agregarlo desde el catálogo.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('./remove_from_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `index=${index}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: 'Producto eliminado del cart',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al eliminar el producto'
                        });
                    });
                }
            });
        }

        function proceedToCheckout() {
            fetch('./order_processing.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Pedido procesado correctamente',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = './orders.php';
                        });
                    } else {
                        let errorMessage = data.message;
                        if (data.debug) {
                            errorMessage += '\n\nTechnical Details:\n';
                            errorMessage += `Code: ${data.debug.error_code}\n`;
                            errorMessage += `File: ${data.debug.error_file}\n`;
                            errorMessage += `Line: ${data.debug.error_line}`;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al procesar el pedido: ' + error.message,
                        confirmButtonText: 'OK'
                    });
                });
        }
    </script>
</body>
</html>