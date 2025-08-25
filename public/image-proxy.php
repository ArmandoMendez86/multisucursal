<?php
// Archivo: /multi-sucursal/public/image-proxy.php

// Verificamos que se haya solicitado una ruta de archivo.
if (!isset($_GET['path'])) {
    http_response_code(400); // Bad Request
    exit('Falta la ruta del archivo.');
}

$requested_path = $_GET['path'];

// --- MEDIDA DE SEGURIDAD CRÍTICA ---
// Nos aseguramos de que solo se puedan solicitar archivos de las carpetas de imágenes.
// Esto previene que alguien intente acceder a archivos sensibles como ../config/config.php
$base_path = '/img/';
if (strpos($requested_path, $base_path) !== 0) {
    http_response_code(403); // Forbidden
    exit('Acceso denegado.');
}

// Limpiamos la ruta para evitar ataques de "Directory Traversal" (../)
$safe_path = realpath(__DIR__ . $requested_path);

// Verificamos que la ruta resuelta siga estando dentro de nuestro directorio de imágenes.
if (!$safe_path || strpos($safe_path, realpath(__DIR__ . '/img')) !== 0) {
    http_response_code(403); // Forbidden
    exit('Ruta no válida.');
}

// Si el archivo existe, lo servimos.
if (file_exists($safe_path) && is_file($safe_path)) {
    $mime_type = mime_content_type($safe_path);
    if ($mime_type) {
        header('Content-Type: ' . $mime_type);
        readfile($safe_path);
        exit;
    }
}

// Si el archivo no se encuentra, devolvemos un error 404.
http_response_code(404); // Not Found
exit('Imagen no encontrada.');