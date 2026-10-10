<?php
// =============================================================
// eliminar-solicitud.php — Elimina una o varias solicitudes.
// Solo puede eliminar el profesional dueño de las solicitudes.
// =============================================================

require_once 'includes/db.php';
require_once 'includes/funciones.php';

iniciar_sesion();
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('dashboard.php');
}

verificar_csrf();

// Acepta ids[] (bulk) o id (individual legacy)
$ids_raw = $_POST['ids'] ?? (isset($_POST['id']) ? [$_POST['id']] : []);
$ids = array_filter(array_map('intval', (array) $ids_raw), fn($v) => $v > 0);

if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$_SESSION['profesional_id']]);
    $pdo->prepare(
        "DELETE FROM solicitudes_presupuesto
         WHERE id IN ($placeholders) AND profesional_id = ?"
    )->execute($params);
}

$filtro = in_array($_POST['filtro'] ?? '', ['todas', 'nuevas']) ? $_POST['filtro'] : 'nuevas';
redirigir('dashboard.php?filtro=' . $filtro);
