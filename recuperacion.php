<?php
// =============================================================
// recuperacion.php — Solicitud de recuperación de contraseña (profesional)
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

if (esta_logueado()) {
    redirigir('dashboard.php');
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
        // Buscar profesional con ese email.
        $stmt = $pdo->prepare(
            "SELECT id_profesional, nombre FROM profesionales WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $prof = $stmt->fetch();

        // Responder igual si existe o no (no revelar si el email está registrado).
        if ($prof) {
            require_once 'includes/mailer.php';

            $token   = crear_token_recuperacion($pdo, $email);
            $base    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                     . '://' . $_SERVER['HTTP_HOST']
                     . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $enlace  = $base . '/nueva-password.php?token=' . urlencode($token);
            $html    = email_recuperacion_html($prof['nombre'], $enlace, 'profesional');

            enviar_email($email, $prof['nombre'], 'Recuperá tu contraseña – OficioRed', $html);
        }

        $enviado = true;
    }
}

$titulo_pagina = 'Recuperar contraseña';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1>Recuperar contraseña</h1>

      <?php if ($enviado): ?>
        <div class="alerta alerta-exito">
          <i data-lucide="check-circle"></i>
          <span>Si ese email está registrado, vas a recibir un enlace para restablecer tu contraseña. Revisá también la carpeta de spam.</span>
        </div>
        <p><a href="login.php">Volver al inicio de sesión</a></p>
      <?php else: ?>

        <?php if ($errores): ?>
          <div class="alerta alerta-error">
            <i data-lucide="alert-circle"></i>
            <span><?= sanitizar($errores[0]) ?></span>
          </div>
        <?php endif; ?>

        <p class="texto-secundario">Ingresá tu email y te enviaremos un enlace para restablecer tu contraseña.</p>

        <form action="recuperacion.php" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= sanitizar($_POST['email'] ?? '') ?>"
                   required autocomplete="email" autofocus>
          </div>

          <button type="submit" class="btn btn-primary">
            <i data-lucide="send"></i> Enviar enlace
          </button>
        </form>

        <p style="margin-top:16px;">
          <a href="login.php">← Volver al inicio de sesión</a>
        </p>

      <?php endif; ?>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
