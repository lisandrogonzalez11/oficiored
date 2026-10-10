<?php
// =============================================================
// nueva-password.php — Restablecer contraseña (profesional)
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();

if (esta_logueado()) {
    redirigir('dashboard.php');
}

csrf_token();

$token = trim($_GET['token'] ?? '');
$email = $token ? validar_token_recuperacion($pdo, $token) : null;

if (!$email) {
    // Token inválido, expirado o ya usado.
    $titulo_pagina = 'Enlace inválido';
    require_once 'includes/header.php';
    ?>
    <section class="seccion">
      <div class="container">
        <div class="auth-card">
          <h1>Enlace inválido o expirado</h1>
          <p class="texto-secundario">
            Este enlace ya no es válido. Los enlaces expiran a la hora de ser generados y solo pueden usarse una vez.
          </p>
          <a href="recuperacion.php" class="btn btn-primary">Solicitar nuevo enlace</a>
        </div>
      </div>
    </section>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$errores  = [];
$guardado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $password      = $_POST['password']      ?? '';
    $password_conf = $_POST['password_conf'] ?? '';

    if (mb_strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password_conf) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            "UPDATE profesionales SET password = ? WHERE email = ?"
        );
        $stmt->execute([$hash, $email]);

        consumir_token_recuperacion($pdo, $token);

        $_SESSION['mensaje']      = 'Tu contraseña fue actualizada. Podés iniciar sesión.';
        $_SESSION['mensaje_tipo'] = 'exito';
        redirigir('login.php');
    }
}

$titulo_pagina = 'Nueva contraseña';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1>Nueva contraseña</h1>
      <p class="texto-secundario">Ingresá tu nueva contraseña para <strong><?= sanitizar($email) ?></strong>.</p>

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
                 required minlength="8" autocomplete="new-password" autofocus>
          <small class="campo-hint">Mínimo 8 caracteres.</small>
        </div>

        <div class="campo">
          <label for="password_conf">Confirmar contraseña</label>
          <input type="password" id="password_conf" name="password_conf"
                 required minlength="8" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary">
          <i data-lucide="lock"></i> Guardar nueva contraseña
        </button>
      </form>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
