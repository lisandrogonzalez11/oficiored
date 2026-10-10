<?php
// Requiere que $pagina_activa esté definida antes de incluir.
// Requiere que la sesión esté iniciada y funciones cargadas.
$pagina_activa = $pagina_activa ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo_pagina ?? 'Panel Admin', ENT_QUOTES, 'UTF-8') ?> — OficioRed Admin</title>
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <link rel="apple-touch-icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/admin.css">
  <!-- Lucide se carga en el footer para que el DOM ya esté completo -->
</head>
<body class="admin-body">

<header class="admin-header">
  <div class="admin-header__inner">
    <a href="index.php" class="admin-header__brand">
      <img src="../assets/img/logo.png" alt="OficioRed" width="28" height="28" class="admin-logo-img">
      OficioRed Admin
    </a>

    <button class="admin-nav__toggle" id="nav-toggle" aria-label="Menú" aria-expanded="false">
      <i data-lucide="menu"></i>
    </button>

    <nav class="admin-nav" id="admin-nav">
      <a href="index.php"
         class="admin-nav__link <?= $pagina_activa === 'inicio' ? 'admin-nav__link--activo' : '' ?>">
        <i data-lucide="layout-dashboard"></i> Inicio
      </a>
      <a href="profesionales.php"
         class="admin-nav__link <?= $pagina_activa === 'profesionales' ? 'admin-nav__link--activo' : '' ?>">
        <i data-lucide="users"></i> Profesionales
      </a>
      <a href="categorias.php"
         class="admin-nav__link <?= $pagina_activa === 'categorias' ? 'admin-nav__link--activo' : '' ?>">
        <i data-lucide="tag"></i> Categorías
      </a>
      <a href="zonas.php"
         class="admin-nav__link <?= $pagina_activa === 'zonas' ? 'admin-nav__link--activo' : '' ?>">
        <i data-lucide="map-pin"></i> Zonas
      </a>
      <a href="apariencia.php"
         class="admin-nav__link admin-nav__link--apariencia <?= $pagina_activa === 'apariencia' ? 'admin-nav__link--apariencia-activo' : '' ?>">
        <i data-lucide="palette"></i> Apariencia
      </a>
      <a href="logout.php" class="admin-nav__link admin-nav__link--logout">
        <i data-lucide="log-out"></i> Salir
      </a>
    </nav>
  </div>
</header>

<?php if (!empty($_SESSION['flash_admin'])): ?>
<div class="admin-container" style="padding-top:1rem;">
  <div class="alerta alerta-<?= htmlspecialchars($_SESSION['flash_admin_tipo'] ?? 'info', ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars($_SESSION['flash_admin'], ENT_QUOTES, 'UTF-8') ?>
  </div>
</div>
<?php
  unset($_SESSION['flash_admin'], $_SESSION['flash_admin_tipo']);
endif;
?>

<main class="admin-main">
  <div class="admin-container">
