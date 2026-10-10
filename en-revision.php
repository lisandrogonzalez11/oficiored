<?php
// =============================================================
// en-revision.php — Perfil en revisión, esperando aprobación admin
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

if (!esta_logueado()) {
    redirigir('login.php');
}

// Verificar estado real desde BD.
$stmt = $pdo->prepare("SELECT nombre, estado FROM profesionales WHERE id_profesional = ?");
$stmt->execute([$_SESSION['profesional_id']]);
$prof = $stmt->fetch();

if (!$prof || $prof['estado'] === 'inactivo') {
    session_destroy();
    redirigir('login.php');
}
if ($prof['estado'] === 'activo') {
    redirigir('dashboard.php');
}
if ($prof['estado'] === 'pendiente') {
    redirigir('verificar-telefono.php');
}

$titulo_pagina = 'Perfil en revisión';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card" style="text-align:center;">

      <div style="margin-bottom:16px;">
        <i data-lucide="clock" style="width:48px;height:48px;color:var(--color-accent);"></i>
      </div>

      <h1>Tu perfil está en revisión</h1>

      <p>
        Hola <strong><?= sanitizar($prof['nombre']) ?></strong>, tu número de teléfono fue verificado correctamente.
      </p>
      <p class="texto-secundario">
        El equipo de OficioRed revisa los perfiles antes de publicarlos. Te notificaremos por email cuando tu perfil sea aprobado y empiece a aparecer en las búsquedas.
      </p>
      <p class="texto-secundario" style="font-size:0.875rem;">
        Este proceso suele tardar menos de 24 horas.
      </p>

      <div style="margin-top:24px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="editar-perfil.php" class="btn btn-secondary">
          <i data-lucide="edit"></i> Completar mi perfil mientras tanto
        </a>
        <a href="logout.php" class="btn btn-secondary">
          <i data-lucide="log-out"></i> Cerrar sesión
        </a>
      </div>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
