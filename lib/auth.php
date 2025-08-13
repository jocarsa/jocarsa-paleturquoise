<?php
// lib/auth.php
session_start();
function require_login() {
    if (!isset($_SESSION["user"])) {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok"=>false,"error"=>"No autenticado"]);
        exit;
    }
}
function current_user() { return $_SESSION["user"] ?? null; }
?>
