<?php
require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();

// Ya logueado → dashboard
if (esta_logueado_admin()) {
    redirigir('index.php');
}

// ----------------------------------------------------------------
// Rate limiting (session-based, sin IP ni tabla nueva)
// ----------------------------------------------------------------
$MAX_INTENTOS   = 5;
$BLOQUEO_SEG    = 300; // 5 minutos

$bloqueado   = false;
$segundos_restantes = 0;

if (isset($_SESSION['admin_bloqueo_hasta'])) {
    if (time() < $_SESSION['admin_bloqueo_hasta']) {
        $bloqueado = true;
        $segundos_restantes = $_SESSION['admin_bloqueo_hasta'] - time();
    } else {
        unset($_SESSION['admin_intentos'], $_SESSION['admin_bloqueo_hasta']);
    }
}

$error = '';

// ----------------------------------------------------------------
// POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    verificar_csrf();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id_usuario, nombre, password FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    $ok = $admin && password_verify($password, $admin['password']);

    if ($ok) {
        // Éxito: limpiar rate limit, regenerar sesión, redirigir
        unset($_SESSION['admin_intentos'], $_SESSION['admin_bloqueo_hasta']);
        session_regenerate_id(true);
        $_SESSION['admin_id']     = (int) $admin['id_usuario'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        redirigir('index.php');
    } else {
        $_SESSION['admin_intentos'] = ($_SESSION['admin_intentos'] ?? 0) + 1;

        if ($_SESSION['admin_intentos'] >= $MAX_INTENTOS) {
            $_SESSION['admin_bloqueo_hasta'] = time() + $BLOQUEO_SEG;
            $bloqueado = true;
            $segundos_restantes = $BLOQUEO_SEG;
            $error = 'Demasiados intentos fallidos. Cuenta bloqueada por 5 minutos.';
        } else {
            $restantes = $MAX_INTENTOS - $_SESSION['admin_intentos'];
            $error = 'Credenciales incorrectas.' . ($restantes <= 2 ? " Te quedan $restantes intento(s)." : '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Admin — OficioRed</title>
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <link rel="apple-touch-icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/admin.css">
  <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.min.js"></script>
</head>
<body class="admin-login-body">

<div class="admin-login-card">
  <h1><img src="../assets/img/logo.png" alt="OficioRed" width="40" height="40" style="vertical-align:middle;margin-right:8px;"> OficioRed Admin</h1>
  <p class="subtitulo">Acceso exclusivo para administradores</p>

  <?php if ($bloqueado): ?>
    <div class="alerta alerta-error">
      Cuenta bloqueada por intentos fallidos. Intentá de nuevo en
      <?= ceil($segundos_restantes / 60) ?> minuto(s).
    </div>
  <?php elseif ($error !== ''): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-group">
      <label class="form-label" for="email">Email</label>
      <input class="form-input" type="email" id="email" name="email"
             autocomplete="username" required
             <?= $bloqueado ? 'disabled' : '' ?>>
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Contraseña</label>
      <div class="password-wrap">
        <input class="form-input" type="password" id="password" name="password"
               autocomplete="current-password" required
               <?= $bloqueado ? 'disabled' : '' ?>>
        <button type="button" class="password-toggle"
                onclick="togglePwd('password', this)"
                tabindex="-1" aria-label="Mostrar contraseña">
          <svg class="ico-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg class="ico-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
      </div>
    </div>

    <button class="btn btn-primary" type="submit" <?= $bloqueado ? 'disabled' : '' ?>>
      Iniciar sesión
    </button>
  </form>

  <p style="text-align:center;margin-top:16px;font-size:0.875rem;">
    <a href="recuperacion.php" style="color:#94a3b8;">¿Olvidaste tu contraseña?</a>
  </p>
</div>

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
function togglePwd(inputId, btn) {
  var input = document.getElementById(inputId);
  var isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.querySelector('.ico-eye').style.display     = isHidden ? 'none' : '';
  btn.querySelector('.ico-eye-off').style.display = isHidden ? '' : 'none';
  btn.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
}
</script>
</body>
</html>
