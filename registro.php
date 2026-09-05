<?php
// =============================================================
// registro.php — Alta de cuenta de profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

// Si ya hay sesion, al panel.
if (esta_logueado()) {
    redirigir('dashboard.php');
}

// Token CSRF.
csrf_token();

$errores = [];
$valores = [
    'nombre'         => '',
    'apellido'       => '',
    'email'          => '',
    'telefono'       => '',
    'oficio'         => '',
    'zona_cobertura' => '',
    'biografia'      => '',
];

// Categorias para el select de oficio.
$categorias = obtener_categorias($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar token CSRF.
    verificar_csrf();

    // Recoger campos.
    $nombre         = trim($_POST['nombre'] ?? '');
    $apellido       = trim($_POST['apellido'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $password_conf  = $_POST['password_conf'] ?? '';
    $telefono       = trim($_POST['telefono'] ?? '');
    $oficio         = trim($_POST['oficio'] ?? '');
    $zona_cobertura = trim($_POST['zona_cobertura'] ?? '');
    $biografia      = trim($_POST['biografia'] ?? '');

    // Conservar valores para repintar el form.
    $valores = compact('nombre', 'apellido', 'email', 'telefono',
                       'oficio', 'zona_cobertura', 'biografia');

    // Validaciones server-side (centralizadas en funciones.php).
    $errores = validar_datos_profesional($pdo, [
        'nombre'         => $nombre,
        'apellido'       => $apellido,
        'email'          => $email,
        'password'       => $password,
        'password_conf'  => $password_conf,
        'oficio'         => $oficio,
        'zona_cobertura' => $zona_cobertura,
        'biografia'      => $biografia,
        'telefono'       => $telefono,
    ], ['validar_password' => true]);

    // Sin errores: crear la cuenta.
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            "INSERT INTO profesionales
                (nombre, apellido, email, password, telefono,
                 biografia, oficio, zona_cobertura)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nombre, $apellido, $email, $hash, $telefono,
            $biografia, $oficio, $zona_cobertura,
        ]);

        $nuevo_id = $pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['profesional_id']     = $nuevo_id;
        $_SESSION['profesional_nombre'] = $nombre;

        $_SESSION['mensaje'] = '¡Bienvenido a OficioRed, ' . $nombre . '! Tu perfil ya está activo.';
        $_SESSION['mensaje_tipo'] = 'exito';

        redirigir('dashboard.php');
    }
}

$titulo_pagina = 'Registrarse';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1>Registrate como profesional</h1>
      <p class="texto-secundario">Es gratis. Empezá a recibir clientes hoy.</p>

      <form action="registro.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= $_SESSION['csrf_token'] ?>">

<?php
$incluir_password              = true;
$zona_placeholder              = 'Ej: El Colorado centro, Barrio Norte';
$telefono_placeholder          = 'Ej: 3718 123456';
$biografia_contador_multilinea = false;
include 'includes/form-campos-profesional.php';
?>

        <button type="submit" class="btn btn-primary">
          <i data-lucide="user-plus"></i> Crear mi perfil
        </button>

      </form>

      <p style="margin-top: 16px;">
        ¿Ya tenés cuenta?
        <a href="login.php" style="color: var(--color-primary);">Ingresá</a>
      </p>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
