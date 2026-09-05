<?php
// =============================================================
// includes/card-profesional.php — Partial reutilizable de card
// Proyecto: OficioRed
// Espera disponible la variable $p (un profesional) en el scope.
// =============================================================
?>
<div class="card-profesional">
  <div class="card-profesional__avatar">
    <?= strtoupper(substr($p['nombre'], 0, 1) . substr($p['apellido'], 0, 1)) ?>
  </div>
  <h3><?= sanitizar($p['nombre'] . ' ' . $p['apellido']) ?></h3>
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
  <a href="perfil.php?id=<?= (int)$p['id_profesional'] ?>"
     class="btn btn-secondary btn-sm">Ver perfil</a>
</div>
