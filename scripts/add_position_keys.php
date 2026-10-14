<?php
require_once __DIR__ . '/../includes/db.php';
$s = $pdo->prepare("INSERT IGNORE INTO configuracion_sitio (clave, valor) VALUES (?,?)");
foreach (['hero_imagen_pos_x'=>'50','hero_imagen_pos_y'=>'50','cta_imagen_pos_x'=>'50','cta_imagen_pos_y'=>'50'] as $k=>$v) {
    $s->execute([$k,$v]);
    echo "OK: $k\n";
}
