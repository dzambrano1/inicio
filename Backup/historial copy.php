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
        #vacunasTable, #banosTable, #parasitosTable, #reproduccionTable, #prenezPartoTable {
            width: 100% !important;
        }

        /* Desktop */
        @media screen and (min-width: 1024px) {
            #vacunasTable th, #vacunasTable td,
            #banosTable th, #banosTable td,
            #parasitosTable th, #parasitosTable td,
            #reproduccionTable th, #reproduccionTable td,
            #prenezPartoTable th, #prenezPartoTable td {
                min-width: 150px;
            }
        }

        /* Tablet */
        @media screen and (max-width: 768px) {
            #vacunasTable th, #vacunasTable td,
            #banosTable th, #banosTable td,
            #parasitosTable th, #parasitosTable td,
            #reproduccionTable th, #reproduccionTable td,
            #prenezPartoTable th, #prenezPartoTable td {
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
            #prenezPartoTable th, #prenezPartoTable td {
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
        #prenezPartoTable th, #prenezPartoTable td {
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
        #prenezPartoTable_wrapper .dataTables_filter,
        #prenezPartoTable_wrapper .dataTables_length,
        #prenezPartoTable_wrapper .dataTables_info,
        #prenezPartoTable_wrapper .dataTables_paginate {
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
                <button type="submit" class="btn btn-primary">Tagid Search</button>
            </div>
        </form>

        <?php
        // Build the base query
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $tagid = $conn->real_escape_string($_GET['search']);
            $baseQuery = "SELECT * FROM historico WHERE tagid = '$tagid'";
        } else {
            $baseQuery = "SELECT * FROM historico";
        }
        $result = $conn->query($baseQuery);

        // Modify the image query section after the base query
        if (isset($_GET['tagid']) && !empty($_GET['tagid'])) {
            $tagid = $conn->real_escape_string($_GET['tagid']);
            // Get image from ganado table
            $imageQuery = "SELECT imagen FROM ganado WHERE tagid = '$tagid'";
            $imageResult = $conn->query($imageQuery);
            $imageRow = $imageResult->fetch_assoc();
            $animalImage = !empty($imageRow['imagen']) ? $imageRow['imagen'] : 'assets/images/default-avatar.png';
        } else {
            $animalImage = 'assets/images/default-avatar.png';
        }
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
                                <th>Carne Pesaje (Kg)</th>
                                <th>Carne Precio ($)</th>
                                <th>Fecha Pesaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['peso']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['peso_precio']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['peso_fecha']) . "</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leche Table -->
            <div class="mb-4">
                <h4 class="sub-section-title">Pesaje Leche</h4>
                <div class="table-responsive">
                    <table id="lecheTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Leche Pesaje (Kg)</th>
                                <th>Precio Leche ($)</th>
                                <th>Fecha Pesaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['leche_peso']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leche_precio']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['leche_fecha']) . "</td>";
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
        <div class="table-section">
            <h3 class="section-title">ALIMENTACIÓN</h3>
            <div class="table-responsive">
                <table id="alimentacionTable" class="table table-striped table-bordered">
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
                        if ($result->num_rows > 0) {
                            $result->data_seek(0);
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['racion']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['racion_peso']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['racion_costo']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['racion_fecha']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SALUD Table -->
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
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['vacuna']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['vacuna_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['vacuna_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Baños Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Baños</h4>
                    <div class="table-responsive">
                        <table id="banosTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Baño Nombre</th>
                                    <th>Costo ($)</th>
                                    <th>Fecha Baño</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['bano']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['bano_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['bano_fecha']) . "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Parásitos Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Parásitos</h4>
                    <div class="table-responsive">
                        <table id="parasitosTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Desparasitante</th>
                                    <th>Costo ($)</th>
                                    <th>Fecha Desparasitacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['parasitos']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['parasitos_costo']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['parasitos_fecha']) . "</td>";
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
        <div class="table-section">
            <h3 class="section-title">REPRODUCCIÓN</h3>
            
            <!-- Desktop version -->
            <div class="desktop-table">
                <h4 class="sub-section-title">Inseminación</h4>
                <div class="table-responsive">
                    <table id="reproduccionTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Inseminación Tipo</th>
                                <th>Inseminación ($)</th>
                                <th>Inseminación Fecha</th>                            
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['inseminacion']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['inseminacion_costo']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['inseminacion_fecha']) . "</td>";
                                    
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Mobile version -->
            <div class="mobile-table">
                <!-- Preñez y Parto Table -->
                <div class="mb-4">
                    <h4 class="sub-section-title">Preñez y Parto</h4>
                    <div class="table-responsive">
                        <table id="prenezPartoTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Preñez Número</th>
                                    <th>Preñez Fecha</th>
                                    <th>Parto Número</th>
                                    <th>Parto Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $result->data_seek(0);
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['prenez_numero']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['prenez_fecha']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['parto_numero']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['parto_fecha']) . "</td>";
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
            $('#pesoTable, #lecheTable, #alimentacionTable, #saludTable, #reproduccionTable, #prenezPartoTable').DataTable({
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
