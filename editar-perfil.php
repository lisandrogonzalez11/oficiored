<?php
// =============================================================
// editar-perfil.php — Edicion del perfil del profesional logueado
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

requerir_login();

// Token CSRF.
csrf_token();

// Categorias para el select de oficio.
$categorias = obtener_categorias($pdo);

// Cargar datos actuales del profesional.
$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email, telefono,
            biografia, oficio, zona_cobertura
     FROM profesionales
     WHERE id_profesional = :id"
);
$stmt->execute([':id' => $_SESSION['profesional_id']]);
$profesional = $stmt->fetch();

if (!$profesional) {
    session_destroy();
    redirigir('login.php');
}

$errores = [];
$valores = $profesional;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar token CSRF.
    verificar_csrf();

    // Recoger campos.
    $nombre         = trim($_POST['nombre'] ?? '');
    $apellido       = trim($_POST['apellido'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $telefono       = trim($_POST['telefono'] ?? '');
    $oficio         = trim($_POST['oficio'] ?? '');
    $zona_cobertura = trim($_POST['zona_cobertura'] ?? '');
    $biografia      = trim($_POST['biografia'] ?? '');

    $valores = compact('nombre', 'apellido', 'email', 'telefono',
                       'oficio', 'zona_cobertura', 'biografia');

    // Validaciones server-side (centralizadas en funciones.php).
    $errores = validar_datos_profesional($pdo, [
        'nombre'         => $nombre,
        'apellido'       => $apellido,
        'email'          => $email,
        'oficio'         => $oficio,
        'zona_cobertura' => $zona_cobertura,
        'biografia'      => $biografia,
        'telefono'       => $telefono,
    ], ['excluir_id' => $_SESSION['profesional_id']]);

    // Sin errores: actualizar (WHERE sobre el id de sesion).
    if (empty($errores)) {
        $stmt = $pdo->prepare(
            "UPDATE profesionales
             SET nombre = ?, apellido = ?, email = ?, telefono = ?,
                 oficio = ?, zona_cobertura = ?, biografia = ?
             WHERE id_profesional = ?"
        );
        $stmt->execute([
            $nombre, $apellido, $email, $telefono,
            $oficio, $zona_cobertura, $biografia,
            $_SESSION['profesional_id'],
        ]);

        $_SESSION['mensaje'] = 'Tu perfil fue actualizado correctamente.';
        $_SESSION['mensaje_tipo'] = 'exito';

        redirigir('dashboard.php');
    }
}

$titulo_pagina = 'Editar mi perfil';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1>Editar mi perfil</h1>
      <p class="texto-secundario">
        <a href="dashboard.php">
          <i data-lucide="arrow-left"></i> Volver al panel
        </a>
      </p>

      <form action="editar-perfil.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= $_SESSION['csrf_token'] ?>">

<?php
$incluir_password              = false;
$zona_placeholder              = '';
$telefono_placeholder          = '';
$biografia_contador_multilinea = true;
include 'includes/form-campos-profesional.php';
?>

        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
          <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i> Guardar cambios
          </button>
          <a href="dashboard.php" class="btn btn-secondary">
            Cancelar
          </a>
        </div>

      </form>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
