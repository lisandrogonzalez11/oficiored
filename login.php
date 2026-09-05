<?php
// =============================================================
// login.php — Inicio de sesion del profesional
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

$error_login  = '';
$email_previo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar token CSRF.
    verificar_csrf();

    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $email_previo = $email;

    if ($email === '' || $password === '') {
        $error_login = 'Completá todos los campos.';
    }

    if ($error_login === '') {
        $stmt = $pdo->prepare(
            "SELECT id_profesional, nombre, password
             FROM profesionales
             WHERE email = ? AND estado = 'activo'
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $fila = $stmt->fetch();

        if (!$fila) {
            $error_login = 'Email o contraseña incorrectos.';
        } elseif (!password_verify($password, $fila['password'])) {
            $error_login = 'Email o contraseña incorrectos.';
        }

        if ($error_login === '') {
            session_regenerate_id(true);
            $_SESSION['profesional_id']     = $fila['id_profesional'];
            $_SESSION['profesional_nombre'] = $fila['nombre'];
            $_SESSION['mensaje'] = '¡Bienvenido de vuelta, ' . $fila['nombre'] . '!';
            $_SESSION['mensaje_tipo'] = 'exito';
            redirigir('dashboard.php');
        }
    }
}

$titulo_pagina = 'Ingresar';
require_once 'includes/header.php';
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card auth-card--login">

      <h1>Ingresá a tu cuenta</h1>

      <?php if ($error_login): ?>
        <div class="alerta alerta-error">
          <i data-lucide="alert-circle"></i>
          <span><?= htmlspecialchars($error_login) ?></span>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= $_SESSION['csrf_token'] ?>">

        <!-- EMAIL -->
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input class="form-input" type="email" id="email" name="email"
                 value="<?= sanitizar($email_previo) ?>" required>
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input class="form-input" type="password" id="password"
                 name="password" required>
        </div>

        <button type="submit" class="btn btn-primary">
          <i data-lucide="log-in"></i> Ingresar
        </button>

      </form>

      <p style="margin-top: 16px;">
        ¿Todavía no tenés perfil?
        <a href="registro.php" style="color: var(--color-primary);">Registrate</a>
      </p>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
