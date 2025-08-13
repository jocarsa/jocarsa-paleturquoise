<?php
require __DIR__ . "/lib/auth.php";
require_login();
$u = current_user();
$isAdmin = ($u["rol"] === "admin");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel — Bolsa de Empleo</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">Bolsa de Empleo</div>
    <div class="spacer"></div>
    <div class="user">Conectado como: <b><?=htmlspecialchars($u["nombre"])?></b> (<?=htmlspecialchars($u["rol"])?>)</div>
    <button id="logoutBtn">Salir</button>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <nav class="menu">
        <?php if ($isAdmin): ?>
          <button class="nav-link active" data-tab="usuarios">
            <span class="dot"></span> Usuarios
          </button>
        <?php endif; ?>
        <button class="nav-link <?= !$isAdmin ? 'active' : '' ?>" data-tab="ofertas">
          <span class="dot"></span> Ofertas
        </button>
        <button class="nav-link" data-tab="campos">
          <span class="dot"></span> Campos
        </button>
        <button class="nav-link" data-tab="candidatos">
          <span class="dot"></span> Candidatos
        </button>
      </nav>
    </aside>

    <main class="content">
      <?php if ($isAdmin): ?>
      <section id="panel-usuarios" class="panel">
        <h2>Usuarios</h2>
        <form id="userForm" class="grid">
          <input type="hidden" name="id">
          <label>Nombre<input name="nombre" required></label>
          <label>Email<input name="email" type="email" required></label>
          <label>Usuario<input name="usuario" required></label>
          <label>Rol
            <select name="rol">
              <option value="gestor">gestor</option>
              <option value="admin">admin</option>
            </select>
          </label>
          <label>Contraseña (opcional al editar)<input name="password" type="password"></label>
          <div class="row">
            <button type="submit">Guardar</button>
            <button type="button" id="userReset" class="secondary">Limpiar</button>
          </div>
        </form>
        <table id="usersTable"></table>
      </section>
      <?php endif; ?>

      <section id="panel-ofertas" class="panel <?= $isAdmin ? 'hidden' : '' ?>">
        <h2>Ofertas</h2>
        <form id="offerForm" class="grid">
          <input type="hidden" name="id">
          <label>Título<input name="titulo" required></label>
          <label>Estado
            <select name="estado">
              <option>abierta</option>
              <option>cerrada</option>
              <option>archivada</option>
            </select>
          </label>
          <label class="wide">Descripción<textarea name="descripcion" rows="3"></textarea></label>
          <div class="row">
            <button type="submit">Guardar</button>
            <button type="button" id="offerReset" class="secondary">Limpiar</button>
          </div>
        </form>
        <table id="offersTable"></table>
      </section>

      <section id="panel-campos" class="panel hidden">
        <h2>Campos de la oferta</h2>
        <div class="row">
          <label>Oferta
            <select id="fieldsOffer"></select>
          </label>
        </div>
        <form id="fieldForm" class="grid">
          <input type="hidden" name="id">
          <input type="hidden" name="offer_id">
          <label>Nombre<input name="nombre" required></label>
          <label>Tipo
            <select name="tipo">
              <option value="texto">Texto</option>
              <option value="checkbox">Checkbox (Sí/No)</option>
              <option value="datetime">Fecha/Hora</option>
              <option value="archivo">Archivo (CV, etc.)</option>
            </select>
          </label>
          <label>Requerido
            <select name="requerido">
              <option value="0">No</option>
              <option value="1">Sí</option>
            </select>
          </label>
          <label>Orden<input name="orden" type="number" value="0"></label>
          <div class="row">
            <button type="submit">Guardar</button>
            <button type="button" id="fieldReset" class="secondary">Limpiar</button>
          </div>
        </form>
        <table id="fieldsTable"></table>
      </section>

      <section id="panel-candidatos" class="panel hidden">
        <h2>Candidatos</h2>
        <div class="row">
          <label>Oferta
            <select id="appsOffer"></select>
          </label>
        </div>
        <form id="applicantForm" class="grid" enctype="multipart/form-data">
          <input type="hidden" name="applicant_id">
          <input type="hidden" name="offer_id">
          <div id="dynamicFields" class="wide"></div>
          <div class="row">
            <button type="submit">Guardar candidato</button>
            <button type="button" id="appReset" class="secondary">Limpiar</button>
          </div>
        </form>
        <div id="appsTableWrap"></div>
      </section>
    </main>
  </div>

  <script src="assets/app.js"></script>
  <script>
    // Logout
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      const fd = new FormData(); fd.append('action','logout');
      await fetch('api.php', {method:'POST', body:fd});
      location.href = 'index.php';
    });
  </script>
</body>
</html>

