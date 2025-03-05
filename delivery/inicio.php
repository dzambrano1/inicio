<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
    <link rel="apple-touch-icon" sizes="180x180" href="./images/android-chrome-192x192.png">
    <link rel="icon" type="./images/chrome-192x192.png" sizes="32x32" href="./images/android-chrome-512x512.png">
    <link rel="icon" type="./images/chrome-512x512.png" sizes="16x16" href="./images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="delivery.css">    
</head>
<body>

    <header>
        <nav class="container navbar">
            <div class="nav-item text-center">
                <a href="./ordenes.php" title="Órdenes" style="color: white;">
                <i class="fa-regular fa-file-powerpoint fa-2xl"></i>
                    <div class="nav-label text-center">Pedidos</div>
                </a>
            </div>
            <div class="nav-item text-center">
                <a href="./carrito.php" title="Carrito" style="color: white;">
                <i class="fa-solid fa-cart-arrow-down fa-2xl" style="color: #ffffff;"></i>
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

<?php

require_once '../conexion_delivery.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filters
$filters = array();
$filterValues = array(    
    'linea' => array(),
    'marca' => array(),
    'ano' => array(),
    'modelo' => array(),
    'numero_parte' => array()
);

// At the beginning of your PHP code, create the filter logic
$where_conditions = [];
$params = [];

// Build conditions in order (cascade from left to right)
if (!empty($_GET['linea'])) {
    $where_conditions[] = "linea = ?";
    $params[] = $_GET['linea'];
}
if (!empty($_GET['marca'])) {
    $where_conditions[] = "marca = ?";
    $params[] = $_GET['marca'];
}
if (!empty($_GET['modelo'])) {
    $where_conditions[] = "modelo = ?";
    $params[] = $_GET['modelo'];
}
if (!empty($_GET['ano'])) {
    $where_conditions[] = "ano = ?";
    $params[] = $_GET['ano'];
}
if (!empty($_GET['numero_parte'])) {
    $where_conditions[] = "numero_parte = ?";
    $params[] = $_GET['numero_parte'];
}

// Create the WHERE clause
$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Prepare and execute the query using prepared statements
$sql = "SELECT numero_parte, image, linea, marca, modelo, ano, precio, existencia
        FROM productos" . $where_clause . " ORDER BY numero_parte ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Debug query
$debug_sql = $sql;
foreach ($params as $param) {
    $debug_sql = preg_replace('/\?/', "'" . $param . "'", $debug_sql, 1);
}
error_log("Executed query: " . $debug_sql);

// Use this same $result for both your cards and DataTable


// Fetch counts for each filter category based on current filters

// linea Counts
$lineaCountsQuery = "SELECT linea, COUNT(*) as count FROM productos";
if (!empty($whereClause)) {
    $lineaCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$lineaCountsQuery .= " GROUP BY linea";

$lineaCountsResult = $conn->query($lineaCountsQuery);

$lineaLabels = [];
$lineaCounts = [];

if ($lineaCountsResult && $lineaCountsResult->num_rows > 0) {
    while ($row = $lineaCountsResult->fetch_assoc()) {
        $lineaLabels[] = $row['linea'];
        $lineaCounts[] = $row['count'];
    }
} else {
    // Handle case when there are no records
    $lineaLabels = ['No Data'];
    $lineaCounts = [0];
}

// marca Counts
$marcaCountsQuery = "SELECT marca, COUNT(*) as count FROM productos";
if (!empty($whereClause)) {
    $marcaCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$marcaCountsQuery .= " GROUP BY marca";

$marcaCountsResult = $conn->query($marcaCountsQuery);

$marcaLabels = [];
$marcaCounts = [];

if ($marcaCountsResult && $marcaCountsResult->num_rows > 0) {
    while ($row = $marcaCountsResult->fetch_assoc()) {
        $marcaLabels[] = $row['marca'];
        $marcaCounts[] = $row['count'];
    }
} else {
    $marcaLabels = ['No Data'];
    $marcaCounts = [0];
}

// modelo Counts
$modeloCountsQuery = "SELECT modelo, COUNT(*) as count FROM productos";
if (!empty($whereClause)) {
    $modeloCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$modeloCountsQuery .= " GROUP BY modelo";

$modeloCountsResult = $conn->query($modeloCountsQuery);

$modeloLabels = [];
$modeloCounts = [];

if ($modeloCountsResult && $modeloCountsResult->num_rows > 0) {
    while ($row = $modeloCountsResult->fetch_assoc()) {
        $modeloLabels[] = $row['modelo'];
        $modeloCounts[] = $row['count'];
    }
} else {
    $modeloLabels = ['No Data'];
    $modeloCounts = [0];
}

// anos Counts
$anoCountsQuery = "SELECT ano, COUNT(*) as count FROM productos";
if (!empty($whereClause)) {
    $anoCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$anoCountsQuery .= " GROUP BY ano";

$anoCountsResult = $conn->query($anoCountsQuery);

$anoLabels = [];
$anoCounts = [];

if ($anoCountsResult && $anoCountsResult->num_rows > 0) {
    while ($row = $anoCountsResult->fetch_assoc()) {
        $anoLabels[] = $row['ano'];
        $anoCounts[] = $row['count'];
    }
} else {
    $anoLabels = ['No Data'];
    $anoCounts = [0];
}

// numero_parte Counts
$numero_parteCountsQuery = "SELECT numero_parte, COUNT(*) as count FROM productos";
if (!empty($whereClause)) {
    $numero_parteCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$numero_parteCountsQuery .= " GROUP BY numero_parte";

$numero_parteCountsResult = $conn->query($numero_parteCountsQuery);

$numero_parteLabels = [];
$numero_parteCounts = [];

if ($numero_parteCountsResult && $numero_parteCountsResult->num_rows > 0) {
    while ($row = $numero_parteCountsResult->fetch_assoc()) {
        $numero_parteLabels[] = $row['numero_parte'];
        $numero_parteCounts[] = $row['count'];
    }
} else {
    $numero_parteLabels = ['No Data'];
    $numero_parteCounts = [0];
}

// Calculate totals for percentage calculations if needed
$totallinea = array_sum($lineaCounts);
$totalmarca = array_sum($marcaCounts);
$totalmodelo = array_sum($modeloCounts);
$totalano = array_sum($anoCounts);
$totalnumero_parte = array_sum($numero_parteCounts);

// Fetch numero_partes based on current filters
$numero_partesQuery = "SELECT numero_parte FROM productos";
if (!empty($whereClause)) {
    $numero_partesQuery .= " WHERE " . implode(' AND ', $where_conditions);
}
$numero_partesResult = $conn->query($numero_partesQuery);

$numero_partes = [];
if ($numero_partesResult && $numero_partesResult->num_rows > 0) {
    while ($row = $numero_partesResult->fetch_assoc()) {
        $numero_partes[] = "'" . $conn->real_escape_string($row['numero_parte']) . "'";
    }
}

// If no numero_partes are found, set to an array with a dummy value to prevent SQL errors
if (empty($numero_partes)) {
    $numero_partes[] = "'NONE'";
}

?>

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Link to the Favicon -->
<link rel="icon" href="images/Ganagram_icono.ico" type="image/x-icon">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<!--Bootstrap 5 Css -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Include Chart.js and Chart.js DataLabels Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<!-- jQuery Core (main library) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

<!-- DataTables JavaScript -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

<!-- DataTables Buttons CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<!-- DataTables Buttons JS -->
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Add these in the <head> section, after your existing DataTables CSS/JS -->
<!-- DataTables Buttons CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<!-- DataTables Buttons JS -->
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<link rel="stylesheet" href="./delivery.css">
</head>
<body>

<!-- Add this script after your buttons -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.scroll-icons-container button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default button behavior
            
            // Get the target section ID from data-bs-target attribute
            const targetId = this.getAttribute('data-bs-target');
            const targetElement = document.getElementById(targetId.substring(1)); // Remove the # from the ID
            
            if (targetElement) {
                // Smooth scroll to the target section
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // If using Bootstrap collapse, toggle it
                const bsCollapse = new bootstrap.Collapse(targetElement, {
                    toggle: true
                });
            }
        });
    });
});
</script>

<!-- Filtros de Productos -->

<div class="container mt-3 mb-4 text-center">
    <button type="button" class="btn agregar-producto" data-bs-toggle="modal" data-bs-target="#newEntryModal">
        <i class="fas fa-plus"></i>
    </button>
</div>

<div class="container filters-container" style="text-align: center;">
  <form method="GET" action="" class="filters-form" style="display: block;">
        <!-- Linea Filter -->
        <select name="linea" onchange="this.form.submit()" style="width: 170px;">
          <option value="">Lineas</option>
          <option value="Suspension" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'Suspension') ? 'selected' : ''; ?>>Suspension</option>
          <option value="Amortiguacion" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'Amortiguacion') ? 'selected' : ''; ?>>Amortiguacion</option>
          <option value="Mozos" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'mozos') ? 'selected' : ''; ?>>Mozos</option>
          <option value="Pastillas Semimetalicas" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'Pastillas Semimetalicas') ? 'selected' : ''; ?>>Pastillas Semimetalicas</option>
          <option value="Ciguenal" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'ciguenal') ? 'selected' : ''; ?>>Ciguenal</option>
          <option value="Tripoides" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'tripoides') ? 'selected' : ''; ?>>Tripoides</option>
          <option value="Rodamientos" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'rodamientos') ? 'selected' : ''; ?>>Rodamientos</option>
          <option value="Clutch" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'clutch') ? 'selected' : ''; ?>>Clutch</option>
          <option value="Bombas de Agua" <?php echo (isset($_GET['linea']) && $_GET['linea'] === 'bombas_de_agua') ? 'selected' : ''; ?>>Bombas de Agua</option>
        </select>
      <!-- Auto Marca Filter -->
      <select name="marca" onchange="this.form.submit()" style="width: 170px;">
        <option value="">Marca</option>
        <?php
        $marca_sql = "SELECT DISTINCT marca FROM productos";
        $where_conditions = [];
        
        if (!empty($_GET['linea'])) {
            $where_conditions[] = "linea = '" . $conn->real_escape_string($_GET['linea']) . "'";
        }
        
        if (!empty($where_conditions)) {
            $marca_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $result_marcas = $conn->query($marca_sql);
        while ($row = $result_marcas->fetch_assoc()) {
            $selected = (isset($_GET['marca']) && $_GET['marca'] === $row['marca']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($row['marca']) . "' $selected>" . htmlspecialchars($row['marca']) . "</option>";
        }
        ?>
      </select>
      <!-- modelo Filter -->
      <select name="modelo" onchange="this.form.submit()" style="width: 170px;">
        <option value="">Modelo</option>
        <?php
        $modelo_sql = "SELECT DISTINCT modelo FROM productos";
        $where_conditions = [];
        
        if (!empty($_GET['linea'])) {
            $where_conditions[] = "linea = '" . $conn->real_escape_string($_GET['linea']) . "'";
        }
        if (!empty($_GET['marca'])) {
            $where_conditions[] = "marca = '" . $conn->real_escape_string($_GET['marca']) . "'";
        }
        
        if (!empty($where_conditions)) {
            $modelo_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $result_modelos = $conn->query($modelo_sql);
        while ($row = $result_modelos->fetch_assoc()) {
            $selected = (isset($_GET['modelo']) && $_GET['modelo'] === $row['modelo']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($row['modelo']) . "' $selected>" . htmlspecialchars($row['modelo']) . "</option>";
        }
        ?>
      </select>
      <!-- Año Filter -->
      <select name="ano" onchange="this.form.submit()" style="width: 170px;">
        <option value="">Año</option>
        <?php
        $ano_sql = "SELECT DISTINCT ano FROM productos";
        $where_conditions = [];
        
        if (!empty($_GET['linea'])) {
            $where_conditions[] = "linea = '" . $conn->real_escape_string($_GET['linea']) . "'";
        }
        if (!empty($_GET['marca'])) {
            $where_conditions[] = "marca = '" . $conn->real_escape_string($_GET['marca']) . "'";
        }
        if (!empty($_GET['modelo'])) {
            $where_conditions[] = "modelo = '" . $conn->real_escape_string($_GET['modelo']) . "'";
        }
        
        if (!empty($where_conditions)) {
            $ano_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $result_anos = $conn->query($ano_sql);
        while ($row = $result_anos->fetch_assoc()) {
            $selected = (isset($_GET['ano']) && $_GET['ano'] === $row['ano']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($row['ano']) . "' $selected>" . htmlspecialchars($row['ano']) . "</option>";
        }
        ?>
      </select>
      <!-- numero_parte Filter -->
      <select name="numero_parte" onchange="this.form.submit()" style="width: 170px;">
        <option value="">Codigo</option>
        <?php
        $numero_parte_sql = "SELECT DISTINCT numero_parte FROM productos";
        $where_conditions = [];
        
        if (!empty($_GET['linea'])) {
            $where_conditions[] = "linea = '" . $conn->real_escape_string($_GET['linea']) . "'";
        }
        if (!empty($_GET['marca'])) {
            $where_conditions[] = "marca = '" . $conn->real_escape_string($_GET['marca']) . "'";
        }
        if (!empty($_GET['modelo'])) {
            $where_conditions[] = "modelo = '" . $conn->real_escape_string($_GET['modelo']) . "'";
        }
        if (!empty($_GET['ano'])) {
            $where_conditions[] = "ano = '" . $conn->real_escape_string($_GET['ano']) . "'";
        }
        
        if (!empty($where_conditions)) {
            $numero_parte_sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $result_numero_parte = $conn->query($numero_parte_sql);
        while ($row = $result_numero_parte->fetch_assoc()) {
            $selected = (isset($_GET['numero_parte']) && $_GET['numero_parte'] === $row['numero_parte']) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($row['numero_parte']) . "' $selected>" . htmlspecialchars($row['numero_parte']) . "</option>";
        }
        ?>
      </select>
  </form>            
</div>

<?php
// Main query for cards and DataTable
$main_sql = "SELECT * FROM productos";
$where_conditions = [];

if (!empty($_GET['linea'])) {
    $where_conditions[] = "linea = '" . $conn->real_escape_string($_GET['linea']) . "'";
}
if (!empty($_GET['marca'])) {
    $where_conditions[] = "marca = '" . $conn->real_escape_string($_GET['marca']) . "'";
}
if (!empty($_GET['modelo'])) {
    $where_conditions[] = "modelo = '" . $conn->real_escape_string($_GET['modelo']) . "'";
}
if (!empty($_GET['ano'])) {
    $where_conditions[] = "ano = '" . $conn->real_escape_string($_GET['ano']) . "'";
}
if (!empty($_GET['numero_parte'])) {
    $where_conditions[] = "numero_parte = '" . $conn->real_escape_string($_GET['numero_parte']) . "'";
}

if (!empty($where_conditions)) {
    $main_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$result = $conn->query($main_sql);
?>

<div class="cards-container">
<?php
if ($result->num_rows > 0) {
    echo '<div class="row g-4">'; // Added g-4 for consistent grid spacing
    while($row = $result->fetch_assoc()) {
        echo '<div class="col-12 col-sm-12 col-md-6 col-lg-3 mb-3">';
        echo '<div class="card" data-id="' . $row['id'] . '" style="padding: 1px; border: 1px solid #ddd; height: 94%; display: flex; flex-direction: column;">
            <input type="hidden" class="product-id" value="' . htmlspecialchars($row['id']) . '">
            
            <div>'; 
                if(!empty($row['image'])) {
                    echo '<img src="' . htmlspecialchars($row['image']) . '" alt="Imagen" id="image_' . $row['numero_parte'] . '" style="width: 100%; height: 200px; border-radius:10px;">';
                } else {
                    echo '<img src="./uploads/default_image.png" alt="Default Imagen" id="image_' . $row['numero_parte'] . '" style="width: 120px; height: auto; border-radius: 50%;">';
                }
                echo '</div>
                <div style="text-align: center;">
                  <div><i class="fas fa-tag"></i> ' . htmlspecialchars($row['numero_parte']) . '</div>
                </div>
                                    
                <div style="display: flex; align-items: center;">
                    <img src="./images/coche.png" alt="Modelo" style="width: 20px; height: 20px; margin-right: 10px;">
                    <span style="margin-top: 10px; margin-bottom: 10px; color: var(--primary-color); font-size: 0.8rem; font-weight: 500;">' . htmlspecialchars($row['modelo']) . '</span>
                </div>

            <table style="width: 100%; 
                         margin-bottom: 2px;
                         border-collapse: collapse;
                         background-color: #ffffff;
                         box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                         border-radius: 12px;
                         overflow: hidden;">
                <tbody>

                    <tr style="background-color: #f8f9fa;">
                        <td style="text-align: left; padding: 12px 15px; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center;">
                                <img src="./images/fabrica.png" alt="Fabrica" style="width: 20px; height: 20px; margin-right: 10px;">
                                <span style="color: #495057; font-size: 0.85rem;">' . htmlspecialchars($row['marca']) . '</span>
                            </div>
                        </td>
                        <td style="text-align: left; padding: 12px 15px; border-bottom: 1px solid #e9ecef;">
                            <div style="display: flex; align-items: center;">
                                <img src="./images/calendario.png" alt="Año" style="width: 20px; height: 20px; margin-right: 10px;">
                                <span style="color: #495057; font-size: 0.85rem;">' . htmlspecialchars($row['ano']) . '</span>
                            </div>
                        </td>
                    </tr>
                    <tr style="background-color: #ffffff;">                        
                        <td style="text-align: left; padding: 12px 15px;">
                            <div style="display: flex; align-items: center;">
                                <i class="fa-solid fa-boxes-stacked" style="font-size: 1.2rem; color: #495057; margin-right: 10px;"></i>
                                <span style="color: #495057; font-size: 0.85rem;">' . htmlspecialchars($row['existencia']) . ' unidades</span>
                            </div>
                        </td>
                        <td style="text-align: left; padding: 12px 15px;">
                            <div style="display: flex; align-items: center;">
                                <img src="./images/moneda.png" alt="Precio" style="width: 20px; height: 20px; margin-right: 10px;">
                                <span style="color: #212529; font-size: 1rem; font-weight: 600;">$' . number_format(htmlspecialchars($row['precio']), 2, ',', '.') . '</span>
                            </div>
                        </td>
                    </tr>
                </tbody>                    
            </table>
            <div class="quantity-selector d-flex align-items-center justify-content-center" style="margin-top: 10px; width: 100%;">
                <button class="btn btn-outline-secondary" onclick="changeQuantity(\'minus\', \'' . $row['id'] . '\')">-</button>
                <input type="number" id="quantity_' . $row['id'] . '" value="0" min="0" class="quantity-input text-center" readonly>            
                <button class="btn btn-outline-secondary" onclick="changeQuantity(\'plus\', \'' . $row['id'] . '\')">+</button>            
            </div>

            <!-- Action buttons moved here -->
            <div class="d-flex justify-content-center mt-2 gap-1 mb-0">
                <button class="btn btn-sm btn-outline-primary" onclick="openUpdateModal(\'' . $row['id'] . '\')" style="flex: 1; margin-bottom: 0;">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProducto(this, \'' . $row['id'] . '\')" style="flex: 1; margin-bottom: 0;">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>

        </div>'; // End of card div
        
        // Add to Cart button container - removed margin-top and added directly after buttons
        echo '<div class="d-flex justify-content-center" style="margin-top: 0;">
            <button class="btn btn-outline-primary w-100" onclick="addToCart(\'' . $row['id'] . '\', \'' . $row['numero_parte'] . '\')">
                <i class="fa-solid fa-cart-plus"></i>
            </button>
        </div>';
        
        echo '</div>'; // End of column
    }
    echo '</div>'; // End row container
} else {
    echo "<p>No information found</p>";
}
?>
</div>

<script>
function changeQuantity(action, productId) {
    const quantityInput = document.getElementById(`quantity_${productId}`);
    let currentQuantity = parseInt(quantityInput.value) || 0;

    if (action === 'plus') {
        currentQuantity++;
    } else if (action === 'minus' && currentQuantity > 0) {
        currentQuantity--;
    }

    // Ensure quantity never goes below 0
    currentQuantity = Math.max(0, currentQuantity);

    // Update only the input element
    quantityInput.value = currentQuantity;
}
</script>

<!-- Delete Producto -->
<script>
function deleteProducto(button, id) {
    // Confirm deletion
    if (!confirm('¿Está seguro de que desea borrar este Producto? Esta acción no se puede deshacer.')) {
        return; // Exit if the user cancels
    }

    // Send AJAX request using jQuery
    $.ajax({
        url: './delete_producto.php', // URL to the PHP script that handles deletion
        type: 'POST',
        data: { id: id }, // Send the product ID to delete
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Producto borrado exitosamente.');
                // Remove the card from the UI
                $(button).closest('.card').remove();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Ocurrió un error al procesar la solicitud.');
            console.error(error);
        }
    });
}
</script>

<h3  class="container mt-4 text-white" id="section-inventario-poblacion-productos">
INVENTARIO
</h3>

<!-- Tabla productos -->
<div class="container table-responsive table-section" style="max-width: 1200px; margin: 0 auto;">
    <table id="productosTable" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th class="text-center">Codigo</th>
                <th class="text-center">Linea</th>
                <th class="text-center">Marca</th>
                <th class="text-center">Modelo</th>
                <th class="text-center">Año</th>
                <th class="text-center">Precio</th>
                <th class="text-center">Existencia</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Use the same $result from the main query that's used for cards
            if ($result && $result->num_rows > 0) {
                // Reset the pointer to the beginning of the result set
                $result->data_seek(0);
                
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['numero_parte']) . "</td>";                    
                    echo "<td>" . htmlspecialchars($row['linea']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['marca']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['modelo']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['ano']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['precio']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['existencia']) . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
</div>
<!-- productos Table Script Inicializacion -->
<script>
// Add this before your DataTable initialization
const spanishTranslation = {
    "decimal": "",
    "emptyTable": "No hay datos disponibles en la tabla",
    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
    "infoFiltered": "(filtrado de _MAX_ registros totales)",
    "infoPostFix": "",
    "thousands": ",",
    "lengthMenu": "Mostrar _MENU_ registros",
    "loadingRecords": "Cargando...",
    "processing": "Procesando...",
    "search": "Buscar:",
    "zeroRecords": "No se encontraron registros coincidentes",
    "paginate": {
        "first": "Primero",
        "last": "Último",
        "next": "Siguiente",
        "previous": "Anterior"
    },
    "aria": {
        "sortAscending": ": activar para ordenar la columna ascendente",
        "sortDescending": ": activar para ordenar la columna descendente"
    }
};

// productos DataTable initialization
$(document).ready(function() {
    $('#productosTable').DataTable({
        responsive: true,
        dom: "Blfrtip",
        bProcessing: true,
        buttons: [ 'excel','pdf'],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]], 
        language: {
            ...spanishTranslation,
            paginate: {
                first: "Primera",
                last: "Última",
                next: "Siguiente",
                previous: "Anterior"
            },
            lengthMenu: "Mostrar _MENU_ registros por página",
        },
        paging: true,
        pagingType: "full_numbers"
    });
});
</script>

<!-- Modal Crear Producto -->

<div class="modal fade" id="newEntryModal" tabindex="-1" aria-labelledby="newEntryModalLabel" aria-hidden="true">	
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: var(--primary-color);">
                <h5 class="modal-title" id="newEntryModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Crear Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newEntryForm" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Left Column - Image -->
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <div class="image-preview-container">
                                    <img id="newImagePreview" src="./images/default_image.png" 
                                         class="img-thumbnail mb-2" alt="Preview" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <label for="newImageUpload" class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="fas fa-upload me-2"></i>Subir Imagen
                                </label>
                                <input type="file" class="d-none" id="newImageUpload" name="image" accept="image/*" onchange="previewImage(event)">
                            </div>
                        </div>

                        <!-- Right Column - Form Fields -->
                        <div class="col-md-8">
                            <div class="row g-3">
                                <!-- Tag ID -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="newnumero_parte" name="newnumero_parte" required>
                                        <label for="newnumero_parte">Numero de Parte</label>
                                        <div class="invalid-feedback">
                                            Por favor ingrese un Numero de Parte válido
                                        </div>
                                    </div>
                                </div>
                                <!-- linea -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="newlinea" name="newlinea" required>
                                            <option value="">Seleccionar</option>
                                            <option value="suspension">Suspension</option>
                                            <option value="amortiguacion">Amortiguacion</option>
                                            <option value="mozos">Mozos</option>
                                            <option value="Pastillas Semimetalicas">Pastillas Semimetalicas</option>
                                            <option value="ciguenal">Ciguenal</option>
                                            <option value="tripoides">Tripoides</option>
                                            <option value="rodamientos">Rodamientos</option>
                                            <option value="clutch">Clutch</option>
                                            <option value="bombas de agua">Bombas de agua</option>
                                        </select>
                                        <label for="newlinea">linea</label>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una linea
                                        </div>
                                    </div>
                                </div>

                                <!-- marca -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="newmarca" required>
                                            <option value="">Seleccionar</option>
                                            <?php
                                            $conn_marcas = new mysqli('localhost', $username, $password, $dbname);
                                            $sql_marcas = "SELECT DISTINCT fabricante FROM marcas ORDER BY fabricante";
                                            $result_marcas = $conn_marcas->query($sql_marcas);
                                            while ($row_marcas = $result_marcas->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($row_marcas['fabricante']) . '">' 
                                                    . htmlspecialchars($row_marcas['fabricante']) . '</option>';
                                            }
                                            $conn_marcas->close();
                                            ?>
                                        </select>
                                        <label for="newmarca">Marca</label>
                                        <div class="invalid-feedback">
                                            Por favor seleccione la marca
                                        </div>
                                    </div>
                                </div>

                                <!-- Modelo -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="newmodelo" name="newmodelo" required>
                                        <label for="newmodelo">Modelo</label>
                                        <div class="invalid-feedback">
                                            Por favor ingrese Modelo
                                        </div>
                                    </div>
                                </div>

                                <!-- ano -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="newano" name="newano" required>
                                            <option value="">Seleccionar</option>
                                            <?php
                                            $conn_anos = new mysqli('localhost', $username, $password, $dbname);
                                            $sql_anos = "SELECT DISTINCT anos_lista FROM anos ORDER BY anos_lista";
                                            $result_anos = $conn_anos->query($sql_anos);
                                            while ($row_anos = $result_anos->fetch_assoc()) {
                                                echo '<option value="' . \htmlspecialchars($row_anos['anos_lista']) . '">' 
                                                    . \htmlspecialchars($row_anos['anos_lista']) . '</option>';
                                            }
                                            $conn_anos->close();
                                            ?>
                                        </select>
                                        <label for="newano">Año</label>
                                        <div class="invalid-feedback">
                                            Por favor seleccione el Año
                                        </div>
                                    </div>
                                </div>

                                <!-- precio -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="newprecio" name="newprecio" required>
                                        <label for="newprecio">Precio</label>
                                    </div>
                                </div>

                                <!-- existencia -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="newexistencia" name="newexistencia" required>
                                        <label for="newexistencia">Existencia</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer btn-group">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="saveNewEntry()">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!--Save New Entry Function  -->
<script>
function saveNewEntry() {
    // Get the form
    const form = document.getElementById('newEntryForm');
    if (!form) {
        console.error('New entry form not found');
        return;
    }

    // Create FormData object
    const formData = new FormData();
    
    // Add form fields
    formData.append('numero_parte', document.getElementById('newnumero_parte').value);
    formData.append('linea', document.getElementById('newlinea').value);
    formData.append('marca', document.getElementById('newmarca').value);
    formData.append('modelo', document.getElementById('newmodelo').value);
    formData.append('ano', document.getElementById('newano').value);
    formData.append('precio', document.getElementById('newprecio').value);
    formData.append('existencia', document.getElementById('newexistencia').value);
    formData.append('image', document.getElementById('newImageUpload').value);

    // Add the image file if one was selected
    const imageFile = document.getElementById('newImageUpload').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Show loading state
    const saveButton = document.querySelector('#newEntryModal .btn-outline-primary');
    if (!saveButton) {
        console.error('Save button not found');
        return;
    }
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    saveButton.disabled = true;

    // Send the create request
    $.ajax({
    url: 'productos_create.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        timeout: 10000,
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'El nuevo Producto ha sido registrado exitosamente.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Reset form
                        form.reset();
                        
                        // Reset image preview
                        const imagePreview = document.getElementById('newImagePreview');
                        if (imagePreview) {
                            imagePreview.src = 'images/default_image.png';
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newEntryModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Refresh page
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Hubo un error al guardar los datos.'
                    });
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al procesar la respuesta del servidor.'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            let errorMessage = 'Hubo un error al enviar los datos';
            if (status === 'timeout') {
                errorMessage = 'La solicitud tardó demasiado tiempo. Por favor, intente de nuevo.';
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
        },
        complete: function() {
            // Restore button state
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        }
    });
}
</script>
<!-- Add this script for image preview -->
<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const output = document.getElementById('newImagePreview');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

<!-- Update Modal -->

<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title text-center" id="updateModalLabel">
                    <i class="fas fa-edit me-2"></i>Actualizar Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateForm" class="needs-validation" novalidate enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12 text-center">
                        <div class="mb-3" style="display: block;">
                        <img id="updateImagePreview" src="./images/default_image.png" 
                            class="img-thumbnail mb-2" alt="Current Image" 
                            style="width: 200px; height: auto; object-fit: cover;">
                        <label for="updateImageUpload" class="btn btn-outline-primary btn-sm mt-2" style="display: block; background-color: var(--primary-color); margin-top: 10px;">
                            <i class="fas fa-upload me-2"></i>Subir Imagen
                        </label>
                        <input type="file" class="d-none" id="updateImageUpload" 
                            accept="image/*" onchange="previewUpdateImage(event)">
                        </div>
                        </div>
                        <!-- Hidden input for product ID -->
                        <input type="hidden" id="updateId" name="id" value="">

                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="updatenumero_parte" name="numero_parte" required>
                                <label for="updatenumero_parte">Numero de Parte</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="updatemarca" name="marca" required>
                                    <option value="">Seleccionar Marca</option>
                                    <?php
                                    // Fetch unique marcas from the database
                                    $marca_sql = "SELECT DISTINCT fabricante FROM marcas ORDER BY fabricante";
                                    $result_marcas = $conn->query($marca_sql);
                                    while ($row_marcas = $result_marcas->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row_marcas['fabricante']) . '">' 
                                            . htmlspecialchars($row_marcas['fabricante']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <label for="updatemarca">Marca</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="updatemodelo" name="modelo" required>
                                <label for="updatemodelo">Modelo</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="updateano" name="ano" required>
                                    <option value="">Seleccionar Año</option>
                                    <?php
                                    // Fetch unique years from the database
                                    $ano_sql = "SELECT DISTINCT anos_lista FROM anos ORDER BY anos_lista";
                                    $result_anos = $conn->query($ano_sql);
                                    while ($row_anos = $result_anos->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($row_anos['anos_lista']) . '">' 
                                            . htmlspecialchars($row_anos['anos_lista']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <label for="updateano">Año</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="updateprecio" name="precio" required>
                                <label for="updateprecio">Precio</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="updateexistencia" name="existencia" required>
                                <label for="updateexistencia">Existencia</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer btn-group">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" style="background-color: var(--primary-color);" onclick="saveUpdates()">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Save Updates Products -->

<script>
	function saveUpdates() {
    // Create a FormData object to hold the form data
    const formData = new FormData();
    
    // Append the updated product data
    formData.append('id', $('#updateId').val());
    formData.append('numero_parte', $('#updatenumero_parte').val());
    formData.append('marca', $('#updatemarca').val());
    formData.append('modelo', $('#updatemodelo').val());
    formData.append('ano', $('#updateano').val());
    formData.append('precio', $('#updateprecio').val());
    formData.append('existencia', $('#updateexistencia').val());
    
    // If a new image is uploaded, append it as well
    const newImageFile = document.getElementById('updateImageUpload').files[0];
    if (newImageFile) {
        formData.append('image', newImageFile);
    }

    // Send the update request
    $.ajax({
        url: 'productos_update.php', // URL to the PHP script that handles the update
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Producto actualizado exitosamente.');
                location.reload(); // Reload the page to see the changes
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error al actualizar el producto: ' + error);
        }
    });
}
</script>
<!-- Crear Nuevo Producto -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Get form element
    const createEntryForm = document.getElementById('newEntryForm');
    const newEntryModal = document.getElementById('newEntryModal');

    if (createEntryForm) {
        // Handle form submission
        createEntryForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            // Create a FormData object from the form
            const formData = new FormData(createEntryForm);

            // Show loading state
            const submitButton = createEntryForm.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
            submitButton.disabled = true;

            // Send the form data using fetch
            fetch('productos_create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Nuevo Producto agregado exitosamente.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Reset form and close modal
                        createEntryForm.reset();
                        const imagePreview = document.getElementById('newImagePreview');
                        if (imagePreview) {
                            imagePreview.src = 'images/default_image.png';
                        }
                        const modal = bootstrap.Modal.getInstance(newEntryModal);
                        if (modal) {
                            modal.hide();
                        }
                        // Reload page to show new entry
                        location.reload();
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al agregar el nuevo Producto.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud.'
                });
            })
            .finally(() => {
                // Restore button state
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });

        // Handle image preview for new entry
        const newImageUpload = document.getElementById('newImageUpload');
        const newImagePreview = document.getElementById('newImagePreview');

        if (newImageUpload && newImagePreview) {
            newImageUpload.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        newImagePreview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Initialize Bootstrap validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php
$conn->close();
?> 

<!-- DataTables Scripts -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<!-- Make sure these exact versions of the libraries are included in your head section -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
function openUpdateModal(id) {
    // Fetch product data using AJAX
    $.ajax({
        url: 'fetch_productos_data.php', // URL to fetch product data
        type: 'GET',
        data: { id: id }, // Send the product ID to fetch specific data
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Populate the modal fields with the product data
            $('#updatenumero_parte').val(data.numero_parte);
            $('#updatemarca').val(data.marca);
            $('#updatemodelo').val(data.modelo);
            $('#updateano').val(data.ano);
            $('#updateprecio').val(data.precio);
            $('#updateexistencia').val(data.existencia);
            $('#updateImagePreview').attr('src', data.image ? data.image : './images/default_image.png');

            // Set the value of the hidden input for the product ID
            $('#updateId').val(id);

            // Show the modal
            $('#updateModal').modal('show');
        },
        error: function(xhr, status, error) {
            alert('Error al cargar los datos del producto: ' + error);
        }
    });
}
</script>

<script>
function previewUpdateImage(event) {
    const file = event.target.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        document.getElementById('updateImagePreview').src = e.target.result;
    };
    
    if (file) {
        reader.readAsDataURL(file);
    }
}
</script>

<script>
function addToCart(productId, numeroParte) {
    const quantityInput = document.getElementById(`quantity_${productId}`);
    const quantity = parseInt(quantityInput.value);

    // Only proceed if quantity is greater than 0
    if (quantity === 0) {
        alert('Por favor seleccione una cantidad mayor a 0');
        return;
    }

    // Prepare the data to send
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('numero_parte', numeroParte);
    formData.append('cantidad', quantity);

    // Send the data to the server
    $.ajax({
        url: 'add_to_cart.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Producto agregado al carrito exitosamente',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Reset quantity to 0 after successful addition
                quantityInput.value = 0;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Error al agregar al carrito'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al agregar al carrito: ' + error
            });
        }
    });
}
</script>
</body>
</html>
