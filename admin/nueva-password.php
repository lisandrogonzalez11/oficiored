<?php
// =============================================================
// admin/nueva-password.php — Restablecer contraseña (admin)
// =============================================================

require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();

if (esta_logueado_admin()) {
    redirigir('index.php');
}

csrf_token();

$token = trim($_GET['token'] ?? '');
$email = $token ? validar_token_recuperacion($pdo, $token) : null;

// Verificar que el email corresponde a un admin (no a un profesional).
if ($email) {
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $email = null; // Token válido pero no pertenece a un admin.
    }
}

if (!$email) {
    $titulo_pagina = 'Enlace inválido';
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Enlace inválido – OficioRed Admin</title>
      <link rel="stylesheet" href="../css/estilos.css">
      <link rel="stylesheet" href="../css/admin.css">
    </head>
    <body class="admin-login-page">
    <div class="admin-login-card">
      <h1 style="font-size:1.25rem;">Enlace inválido o expirado</h1>
      <p style="color:#94a3b8;font-size:0.875rem;">Este enlace ya no es válido. Expira a la hora y solo puede usarse una vez.</p>
      <a href="recuperacion.php" class="btn btn-primary" style="width:100%;text-align:center;">Solicitar nuevo enlace</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.1/dist/umd/lucide.min.js"></script>
    <script>lucide.createIcons();</script>
    </body></html>
    <?php
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $password      = $_POST['password']      ?? '';
    $password_conf = $_POST['password_conf'] ?? '';

    if (mb_strlen($password) < 10) {
        $errores[] = 'La contraseña debe tener al menos 10 caracteres.';
    } elseif ($password !== $password_conf) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ?")
            ->execute([$hash, $email]);

        consumir_token_recuperacion($pdo, $token);

        $_SESSION['flash_admin']      = 'Contraseña actualizada. Podés iniciar sesión.';
        $_SESSION['flash_admin_tipo'] = 'exito';
        redirigir('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nueva contraseña – OficioRed Admin</title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-login-page">

<div class="admin-login-card">
  <div class="admin-login-logo">
    <span class="admin-login-logo-text">OficioRed</span>
    <span class="admin-login-badge">Admin</span>
  </div>

  <h1 style="font-size:1.25rem;margin-bottom:8px;">Nueva contraseña</h1>
  <p style="color:#94a3b8;font-size:0.875rem;margin-bottom:24px;">
    Para <strong style="color:#e2e8f0;"><?= sanitizar($email) ?></strong>
  </p>

  <?php if ($errores): ?>
    <div class="alerta alerta-error">
      <i data-lucide="alert-circle"></i>
      <span><?= sanitizar($errores[0]) ?></span>
    </div>
  <?php endif; ?>

  <form action="nueva-password.php?token=<?= urlencode($token) ?>" method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="campo">
      <label for="password">Nueva contraseña</label>
      <input type="password" id="password" name="password"
             required minlength="10" autocomplete="new-password" autofocus>
      <small class="campo-hint">Mínimo 10 caracteres.</small>
    </div>

    <div class="campo">
      <label for="password_conf">Confirmar contraseña</label>
      <input type="password" id="password_conf" name="password_conf"
             required minlength="10" autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;">
      <i data-lucide="lock"></i> Guardar nueva contraseña
    </button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.263.1/dist/umd/lucide.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
