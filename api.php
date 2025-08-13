<?php
// api.php: backend JSON para AJAX
header("Content-Type: application/json; charset=utf-8");
require __DIR__ . "/lib/db.php";
require __DIR__ . "/lib/auth.php";

$db = db();
$action = $_POST["action"] ?? $_GET["action"] ?? null;

function j($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function must($cond, $msg){ if(!$cond){ j(["ok"=>false,"error"=>$msg]); } }

function is_admin() {
  $u = current_user();
  return $u && $u["rol"] === "admin";
}

// --- LOGIN ---
if ($action === "login") {
    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";
    $stmt = $db->prepare("SELECT id, nombre, email, usuario, password_hash, rol FROM users WHERE usuario=?");
    $stmt->execute([$usuario]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($password, $u["password_hash"])) {
        $_SESSION["user"] = ["id"=>$u["id"],"nombre"=>$u["nombre"],"email"=>$u["email"],"usuario"=>$u["usuario"],"rol"=>$u["rol"]];
        j(["ok"=>true,"user"=>$_SESSION["user"]]);
    } else {
        j(["ok"=>false,"error"=>"Usuario o contraseña incorrectos"]);
    }
}
if ($action === "logout") {
    session_destroy();
    j(["ok"=>true]);
}

// --- REQUIERE LOGIN PARA LO DEMÁS ---
require_login();

// --- USUARIOS (solo admin) ---
if ($action === "users.list") {
    must(is_admin(), "No autorizado");
    $rows = $db->query("SELECT id, nombre, email, usuario, rol, creado_en FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    j(["ok"=>true, "rows"=>$rows]);
}
if ($action === "users.create") {
    must(is_admin(), "No autorizado");
    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $usuario = trim($_POST["usuario"] ?? "");
    $rol = $_POST["rol"] ?? "gestor";
    $password = $_POST["password"] ?? "123456";
    must($nombre && $email && $usuario, "Campos obligatorios");
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (nombre,email,usuario,password_hash,rol) VALUES (?,?,?,?,?)");
    $stmt->execute([$nombre,$email,$usuario,$hash,$rol]);
    j(["ok"=>true, "id"=>$db->lastInsertId()]);
}
if ($action === "users.update") {
    must(is_admin(), "No autorizado");
    $id = (int)($_POST["id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $usuario = trim($_POST["usuario"] ?? "");
    $rol = $_POST["rol"] ?? "gestor";
    must($id, "ID requerido");
    $set = "nombre=?, email=?, usuario=?, rol=?";
    $params = [$nombre,$email,$usuario,$rol,$id];
    if (isset($_POST["password"]) && $_POST["password"] !== "") {
        $set .= ", password_hash=?";
        $params = [$nombre,$email,$usuario,$rol,password_hash($_POST["password"], PASSWORD_DEFAULT),$id];
    }
    $stmt = $db->prepare("UPDATE users SET $set WHERE id=?");
    $stmt->execute($params);
    j(["ok"=>true]);
}
if ($action === "users.delete") {
    must(is_admin(), "No autorizado");
    $id = (int)($_POST["id"] ?? 0);
    must($id, "ID requerido");
    $stmt = $db->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    j(["ok"=>true]);
}

// --- OFERTAS ---
if ($action === "offers.list") {
    $rows = $db->query("SELECT * FROM job_offers ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    j(["ok"=>true, "rows"=>$rows]);
}
if ($action === "offers.create") {
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "abierta";
    must($titulo, "Título requerido");
    $stmt = $db->prepare("INSERT INTO job_offers (titulo, descripcion, estado) VALUES (?,?,?)");
    $stmt->execute([$titulo,$descripcion,$estado]);
    j(["ok"=>true,"id"=>$db->lastInsertId()]);
}
if ($action === "offers.update") {
    $id = (int)($_POST["id"] ?? 0);
    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "abierta";
    must($id, "ID requerido");
    $stmt = $db->prepare("UPDATE job_offers SET titulo=?, descripcion=?, estado=? WHERE id=?");
    $stmt->execute([$titulo,$descripcion,$estado,$id]);
    j(["ok"=>true]);
}
if ($action === "offers.delete") {
    $id = (int)($_POST["id"] ?? 0);
    must($id, "ID requerido");
    $stmt = $db->prepare("DELETE FROM job_offers WHERE id=?");
    $stmt->execute([$id]);
    j(["ok"=>true]);
}

// --- CAMPOS DE OFERTA ---
if ($action === "fields.list") {
    $offer_id = (int)($_GET["offer_id"] ?? $_POST["offer_id"] ?? 0);
    must($offer_id, "offer_id requerido");
    $stmt = $db->prepare("SELECT * FROM job_fields WHERE offer_id=? ORDER BY orden, id");
    $stmt->execute([$offer_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    j(["ok"=>true, "rows"=>$rows]);
}
if ($action === "fields.create") {
    $offer_id = (int)($_POST["offer_id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $tipo = $_POST["tipo"] ?? "texto";
    $requerido = (int)($_POST["requerido"] ?? 0);
    $orden = (int)($_POST["orden"] ?? 0);
    must($offer_id && $nombre, "Campos obligatorios");
    $stmt = $db->prepare("INSERT INTO job_fields (offer_id,nombre,tipo,requerido,orden) VALUES (?,?,?,?,?)");
    $stmt->execute([$offer_id,$nombre,$tipo,$requerido,$orden]);
    j(["ok"=>true,"id"=>$db->lastInsertId()]);
}
if ($action === "fields.update") {
    $id = (int)($_POST["id"] ?? 0);
    must($id, "ID requerido");
    $nombre = trim($_POST["nombre"] ?? "");
    $tipo = $_POST["tipo"] ?? "texto";
    $requerido = (int)($_POST["requerido"] ?? 0);
    $orden = (int)($_POST["orden"] ?? 0);
    $stmt = $db->prepare("UPDATE job_fields SET nombre=?, tipo=?, requerido=?, orden=? WHERE id=?");
    $stmt->execute([$nombre,$tipo,$requerido,$orden,$id]);
    j(["ok"=>true]);
}
if ($action === "fields.delete") {
    $id = (int)($_POST["id"] ?? 0);
    must($id, "ID requerido");
    $stmt = $db->prepare("DELETE FROM job_fields WHERE id=?");
    $stmt->execute([$id]);
    j(["ok"=>true]);
}

// --- CANDIDATOS ---
function file_safe_name($name) {
    return preg_replace('/[^A-Za-z0-9._-]/','_', $name);
}

if ($action === "applicants.list") {
    $offer_id = (int)($_GET["offer_id"] ?? $_POST["offer_id"] ?? 0);
    must($offer_id, "offer_id requerido");
    // columnas dinámicas
    $fields = $db->prepare("SELECT * FROM job_fields WHERE offer_id=? ORDER BY orden,id");
    $fields->execute([$offer_id]);
    $fields = $fields->fetchAll(PDO::FETCH_ASSOC);

    $apps = $db->prepare("SELECT * FROM applicants WHERE offer_id=? ORDER BY id DESC");
    $apps->execute([$offer_id]);
    $apps = $apps->fetchAll(PDO::FETCH_ASSOC);

    // Mapear valores
    foreach ($apps as &$a) {
        $vals = $db->prepare("SELECT af.*, jf.nombre, jf.tipo FROM applicant_fields af JOIN job_fields jf ON af.field_id=jf.id WHERE af.applicant_id=?");
        $vals->execute([$a["id"]]);
        $rows = $vals->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            if ($r["tipo"] === "texto") $map[$r["field_id"]] = $r["valor_texto"];
            elseif ($r["tipo"] === "checkbox") $map[$r["field_id"]] = $r["valor_bool"] ? 1 : 0;
            elseif ($r["tipo"] === "datetime") $map[$r["field_id"]] = $r["valor_datetime"];
            elseif ($r["tipo"] === "archivo") $map[$r["field_id"]] = $r["valor_archivo"];
        }
        $a["values"] = $map;
    }
    j(["ok"=>true, "fields"=>$fields, "rows"=>$apps]);
}

if ($action === "applicants.create_or_update") {
    $offer_id = (int)($_POST["offer_id"] ?? 0);
    $applicant_id = (int)($_POST["applicant_id"] ?? 0);
    must($offer_id, "offer_id requerido");

    if (!$applicant_id) {
        $stmt = $db->prepare("INSERT INTO applicants (offer_id) VALUES (?)");
        $stmt->execute([$offer_id]);
        $applicant_id = (int)$db->lastInsertId();
    } else {
        $stmt = $db->prepare("UPDATE applicants SET actualizado_en=datetime('now') WHERE id=?");
        $stmt->execute([$applicant_id]);
    }

    // Recuperar campos oferta
    $fields = $db->prepare("SELECT * FROM job_fields WHERE offer_id=? ORDER BY orden,id");
    $fields->execute([$offer_id]);
    $fields = $fields->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fields as $f) {
        $fid = (int)$f["id"];
        $tipo = $f["tipo"];
        $valor_texto = null; $valor_bool = null; $valor_datetime = null; $valor_archivo = null;

        if ($tipo === "texto") $valor_texto = $_POST["field_$fid"] ?? null;
        if ($tipo === "checkbox") $valor_bool = isset($_POST["field_$fid"]) && $_POST["field_$fid"] == "1" ? 1 : 0;
        if ($tipo === "datetime") $valor_datetime = $_POST["field_$fid"] ?? null;

        if ($tipo === "archivo" && isset($_FILES["field_$fid"]) && $_FILES["field_$fid"]["error"] === UPLOAD_ERR_OK) {
            $orig = $_FILES["field_$fid"]["name"];
            $tmp = $_FILES["field_$fid"]["tmp_name"];
            $safe = file_safe_name($orig);
            $dir = __DIR__ . "/uploads/offer_$offer_id/applicant_$applicant_id";
            if (!is_dir($dir)) { mkdir($dir, 0777, true); }
            $dest = $dir . "/" . $safe;
            move_uploaded_file($tmp, $dest);
            $valor_archivo = "uploads/offer_$offer_id/applicant_$applicant_id/" . $safe;
        } else if ($tipo === "archivo" && isset($_POST["field_$fid"])) {
            // permitir mantener valor existente al editar
            $valor_archivo = $_POST["field_$fid"];
        }

        // UPSERT
        $exists = $db->prepare("SELECT id FROM applicant_fields WHERE applicant_id=? AND field_id=?");
        $exists->execute([$applicant_id,$fid]);
        if ($exists->fetchColumn()) {
            $stmt = $db->prepare("UPDATE applicant_fields SET valor_texto=?, valor_bool=?, valor_datetime=?, valor_archivo=? WHERE applicant_id=? AND field_id=?");
            $stmt->execute([$valor_texto,$valor_bool,$valor_datetime,$valor_archivo,$applicant_id,$fid]);
        } else {
            $stmt = $db->prepare("INSERT INTO applicant_fields (applicant_id, field_id, valor_texto, valor_bool, valor_datetime, valor_archivo) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$applicant_id,$fid,$valor_texto,$valor_bool,$valor_datetime,$valor_archivo]);
        }
    }

    j(["ok"=>true, "applicant_id"=>$applicant_id]);
}

if ($action === "applicants.delete") {
    $id = (int)($_POST["id"] ?? 0);
    must($id, "ID requerido");
    $stmt = $db->prepare("DELETE FROM applicants WHERE id=?");
    $stmt->execute([$id]);
    j(["ok"=>true]);
}

j(["ok"=>false, "error"=>"Acción no reconocida"]);
?>
