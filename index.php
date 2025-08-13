<?php
require __DIR__ . "/lib/auth.php";
if (isset($_SESSION["user"])) {
  header("Location: dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Acceso — Bolsa de Empleo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="centered">
  <div class="card">
    <h1>Bolsa de Empleo</h1>
    <form id="loginForm">
      <label>Usuario
        <input name="usuario" required>
      </label>
      <label>Contraseña
        <input name="password" type="password" required>
      </label>
      <button type="submit">Entrar</button>
      <div id="loginMsg" class="msg"></div>
    </form>
    <p class="muted">Usuario por defecto: <b>admin</b> / Contraseña: <b>admin</b><br>Ejecuta <code>init_db.php</code> la primera vez.</p>
  </div>
  <script>
    const f = document.getElementById('loginForm');
    const msg = document.getElementById('loginMsg');
    f.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(f);
      fd.append('action','login');
      const r = await fetch('api.php', {method:'POST', body:fd});
      const j = await r.json();
      if (j.ok) location.href = 'dashboard.php';
      else msg.textContent = j.error || 'Error';
    });
  </script>
</body>
</html>
