<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$titulo_pagina = 'Inicio';
$pagina_activa = 'inicio';

// Contadores
$total         = (int) $pdo->query("SELECT COUNT(*) FROM profesionales")->fetchColumn();
$activos       = (int) $pdo->query("SELECT COUNT(*) FROM profesionales WHERE estado = 'activo'")->fetchColumn();
$pendientes    = (int) $pdo->query("SELECT COUNT(*) FROM profesionales WHERE estado IN ('pendiente','en_revision')")->fetchColumn();
$en_revision   = (int) $pdo->query("SELECT COUNT(*) FROM profesionales WHERE estado = 'en_revision'")->fetchColumn();
$inactivos     = (int) $pdo->query("SELECT COUNT(*) FROM profesionales WHERE estado = 'inactivo'")->fetchColumn();
$categorias    = (int) $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();

include 'includes/header-admin.php';
?>

<div class="admin-page-header">
  <h1>Panel de administración</h1>
  <span>Bienvenido/a, <?= htmlspecialchars($_SESSION['admin_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
</div>

<div class="admin-contadores">
  <div class="admin-contador">
    <i data-lucide="users"></i>
    <div><strong><?= $total ?></strong><span>Total profesionales</span></div>
  </div>
  <div class="admin-contador admin-contador--activo">
    <i data-lucide="user-check"></i>
    <div><strong><?= $activos ?></strong><span>Activos</span></div>
  </div>
  <div class="admin-contador admin-contador--inactivo">
    <i data-lucide="user-x"></i>
    <div><strong><?= $inactivos ?></strong><span>Inactivos</span></div>
  </div>
  <div class="admin-contador <?= $en_revision > 0 ? 'admin-contador--alerta' : '' ?>">
    <i data-lucide="clock"></i>
    <div><strong><?= $en_revision ?></strong><span>En revisión</span></div>
  </div>
  <div class="admin-contador">
    <i data-lucide="tag"></i>
    <div><strong><?= $categorias ?></strong><span>Categorías</span></div>
  </div>
</div>

<?php if ($en_revision > 0): ?>
<div class="admin-alerta-accion">
  <i data-lucide="user-check"></i>
  <span>Hay <strong><?= $en_revision ?></strong> perfil<?= $en_revision > 1 ? 'es' : '' ?> en revisión esperando aprobación.</span>
  <a href="profesionales.php?filtro=en_revision" class="btn btn-sm btn-primary">Revisar</a>
</div>
<?php endif; ?>

<div class="admin-accesos-rapidos">
  <a href="profesionales.php" class="admin-acceso">
    <i data-lucide="users"></i> Gestionar profesionales
  </a>
  <a href="categorias.php" class="admin-acceso">
    <i data-lucide="tag"></i> Gestionar categorías
  </a>
</div>

<?php include 'includes/footer-admin.php'; ?>
