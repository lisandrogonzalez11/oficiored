<?php
// =============================================================
// buscar.php — Resultados de busqueda de profesionales por oficio
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

// Termino de busqueda.
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Sin termino: volver al inicio antes de cualquier output HTML.
if ($q === '') {
    redirigir('index.php');
}

$titulo_pagina = 'Resultados para "' . $q . '"';

$resultados        = [];
$busqueda_invalida = false;

if (strlen($q) < 2) {
    $busqueda_invalida = true;
} else {
    $stmt = $pdo->prepare(
        "SELECT id_profesional, nombre, apellido, oficio,
                zona_cobertura, telefono, email
         FROM profesionales
         WHERE oficio LIKE :termino
         AND estado = 'activo'
         ORDER BY nombre ASC"
    );
    $stmt->execute([':termino' => '%' . $q . '%']);
    $resultados = $stmt->fetchAll();
}

require_once 'includes/header.php';
?>

<!-- BARRA DE BUSQUEDA COMPACTA -->
<section class="barra-busqueda-compacta">
  <div class="container">
    <form action="buscar.php" method="GET" class="form-busqueda">
      <input type="text" name="q" value="<?= sanitizar($q) ?>"
             placeholder="¿Qué oficio necesitás?"
             minlength="2" required>
      <button type="submit" class="btn btn-accent">
        <i data-lucide="search"></i> Buscar
      </button>
    </form>
  </div>
</section>

<!-- ENCABEZADO DE RESULTADOS -->
<section class="seccion">
  <div class="container">

    <?php if (!empty($busqueda_invalida)): ?>
      <div class="estado-vacio">
        <i data-lucide="alert-circle"></i>
        <p>Escribí al menos 2 caracteres para buscar.</p>
      </div>

    <?php elseif (empty($resultados)): ?>
      <div class="estado-vacio">
        <i data-lucide="alert-circle"></i>
        <p>No encontramos profesionales para "<?= sanitizar($q) ?>"</p>
        <p>Intentá con otro término o explorá las categorías</p>
        <a href="index.php" class="btn btn-secondary">Volver al inicio</a>
      </div>

    <?php else: ?>
      <h2>
        <i data-lucide="check-circle"></i>
        <?= count($resultados) ?> profesionales encontrados para
        "<?= sanitizar($q) ?>"
      </h2>

      <div class="grid-profesionales">
        <?php foreach ($resultados as $p): ?>
          <?php include 'includes/card-profesional.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
