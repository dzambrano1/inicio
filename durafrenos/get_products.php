<?php
// Enable error reporting for debugging 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './conexion.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Connect to database
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Build query
    $query = "SELECT * FROM products WHERE 1=1";
    
    // Apply filters if provided
    if (!empty($_GET['category'])) {
        $category = mysqli_real_escape_string($conn, $_GET['category']);
        $query .= " AND category = '$category'";
    }
    
    if (!empty($_GET['make'])) {
        $make = mysqli_real_escape_string($conn, $_GET['make']);
        $query .= " AND make = '$make'";
    }
    
    if (!empty($_GET['model'])) {
        $model = mysqli_real_escape_string($conn, $_GET['model']);
        $query .= " AND model = '$model'";
    }
    
    if (!empty($_GET['year'])) {
        $year = mysqli_real_escape_string($conn, $_GET['year']);
        $query .= " AND year = '$year'";
    }
    
    // Order the results
    $query .= " ORDER BY category, make, model";
    
    // Execute query
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    // Fetch results
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    // Return as JSON
    echo json_encode($products);
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    // Close connection if it exists
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>

<script>
// Completely new implementation - place this at the end of your file before </body>
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM fully loaded - Setting up filters");
    
    // Direct reference to buttons
    const applyBtn = document.getElementById('applyFilters');
    const resetBtn = document.getElementById('resetFilters');
    
    if (!applyBtn || !resetBtn) {
        console.error("Filter buttons not found!");
        return;
    }
    
    // Apply filters
    applyBtn.onclick = function(e) {
        e.preventDefault();
        console.log("Apply button clicked");
        
        // Show loading message
        document.getElementById('productListing').innerHTML = '<div class="alert alert-info">Cargando productos...</div>';
        
        // Build filter parameters
        const category = document.getElementById('categoryFilter').value;
        const make = document.getElementById('makeFilter').value;
        const model = document.getElementById('modelFilter').value;
        const year = document.getElementById('yearFilter').value;
        
        // Create URL
        let url = 'get_products.php';
        let params = [];
        
        if (category) params.push('category=' + encodeURIComponent(category));
        if (make) params.push('make=' + encodeURIComponent(make));
        if (model) params.push('model=' + encodeURIComponent(model));
        if (year) params.push('year=' + encodeURIComponent(year));
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        console.log("Fetching URL:", url);
        
        // Use vanilla AJAX for simplicity
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                console.log("Server response:", xhr.responseText);
                try {
                    const data = JSON.parse(xhr.responseText);
                    displayProducts(data);
                } catch (e) {
                    console.error("JSON parse error:", e);
                    document.getElementById('productListing').innerHTML = 
                        '<div class="alert alert-danger">Error processing data: ' + e.message + '</div>';
                }
            } else {
                console.error("Server error:", xhr.status, xhr.statusText);
                document.getElementById('productListing').innerHTML = 
                    '<div class="alert alert-danger">Error del servidor: ' + xhr.status + ' ' + xhr.statusText + '</div>';
            }
        };
        
        xhr.onerror = function() {
            console.error("Network error");
            document.getElementById('productListing').innerHTML = 
                '<div class="alert alert-danger">Error de red - por favor intente nuevamente</div>';
        };
        
        xhr.send();
    };
    
    // Reset filters
    resetBtn.onclick = function(e) {
        e.preventDefault();
        console.log("Reset button clicked");
        
        // Clear all filter fields
        document.getElementById('categoryFilter').value = '';
        document.getElementById('makeFilter').value = '';
        document.getElementById('modelFilter').value = '';
        document.getElementById('yearFilter').value = '';
        
        // Reload all products
        applyBtn.click();
    };
    
    // Function to display products
    function displayProducts(products) {
        console.log("Displaying", products.length, "products");
        
        // Get the product listing container
        const container = document.getElementById('productListing');
        
        if (!products || products.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No se encontraron productos con los filtros seleccionados.</div>';
            return;
        }
        
        // Create the table structure
        let html = `
            <table id="productsTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Precio</th>
                        <th>Inventario</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Add each product to the table
        products.forEach(function(product) {
            const imageHtml = product.image 
                ? `<img src="${product.image}" alt="${product.make} ${product.model}" class="product-image" style="height:50px; cursor:pointer;" data-full-image="${product.image}">`
                : '<span class="text-muted">No imagen</span>';
                
            // Determine actions based on user role (directly checking PHP variable)
            let actionsHtml;
            
            if ('<?php echo isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin" ? "true" : "false"; ?>' === 'true') {
                // Admin actions
                actionsHtml = `
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-warning" onclick="editProduct(${product.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteProduct(${product.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            } else {
                // Customer actions
                actionsHtml = `
                    <div class="btn-group btn-group-sm">
                        ${parseInt(product.stock) > 0 ? 
                            `<button class="btn btn-primary" onclick="addToCart(${product.id})">
                                <i class="fas fa-cart-plus"></i>
                            </button>` : 
                            `<button class="btn btn-secondary" disabled>
                                <i class="fas fa-cart-plus"></i>
                            </button>`
                        }
                    </div>
                `;
            }
            
            // Add row to table
            html += `
                <tr>
                    <td>${product.code || ''}</td>
                    <td>${product.make || ''}</td>
                    <td>${product.model || ''}</td>
                    <td>${product.year || ''}</td>
                    <td>$${parseFloat(product.price).toFixed(2)}</td>
                    <td>${product.stock}</td>
                    <td>${imageHtml}</td>
                    <td>${actionsHtml}</td>
                </tr>
            `;
        });
        
        // Close the table
        html += `
                </tbody>
            </table>
        `;
        
        // Update the container
        container.innerHTML = html;
        
        // Initialize DataTable on the table (if jQuery and DataTables are available)
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            try {
                $('#productsTable').DataTable({
                    responsive: true,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                    }
                });
                console.log("DataTable initialized");
            } catch (e) {
                console.error("DataTable initialization error:", e);
            }
        } else {
            console.warn("jQuery or DataTables not available");
        }
        
        // Add click event to product images
        document.querySelectorAll('.product-image').forEach(function(img) {
            img.addEventListener('click', function() {
                const fullImage = this.getAttribute('data-full-image');
                const alt = this.getAttribute('alt');
                
                document.getElementById('enlargedImage').src = fullImage;
                document.getElementById('enlargedImage').alt = alt;
                document.getElementById('imageModalLabel').textContent = alt;
                
                // Show the modal (using Bootstrap's JS if available)
                if (typeof bootstrap !== 'undefined') {
                    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                    imageModal.show();
                } else {
                    // Fallback for when Bootstrap JS is not available
                    const modal = document.getElementById('imageModal');
                    modal.style.display = 'block';
                    modal.classList.add('show');
                }
            });
        });
    }
    
    // Initialize by loading all products on page load
    console.log("Triggering initial load");
    applyBtn.click();
});
</script>