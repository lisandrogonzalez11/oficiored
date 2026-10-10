<?php
// =============================================================
// verificar-telefono.php — Verificación de cuenta por OTP email
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'lib/EmailOTP.php';

iniciar_sesion();

// Solo accesible si está logueado y pendiente de verificación.
if (!esta_logueado()) {
    redirigir('login.php');
}

// Si ya está activo, no necesita verificar.
$stmt_est = $pdo->prepare("SELECT estado, nombre, email FROM profesionales WHERE id_profesional = ?");
$stmt_est->execute([$_SESSION['profesional_id']]);
$prof_check = $stmt_est->fetch();

if (!$prof_check || in_array($prof_check['estado'], ['activo', 'en_revision'], true)) {
    redirigir('dashboard.php');
}
if ($prof_check['estado'] === 'inactivo') {
    session_destroy();
    redirigir('login.php');
}

$errores    = [];
$mensaje_ok = '';

// -------------------------------------------------------
// POST: reenviar código
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reenviar') {
    verificar_csrf();

    // Rate limit: máximo 1 reenvío cada 2 minutos.
    $ultimo_envio = $_SESSION['otp_ultimo_envio'] ?? 0;
    if (time() - $ultimo_envio < 120) {
        $errores[] = 'Esperá ' . (120 - (time() - $ultimo_envio)) . ' segundos antes de reenviar.';
    } else {
        $codigo  = EmailOTP::generarCodigo();
        EmailOTP::guardarOTP($pdo, (int) $_SESSION['profesional_id'], $codigo);
        $enviado = EmailOTP::enviarCodigo($prof_check['email'], $prof_check['nombre'], $codigo);
        if ($enviado) {
            $_SESSION['otp_ultimo_envio'] = time();
            $mensaje_ok = 'Código reenviado. Revisá tu email.';
        } else {
            $errores[] = 'No se pudo enviar el código. Intentá de nuevo en unos minutos.';
        }
    }
}

// -------------------------------------------------------
// POST: verificar código
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'verificar') {
    verificar_csrf();

    $codigo_ingresado = trim($_POST['codigo'] ?? '');

    if (!preg_match('/^\d{6}$/', $codigo_ingresado)) {
        $errores[] = 'El código debe ser de 6 dígitos.';
    } else {
        // Buscar OTP válido.
        $stmt_otp = $pdo->prepare(
            "SELECT id, intentos FROM otp_whatsapp
             WHERE profesional_id = ? AND usado = 0 AND expira_en > NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt_otp->execute([$_SESSION['profesional_id']]);
        $otp = $stmt_otp->fetch();

        if (!$otp) {
            $errores[] = 'El código expiró. Solicitá uno nuevo.';
        } elseif ($otp['intentos'] >= 3) {
            $errores[] = 'Demasiados intentos fallidos. Solicitá un nuevo código.';
            $pdo->prepare("UPDATE otp_whatsapp SET usado = 1 WHERE id = ?")
                ->execute([$otp['id']]);
        } else {
            // Buscar código con ese valor.
            $stmt_match = $pdo->prepare(
                "SELECT id FROM otp_whatsapp
                 WHERE id = ? AND codigo = ? AND usado = 0 AND expira_en > NOW()"
            );
            $stmt_match->execute([$otp['id'], $codigo_ingresado]);
            $match = $stmt_match->fetch();

            if (!$match) {
                // Incrementar intentos.
                $pdo->prepare("UPDATE otp_whatsapp SET intentos = intentos + 1 WHERE id = ?")
                    ->execute([$otp['id']]);
                $restantes = 3 - ($otp['intentos'] + 1);
                $errores[] = 'Código incorrecto.' . ($restantes > 0 ? " Te quedan $restantes intento(s)." : ' Solicitá un nuevo código.');
            } else {
                // ¡Verificado! Marcar OTP como usado, poner en revisión para aprobación del admin.
                $pdo->prepare("UPDATE otp_whatsapp SET usado = 1 WHERE id = ?")
                    ->execute([$match['id']]);
                $pdo->prepare(
                    "UPDATE profesionales SET estado = 'en_revision', telefono_verificado = 1
                     WHERE id_profesional = ?"
                )->execute([$_SESSION['profesional_id']]);

                session_regenerate_id(true);
                $_SESSION['profesional_estado'] = 'en_revision';
                $_SESSION['mensaje']      = '¡Email verificado! Completá tu perfil mientras esperás la aprobación del equipo.';
                $_SESSION['mensaje_tipo'] = 'exito';

                redirigir('dashboard.php');
            }
        }
    }
}

$titulo_pagina = 'Verificar email';
require_once 'includes/header.php';

// Mostrar email con parte del dominio oculta para privacidad.
$email_prof = $prof_check['email'] ?? '';
$email_partes = explode('@', $email_prof);
$email_mostrar = '';
if (count($email_partes) === 2) {
    $local = $email_partes[0];
    $email_mostrar = (mb_strlen($local) > 2 ? mb_substr($local, 0, 2) . '***' : $local)
                   . '@' . $email_partes[1];
}
?>

<section class="seccion">
  <div class="container">
    <div class="auth-card">

      <h1><i data-lucide="mail"></i> Verificá tu email</h1>
      <p class="texto-secundario">
        Enviamos un código de 6 dígitos a
        <?= $email_mostrar ? "<strong>" . htmlspecialchars($email_mostrar, ENT_QUOTES, 'UTF-8') . "</strong>" : 'tu email' ?>.
        Ingresalo para continuar.
      </p>

      <?php if ($mensaje_ok): ?>
        <div class="alerta alerta-exito">
          <i data-lucide="check-circle"></i>
          <span><?= sanitizar($mensaje_ok) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($errores): ?>
        <div class="alerta alerta-error">
          <i data-lucide="alert-circle"></i>
          <span><?= sanitizar($errores[0]) ?></span>
        </div>
      <?php endif; ?>

      <!-- Formulario de verificación -->
      <form action="verificar-telefono.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="accion" value="verificar">

        <div class="campo">
          <label for="codigo">Código de verificación</label>
          <input type="text" id="codigo" name="codigo"
                 inputmode="numeric" pattern="\d{6}" maxlength="6"
                 placeholder="000000" autocomplete="one-time-code"
                 autofocus style="letter-spacing:0.4em;font-size:1.5rem;text-align:center;">
        </div>

        <button type="submit" class="btn btn-primary">
          <i data-lucide="check"></i> Verificar
        </button>
      </form>

      <!-- Reenviar -->
      <?php
        $ultimo = $_SESSION['otp_ultimo_envio'] ?? 0;
        $puede_reenviar = (time() - $ultimo) >= 120;
      ?>
      <form action="verificar-telefono.php" method="POST" style="margin-top:16px;">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="accion" value="reenviar">
        <button type="submit" class="btn btn-secondary btn-sm"
                <?= $puede_reenviar ? '' : 'disabled' ?>>
          <i data-lucide="refresh-cw"></i>
          <?= $puede_reenviar ? 'Reenviar código' : 'Reenviar (esperá 2 min)' ?>
        </button>
      </form>

      <p style="margin-top:20px;font-size:0.875rem;color:var(--color-text-light);">
        ¿No encontrás el email? Revisá la carpeta de spam o correo no deseado.
      </p>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
