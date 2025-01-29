<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ganagram";

// Database connection
$conn = new mysqli("localhost", $username,$password , "ganagram");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Animal History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-section {
            margin-bottom: 40px;
        }
        .section-title {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 48px;
            font-weight:bolder;
            color: #83956e;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
        }
        
        /* Responsive font sizes */
        @media screen and (max-width: 768px) {
            .page-title {
                font-size: 36px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .page-title {
                font-size: 24px;
            }
            .section-title {
                font-size: 18px;
            }
            .desktop-table {
                display: none;
            }
            .mobile-table {
                display: block;
            }
        }
        @media screen and (min-width: 320) {
            .desktop-table {
                display: block;
            }
            .mobile-table {
                display: none;
            }
        }
        .dtr-details {
            width: 100%;
        }
        
        /* Specific styles for Carne table */
        #pesoTable {
            width: 100% !important;
        }
        
        /* Desktop */
        @media screen and (min-width: 1024px) {
            #pesoTable th, 
            #pesoTable td {
                min-width: 150px;
            }
        }
        
        /* Tablet */
        @media screen and (max-width: 768px) {
            #pesoTable th, 
            #pesoTable td {
                min-width: 100px;
                font-size: 14px;
                padding: 8px 4px;
            }
        }
        
        /* Mobile */
        @media screen and (max-width: 480px) {
            #pesoTable th, 
            #pesoTable td {
                min-width: 80px;
                font-size: 12px;
                padding: 6px 3px;
            }
        }
        
        /* Center align all content in Carne table */
        #pesoTable th,
        #pesoTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }
        
        /* Center align DataTables controls for Carne table */
        #pesoTable_wrapper .dataTables_filter,
        #pesoTable_wrapper .dataTables_length,
        #pesoTable_wrapper .dataTables_info,
        #pesoTable_wrapper .dataTables_paginate {
            text-align: center !important;
        }
        
        /* Specific styles for Leche table */
        #lecheTable {
            width: 100% !important;
        }
        
        /* Desktop */
        @media screen and (min-width: 1024px) {
            #lecheTable th, 
            #lecheTable td {
                min-width: 150px;
            }
        }
        
        /* Tablet */
        @media screen and (max-width: 768px) {
            #lecheTable th, 
            #lecheTable td {
                min-width: 100px;
                font-size: 14px;
                padding: 8px 4px;
            }
        }
        
        /* Mobile */
        @media screen and (max-width: 480px) {
            #lecheTable th, 
            #lecheTable td {
                min-width: 80px;
                font-size: 12px;
                padding: 6px 3px;
            }
        }
        
        /* Center align all content in Leche table */
        #lecheTable th,
        #lecheTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }
        
        /* Center align DataTables controls for Leche table */
        #lecheTable_wrapper .dataTables_filter,
        #lecheTable_wrapper .dataTables_length,
        #lecheTable_wrapper .dataTables_info,
        #lecheTable_wrapper .dataTables_paginate {
            text-align: center !important;
        }
        
        /* Specific styles for Alimentacion table */
        #alimentacionTable {
            width: 100% !important;
        }
        
        /* Desktop */
        @media screen and (min-width: 1024px) {
            #alimentacionTable th, 
            #alimentacionTable td {
                min-width: 150px;
            }
        }
        
        /* Tablet */
        @media screen and (max-width: 768px) {
            #alimentacionTable th, 
            #alimentacionTable td {
                min-width: 100px;
                font-size: 14px;
                padding: 8px 4px;
            }
        }
        
        /* Mobile */
        @media screen and (max-width: 480px) {
            #alimentacionTable th, 
            #alimentacionTable td {
                min-width: 80px;
                font-size: 12px;
                padding: 6px 3px;
            }
        }
        
        /* Center align all content in Alimentacion table */
        #alimentacionTable th,
        #alimentacionTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }
        
        /* Center align DataTables controls for Alimentacion table */
        #alimentacionTable_wrapper .dataTables_filter,
        #alimentacionTable_wrapper .dataTables_length,
        #alimentacionTable_wrapper .dataTables_info,
        #alimentacionTable_wrapper .dataTables_paginate {
            text-align: center !important;
        }
        
        /* Styles for Vacunas, Baños, Parasitos, Reproduccion, and Preñez y Parto tables */
        #vacunasTable, #banosTable, #parasitosTable, #reproduccionTable, #prenezTable, #partoTable {
            width: 100% !important;
        }

        /* Desktop */
        @media screen and (min-width: 1024px) {
            #vacunasTable th, #vacunasTable td,
            #banosTable th, #banosTable td,
            #parasitosTable th, #parasitosTable td,
            #reproduccionTable th, #reproduccionTable td,
            #prenezTable th, #prenezTable td,
            #partoTable th, #partoTable td {
                min-width: 150px;
            }
        }

        /* Tablet */
        @media screen and (max-width: 768px) {
            #vacunasTable th, #vacunasTable td,
            #banosTable th, #banosTable td,
            #parasitosTable th, #parasitosTable td,
            #reproduccionTable th, #reproduccionTable td,
            #prenezTable th, #prenezTable td,
            #partoTable th, #partoTable td {
                min-width: 100px;
                font-size: 14px;
                padding: 8px 4px;
            }
        }

        /* Mobile */
        @media screen and (max-width: 480px) {
            #vacunasTable th, #vacunasTable td,
            #banosTable th, #banosTable td,
            #parasitosTable th, #parasitosTable td,
            #reproduccionTable th, #reproduccionTable td,
            #prenezTable th, #prenezTable td,
            #partoTable th, #partoTable td {
                min-width: 80px;
                font-size: 12px;
                padding: 6px 3px;
            }
        }

        /* Center align all content */
        #vacunasTable th, #vacunasTable td,
        #banosTable th, #banosTable td,
        #parasitosTable th, #parasitosTable td,
        #reproduccionTable th, #reproduccionTable td,
        #prenezTable th, #prenezTable td,
        #partoTable th, #partoTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Center align DataTables controls */
        #vacunasTable_wrapper .dataTables_filter,
        #vacunasTable_wrapper .dataTables_length,
        #vacunasTable_wrapper .dataTables_info,
        #vacunasTable_wrapper .dataTables_paginate,
        #banosTable_wrapper .dataTables_filter,
        #banosTable_wrapper .dataTables_length,
        #banosTable_wrapper .dataTables_info,
        #banosTable_wrapper .dataTables_paginate,
        #parasitosTable_wrapper .dataTables_filter,
        #parasitosTable_wrapper .dataTables_length,
        #parasitosTable_wrapper .dataTables_info,
        #parasitosTable_wrapper .dataTables_paginate,
        #reproduccionTable_wrapper .dataTables_filter,
        #reproduccionTable_wrapper .dataTables_length,
        #reproduccionTable_wrapper .dataTables_info,
        #reproduccionTable_wrapper .dataTables_paginate,
        #prenezTable_wrapper .dataTables_filter,
        #prenezTable_wrapper .dataTables_length,
        #prenezTable_wrapper .dataTables_info,
        #prenezTable_wrapper .dataTables_paginate,
        #partoTable_wrapper .dataTables_filter,
        #partoTable_wrapper .dataTables_length,
        #partoTable_wrapper .dataTables_info,
        #partoTable_wrapper .dataTables_paginate {
            text-align: center !important;
        }

        .section-title {
            background-color: #83956e;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .sub-section-title {
            color: #689260;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #83956e;
            border-color: #83956e;
        }

        .btn-primary:hover {
            background-color: #689260;
            border-color: #689260;
        }

        /* DataTables custom styling */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #83956e;
            border-radius: 4px;
        }

        .dataTables_wrapper .paginate_button.current {
            background: #83956e !important;
            color: white !important;
            border: 1px solid #83956e !important;
        }

        .dataTables_wrapper .paginate_button:hover {
            background: #689260 !important;
            color: white !important;
            border: 1px solid #689260 !important;
        }

        /* Add these styles within the existing <style> tag */
        .header-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .animal-name {
            text-align: center;
            color: #689260;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        @media screen and (max-width: 768px) {
            .animal-name {
                font-size: 20px;
            }
        }

        @media screen and (max-width: 480px) {
            .animal-name {
                font-size: 18px;
            }
        }

        .back-btn {
            position: absolute;
            left: 20px;
            font-size: 64px;
            color: #83956e;
            text-decoration: none;
            transition: color 0.3s;
            z-index: 1000;
            margin-top: 5px;
        }

        .back-btn:hover {
            color: #689260;
        }

        @media screen and (max-width: 768px) {
            .back-btn {
                font-size: 56px;
                left: 15px;
            }
        }

        @media screen and (max-width: 480px) {
            .back-btn {
                font-size: 48px;
                left: 10px;
            }
        }

        /* Remove the margin-left from input-group */
        /*.input-group {
            margin-left: 100px;
        }*/

        #concentradoTable th,
        #concentradoTable td {
            text-align: center !important;
            vertical-align: middle !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Add back button before the header container -->
        <a href="inventario.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <?php
        // Get animal name if tagid is provided
        $animalName = '';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $nameQuery = "SELECT nombre FROM ganado WHERE tagid = '$tagid'";
            $nameResult = $conn->query($nameQuery);
            if ($nameResult && $nameResult->num_rows > 0) {
                $nameRow = $nameResult->fetch_assoc();
                $animalName = $nameRow['nombre'];
            }
        }
        ?>
        <div class="header-container">
            <div>
                <h1 class="page-title">HISTÓRICOS</h1>
                <?php if (!empty($animalName)): ?>
                    <div class="animal-name">(<?php echo htmlspecialchars(strtoupper($animalName)); ?>)</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" id="search" name="search" placeholder="Buscar por Tag ID..." 
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>

        <?php
        // Build the base query
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $baseQuery_peso = "SELECT * FROM h_peso WHERE h_tagid = '$tagid'";
        } else {
            $baseQuery_peso = "SELECT * FROM h_peso";
        }
        $result_peso = $conn->query($baseQuery_peso);

        ?>

        <!-- PRODUCCION Tables -->
        <div class="table-section">
            <h3 class="section-title">PRODUCCIÓN</h3>
            
            <!-- Peso Table -->
            <div class="mb-4">
                <h4 class="sub-section-title">Pesaje Animal</h4>
                <div class="table-responsive">
                    <table id="pesoTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Pesaje (Kg)</th>
                                <th>Precio ($/Kg)</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_peso->num_rows > 0) {
                                $result_peso->data_seek(0);
                                while($row = $result_peso->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['h_peso_fecha']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_peso']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_peso_precio']) . "</td>";                                    
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leche Table -->

            <?php
            // Build the base query
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $tagid = $conn->real_escape_string($_GET['search']);
                $baseQuery_leche = "SELECT * FROM h_leche WHERE h_tagid = '$tagid'";
            } else {
                $baseQuery_leche = "SELECT * FROM h_leche";
            }
            $result_leche = $conn->query($baseQuery_leche);
            ?>
            <div class="mb-4">
                <h4 class="sub-section-title">Pesaje Leche</h4>
                <div class="table-responsive">
                    <table id="lecheTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Pesaje (Kg)</th>
                                <th>Precio ($/Kg)</th>
                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_leche->num_rows > 0) {
                                $result_leche->data_seek(0);
                                while($row = $result_leche->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['h_leche_fecha']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_leche_peso']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_leche_precio']) . "</td>";                                    
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ALIMENTACION Table -->

        <?php
        // Build the base query
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $baseQuery_racion = "SELECT * FROM h_racion WHERE h_tagid = '$tagid'";
        } else {
            $baseQuery_racion = "SELECT * FROM h_racion";
        }
        $result_racion = $conn->query($baseQuery_racion);
        ?>
        <div class="table-section">
            <h3 class="section-title">CONCENTRADO</h3>
            <div class="table-responsive">
                <table id="concentradoTable" class="table table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Alimento</th>
                            <th>Peso Ración (Kg)</th>
                            <th>Costo Ración ($)</th>
                            <th>Fecha Comienzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_racion->num_rows > 0) {
                            $result_racion->data_seek(0);
                            while($row = $result_racion->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['h_racion_nombre']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['h_racion_peso']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['h_racion_costo']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['h_racion_fecha']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SALUD Table -->

        <?php
        // Build the base query
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $baseQuery_vacuna = "SELECT * FROM h_vacuna WHERE h_tagid = '$tagid'";
        } else {
            $baseQuery_vacuna = "SELECT * FROM h_vacuna";
        }
        $result_vacuna = $conn->query($baseQuery_vacuna);
        ?>
        <div class="table-section">
            <h3 class="section-title">SALUD</h3>
            
            <!-- Mobile version -->
            <div class="mobile-table">
                <!-- Vacunas Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Vacunas</h4>
                    <div class="table-responsive">
                        <table id="vacunasTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Vacuna Nombre</th>
                                    <th>Costo ($)</th>
                                    <th>Fecha Vacunacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_vacuna->num_rows > 0) {
                                    $result_vacuna->data_seek(0);
                                    while($row = $result_vacuna->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['h_vacuna']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_vacuna_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_vacuna_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Baños Table -->

                <?php
                // Build the base query
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $tagid = $conn->real_escape_string($_GET['search']);
                    $baseQuery_bano = "SELECT * FROM h_bano WHERE h_tagid = '$tagid'";
                } else {
                    $baseQuery_bano = "SELECT * FROM h_bano";
                }
                $result_bano = $conn->query($baseQuery_bano);
                ?>
                <div class="mb-4">
                    <h4 class="sub-section-title">Baños</h4>
                    <div class="table-responsive">
                        <table id="banosTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Costo ($)</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_bano->num_rows > 0) {
                                    $result_bano->data_seek(0);
                                    while($row = $result_bano->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['h_bano']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_bano_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_bano_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Parásitos Table -->

                <?php
                // Build the base query
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $tagid = $conn->real_escape_string($_GET['search']);
                    $baseQuery_parasitos = "SELECT * FROM h_parasitos WHERE h_tagid = '$tagid'";
                } else {
                    $baseQuery_parasitos = "SELECT * FROM h_parasitos";
                }
                $result_parasitos = $conn->query($baseQuery_parasitos);
                ?>
                <div class="mb-4">
                    <h4 class="sub-section-title">Parásitos</h4>
                    <div class="table-responsive">
                        <table id="parasitosTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Desparasitante</th>
                                    <th>Costo ($)</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_parasitos->num_rows > 0) {
                                    $result_parasitos->data_seek(0);
                                    while($row = $result_parasitos->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['h_parasitos']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_parasitos_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_parasitos_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- REPRODUCCION Table -->

        <?php
        // Build the base query
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $baseQuery_inseminacion = "SELECT * FROM h_inseminacion WHERE h_tagid = '$tagid'";
        } else {
            $baseQuery_inseminacion = "SELECT * FROM h_inseminacion";
        }
        $result_inseminacion = $conn->query($baseQuery_inseminacion);
        ?>
        <div class="table-section">
            <h3 class="section-title">REPRODUCCIÓN</h3>
            
            <!-- Desktop version -->
            <div class="desktop-table">
                <h4 class="sub-section-title">Inseminación</h4>
                <div class="table-responsive">
                    <table id="reproduccionTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Costo ($)</th>
                                <th>Fecha</th>                            
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_inseminacion->num_rows > 0) {
                                $result_inseminacion->data_seek(0);
                                while($row = $result_inseminacion->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['h_inseminacion']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_inseminacion_costo']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['h_inseminacion_fecha']) . "</td>";
                                    
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Mobile version -->
            <?php
            // Build the base query
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $tagid = $conn->real_escape_string($_GET['search']);
                $baseQuery_prenez = "SELECT * FROM h_prenez WHERE h_tagid = '$tagid'";
            } else {
                $baseQuery_prenez = "SELECT * FROM h_prenez";
            }
            $result_prenez = $conn->query($baseQuery_prenez);
            ?>


            <div class="mobile-table">
                <!-- Preñez Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Preñez</h4>
                    <div class="table-responsive">
                        <table id="prenezTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Preñez Número</th>
                                    <th>Preñez Fecha</th>                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_prenez->num_rows > 0) {
                                    $result_prenez->data_seek(0);
                                    while($row = $result_prenez->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['h_prenez_numero']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_prenez_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mobile version -->
            <?php
            // Build the base query
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $tagid = $conn->real_escape_string($_GET['search']);
                $baseQuery_parto = "SELECT * FROM h_parto WHERE h_tagid = '$tagid'";
            } else {
                $baseQuery_parto = "SELECT * FROM h_parto";
            }
            $result_parto = $conn->query($baseQuery_parto);
            ?>


            <div class="mobile-table">
                <!-- Parto Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Parto</h4>
                    <div class="table-responsive">
                        <table id="partoTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Parto Número</th>
                                    <th>Parto Fecha</th>                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_parto->num_rows > 0) {
                                    $result_parto->data_seek(0);
                                    while($row = $result_parto->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['h_parto_numero']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['h_parto_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize all tables with responsive feature
            $('#pesoTable, #lecheTable, #alimentacionTable, #saludTable, #reproduccionTable, #prenezTable, #partoTable').DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "Filter results:"
                }
            });
            
            if (!$.fn.DataTable.isDataTable('#pesoTable')) {
                $('#pesoTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    autoWidth: false,
                    scrollX: false,
                    columnDefs: [
                        {
                            targets: '_all',
                            className: 'dt-head-center dt-body-center',
                            width: '33.33%'
                        }
                    ]
                });
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
