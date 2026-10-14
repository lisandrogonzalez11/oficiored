<?php
// Script de un solo uso: corrige el encoding del texto CTA en configuracion_sitio
require_once __DIR__ . '/../includes/db.php';

$fixes = [
    'cta_titulo'    => '¿Sos trabajador independiente?',
    'cta_subtitulo' => 'Publicá tu perfil gratis y conseguí más clientes en El Colorado.',
];

$stmt = $pdo->prepare(
    "INSERT INTO configuracion_sitio (clave, valor) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
);

foreach ($fixes as $k => $v) {
    $stmt->execute([$k, $v]);
    echo "OK: $k\n";
}
echo "Encoding corregido.\n";
