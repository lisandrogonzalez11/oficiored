<?php
// =============================================================
// index.php — Home publica con buscador, categorias y destacados
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

$titulo_pagina = 'Inicio';

// Categorias para la seccion de explorar.
$categorias = obtener_categorias($pdo);

// Hasta 6 profesionales destacados (mas recientes).
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, oficio,
            zona_cobertura, telefono, email
     FROM profesionales
     WHERE estado = 'activo'
     ORDER BY fecha_registro DESC
     LIMIT 6"
);
$stmt->execute();
$destacados = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- HERO / BUSCADOR PRINCIPAL -->
<section class="hero">
  <div class="container">
    <h1>Encontrá el profesional que necesitás</h1>
    <p>Electricistas, plomeros, albañiles y más en El Colorado</p>
    <form action="buscar.php" method="GET" class="form-busqueda">
      <input type="text" name="q" id="campo-busqueda"
             placeholder="¿Qué oficio necesitás?"
             minlength="2" required>
      <button type="submit" class="btn btn-accent">
        <i data-lucide="search"></i> Buscar
      </button>
    </form>
  </div>
</section>

<!-- CATEGORIAS DESTACADAS -->
<section class="seccion seccion-categorias">
  <div class="container">
    <h2>Explorá por categoría</h2>
    <div class="grid-categorias">
      <?php foreach ($categorias as $cat): ?>
        <a href="buscar.php?q=<?= urlencode($cat['nombre']) ?>"
           class="card-categoria">
          <i data-lucide="<?= sanitizar($cat['icono_lucide']) ?>"></i>
          <span><?= sanitizar($cat['nombre']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PROFESIONALES DESTACADOS -->
<section class="seccion seccion-destacados">
  <div class="container">
    <h2>Profesionales disponibles</h2>

    <?php if (empty($destacados)): ?>
      <div class="estado-vacio">
        <i data-lucide="users"></i>
        <p>Todavía no hay profesionales registrados.</p>
        <a href="registro.php" class="btn btn-primary">
          ¿Sos trabajador? Registrate
        </a>
      </div>
    <?php else: ?>
      <div class="grid-profesionales">
        <?php foreach ($destacados as $p): ?>
          <?php include 'includes/card-profesional.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- COMO FUNCIONA -->
<section class="seccion seccion-como-funciona">
  <div class="container">
    <h2>¿Cómo funciona?</h2>
    <div class="pasos">
      <div class="paso">
        <i data-lucide="search"></i>
        <p>Buscá el oficio que necesitás</p>
      </div>
      <div class="paso">
        <i data-lucide="user"></i>
        <p>Encontrá un profesional de tu zona</p>
      </div>
      <div class="paso">
        <i data-lucide="phone"></i>
        <p>Contactalo directamente</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA PARA PROFESIONALES -->
<section class="seccion seccion-cta">
  <div class="container">
    <h2>¿Sos trabajador independiente?</h2>
    <p>Publicá tu perfil gratis y conseguí más clientes en El Colorado.</p>
    <a href="registro.php" class="btn btn-accent">Registrarme gratis</a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
