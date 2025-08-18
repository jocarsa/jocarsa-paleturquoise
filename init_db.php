<?php
// init_db.php: Ejecuta una sola vez para crear tablas y usuario admin por defecto.
header("Content-Type: text/plain; charset=utf-8");
require __DIR__ . "/lib/db.php";

$db = db();

$db->exec("PRAGMA foreign_keys = ON");

$db->exec("
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  usuario TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  rol TEXT NOT NULL DEFAULT 'admin', -- admin (puede todo) / gestor (no gestiona usuarios)
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS job_offers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  descripcion TEXT,
  estado TEXT NOT NULL DEFAULT 'abierta', -- abierta / cerrada / archivada
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS job_fields (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  offer_id INTEGER NOT NULL REFERENCES job_offers(id) ON DELETE CASCADE,
  nombre TEXT NOT NULL,
  tipo TEXT NOT NULL CHECK (tipo IN ('archivo','texto','checkbox','datetime')),
  requerido INTEGER NOT NULL DEFAULT 0,
  orden INTEGER NOT NULL DEFAULT 0
);
");

$db->exec("
CREATE TABLE IF NOT EXISTS applicants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  offer_id INTEGER NOT NULL REFERENCES applicants(id) ON DELETE CASCADE,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT
);
");

/* Fix: the correct FK for applicants.offer_id is job_offers(id).
   If an older incorrect table exists, ensure correct one. */
$db->exec("
CREATE TABLE IF NOT EXISTS applicants_tmp_fix (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  offer_id INTEGER NOT NULL REFERENCES job_offers(id) ON DELETE CASCADE,
  creado_en TEXT NOT NULL DEFAULT (datetime('now')),
  actualizado_en TEXT
);
");

// If original 'applicants' has wrong FK, migrate rows (best-effort)
try {
  $count = $db->query("SELECT COUNT(*) FROM applicants_tmp_fix")->fetchColumn();
  if ($count == 0) {
    $db->exec("INSERT INTO applicants_tmp_fix (id, offer_id, creado_en, actualizado_en)
               SELECT id, offer_id, creado_en, actualizado_en FROM applicants");
    $db->exec("DROP TABLE applicants");
    $db->exec("ALTER TABLE applicants_tmp_fix RENAME TO applicants");
  } else {
    $db->exec("DROP TABLE applicants_tmp_fix");
  }
} catch (Exception $e) {
  // ignore if structure already correct
}

$db->exec("
CREATE TABLE IF NOT EXISTS applicant_fields (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  applicant_id INTEGER NOT NULL REFERENCES applicants(id) ON DELETE CASCADE,
  field_id INTEGER NOT NULL REFERENCES job_fields(id) ON DELETE CASCADE,
  valor_texto TEXT,
  valor_bool INTEGER,
  valor_datetime TEXT,
  valor_archivo TEXT,
  UNIQUE(applicant_id, field_id)
);
");

/* ===== NUEVA TABLA: ENTREVISTAS ===== */
$db->exec("
CREATE TABLE IF NOT EXISTS interviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  offer_id INTEGER NOT NULL REFERENCES job_offers(id) ON DELETE CASCADE,
  applicant_id INTEGER NOT NULL REFERENCES applicants(id) ON DELETE CASCADE,
  fecha_hora TEXT NOT NULL,
  numero INTEGER NOT NULL,
  resultado TEXT,
  observaciones TEXT,
  creado_en TEXT NOT NULL DEFAULT (datetime('now'))
);
");

// Crear usuario admin por defecto si no existe
$adminExists = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($adminExists == 0) {
    $hash = password_hash("admin", PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (nombre, email, usuario, password_hash, rol) VALUES (?,?,?,?,?)");
    $stmt->execute(["Administrador", "admin@example.com", "admin", $hash, "admin"]);
    echo "✅ Tablas creadas y usuario admin/admin generado.\n";
} else {
    echo "ℹ️ Tablas ya existen. No se crearon usuarios.\n";
}
echo "Listo.\n";
?>

