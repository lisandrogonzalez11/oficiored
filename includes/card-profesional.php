<?php
// =============================================================
// includes/card-profesional.php — Partial reutilizable de card
// Proyecto: OficioRed
// Espera disponible la variable $p (un profesional) en el scope.
// =============================================================
?>
<div class="card-profesional">
  <?php if (!empty($p['foto_perfil']) && foto_nombre_valido($p['foto_perfil'])): ?>
    <img src="uploads/fotos/<?= sanitizar($p['foto_perfil']) ?>"
         alt="<?= sanitizar($p['nombre'] . ' ' . $p['apellido']) ?>"
         class="card-profesional__foto">
  <?php else: ?>
    <div class="card-profesional__avatar">
      <?= strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1)) ?>
    </div>
  <?php endif; ?>
  <div class="card-profesional__nombre-wrap">
    <h3><?= sanitizar($p['nombre'] . ' ' . $p['apellido']) ?></h3>
  </div>
  <span class="badge"><?= sanitizar($p['oficio']) ?></span>
  <p class="card-profesional__zona">
    <i data-lucide="map-pin"></i> <?= sanitizar($p['zona_cobertura']) ?>
  </p>
  <?php if (!empty($p['telefono'])): ?>
    <p class="card-profesional__contacto">
      <i data-lucide="phone"></i> <?= sanitizar($p['telefono']) ?>
    </p>
  <?php endif; ?>
  <p class="card-profesional__contacto">
    <i data-lucide="mail"></i> <?= sanitizar($p['email']) ?>
  </p>
  <!-- El ::after de este enlace cubre toda la card (stretched link) -->
  <a href="perfil.php?id=<?= (int)$p['id_profesional'] ?>"
     class="btn btn-secondary btn-sm card-profesional__ver-perfil">Ver perfil</a>
</div>
