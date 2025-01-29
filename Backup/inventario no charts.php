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

// Add this after the database connection and before the main query
$filters = array();
$filterValues = array(
    'especie' => array(),
    'sexo' => array(),
    'raza' => array(),
    'clasificacion' => array(),
    'estatus' => array()
);

// Get unique values for each filter - with error checking
foreach ($filterValues as $field => &$values) {
    $query = "SELECT DISTINCT $field FROM ganado WHERE $field IS NOT NULL AND $field != ''";
    
    // Add condition for dependent filters - only for raza and clasificacion
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
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GANAGRAM</title>
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
                grid-template-columns: repeat(3, 1fr);
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
            <option value="">Clasificacion</option>
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
                                        onclick="actualizarPeso(' . $row['id'] . ')" 
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
                                            onclick="actualizarLeche(' . $row['id'] . ')" 
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
                                        id="concentrado_' . $row['id'] . '"
                                    >                                
                            </p>    
                            <p><b>Ración (Kg):</b> 
                                <input type="number" 
                                    style="width: 63px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                    value="' . htmlspecialchars($row['racion_peso']) . '" 
                                    id="racion_' . $row['id'] . '"
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
                                       id="racion-precio_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                            title="Actualizar Ración" 
                                            onclick="actualizarRacion(' . $row['id'] . ')" 
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
                                       style="width: 97px; padding: 2px 5px; margin: 0 5px; vertical-align: middle;" 
                                       value="' . htmlspecialchars($row['bano_costo']) . '" 
                                       id="bano-costo_' . $row['id'] . '"
                                >                                
                            </p>
                            <div class="action-btn-container" style="display: flex; justify-content: center;">
                                <button class="action-btn weight-btn" 
                                        title="Actualizar Baño" 
                                        onclick="actualizarBano(' . $row['id'] . ')" 
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
                                        onclick="actualizarParasitos(' . $row['id'] . ')" 
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
                                        onclick="actualizarDestete(' . $row['id'] . ')" 
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
                                    id="prenez_numero' . $row['id'] . '">                                
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
                                        onclick="actualizarPrenez(' . $row['id'] . ')" 
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
                                    id="parto-numero' . $row['id'] . '">
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
                                        onclick="actualizarParto(' . $row['id'] . ')" 
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
                                        onclick="actualizarInseminacion(' . $row['id'] . ')" 
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

</body>
<script >
    // Main functionality for handling cards and modals
document.addEventListener('DOMContentLoaded', function() {
  // Get modal elements
  const newEntryModal = document.getElementById('newEntryModal');
  const updateEntryModal = document.getElementById('updateEntryModal');
  const closeButtons = document.getElementsByClassName('close');

  // Close modal when clicking on X button
  Array.from(closeButtons).forEach(button => {
      button.onclick = function() {
          newEntryModal.style.display = "none";
          updateEntryModal.style.display = "none";
      }
  });

  // Close modal when clicking outside
  window.onclick = function(event) {
      if (event.target === newEntryModal || event.target === updateEntryModal) {
          newEntryModal.style.display = "none";
          updateEntryModal.style.display = "none";
      }
  }

  // Handle image preview for new entries
  const newImageUpload = document.getElementById('newImageUpload');
  const newImagePreview = document.getElementById('newImagePreview');

  if (newImageUpload) {
      newImageUpload.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
              const reader = new FileReader();
              reader.onload = function(e) {
                  newImagePreview.src = e.target.result;
              }
              reader.readAsDataURL(file);
          }
      });
  }

  // Handle image preview for updates
  const imageUpload = document.getElementById('imageUpload');
  const imagePreview = document.getElementById('imagePreview');

  if (imageUpload) {
      imageUpload.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
              const reader = new FileReader();
              reader.onload = function(e) {
                  imagePreview.src = e.target.result;
              }
              reader.readAsDataURL(file);
          }
      });
  }

  // Handle form submissions
  const createEntryForm = document.getElementById('createEntryForm');
  if (createEntryForm) {
      createEntryForm.addEventListener('submit', handleNewEntrySubmit);
  }

  const updateEntryForm = document.getElementById('newEntryForm');
  if (updateEntryForm) {
      updateEntryForm.addEventListener('submit', handleUpdateSubmit);
  }
});

// Toggle details in cards
function toggleDetails(button) {
  const contactInfo = button.nextElementSibling;
  const isHidden = contactInfo.style.display === 'none' || !contactInfo.style.display;
  contactInfo.style.display = isHidden ? 'block' : 'none';
  button.textContent = isHidden ? 'VER MENOS' : 'VER MAS';
}

// Open new entry modal
function openModal() {
  const modal = document.getElementById('newEntryModal');
  modal.style.display = "block";
  // Reset form
  document.getElementById('createEntryForm').reset();
  document.getElementById('newImagePreview').src = './images/Agregar_Logo-png.png';
}

// Handle new entry form submission
async function handleNewEntrySubmit(e) {
  e.preventDefault();
  const formData = new FormData(e.target);

  try {
      const response = await fetch('create_animal.php', {
          method: 'POST',
          body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
          alert('Animal agregado exitosamente');
          location.reload();
      } else {
          alert('Error al agregar animal: ' + result.message);
      }
  } catch (error) {
      console.error('Error:', error);
      alert('Error al procesar la solicitud');
  }
}

// Delete animal
async function deleteAnimal(button, id) {
  if (!confirm('¿Está seguro de que desea eliminar este animal?')) {
      return;
  }

  try {
      const response = await fetch('delete_animal.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id: id })
      });

      const result = await response.json();

      if (result.success) {
          button.closest('.card').remove();
          alert('Animal eliminado exitosamente');
      } else {
          alert('Error al eliminar animal: ' + result.message);
      }
  } catch (error) {
      console.error('Error:', error);
      alert('Error al procesar la solicitud');
  }
}

function openHistory(tagid) {
    window.location.href = `historial.php?search=${encodeURIComponent(tagid)}`;
}

// Function to update Peso
async function actualizarPeso(animalId) {
    // Get input values
    const peso = document.getElementById(`peso-animal_${animalId}`).value;
    const pesoFecha = document.getElementById(`peso-fecha_${animalId}`).value;
    const pesoPrecio = document.getElementById(`peso-precio_${animalId}`).value;

    // Basic validation
    if (!peso || peso <= 0) {
        alert('Por favor, ingrese un peso válido.');
        return;
    }

    if (!pesoFecha) {
        alert('Por favor, seleccione una fecha válida.');
        return;
    }

    if (!pesoPrecio || pesoPrecio < 0) {
        alert('Por favor, ingrese un precio válido.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar el peso?')) {
        return;
    }

    // Prepare the data to send
    const data = {
        id: animalId,
        peso: parseFloat(peso),
        peso_fecha: pesoFecha,
        peso_precio: parseFloat(pesoPrecio)
    };

    try {
        const response = await fetch('update_weight.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Peso actualizado exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar el peso: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

async function actualizarParasitos(id) {
      const parasitos = document.getElementById(`parasitos_${id}`).value;
      const parasitosFecha = document.getElementById(`parasitos-fecha_${id}`).value;
      const parasitosCosto = document.getElementById(`parasitos-costo_${id}`).value;

      // Basic validation
      if (!parasitos) {
          alert('Por favor, ingrese un valor para Parasitos.');
          return;
      }

      if (!parasitosFecha) {
          alert('Por favor, seleccione una fecha para Parasitos.');
          return;
      }

      if (!parasitosCosto || parasitosCosto < 0) {
          alert('Por favor, ingrese un costo válido para Parasitos.');
          return;
      }

      // Confirm update
      if (!confirm('¿Está seguro de que desea actualizar los datos de Parasitos?')) {
          return;
      }

      // Prepare data
      const data = {
          id: id,
          parasitos: parasitos,
          parasitos_fecha: parasitosFecha,
          parasitos_costo: parseFloat(parasitosCosto)
      };

      try {
          const response = await fetch('update_parasitos.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify(data)
          });

          const result = await response.json();

          if (result.success) {
              alert('Datos de Parasitos actualizados exitosamente.');
              location.reload();
          } else {
              alert('Error al actualizar Parasitos: ' + result.message);
          }
      } catch (error) {
          console.error('Error:', error);
          alert('Error al procesar la solicitud.');
      }
  }


function actualizarRacion(id) {
    // Get input values
    const racionNombre = document.getElementById(`concentrado_${id}`).value.trim();
    const racionPeso = document.getElementById(`racion_${id}`).value;
    const racionFecha = document.getElementById(`racion-fecha_${id}`).value;
    const racionCosto = document.getElementById(`racion-precio_${id}`).value;

    // Basic validation
    if (!racionNombre) {
        alert('Por favor, ingrese el nombre del concentrado.');
        return;
    }

    if (!racionPeso || racionPeso < 0) {
        alert('Por favor, ingrese un peso de ración válido.');
        return;
    }

    if (!racionFecha) {
        alert('Por favor, seleccione una fecha válida para la ración.');
        return;
    }

    if (!racionCosto || racionCosto < 0) {
        alert('Por favor, ingrese un precio válido para la ración.');
        return;
    }

    // Prepare data to send
    const data = {
        id: id,
        racion_nombre: racionNombre,
        racion_peso: parseFloat(racionPeso),
        racion_costo: parseFloat(racionCosto),
        racion_fecha: racionFecha
    };

    // Send data via Fetch API
    fetch('update_racion_full.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Ración actualizada exitosamente.');
            location.reload(); // Reload to see the updated data
        } else {
            alert('Error al actualizar la ración: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function actualizarVacuna(animalId) {
    // Get input values
    const vacuna = document.getElementById(`vacuna_${animalId}`).value.trim();
    const vacunaFecha = document.getElementById(`vacuna-fecha_${animalId}`).value;
    const vacunaPrecio = document.getElementById(`vacuna-precio_${animalId}`).value.trim();

    // Basic validation
    if (vacuna === '') {
        alert('Por favor, ingrese el nombre de la vacuna.');
        return;
    }

    if (vacunaFecha === '') {
        alert('Por favor, seleccione una fecha de vacunación.');
        return;
    }

    if (vacunaPrecio === '' || isNaN(vacunaPrecio) || parseFloat(vacunaPrecio) < 0) {
        alert('Por favor, ingrese un precio de vacuna válido.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar la vacunación?')) {
        return;
    }

    // Prepare data to send
    const data = {
        id: animalId,
        vacuna: vacuna,
        vacuna_fecha: vacunaFecha,
        vacuna_costo: parseFloat(vacunaPrecio)
    };

    // Send the data via fetch
    fetch('update_vacuna.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Vacunación actualizada exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar vacunación: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    });
}

// Function to update Baño information
async function actualizarBano(animalId) {
    // Retrieve input values
    const bano = document.getElementById(`bano_${animalId}`).value.trim();
    const banoFecha = document.getElementById(`bano-fecha_${animalId}`).value;
    const banoCostoInput = document.getElementById(`bano-costo_${animalId}`).value;

    // Parse banoCosto to float
    const banoCosto = parseFloat(banoCostoInput);

    // Basic validation
    if (!bano) {
        alert('Por favor, ingrese un valor válido para Baño.');
        return;
    }

    if (!banoFecha) {
        alert('Por favor, seleccione una fecha válida para Baño.');
        return;
    }

    if (isNaN(banoCosto) || banoCosto < 0) {
        alert('Por favor, ingrese un costo válido para Baño.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar la información de Baño?')) {
        return;
    }

    // Prepare the data to send
    const data = {
        id: animalId,
        bano: bano,
        bano_fecha: banoFecha,
        bano_costo: banoCosto
    };

    try {
        const response = await fetch('update_bano_full.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Información de Baño actualizada exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar Baño: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

async function actualizarDestete(animalId) {
    const destetePeso = document.getElementById(`destete_${animalId}`).value;
    const desteteFecha = document.getElementById(`destete-fecha_${animalId}`).value;

    // Basic validation
    if (!destetePeso || destetePeso <= 0) {
        alert('Por favor, ingrese un peso válido.');
        return;
    }

    if (!desteteFecha) {
        alert('Por favor, seleccione una fecha válida.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar el destete?')) {
        return;
    }

    // Prepare the data to send
    const data = {
        id: animalId,
        destete_peso: parseFloat(destetePeso),
        destete_fecha: desteteFecha
    };

    try {
        const response = await fetch('update_destete_full.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Destete actualizado exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar el destete: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

async function actualizarPrenez(animalId) {
    const preñezNumero = document.getElementById(`prenez_numero${animalId}`).value;
    const preñezFecha = document.getElementById(`prenez-fecha_${animalId}`).value;

    // Basic validation
    if (!preñezNumero || preñezNumero <= 0) {
        alert('Por favor, ingrese un número de preñez válido.');
        return;
    }

    if (!preñezFecha) {
        alert('Por favor, seleccione una fecha de preñez.');
        return;
    }

    // Confirm update
    if (!confirm('¿Está seguro de que desea actualizar la preñez?')) {
        return;
    }

    // Prepare data
    const data = {
        id: animalId,
        preñez_numero: preñezNumero,
        preñez_fecha: preñezFecha
    };

    try {
        const response = await fetch('update_prenez_full.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Preñez actualizada exitosamente');
            location.reload();
        } else {
            alert('Error al actualizar la preñez: ' + result.message);
        }

    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    }
}

// Function to update Parto
async function actualizarParto(animalId) {
    // Retrieve input values
    const partoNumeroInput = document.getElementById(`parto-numero${animalId}`);
    const partoFechaInput = document.getElementById(`parto-fecha_${animalId}`);

    const partoNumero = partoNumeroInput.value;
    const partoFecha = partoFechaInput.value;

    // Basic validation
    if (!partoNumero || partoNumero <= 0) {
        alert('Por favor, ingrese un número de parto válido.');
        return;
    }

    if (!partoFecha) {
        alert('Por favor, seleccione una fecha válida.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar los datos de parto?')) {
        return;
    }

    // Prepare data payload
    const data = {
        id: animalId,
        parto_numero: parseInt(partoNumero, 10),
        parto_fecha: partoFecha
    };

    try {
        const response = await fetch('update_parto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Datos de parto actualizados exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar los datos de parto: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

// Function to update Inseminación details
async function actualizarInseminacion(animalId) {
    // Get input values
    const inseminacion = document.getElementById(`inseminacion-tipo_${animalId}`).value.trim();
    const inseminacionFecha = document.getElementById(`inseminacion-fecha_${animalId}`).value;
    const inseminacionCosto = document.getElementById(`inseminacion-costo_${animalId}`).value.trim();

    // Basic validation
    if (!inseminacion) {
        alert('Por favor, ingrese el tipo de inseminación.');
        return;
    }

    if (!inseminacionFecha) {
        alert('Por favor, seleccione una fecha válida.');
        return;
    }

    if (!inseminacionCosto || parseFloat(inseminacionCosto) < 0) {
        alert('Por favor, ingrese un costo válido para la inseminación.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar la inseminación?')) {
        return;
    }

    // Prepare the data to send
    const data = {
        id: animalId,
        inseminacion: inseminacion,
        inseminacion_fecha: inseminacionFecha,
        inseminacion_costo: parseFloat(inseminacionCosto)
    };

    try {
        const response = await fetch('update_inseminacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Inseminación actualizada exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar inseminación: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

async function actualizarLeche(animalId) {
    // Get input values
    const lechePeso = document.getElementById(`leche-peso_${animalId}`).value;
    const lecheFecha = document.getElementById(`leche-fecha_${animalId}`).value;
    const lechePrecio = document.getElementById(`leche-precio_${animalId}`).value;

    // Basic validation
    if (!lechePeso || lechePeso <= 0) {
        alert('Por favor, ingrese un peso válido.');
        return;
    }

    if (!lecheFecha) {
        alert('Por favor, seleccione una fecha válida.');
        return;
    }

    if (!lechePrecio || lechePrecio < 0) {
        alert('Por favor, ingrese un precio válido.');
        return;
    }

    // Confirm before updating
    if (!confirm('¿Está seguro de que desea actualizar la leche?')) {
        return;
    }

    // Prepare data
    const data = {
        id: animalId,
        leche_peso: parseFloat(lechePeso),
        leche_fecha: lecheFecha,
        leche_precio: parseFloat(lechePrecio)
    };

    try {
        const response = await fetch('update_leche_full.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Leche actualizada exitosamente.');
            location.reload(); // Reload to see updated values
        } else {
            alert('Error al actualizar la leche: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la solicitud.');
    }
}

</script>

</html>
<?php
$conn->close();
?> 