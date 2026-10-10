<?php
// =============================================================
// admin/ver-dni.php — Proxy seguro para ver foto de DNI
// Nunca expone la ruta real del archivo.
// =============================================================

require_once '../includes/db.php';
require_once '../includes/funciones.php';

iniciar_sesion();
requerir_admin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Parámetro inválido.');
}

$stmt = $pdo->prepare(
    "SELECT foto_dni FROM profesionales WHERE id_profesional = ? LIMIT 1"
);
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['foto_dni']) || !foto_nombre_valido($row['foto_dni'])) {
    http_response_code(404);
    exit('Imagen no encontrada.');
}

$ruta = __DIR__ . '/../uploads/dni/' . $row['foto_dni'];

if (!file_exists($ruta) || !is_file($ruta)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

// Servir la imagen directamente sin exponer la ruta.
$mime = mime_content_type($ruta);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(415);
    exit('Tipo de archivo no permitido.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: no-store');
readfile($ruta);
exit;
