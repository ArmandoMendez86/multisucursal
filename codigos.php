<?php
// Desactiva el límite de tiempo para que el script no se corte si hay muchos datos.
set_time_limit(0);

// Incluye tu clase de conexión a la base de datos.
require_once __DIR__ . '/config/Database.php';

// --- CONFIGURACIÓN ---
// Asegúrate de que el archivo CSV esté en la misma carpeta que este script (la carpeta 'public').
$csvFilePath = __DIR__ . '/Productos_CodigoBarras.csv';
// --------------------

echo "<h1>Poblando Tabla de Códigos de Barras</h1>";

if (!file_exists($csvFilePath)) {
    die("<p style='color:red;'>Error: No se encontró el archivo CSV en la ruta: " . htmlspecialchars($csvFilePath) . "</p>");
}

// Conexión a la base de datos
try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p style='color:red;'>Error de conexión a la base de datos: " . $e->getMessage() . "</p>");
}

// Array para rastrear los códigos de barras ya insertados y evitar duplicados.
$insertedBarcodes = [];
$totalRows = 0;
$insertedRows = 0;
$skippedRows = 0;
$errorRows = 0;

// Preparamos la consulta una sola vez para mejorar el rendimiento.
$sql = "INSERT INTO producto_codigos (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
$stmt = $conn->prepare($sql);

// Abrimos el archivo CSV para leerlo.
$fileHandle = fopen($csvFilePath, 'r');

if ($fileHandle === false) {
    die("<p style='color:red;'>Error: No se pudo abrir el archivo CSV.</p>");
}

// Iniciamos una transacción para hacer el proceso más rápido y seguro.
$conn->beginTransaction();

// Saltamos la primera línea (el encabezado del CSV).
fgetcsv($fileHandle);

try {
    // Leemos el archivo línea por línea.
    while (($data = fgetcsv($fileHandle, 1000, ",")) !== false) {
        $totalRows++;

        // Asignamos los valores de las columnas a variables.
        $id_producto = trim($data[0]);
        $codigo_barras = trim($data[1]);

        // Verificamos si el producto ID es válido y el código no está vacío.
        if (empty($id_producto) || !is_numeric($id_producto) || empty($codigo_barras)) {
            echo "<p style='color:orange;'>Fila #{$totalRows} omitida: ID de producto o código de barras inválido.</p>";
            $skippedRows++;
            continue;
        }

        // --- Lógica para evitar duplicados ---
        if (in_array($codigo_barras, $insertedBarcodes)) {
            echo "<p style='color:blue;'>Fila #{$totalRows} omitida: El código de barras '{$codigo_barras}' ya fue procesado para otro producto.</p>";
            $skippedRows++;
            continue;
        }

        // Intentamos insertar el registro.
        try {
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->bindParam(':codigo_barras', $codigo_barras, PDO::PARAM_STR);
            $stmt->execute();
            
            // Si la inserción fue exitosa, lo añadimos a nuestro registro.
            $insertedBarcodes[] = $codigo_barras;
            $insertedRows++;

        } catch (PDOException $e) {
            // Capturamos errores, por ejemplo, si un id_producto no existe en la tabla `productos`.
            echo "<p style='color:red;'>Error en la fila #{$totalRows}: " . $e->getMessage() . " (Producto ID: {$id_producto}, Código: {$codigo_barras})</p>";
            $errorRows++;
        }
    }

    // Si todo salió bien, confirmamos los cambios en la base de datos.
    $conn->commit();

} catch (Exception $e) {
    // Si algo falla, revertimos todo.
    $conn->rollBack();
    echo "<p style='color:red;'><strong>Ocurrió un error crítico y se revirtieron todos los cambios:</strong> " . $e->getMessage() . "</p>";
}

fclose($fileHandle);

// --- Resumen Final ---
echo "<h2>Proceso Finalizado</h2>";
echo "<ul>";
echo "<li><strong>Filas leídas del CSV:</strong> {$totalRows}</li>";
echo "<li><strong>Códigos insertados correctamente:</strong> <span style='color:green;'>{$insertedRows}</span></li>";
echo "<li><strong>Filas omitidas (duplicados o inválidas):</strong> <span style='color:blue;'>{$skippedRows}</span></li>";
echo "<li><strong>Filas con error (ej. ID de producto no existe):</strong> <span style='color:red;'>{$errorRows}</span></li>";
echo "</ul>";
?>
