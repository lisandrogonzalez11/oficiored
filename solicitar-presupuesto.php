<?php
// =============================================================
// solicitar-presupuesto.php — Procesa el formulario de solicitud
// de presupuesto enviado desde perfil.php
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';
require_once 'includes/mailer.php';

iniciar_sesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

verificar_csrf();

$profesional_id = filter_input(INPUT_POST, 'profesional_id', FILTER_VALIDATE_INT);
if (!$profesional_id || $profesional_id <= 0) {
    redirigir('index.php');
}

$stmt = $pdo->prepare(
    "SELECT id_profesional, nombre, apellido, email
     FROM profesionales
     WHERE id_profesional = ? AND estado = 'activo'
     LIMIT 1"
);
$stmt->execute([$profesional_id]);
$profesional = $stmt->fetch();

if (!$profesional) {
    redirigir('index.php');
}

$nombre_cliente   = trim($_POST['nombre_cliente']   ?? '');
$telefono_cliente = trim($_POST['telefono_cliente']  ?? '');
$email_cliente    = trim($_POST['email_cliente']     ?? '');
$modalidad        = $_POST['modalidad'] ?? 'presencial';
$zona_servicio    = trim($_POST['zona_servicio']     ?? '');
$descripcion      = trim($_POST['descripcion']       ?? '');

if (!in_array($modalidad, ['presencial', 'digital'], true)) {
    $modalidad = 'presencial';
}

$errores = [];

if (mb_strlen($nombre_cliente) < 2 || mb_strlen($nombre_cliente) > 100) {
    $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
}
if (mb_strlen($telefono_cliente) < 6 || mb_strlen($telefono_cliente) > 30) {
    $errores[] = 'El teléfono es requerido (entre 6 y 30 caracteres).';
}
if ($email_cliente !== '' && !filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email ingresado no es válido.';
}
if ($modalidad === 'presencial' && mb_strlen($zona_servicio) < 2) {
    $errores[] = 'Indicá la zona o dirección donde necesitás el servicio.';
}
if (mb_strlen($descripcion) < 10 || mb_strlen($descripcion) > 1000) {
    $errores[] = 'La descripción debe tener entre 10 y 1000 caracteres.';
}

if (!empty($errores)) {
    $_SESSION['mensaje']      = implode(' ', $errores);
    $_SESSION['mensaje_tipo'] = 'error';
    redirigir('perfil.php?id=' . $profesional_id);
}

$insert = $pdo->prepare(
    "INSERT INTO solicitudes_presupuesto
        (profesional_id, nombre_cliente, telefono_cliente, email_cliente,
         modalidad, zona_servicio, descripcion)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$insert->execute([
    $profesional_id,
    $nombre_cliente,
    $telefono_cliente,
    $email_cliente ?: null,
    $modalidad,
    ($modalidad === 'presencial' ? $zona_servicio : null),
    $descripcion,
]);

// Notificación al profesional por email (fallo silencioso)
$nombre_prof = $profesional['nombre'] . ' ' . $profesional['apellido'];
$asunto = 'Nueva solicitud de presupuesto — OficioRed';

$linea_tel = '<tr><td style="padding:6px 0;color:#64748b;width:120px;">Teléfono</td><td style="padding:6px 0;"><a href="https://wa.me/' . rawurlencode(preg_replace('/\D/', '', $telefono_cliente)) . '">' . htmlspecialchars($telefono_cliente, ENT_QUOTES, 'UTF-8') . '</a></td></tr>';
$linea_email = $email_cliente ? '<tr><td style="padding:6px 0;color:#64748b;">Email</td><td style="padding:6px 0;"><a href="mailto:' . htmlspecialchars($email_cliente, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($email_cliente, ENT_QUOTES, 'UTF-8') . '</a></td></tr>' : '';
$linea_modalidad = '<tr><td style="padding:6px 0;color:#64748b;">Modalidad</td><td style="padding:6px 0;">' . ($modalidad === 'presencial' ? 'Presencial' : 'A distancia / digital') . '</td></tr>';
$linea_zona = ($modalidad === 'presencial' && $zona_servicio) ? '<tr><td style="padding:6px 0;color:#64748b;">Zona</td><td style="padding:6px 0;">' . htmlspecialchars($zona_servicio, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '';

$cuerpo_html = '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f8fafc;margin:0;padding:32px;">
  <div style="max-width:520px;margin:auto;background:#fff;border-radius:8px;padding:32px;border:1px solid #e2e8f0;">
    <h2 style="margin:0 0 8px;color:#1e293b;">Nueva solicitud de presupuesto</h2>
    <p style="color:#64748b;margin:0 0 24px;">Alguien te contactó a través de <strong>OficioRed</strong>.</p>
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="padding:6px 0;color:#64748b;width:120px;">Nombre</td><td style="padding:6px 0;">' . htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') . '</td></tr>
      ' . $linea_tel . $linea_email . $linea_modalidad . $linea_zona . '
    </table>
    <div style="margin:20px 0;padding:16px;background:#f1f5f9;border-radius:6px;">
      <p style="margin:0;color:#1e293b;">' . nl2br(htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8')) . '</p>
    </div>
    <p style="color:#94a3b8;font-size:0.85em;margin:0;">Respondé al teléfono del cliente para coordinar.</p>
  </div>
</body>
</html>';

enviar_email($profesional['email'], $nombre_prof, $asunto, $cuerpo_html);

$_SESSION['mensaje']      = '¡Solicitud enviada! ' . $profesional['nombre'] . ' recibirá tus datos y se pondrá en contacto pronto.';
$_SESSION['mensaje_tipo'] = 'exito';
redirigir('perfil.php?id=' . $profesional_id);
