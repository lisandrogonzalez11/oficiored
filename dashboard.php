<?php
// =============================================================
// dashboard.php — Panel privado del profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

requerir_login();

// Cargar datos del profesional logueado.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            biografia, oficio, zona_cobertura, estado, fecha_registro
     FROM profesionales
     WHERE id_profesional = :id
     LIMIT 1"
);
$stmt->execute([':id' => $_SESSION['profesional_id']]);
$profesional = $stmt->fetch();

// Registro inexistente (borrado manual): cerrar sesion.
if (!$profesional) {
    session_destroy();
    redirigir('login.php');
}

$titulo_pagina = 'Mi panel';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">

    <!-- BIENVENIDA -->
    <div class="dashboard-bienvenida">
      <h1>Hola, <?= sanitizar($profesional['nombre']) ?></h1>
      <span class="badge <?= $profesional['estado'] === 'activo'
                            ? 'badge-activo' : 'badge-inactivo' ?>">
        <i data-lucide="<?= $profesional['estado'] === 'activo'
                            ? 'check-circle' : 'pause-circle' ?>"></i>
        <?= $profesional['estado'] === 'activo' ? 'Perfil activo' : 'Perfil inactivo' ?>
      </span>
    </div>

    <!-- ALERTA SI FALTA TELEFONO -->
    <?php if (empty($profesional['telefono'])): ?>
      <div class="alerta alerta-error" style="margin-bottom: 24px;">
        <i data-lucide="alert-triangle"></i>
        <span>
          Agregá tu teléfono para que los clientes puedan contactarte más fácil.
          <a href="editar-perfil.php">Actualizar perfil</a>
        </span>
      </div>
    <?php endif; ?>

    <!-- ACCIONES RAPIDAS -->
    <h2>¿Qué querés hacer?</h2>
    <div class="acciones-rapidas">

      <a href="editar-perfil.php" class="accion-rapida">
        <i data-lucide="edit"></i>
        <div>
          <strong>Editar mi perfil</strong>
          <p class="texto-secundario">Actualizá tus datos y oficio</p>
        </div>
      </a>

      <a href="perfil.php?id=<?= (int)$profesional['id_profesional'] ?>"
         class="accion-rapida">
        <i data-lucide="eye"></i>
        <div>
          <strong>Ver mi perfil público</strong>
          <p class="texto-secundario">Así te ven los clientes</p>
        </div>
      </a>

      <a href="logout.php" class="accion-rapida accion-rapida--logout">
        <i data-lucide="log-out"></i>
        <div>
          <strong>Cerrar sesión</strong>
          <p class="texto-secundario">Salir de tu cuenta</p>
        </div>
      </a>

    </div>

    <!-- RESUMEN DEL PERFIL -->
    <div class="resumen-perfil">
      <h2>Tu información actual</h2>
      <div class="resumen-perfil__grid">

        <div class="resumen-item">
          <i data-lucide="briefcase"></i>
          <div>
            <span class="texto-secundario">Oficio</span>
            <strong><?= sanitizar($profesional['oficio']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="map-pin"></i>
          <div>
            <span class="texto-secundario">Zona de cobertura</span>
            <strong><?= sanitizar($profesional['zona_cobertura']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="mail"></i>
          <div>
            <span class="texto-secundario">Email</span>
            <strong><?= sanitizar($profesional['email']) ?></strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="phone"></i>
          <div>
            <span class="texto-secundario">Teléfono</span>
            <strong>
              <?= !empty($profesional['telefono'])
                  ? sanitizar($profesional['telefono'])
                  : '<span class="texto-secundario">No cargado</span>' ?>
            </strong>
          </div>
        </div>

        <div class="resumen-item">
          <i data-lucide="calendar"></i>
          <div>
            <span class="texto-secundario">En OficioRed desde</span>
            <strong><?= formatear_fecha($profesional['fecha_registro']) ?></strong>
          </div>
        </div>

      </div>
    </div>

  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
