<?php
// Entrega de archivos subidos (requiere login)
require __DIR__ . "/lib/auth.php";
require_login();

$path = $_GET["path"] ?? "";
$real = realpath(__DIR__ . "/" . $path);
$base = realpath(__DIR__);
if (!$real || strpos($real, $base) !== 0 || !is_file($real)) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit;
}
$mime = mime_content_type($real) ?: "application/octet-stream";
header("Content-Type: $mime");
header("Content-Length: " . filesize($real));
header("Content-Disposition: inline; filename=\"" . basename($real) . "\"");
readfile($real);
?>
