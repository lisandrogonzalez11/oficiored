<?php
// =============================================================
// registro.php — Alta de cuenta de profesional
// Proyecto: OficioRed
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'lib/EmailOTP.php';

iniciar_sesion();

// Si ya hay sesion, al panel.
if (esta_logueado()) {
    redirigir('dashboard.php');
}

// Token CSRF.
csrf_token();

$errores             = [];
$zonas_seleccionadas = [];
$valores = [
    'nombre'         => '',
    'apellido'       => '',
    'email'          => '',
    'telefono'       => '',
    'oficio'         => '',
    'zona_cobertura' => '',
    'biografia'      => '',
];

// Categorias y zonas para el formulario.
$categorias    = obtener_categorias($pdo);
$zonas_activas = obtener_zonas($pdo);

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

    // Zonas seleccionadas (para repintar en caso de error)
    $zonas_seleccionadas = array_map('intval',
        array_filter($_POST['zonas'] ?? [], fn($v) => (int)$v > 0)
    );

    // Validaciones server-side (centralizadas en funciones.php).
    $errores = validar_datos_profesional($pdo, [
        'nombre'         => $nombre,
        'apellido'       => $apellido,
        'email'          => $email,
        'password'       => $password,
        'password_conf'  => $password_conf,
        'oficio'         => $oficio,
        'zona_cobertura' => '',
        'biografia'      => $biografia,
        'telefono'       => $telefono,
    ], ['validar_password' => true]);

    if (empty($zonas_seleccionadas)) {
        $errores['zonas'] = 'Seleccioná al menos una zona de cobertura.';
    }

    // Sin errores: crear la cuenta.
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Resolver categoria_id a partir del nombre de oficio seleccionado.
        $categoria_id = null;
        foreach ($categorias as $cat) {
            if ($cat['nombre'] === $oficio) {
                $categoria_id = (int) $cat['id_categoria'];
                break;
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO profesionales
                (nombre, apellido, email, password, telefono,
                 biografia, oficio, zona_cobertura, categoria_id, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')"
        );
        $stmt->execute([
            $nombre, $apellido, $email, $hash, $telefono,
            $biografia, $oficio, $zona_cobertura, $categoria_id,
        ]);

        $nuevo_id = (int) $pdo->lastInsertId();

        // Guardar zonas en la tabla pivot y actualizar texto zona_cobertura.
        $texto_zona = guardar_zonas_profesional($pdo, $nuevo_id, $zonas_seleccionadas);
        $pdo->prepare("UPDATE profesionales SET zona_cobertura = ? WHERE id_profesional = ?")
            ->execute([$texto_zona, $nuevo_id]);

        // Generar y enviar OTP por email.
        $codigo = EmailOTP::generarCodigo();
        EmailOTP::guardarOTP($pdo, $nuevo_id, $codigo);
        EmailOTP::enviarCodigo($email, $nombre, $codigo);

        session_regenerate_id(true);
        $_SESSION['profesional_id']     = $nuevo_id;
        $_SESSION['profesional_nombre'] = $nombre;
        $_SESSION['profesional_estado'] = 'pendiente';
        $_SESSION['otp_ultimo_envio']   = time();

        redirigir('verificar-telefono.php');
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
