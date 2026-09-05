<?php
// =============================================================
// includes/config.example.php — Plantilla de configuracion (C3)
// Copiar a config.local.php y completar con los valores del entorno.
// config.local.php NO debe versionarse; este ejemplo SI.
// Si config.local.php no existe, db.php usa este archivo como fallback.
// =============================================================
return [
    'db_host'    => 'localhost',
    'db_name'    => 'oficiored',
    'db_user'    => 'root',
    'db_pass'    => '',
    'db_charset' => 'utf8mb4',
    'app_debug'  => true,
];
