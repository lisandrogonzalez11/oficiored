<?php
// =============================================================
// perfil.php — Vista de detalle publica de un profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

// Validar el parametro id.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    redirigir('index.php');
}

// Consultar el profesional activo.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            biografia, fecha_registro, oficio, zona_cobertura
     FROM profesionales
     WHERE id_profesional = :id AND estado = 'activo'"
);
$stmt->execute([':id' => $id]);
$profesional = $stmt->fetch();

// No encontrado: mensaje flash y vuelta al inicio.
if (!$profesional) {
    $_SESSION['mensaje'] = 'Profesional no encontrado.';
    $_SESSION['mensaje_tipo'] = 'error';
    redirigir('index.php');
}

$titulo_pagina = $profesional['nombre'] . ' ' . $profesional['apellido'];

require_once 'includes/header.php';
?>

<!-- PERFIL PRINCIPAL -->
<section class="seccion perfil-header">
  <div class="container">
    <div class="perfil-avatar">
      <?= strtoupper(substr($profesional['nombre'], 0, 1)
          . substr($profesional['apellido'], 0, 1)) ?>
    </div>
    <h1><?= sanitizar($profesional['nombre'] . ' '
            . $profesional['apellido']) ?></h1>
    <span class="badge"><?= sanitizar($profesional['oficio']) ?></span>
    <p>
      <i data-lucide="map-pin"></i>
      <?= sanitizar($profesional['zona_cobertura']) ?>
    </p>
    <p class="texto-secundario">
      En OficioRed desde <?= formatear_fecha($profesional['fecha_registro']) ?>
    </p>
  </div>
</section>

<!-- DATOS DE CONTACTO -->
<section class="seccion tarjeta-contacto">
  <div class="container">
    <h2>Contactar a <?= sanitizar($profesional['nombre']) ?></h2>

    <?php if (!empty($profesional['telefono'])): ?>
      <a href="tel:<?= sanitizar($profesional['telefono']) ?>"
         class="contacto-link">
        <i data-lucide="phone"></i>
        <?= sanitizar($profesional['telefono']) ?>
      </a>
    <?php endif; ?>

    <a href="mailto:<?= sanitizar($profesional['email']) ?>"
       class="contacto-link">
      <i data-lucide="mail"></i>
      <?= sanitizar($profesional['email']) ?>
    </a>

    <p class="texto-secundario">
      Mencioná que lo encontraste en OficioRed
    </p>
  </div>
</section>

<!-- DESCRIPCION / BIOGRAFIA (solo si existe) -->
<?php if (!empty($profesional['biografia'])): ?>
  <section class="seccion">
    <div class="container">
      <h2>Sobre <?= sanitizar($profesional['nombre']) ?></h2>
      <p><?= nl2br(sanitizar($profesional['biografia'])) ?></p>
    </div>
  </section>
<?php endif; ?>

<!-- NAVEGACION DE RETORNO -->
<section class="seccion">
  <div class="container">
    <a href="javascript:history.back()" class="link-volver">
      <i data-lucide="arrow-left"></i> Volver a los resultados
    </a>
    <a href="index.php" class="link-volver">
      Ver más profesionales
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
