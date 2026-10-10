<?php
// =============================================================
// admin/recuperacion.php — Solicitud de recuperación de contraseña (admin)
// =============================================================

require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();

if (esta_logueado_admin()) {
    redirigir('index.php');
}

csrf_token();

$enviado = false;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un email válido.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare(
            "SELECT id_usuario, nombre FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin) {
            require_once '../includes/mailer.php';

            $token   = crear_token_recuperacion($pdo, $email);
            $base    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                     . '://' . $_SERVER['HTTP_HOST']
                     . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $enlace  = $base . '/nueva-password.php?token=' . urlencode($token);
            $html    = email_recuperacion_html($admin['nombre'], $enlace, 'admin');

            enviar_email($email, $admin['nombre'], 'Recuperá tu contraseña – OficioRed Admin', $html);
        }

        $enviado = true;
    }
}

$titulo_pagina = 'Recuperar contraseña – Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= sanitizar($titulo_pagina) ?></title>
  <link rel="stylesheet" href="../css/estilos.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-login-page">

<div class="admin-login-card">
  <div class="admin-login-logo">
    <span class="admin-login-logo-text">OficioRed</span>
    <span class="admin-login-badge">Admin</span>
  </div>

  <?php if ($enviado): ?>
    <div class="alerta alerta-exito">
      <i data-lucide="check-circle"></i>
      <span>Si ese email está registrado, vas a recibir un enlace para restablecer tu contraseña.</span>
    </div>
    <a href="login.php" class="btn btn-primary" style="width:100%;text-align:center;">Volver al login</a>
  <?php else: ?>

    <h1 style="font-size:1.25rem;margin-bottom:8px;">Recuperar contraseña</h1>
    <p style="color:#94a3b8;font-size:0.875rem;margin-bottom:24px;">
      Te enviaremos un enlace a tu email de administrador.
    </p>

    <?php if ($errores): ?>
      <div class="alerta alerta-error">
        <i data-lucide="alert-circle"></i>
        <span><?= sanitizar($errores[0]) ?></span>
      </div>
    <?php endif; ?>

    <form action="recuperacion.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="campo">
        <label for="email">Email de administrador</label>
        <input type="email" id="email" name="email"
               value="<?= sanitizar($_POST['email'] ?? '') ?>"
               required autocomplete="email" autofocus>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">
        <i data-lucide="send"></i> Enviar enlace
      </button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:0.875rem;">
      <a href="login.php" style="color:#94a3b8;">← Volver al login</a>
    </p>

  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.263.1/dist/umd/lucide.min.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>
