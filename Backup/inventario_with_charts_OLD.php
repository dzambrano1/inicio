<?php


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filters
$filters = array();
$filterValues = array(
    'especie' => array(),
    'sexo' => array(),
    'raza' => array(),
    'clasificacion' => array(),
    'estatus' => array()
);

// Fetch unique filter values
foreach ($filterValues as $field => &$values) {
    $query = "SELECT DISTINCT $field FROM ganado WHERE $field IS NOT NULL AND $field != ''";
    
    // Apply dependent filter for 'raza' and 'clasificacion' based on 'especie'
    if (($field === 'raza' || $field === 'clasificacion') && isset($_GET['especie']) && $_GET['especie'] !== '') {
        $query .= " AND especie = '" . $conn->real_escape_string($_GET['especie']) . "'";
    }
    
    $query .= " ORDER BY $field";
    $filterResult = $conn->query($query);
    
    if ($filterResult) {
        while ($row = $filterResult->fetch_assoc()) {
            if (!empty($row[$field])) {
                $values[] = $row[$field];
            }
        }
        $filterResult->free();
    } else {
        echo "Error fetching $field values: " . $conn->error;
    }
}

// Debug output - remove after confirming values
echo "<!-- Debug: " . print_r($filterValues, true) . " -->";

// Build WHERE clause based on selected filters
$whereClause = array();
foreach (array_keys($filterValues) as $field) {
    if (isset($_GET[$field]) && $_GET[$field] !== '') {
        $whereClause[] = "$field = '" . $conn->real_escape_string($_GET[$field]) . "'";
    }
}

// Modify the main query to include filters
$sql = "SELECT * FROM ganado";
if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(' AND ', $whereClause);
}
$result = $conn->query($sql);

// Debug output - remove after confirming query
echo "<!-- Debug SQL: $sql -->";

// Fetch count of each sexo based on filtered data
$sexoCountsQuery = "SELECT sexo, COUNT(*) as count FROM ganado";
if (!empty($whereClause)) {
    $sexoCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$sexoCountsQuery .= " GROUP BY sexo";

$sexoCountsResult = $conn->query($sexoCountsQuery);

$sexoLabels = [];
$sexoCounts = [];

if ($sexoCountsResult && $sexoCountsResult->num_rows > 0) {
    while ($row = $sexoCountsResult->fetch_assoc()) {
        $sexoLabels[] = $row['sexo'];
        $sexoCounts[] = $row['count'];
    }
} else {
    // Handle case when there are no records
    $sexoLabels = ['No Data'];
    $sexoCounts = [0];
}

// Fetch count of each raza based on filtered data
$razaCountsQuery = "SELECT raza, COUNT(*) as count FROM ganado";
if (!empty($whereClause)) {
    $razaCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$razaCountsQuery .= " GROUP BY raza";

$razaCountsResult = $conn->query($razaCountsQuery);

$razaLabels = [];
$razaCounts = [];

if ($razaCountsResult && $razaCountsResult->num_rows > 0) {
    while ($row = $razaCountsResult->fetch_assoc()) {
        $razaLabels[] = $row['raza'];
        $razaCounts[] = $row['count'];
    }
} else {
    // Handle case when there are no records
    $razaLabels = ['No Data'];
    $razaCounts = [0];
}

// Fetch count of each clasificacion based on filtered data
$clasificacionCountsQuery = "SELECT clasificacion, COUNT(*) as count FROM ganado";
if (!empty($whereClause)) {
    $clasificacionCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$clasificacionCountsQuery .= " GROUP BY clasificacion";

$clasificacionCountsResult = $conn->query($clasificacionCountsQuery);

$clasificacionLabels = [];
$clasificacionCounts = [];

if ($clasificacionCountsResult && $clasificacionCountsResult->num_rows > 0) {
    while ($row = $clasificacionCountsResult->fetch_assoc()) {
        $clasificacionLabels[] = $row['clasificacion'];
        $clasificacionCounts[] = $row['count'];
    }
} else {
    // Handle case when there are no records
    $clasificacionLabels = ['No Data'];
    $clasificacionCounts = [0];
}

// Fetch count of each estatus based on filtered data
$estatusCountsQuery = "SELECT estatus, COUNT(*) as count FROM ganado";
if (!empty($whereClause)) {
    $estatusCountsQuery .= " WHERE " . implode(' AND ', $whereClause);
}
$estatusCountsQuery .= " GROUP BY estatus";

$estatusCountsResult = $conn->query($estatusCountsQuery);

$estatusLabels = [];
$estatusCounts = [];

if ($estatusCountsResult && $estatusCountsResult->num_rows > 0) {
    while ($row = $estatusCountsResult->fetch_assoc()) {
        $estatusLabels[] = $row['estatus'];
        $estatusCounts[] = $row['count'];
    }
} else {
    // Handle case when there are no records
    $estatusLabels = ['No Data'];
    $estatusCounts = [0];
}

// Calculate the total count for percentage calculation
$totalSexo = array_sum($sexoCounts);
$totalRaza = array_sum($razaCounts);
$totalClasificacion = array_sum($clasificacionCounts);
$totalEstatus = array_sum($estatusCounts);

// Close the PHP tag before starting HTML
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GANAGRAM</title>
    <!-- Link to the Favicon -->
    <link rel="icon" href="images/ganagram_ico.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cards-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
                      
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 3px solid #f0f0f0;
            border-radius: 50%;
            box-shadow: 1 8px 8px rgba(0,0,0,0.1);  
            transition: transform 0.3s;
            cursor: pointer;
        }

        .name {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        .title {
            color: #666;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .contact-info {
            text-align: left;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: none;
        }

        .contact-info.show {
            display: block;
        }

        .contact-info p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        .contact-info i {
            width: 20px;
            margin-right: 8px;
            color: #666;
        }

        .more-details-btn {
            margin-top: 15px;
            padding: 8px 20px;
            background-color: #83956e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .more-details-btn:hover {
            background-color: #689260;
        }

        @media (max-width: 1200px) {
            .cards-container {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 900px) {
            .cards-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
        }

        .filters-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
        }

        .filters-form {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filters-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: white;
            min-width: 200px;
            cursor: pointer;
        }

        .filters-form select:hover {
            border-color: #83956e;
        }

        @media (max-width: 900px) {
            .filters-form {
                flex-direction: column;
                align-items: center;
            }
            .filters-form select {
                width: 100%;
                max-width: 300px;
            }
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 8px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            transition: background-color 0.3s;
        }

        .action-btn.update-btn i {
            color: #4CAF50;
        }

        .action-btn.history-btn i {
            color: #2196F3;
        }

        .action-btn.delete-btn i {
            color: #f44336;
        }

        .new-entry-btn {
            background-color: #83956e;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s, transform 0.2s;
            flex-shrink: 0;
        }

        .new-entry-btn:hover {
            background-color: #689260;
            transform: scale(1.05);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 1000px;
            position: relative;
            max-height: 80vh;
            overflow-y: scroll;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #83956e #f0f0f0;
        }

        /* Webkit (Chrome, Safari, Edge) scrollbar styles */
        .modal-content::-webkit-scrollbar {
            width: 8px;
            position: absolute;
            left: 0;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #83956e;
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #689260;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 1px;
        }

        .form-group label {
            display: block;
            margin-bottom: 1px;
        }

        .form-group input,
        .form-group select {
            width: 90%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .submit-btn-container {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 10px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-group label {
            display: block;
            margin-bottom: 1px;
        }

        .form-group input,
        .form-group select {
            width: 90%;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .submit-btn {
            background-color: #83956e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #689260;
        }

        .image-upload-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .image-preview {
            width: 55%;
            height: 55%;
            overflow: hidden;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-preview img {
            width: 55%;
            height: 55%;            
            border: none;
            border-radius: 100%;
            object-fit: cover;
        }

        .image-upload-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #83956e;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .image-upload-label:hover {
            background-color: #689260;
        }

        #imageUpload {
            display: none;
        }

        .image-column {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .name-column {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .modal-title {
            text-align: center;
            font-size: 24px;
            color: #333;
            margin: 0 0 20px 0;
            padding: 10px 0;
            border-bottom: 2px solid #83956e;
            width: 100%;
        }

        .submit-btn-container {
            margin-top: 20px;
            text-align: center;
            width: 100%;
        }

        .submit-btn {
            background-color: #83956e;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            max-width: 800px;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #689260;
        }

        .sex-column {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .fields-section {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin: 20px 0;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            justify-content: space-between;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-row:last-child {
            margin-bottom: 0;
        }

        .form-grid-three-columns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .image-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .image-preview {
            width: 200px;
            height: 200px;
            margin: 20px 0;
            overflow: hidden;
            border-radius: 50%;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .center-column,
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .image-upload-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #83956e;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .image-upload-label:hover {
            background-color: #689260;
        }

        #newImageUpload {
            display: none;
        }

        .image-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            width: 100%;
        }

        .image-preview {
            width: 200px;
            height: 200px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #f0f0f0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-upload-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #83956e;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .image-upload-label:hover {
            background-color: #689260;
        }

        #imageUpload {
            display: none;
        }

        .form-grid-two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .form-grid-three-columns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .column {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .action-btn.weight-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            transition: background-color 0.3s;
            vertical-align: middle;
        }

        .action-btn.weight-btn i {
            color: #4CAF50;
            font-size: 14px;
        }

        .action-btn.weight-btn:hover {
            background-color: rgba(76, 175, 80, 0.1);
        }

    </style>
    <!-- Include Chart.js and Chart.js DataLabels Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>
<div class="filters-container">
    <form method="GET" action="" class="filters-form">
        <select name="especie" onchange="this.form.submit()">
            <option value="">Especie</option>
            <?php foreach ($filterValues['especie'] as $value): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo (isset($_GET['especie']) && $_GET['especie'] === $value) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sexo" onchange="this.form.submit()">
            <option value="">Sexo</option>
            <?php foreach ($filterValues['sexo'] as $value): ?>
                <option value="<?php echo htmlspecialchars($value); ?>" 
                    <?php echo (isset($_GET['sexo']) && $_GET['sexo'] === $value) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="raza" onchange="this.form.submit()">
            <option value="">Raza</option>
            <?php foreach ($filterValues['raza'] as $value): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo (isset($_GET['raza']) && $_GET['raza'] === $value) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="clasificacion" onchange="this.form.submit()">
            <option value="">Clasificación</option>
            <?php foreach ($filterValues['clasificacion'] as $value): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo (isset($_GET['clasificacion']) && $_GET['clasificacion'] === $value) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="estatus" onchange="this.form.submit()">
            <option value="">Estatus</option>
            <?php foreach ($filterValues['estatus'] as $value): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"
                    <?php echo (isset($_GET['estatus']) && $_GET['estatus'] === $value) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($value); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <button class="new-entry-btn" title="Agregar Nuevo Animal" onclick="openModal()">
        <i class="fas fa-plus"></i>
    </button>
</div>
<div style="max-width: 1400px; margin: 0px auto; padding: 0 10px;">
    <p style="color: #666; font-size: 16px; margin: 0; text-align: center;">
        Total <?php echo $result->num_rows; ?> animales mostrados 
        (<?php
            // Get the sum of weights for filtered entries
            $sumQuery = $sql;  // Use the same filtered query
            if (strpos($sumQuery, 'WHERE') !== false) {
                $sumQuery = str_replace("SELECT *", "SELECT SUM(peso) as total_peso", $sumQuery);
            } else {
                $sumQuery = "SELECT SUM(peso) as total_peso FROM ganado";
            }
            $sumResult = $conn->query($sumQuery);
            $sumRow = $sumResult->fetch_assoc();
            echo number_format($sumRow['total_peso'], 2) . ' Kg';
        ?>) 
        (Monto: $<?php
            $price_per_kg = 2.01;
            $total_amount = $sumRow['total_peso'] * $price_per_kg;
            echo number_format($total_amount, 2);
        ?> @2.01 $/Kg)
    </p>
</div>
    <div class="cards-container">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<div class="card" data-id="' . $row['id'] . '">
                    <div class="avatar">';
                if(!empty($row['imagen'])) {
                    echo '<img src="' . htmlspecialchars($row['imagen']) . '" alt="Imagen">';
                }
                echo '</div>
                    <p><b><i class="fas fa-baby"></i></b> ' . htmlspecialchars($row['nacimiento']) . '</p>
                    <div class="name">' . htmlspecialchars($row['nombre']) . '</div>
                    <div class="title"><p><i class="fas fa-tag"></i> ' . htmlspecialchars($row['tagid']) . '</p></div>
                    <button class="more-details-btn" onclick="toggleDetails(this)">VER MAS</button>
                    <div class="contact-info">
                        <p><b>Genero:</b> ' . htmlspecialchars($row['sexo']) . '</p>
                        <p><b>Raza:</b> ' . htmlspecialchars($row['raza']) . '</p>
                        <p><b>Clasificacion:</b> ' . htmlspecialchars($row['clasificacion']) . '</p>
                        <p><b>Estatus:</b> ' . htmlspecialchars($row['estatus']) . '</p>
                        
                        <!-- CARNE -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Peso (Kg):</b> 
                                <input type="number" 
                                       style="width: 74px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['peso']) . '" 
                                       id="peso-animal_' . $row['id'] . '"
                                >
                            </p>                        
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['peso_fecha']) . '" 
                                    id="peso-fecha_' . $row['id'] . '"
                                >
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['peso_precio']) . '" 
                                       id="peso-precio_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Peso" 
                                        onclick="updateWeight(' . $row['id'] . ')" 
                                        style="justify-content: center;">
                                        <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- LECHE -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Leche (Kg):</b> 
                                <input type="number" 
                                       style="width: 74px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['leche_peso']) . '" 
                                       id="leche-peso_' . $row['id'] . '"
                                >
                            </p>                        
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['leche_fecha']) . '" 
                                    id="leche-fecha_' . $row['id'] . '"
                                >
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['leche_precio']) . '" 
                                       id="leche-precio_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                            title="Actualizar Leche" 
                                            onclick="updateMilk(' . $row['id'] . ')" 
                                            style="justify-content: center;">
                                        <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- RACION -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Concentrado:</b> 
                                    <input type="text" 
                                        style="width: 63px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                        value="' . htmlspecialchars($row['racion_nombre']) . '" 
                                        id="racion-nombre_' . $row['id'] . '"
                                    >                                
                            </p>    
                            <p><b>Ración (Kg):</b> 
                                <input type="number" 
                                    style="width: 63px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['racion_peso']) . '" 
                                    id="racion-peso_' . $row['id'] . '"
                                >                                
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['racion_fecha']) . '" 
                                    id="racion-fecha_' . $row['id'] . '"
                                >                                
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['racion_costo']) . '" 
                                       id="racion-costo_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                            title="Actualizar Ración" 
                                            onclick="updateFood(' . $row['id'] . ')" 
                                            style="justify-content: center;">
                                        <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- VACUNA -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Vacuna:</b> 
                                <input type="text" 
                                    style="width: 91px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['vacuna']) . '" 
                                    id="vacuna_' . $row['id'] . '"
                                >                                
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['vacuna_fecha']) . '" 
                                    id="vacuna-fecha_' . $row['id'] . '"
                                >                                
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['vacuna_costo']) . '" 
                                       id="vacuna-precio_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Vacunación" 
                                        onclick="actualizarVacuna(' . $row['id'] . ')" 
                                        style="justify-content: center;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- BAÑO -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Baño:</b> 
                                <input type="text" 
                                    style="width: 105px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['bano']) . '" 
                                    id="bano_' . $row['id'] . '"
                                >
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['bano_fecha']) . '" 
                                    id="bano-fecha_' . $row['id'] . '">
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       step="0.1"
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['bano_costo']) . '" 
                                       id="bano-costo_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Baño" 
                                        onclick="updateBano(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PARASITOS -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Parasitos:</b> 
                                <input type="text" 
                                    style="width: 77px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['parasitos']) . '" 
                                    id="parasitos_' . $row['id'] . '">                                
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['parasitos_fecha']) . '" 
                                    id="parasitos-fecha_' . $row['id'] . '">
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['parasitos_costo']) . '" 
                                       id="parasitos-costo_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Parasitos" 
                                        onclick="updateParasitos(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- DESTETE -->

                        <div class="task-container" style="border: 1px solid #ddd;margin-top: 10px;  padding: 10px; border-radius: 5px;">
                            <p><b>Destete (Kg):</b> 
                                <input type="number" 
                                    style="width: 58px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['destete_peso']) . '" 
                                    id="destete_' . $row['id'] . '">                                
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['destete_fecha']) . '" 
                                    id="destete-fecha_' . $row['id'] . '">
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Destete" 
                                        onclick="updateDestete(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- PREÑEZ -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Preñez No.:</b> 
                                <input type="number" 
                                    style="width: 65px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['prenez_numero']) . '" 
                                    id="prenez-numero_' . $row['id'] . '">                                
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['prenez_fecha']) . '" 
                                    id="prenez-fecha_' . $row['id'] . '">
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Preñez" 
                                        onclick="updatePrenez(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PARTO -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Parto No. :</b> 
                                <input type="number" 
                                    style="width: 73px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['parto_numero']) . '" 
                                    id="parto-numero_' . $row['id'] . '">
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['parto_fecha']) . '" 
                                    id="parto-fecha_' . $row['id'] . '">
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Parto" 
                                        onclick="updateParto(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- INSEMINACION -->

                        <div class="task-container" style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px;">
                            <p><b>Inseminación:</b> 
                                <input type="text" 
                                    style="width: 50px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['inseminacion']) . '" 
                                    id="inseminacion-tipo_' . $row['id'] . '">
                            </p>
                            <p><b>Fecha:</b> 
                                <input type="date" 
                                    style="width: 100px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['inseminacion_fecha']) . '" 
                                    id="inseminacion-fecha_' . $row['id'] . '">
                            </p>
                            <p><b>Precio:</b> 
                                <input type="number" 
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['inseminacion_costo']) . '" 
                                       id="inseminacion-costo_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Inseminación" 
                                        onclick="updateInseminacion(' . $row['id'] . ')" 
                                        style="vertical-align: middle;">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <div class="action-buttons">                            
                            <button class="action-btn history-btn" title="Historial" onclick="openHistory(\'' . htmlspecialchars($row['tagid']) . '\')">
                                <i class="fas fa-clock-rotate-left"></i>
                            </button>                            
                            <button class="action-btn delete-btn" title="Borrar" onclick="deleteAnimal(this, ' . $row['id'] . ')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo "<p>No information found</p>";
        }
        ?>
    </div>

    <!-- Pie Charts Section -->
    <div style="max-width: 600px; margin: 40px auto;">
        <h3 style="text-align: center;">Distribución por Sexo (%)</h3>
        <canvas id="sexoPieChart"></canvas>
    </div>

    <div style="max-width: 600px; margin: 40px auto;">
        <h3 style="text-align: center;">Distribución por Raza (%)</h3>
        <canvas id="razaPieChart"></canvas>
    </div>

    <div style="max-width: 600px; margin: 40px auto;">
        <h3 style="text-align: center;">Distribución por Clasificación (%)</h3>
        <canvas id="clasificacionPieChart"></canvas>
    </div>

    <div style="max-width: 600px; margin: 40px auto;">
        <h3 style="text-align: center;">Distribución por Estatus (%)</h3>
        <canvas id="estatusPieChart"></canvas>
    </div>

    <div id="newEntryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="createEntryForm">
                <div class="form-grid-three-columns">
                    <!-- Left Column -->
                    <div class="image-column">
                        <div class="form-group">
                            <label for="newNombre">Nombre:</label>
                            <input type="text" id="newNombre" name="nombre" required>
                        </div>
                        <div class="image-preview">
                            <img id="newImagePreview" src="./images/Agregar_Logo-png.png" alt="Preview">
                        </div>
                        <label for="newImageUpload" class="image-upload-label">
                            <i class="fas fa-upload"></i> Seleccionar Imagen
                        </label>
                        <input type="file" id="newImageUpload" name="imagen" accept="image/*" style="display: none;">
                    </div>

                    <!-- Center Column -->
                    <div class="center-column">

                        <div class="form-group">
                            <label for="newSexo">Sexo:</label>
                            <select id="newSexo" name="sexo" required>
                                <option value="">Seleccionar</option>
                                <option value="Macho">Macho</option>
                                <option value="Hembra">Hembra</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newEstatus">Estatus:</label>
                            <select id="newEstatus" name="estatus" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($filterValues['estatus'] as $value): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newPeso">Peso (Kg):</label>
                            <input type="number" id="newPeso" name="peso" step="0.01" required>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="right-column">
                        <div class="form-group">
                            <label for="newTagid">Tag ID:</label>
                            <input type="text" id="newTagid" name="tagid" required>
                        </div>
                        <div class="form-group">
                            <label for="newRaza">Raza:</label>
                            <select id="newRaza" name="raza" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($filterValues['raza'] as $value): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newClasificacion">Clasificación:</label>
                            <select id="newClasificacion" name="clasificacion" required>
                                <option value="">Seleccionar</option>
                                <?php foreach ($filterValues['clasificacion'] as $value): ?>
                                    <option value="<?php echo htmlspecialchars($value); ?>">
                                        <?php echo htmlspecialchars($value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="newFechaPeso">Fecha de Peso:</label>
                            <input type="date" id="newFechaPeso" name="peso_fecha" required>
                        </div>
                    </div>
                </div>
                <div class="submit-btn-container">
                    <button type="submit" class="submit-btn">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Chart.js and Chart.js DataLabels Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        // Toggle details in cards
    function toggleDetails(button) {
    const contactInfo = button.nextElementSibling;
    const isHidden = contactInfo.style.display === 'none' || !contactInfo.style.display;
    contactInfo.style.display = isHidden ? 'block' : 'none';
    button.textContent = isHidden ? 'VER MENOS' : 'VER MAS';
    }
    function openHistory(tagid) {
        window.location.href = 'historial.php?id=' + encodeURIComponent(tagid);
    }
    </script>
    <script>
        // Existing scripts...

        // Initialize Pie Charts with Percentages and Data Labels
        document.addEventListener('DOMContentLoaded', function () {
            // Sexo Pie Chart
            var ctxSexo = document.getElementById('sexoPieChart').getContext('2d');
            var sexoPieChart = new Chart(ctxSexo, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($sexoLabels); ?>,
                    datasets: [{
                        label: 'Cantidad por Sexo',
                        data: <?php echo json_encode($sexoCounts); ?>,
                        backgroundColor: [
                            '#4CAF50', // Macho
                            '#FF6384', // Hembra
                            '#36A2EB', // Other if any
                            '#FFCE56', // Additional colors if needed
                            '#AA65D3',
                            '#FF9F40'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            formatter: function(value, context) {
                                var sum = context.chart._metasets[context.datasetIndex].total;
                                var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var sum = context.chart._metasets[context.datasetIndex].total;
                                    var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                    return label + ': ' + percentage + '%';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Sexo (%)'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Raza Pie Chart
            var ctxRaza = document.getElementById('razaPieChart').getContext('2d');
            var razaPieChart = new Chart(ctxRaza, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($razaLabels); ?>,
                    datasets: [{
                        label: 'Cantidad por Raza',
                        data: <?php echo json_encode($razaCounts); ?>,
                        backgroundColor: [
                            '#FF6384', // Example color for Raza
                            '#36A2EB',
                            '#FFCE56',
                            '#4CAF50',
                            '#AA65D3',
                            '#FF9F40'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            formatter: function(value, context) {
                                var sum = context.chart._metasets[context.datasetIndex].total;
                                var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var sum = context.chart._metasets[context.datasetIndex].total;
                                    var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                    return label + ': ' + percentage + '%';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Raza (%)'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Clasificacion Pie Chart
            var ctxClasificacion = document.getElementById('clasificacionPieChart').getContext('2d');
            var clasificacionPieChart = new Chart(ctxClasificacion, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($clasificacionLabels); ?>,
                    datasets: [{
                        label: 'Cantidad por Clasificación',
                        data: <?php echo json_encode($clasificacionCounts); ?>,
                        backgroundColor: [
                            '#FF9F40', // Example color for Clasificacion
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4CAF50',
                            '#AA65D3'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            formatter: function(value, context) {
                                var sum = context.chart._metasets[context.datasetIndex].total;
                                var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var sum = context.chart._metasets[context.datasetIndex].total;
                                    var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                    return label + ': ' + percentage + '%';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Clasificación (%)'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });

            // Estatus Pie Chart
            var ctxEstatus = document.getElementById('estatusPieChart').getContext('2d');
            var estatusPieChart = new Chart(ctxEstatus, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($estatusLabels); ?>,
                    datasets: [{
                        label: 'Cantidad por Estatus',
                        data: <?php echo json_encode($estatusCounts); ?>,
                        backgroundColor: [
                            '#FF6384', // Example color for Estatus
                            '#36A2EB',
                            '#FFCE56',
                            '#4CAF50',
                            '#AA65D3',
                            '#FF9F40',
                            '#8e5ea2',
                            '#3cba9f'
                        ],
                        borderColor: '#fff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            formatter: function(value, context) {
                                var sum = context.chart._metasets[context.datasetIndex].total;
                                var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                return percentage + '%';
                            },
                            color: '#fff',
                            font: {
                                weight: 'bold'
                            }
                        },
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var sum = context.chart._metasets[context.datasetIndex].total;
                                    var percentage = sum > 0 ? ((value / sum) * 100).toFixed(2) : 0;
                                    return label + ': ' + percentage + '%';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribución por Estatus (%)'
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        });
    </script>
    <script>
        function updateWeight(id) {
            // Get the input values
            const peso = document.getElementById(`peso-animal_${id}`).value;
            const pesoFecha = document.getElementById(`peso-fecha_${id}`).value;
            const pesoPrecio = document.getElementById(`peso-precio_${id}`).value;

            // Basic validation
            if (peso === '' || pesoFecha === '' || pesoPrecio === '') {
                alert('Por favor, completa todos los campos.');
                return;
            }

            // Prepare the data to be sent
            const data = {
                id: id,
                peso: peso,
                peso_fecha: pesoFecha,
                peso_precio: pesoPrecio
            };

            // Send AJAX request using jQuery
            $.ajax({
                url: 'update_weight.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Peso actualizado exitosamente.');
                        // Optionally, you can update the UI or reload the page
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
    <script>
        function updateMilk(animalId) {
            const lechePeso = document.getElementById(`leche-peso_${animalId}`).value;
            const lecheFecha = document.getElementById(`leche-fecha_${animalId}`).value;
            const lechePrecio = document.getElementById(`leche-precio_${animalId}`).value;

            // Validate inputs
            if (!lechePeso || !lecheFecha || !lechePrecio) {
                alert('Por favor, complete todos los campos.');
                return;
            }

            // Prepare data
            const data = {
                id: animalId,
                leche_peso: parseFloat(lechePeso),
                leche_fecha: lecheFecha,
                leche_precio: parseFloat(lechePrecio)
            };

            // Send data to the server
            fetch('update_leche_full.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Leche actualizada exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar la leche: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    <script>
        function updateFood(id) {
            const nombre = document.getElementById(`racion-nombre_${id}`).value;
            const racionPeso = document.getElementById(`racion-peso_${id}`).value;
            const racionFecha = document.getElementById(`racion-fecha_${id}`).value;
            const racion_costo = document.getElementById(`racion-costo_${id}`).value;

            // Basic validation
            if (nombre === '' || racionPeso === '' || racionFecha === '' || racion_costo === '') {
                alert('Por favor, completa todos los campos.');
                return;
            }

            // Prepare the data to be sent
            const data = {
                id: id,
                racion_nombre: nombre,
                racion_peso: racionPeso,
                racion_fecha: racionFecha,
                racion_costo: racion_costo
            };

            // Send AJAX request using jQuery
            $.ajax({
                url: 'update_food.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Ración actualizada exitosamente.');
                        location.reload(); // Reload to see updated values
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
    <script>
        function actualizarVacuna(id) {
            const vacuna = document.getElementById(`vacuna_${id}`).value;
            const vacunaFecha = document.getElementById(`vacuna-fecha_${id}`).value;
            const vacunaCosto = document.getElementById(`vacuna-precio_${id}`).value;

            const data = {
                id: id,
                vacuna: vacuna,
                vacuna_fecha: vacunaFecha,
                vacuna_costo: vacunaCosto
            };

            fetch('update_vacuna.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Optionally refresh the page or update the UI
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
    <script>
        function updateBano(id) {
            const bano = document.getElementById(`bano_${id}`).value.trim();
            const banoFecha = document.getElementById(`bano-fecha_${id}`).value;
            const banoCosto = document.getElementById(`bano-costo_${id}`).value.trim();

            // Basic validation
            if (bano === '') {
                alert('Por favor, ingrese un valor válido para Baño.');
                return;
            }

            if (banoFecha === '') {
                alert('Por favor, seleccione una fecha válida para Baño.');
                return;
            }

            if (banoCosto === '' || isNaN(banoCosto) || parseFloat(banoCosto) < 0) {
                alert('Por favor, ingrese un costo válido para Baño.');
                return;
            }

            // Confirm before updating
            if (!confirm('¿Está seguro de que desea actualizar el Baño?')) {
                return;
            }

            // Prepare data to send
            const data = {
                id: id,
                bano: bano,
                bano_fecha: banoFecha,
                bano_costo: parseFloat(banoCosto)
            };

            // Send the data via fetch
            fetch('update_bano.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Baño actualizado exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar Baño: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    <script>
        function updateParasitos(id) {
            const parasitos = document.getElementById(`parasitos_${id}`).value.trim();
            const parasitosFecha = document.getElementById(`parasitos-fecha_${id}`).value;
            const parasitosCosto = document.getElementById(`parasitos-costo_${id}`).value.trim();

            // Basic validation
            if (parasitos === '') {
                alert('Por favor, ingrese un valor válido para Parásitos.');
                return;
            }

            if (parasitosFecha === '') {
                alert('Por favor, seleccione una fecha válida para Parásitos.');
                return;
            }

            if (parasitosCosto === '' || isNaN(parasitosCosto) || parseFloat(parasitosCosto) < 0) {
                alert('Por favor, ingrese un costo válido para Parásitos.');
                return;
            }

            // Confirm before updating
            if (!confirm('¿Está seguro de que desea actualizar los Parásitos?')) {
                return;
            }

            // Prepare data to send
            const data = {
                id: id,
                parasitos: parasitos,
                parasitos_fecha: parasitosFecha,
                parasitos_costo: parseFloat(parasitosCosto)
            };

            // Send the data via fetch
            fetch('update_parasitos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Parásitos actualizados exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar Parásitos: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    <script>
        function updateDestete(id) {
            const destetePeso = document.getElementById(`destete_${id}`).value.trim();
            const desteteFecha = document.getElementById(`destete-fecha_${id}`).value;

            // Basic validation
            if (destetePeso === '' || isNaN(destetePeso) || parseFloat(destetePeso) <= 0) {
                alert('Por favor, ingrese un peso válido para el destete.');
                return;
            }

            if (desteteFecha === '') {
                alert('Por favor, seleccione una fecha válida para el destete.');
                return;
            }

            // Confirm before updating
            if (!confirm('¿Está seguro de que desea actualizar el destete?')) {
                return;
            }

            // Prepare data to send
            const data = {
                id: id,
                destete_peso: parseFloat(destetePeso),
                destete_fecha: desteteFecha
            };

            // Send the data via fetch
            fetch('update_destete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Destete actualizado exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar el destete: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    <script>
        function updatePrenez(id) {
            const prenezNumero = document.getElementById(`prenez-numero_${id}`).value.trim();
            const prenezFecha = document.getElementById(`prenez-fecha_${id}`).value;

            // Basic validation
            if (prenezNumero === '' || isNaN(prenezNumero) || parseFloat(prenezNumero) <= 0) {
                alert('Por favor, ingrese un número válido para Preñez.');
                return;
            }

            if (prenezFecha === '') {
                alert('Por favor, seleccione una fecha válida para Preñez.');
                return;
            }

            // Confirm before updating
            if (!confirm('¿Está seguro de que desea actualizar la Preñez?')) {
                return;
            }

            // Prepare data to send
            const data = {
                id: id,
                prenez_numero: parseFloat(prenezNumero),
                prenez_fecha: prenezFecha
            };

            // Send the data via fetch
            fetch('update_prenez.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Preñez actualizada exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar la Preñez: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    <script>
        function updateParto(id) {
            const partoNumero = document.getElementById(`parto-numero_${id}`).value.trim();
            const partoFecha = document.getElementById(`parto-fecha_${id}`).value;

            // Basic validation
            if (partoNumero === '' || isNaN(partoNumero) || parseFloat(partoNumero) <= 0) {
                alert('Por favor, ingrese un número válido para el Parto.');
                return;
            }

            if (partoFecha === '') {
                alert('Por favor, seleccione una fecha válida para el Parto.');
                return;
            }

            // Confirm before updating
            if (!confirm('¿Está seguro de que desea actualizar el Parto?')) {
                return;
            }

            // Prepare data to send
            const data = {
                id: id,
                parto_numero: parseFloat(partoNumero),
                parto_fecha: partoFecha
            };

            // Send the data via fetch
            fetch('update_parto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Parto actualizado exitosamente.');
                    location.reload(); // Reload to see updated values
                } else {
                    alert('Error al actualizar el Parto: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud.');
            });
        }
    </script>
    
</body>
</html>

<?php
$conn->close();
?> 