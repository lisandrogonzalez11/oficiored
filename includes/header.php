<?php
// =============================================================
// includes/header.php — Encabezado HTML comun a todas las paginas
// Proyecto: OficioRed
// Asume que includes/funciones.php ya fue incluido por la pagina.
// =============================================================

iniciar_sesion();

if (!isset($titulo_pagina)) {
    $titulo_pagina = 'OficioRed';
}

$logueado = esta_logueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="OficioRed — Encontrá profesionales de oficio confiables en El Colorado, Formosa">
  <title><?= htmlspecialchars($titulo_pagina) ?> | OficioRed</title>

  <!-- Open Graph -->
  <meta property="og:title" content="OficioRed">
  <meta property="og:description" content="Conectamos vecinos con profesionales de oficio en El Colorado">
  <meta property="og:image" content="assets/img/og-image.jpg">
  <meta property="og:type" content="website">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

  <!-- Lucide Icons (version fijada para evitar cambios sorpresivos del CDN) -->
  <script src="https://unpkg.com/lucide@1.24.0"></script>

  <!-- Estilos del sitio -->
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

  <header class="header">
    <div class="container header__inner">

      <a href="index.php" class="header__logo">
        <img src="assets/img/logo.svg" alt="OficioRed" width="32" height="32">
        <span>OficioRed</span>
      </a>

      <button class="header__menu-toggle" id="menu-toggle"
              aria-label="Abrir menú">
        <i data-lucide="menu"></i>
      </button>

      <nav class="header__nav" id="header-nav">
        <a href="index.php">Inicio</a>
        <a href="buscar.php">Buscar</a>

        <?php if ($logueado): ?>
          <a href="dashboard.php">Mi perfil</a>
          <a href="logout.php">Cerrar sesión</a>
        <?php else: ?>
          <a href="registro.php">Registrate</a>
          <a href="login.php" class="btn btn-primary btn-sm">Ingresar</a>
        <?php endif; ?>

      </nav>
    </div>
  </header>

  <!-- Mensaje flash (si existe) -->
  <?php if (!empty($_SESSION['mensaje'])): ?>
    <div class="alerta alerta-<?= $_SESSION['mensaje_tipo'] ?? 'exito' ?> mensaje-flash">
      <i data-lucide="<?= ($_SESSION['mensaje_tipo'] ?? 'exito') === 'error' ? 'alert-circle' : 'check-circle' ?>"></i>
      <span><?= htmlspecialchars($_SESSION['mensaje']) ?></span>
    </div>
    <?php
      unset($_SESSION['mensaje']);
      unset($_SESSION['mensaje_tipo']);
    ?>
  <?php endif; ?>

  <main>
