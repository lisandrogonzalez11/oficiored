<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$id_prof = (int) ($_GET['id'] ?? 0);
if ($id_prof <= 0) {
    redirigir('profesionales.php');
}

// ----------------------------------------------------------------
// POST: toggle estado desde detalle
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $nuevo_estado = ($_POST['nuevo_estado'] ?? '') === 'activo' ? 'activo' : 'inactivo';

    $stmt = $pdo->prepare("UPDATE profesionales SET estado = ? WHERE id_profesional = ?");
    $stmt->execute([$nuevo_estado, $id_prof]);

    $label = $nuevo_estado === 'activo' ? 'activado' : 'inactivado';
    $_SESSION['flash_admin'] = "Profesional $label exitosamente.";
    $_SESSION['flash_admin_tipo'] = $nuevo_estado === 'activo' ? 'exito' : 'info';

    redirigir("profesional-detalle.php?id=$id_prof");
}

// ----------------------------------------------------------------
// GET: datos del profesional
// ----------------------------------------------------------------
$stmt = $pdo->prepare(
    "SELECT p.*, c.nombre AS categoria_nombre
     FROM profesionales p
     LEFT JOIN categorias c ON c.id_categoria = p.categoria_id
     WHERE p.id_profesional = ?"
);
$stmt->execute([$id_prof]);
$p = $stmt->fetch();

if (!$p) {
    redirigir('profesionales.php');
}

// Servicios
$stmt2 = $pdo->prepare(
    "SELECT nombre, activo FROM servicios WHERE profesional_id = ? ORDER BY activo DESC, nombre"
);
$stmt2->execute([$id_prof]);
$servicios = $stmt2->fetchAll();

$titulo_pagina = 'Detalle de profesional';
$pagina_activa = 'profesionales';

$nombre_completo = sanitizar($p['nombre']) . ' ' . sanitizar($p['apellido']);
$iniciales = mb_strtoupper(mb_substr($p['nombre'], 0, 1) . mb_substr($p['apellido'], 0, 1));

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Detalle del profesional</h1>
  <a href="profesionales.php">← Volver a la lista</a>
</div>

<div class="admin-card">
  <!-- Cabecera -->
  <div class="admin-prof-header">
    <?php if (!empty($p['foto_perfil']) && foto_nombre_valido($p['foto_perfil'])): ?>
      <img src="../uploads/fotos/<?= sanitizar($p['foto_perfil']) ?>"
           alt="Foto de <?= $nombre_completo ?>" class="admin-prof-foto">
    <?php else: ?>
      <div class="admin-prof-avatar"><?= $iniciales ?></div>
    <?php endif; ?>
    <div class="admin-prof-info">
      <h2><?= $nombre_completo ?></h2>
      <p>
        <?= sanitizar($p['email']) ?> &nbsp;·&nbsp;
        <span class="badge badge-<?= $p['estado'] ?>">
          <?= $p['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?>
        </span>
      </p>
    </div>
    <!-- Toggle estado -->
    <?php if ($p['estado'] === 'activo'): ?>
      <form method="post" action="profesional-detalle.php?id=<?= $id_prof ?>" style="margin-left:auto;"
            onsubmit="return confirm('¿Inactivar a <?= addslashes($nombre_completo) ?>? Su perfil dejará de aparecer públicamente.');">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="nuevo_estado" value="inactivo">
        <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-accent);">
          <i data-lucide="user-x"></i> Inactivar
        </button>
      </form>
    <?php else: ?>
      <form method="post" action="profesional-detalle.php?id=<?= $id_prof ?>" style="margin-left:auto;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="nuevo_estado" value="activo">
        <button type="submit" class="btn btn-sm btn-secundario" style="color:var(--color-success);">
          <i data-lucide="user-check"></i> Activar
        </button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Datos -->
  <div class="admin-detalle-grid">
    <div class="admin-detalle-item">
      <label>Email</label>
      <span><?= sanitizar($p['email']) ?></span>
    </div>
    <div class="admin-detalle-item">
      <label>Teléfono</label>
      <span><?= $p['telefono'] ? sanitizar($p['telefono']) : '—' ?></span>
    </div>
    <div class="admin-detalle-item">
      <label>Categoría</label>
      <span><?= sanitizar($p['categoria_nombre'] ?? '—') ?></span>
    </div>
    <div class="admin-detalle-item">
      <label>Oficio</label>
      <span><?= $p['oficio'] ? sanitizar($p['oficio']) : '—' ?></span>
    </div>
    <div class="admin-detalle-item">
      <label>Zona de cobertura</label>
      <span><?= $p['zona_cobertura'] ? sanitizar($p['zona_cobertura']) : '—' ?></span>
    </div>
    <div class="admin-detalle-item">
      <label>Fecha de registro</label>
      <span><?= htmlspecialchars(formatear_fecha($p['fecha_registro']), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if (!empty($p['biografia'])): ?>
    <div class="admin-detalle-item" style="grid-column:1/-1;">
      <label>Biografía</label>
      <span><?= sanitizar($p['biografia']) ?></span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Servicios -->
  <h2 style="margin-top:1.25rem;">Servicios (<?= count($servicios) ?>)</h2>
  <?php if (empty($servicios)): ?>
    <p style="color:var(--color-text-light);font-size:0.875rem;">Sin servicios registrados.</p>
  <?php else: ?>
    <div class="admin-servicios-lista">
      <?php foreach ($servicios as $s): ?>
        <?php $estado_str = $s['activo'] ? 'activo' : 'inactivo'; ?>
        <span class="admin-servicio-chip admin-servicio-chip--<?= $estado_str ?>">
          <i data-lucide="<?= $s['activo'] ? 'check-circle' : 'circle' ?>"></i>
          <?= sanitizar($s['nombre']) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer-admin.php'; ?>
