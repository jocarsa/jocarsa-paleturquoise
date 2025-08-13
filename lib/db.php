<?php
// lib/db.php
// Conexión SQLite con creación automática de carpeta
$DB_PATH = __DIR__ . "/../data.sqlite";
try {
    $pdo = new PDO("sqlite:" . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"No se pudo abrir la base de datos: ".$e->getMessage()]);
    exit;
}
function db() {
    global $pdo;
    return $pdo;
}
?>
