<?php
// =============================================================
// marcar-leida.php — Marca una solicitud de presupuesto como leída
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('dashboard.php');
}

verificar_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    redirigir('dashboard.php');
}

// Solo puede marcar sus propias solicitudes
$stmt = $pdo->prepare(
    "UPDATE solicitudes_presupuesto
     SET leida = 1
     WHERE id = ? AND profesional_id = ?"
);
$stmt->execute([$id, $_SESSION['profesional_id']]);

redirigir('dashboard.php');
