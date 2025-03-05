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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="delivery.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<header>
    <nav class="container navbar">
        <div class="nav-item text-center">
            <a href="inicio.php" title="Inicio" style="color: white;">
                <i class="fa-solid fa-home fa-2xl"></i>
                <div class="nav-label text-center">Inicio</div>
            </a>
        </div>
        <div class="nav-item text-center">
            <a href="ordenes.php" title="Órdenes" style="color: white;">
                <i class="fa-regular fa-file-powerpoint fa-2xl"></i>
                <div class="nav-label text-center">Pedidos</div>
            </a>
        </div>
        <div class="nav-item text-center">
            <a href="estado_de_cuenta.php" title="Estado de Cuenta" style="color: white;">
                <i class="fa-solid fa-dollar-sign fa-2xl"></i>
                <div class="nav-label text-center">Pagos</div>
            </a>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <h2 class="text-center mb-4">Carrito de Compras</h2>
    
    <div class="table-responsive">
        <table id="carrito" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">Código</th>
                    <th class="text-center">Marca</th>
                    <th class="text-center">Modelo</th>
                    <th class="text-center">Precio</th>
                    <th class="text-center">Existencia</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Subtotal</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once '../conexion_delivery.php';

                $conn = mysqli_connect($servername, $username, $password, $dbname);

                if (!$conn) {
                    die("Connection failed: " . mysqli_connect_error());
                }

                // Get the cart items from the session
                session_start();
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = array();
                }

                $total = 0;
                
                // In the table body section of carrito.php
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
    
    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-secondary me-2" onclick="window.location.href='inicio.php'">
            <i class="fas fa-arrow-left"></i> Seguir Comprando
        </button>
        <button class="btn btn-primary" onclick="procesarPedido()">
            Procesar Pedido <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>

<script>
function removeFromCart(productId) {
    if (confirm('¿Está seguro de que desea eliminar este producto del carrito?')) {
        $.ajax({
            url: 'remove_from_cart.php',
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

function procesarPedido() {
    window.location.href = 'procesar_pedido.php';
}
</script>

</body>
</html>