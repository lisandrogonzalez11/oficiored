<?php
// =============================================================
// includes/db.php — Conexion PDO a la base de datos
// Proyecto: OficioRed
// Deja disponible la variable $pdo. No produce output HTML.
// =============================================================

// -------------------------------------------------------------
// Configuracion de entorno (C3)
// Las credenciales y el flag de debug viven en config.local.php,
// que NO se versiona. Si no existe, se usa config.example.php como
// fallback para que el proyecto siga arrancando out-of-the-box.
// -------------------------------------------------------------
$config_file = __DIR__ . '/config.local.php';
if (!is_file($config_file)) {
    $config_file = __DIR__ . '/config.example.php';
}
$config = require $config_file;

// -------------------------------------------------------------
// Politica de manejo de errores (C1)
// APP_DEBUG debe quedar en TRUE en local y pasarse a FALSE al
// publicar en produccion (InfinityFree). En produccion los errores
// se registran en el log y NUNCA se muestran al usuario (no se
// filtran rutas, consultas SQL ni detalles internos).
// En local el comportamiento de errores queda igual que siempre.
// El valor se toma de config.local.php ('app_debug').
// -------------------------------------------------------------
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $config['app_debug'] ?? true);
}

if (!APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    // Pagina generica ante cualquier fallo, sin filtrar detalles.
    $mostrar_error_generico = function () {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
           . '<title>Error | OficioRed</title></head><body>'
           . '<h1>Algo salió mal</h1>'
           . '<p>Tuvimos un problema técnico. Probá de nuevo en unos minutos.</p>'
           . '<p><a href="index.php">Volver al inicio</a></p>'
           . '</body></html>';
    };

    // Excepciones no capturadas (por ejemplo, PDOException en una query).
    set_exception_handler(function ($e) use ($mostrar_error_generico) {
        error_log('Excepción no capturada: ' . $e->getMessage());
        $mostrar_error_generico();
    });

    // Errores fatales de PHP.
    register_shutdown_function(function () use ($mostrar_error_generico) {
        $error = error_get_last();
        $fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if ($error !== null && in_array($error['type'], $fatales, true)) {
            error_log('Error fatal: ' . $error['message']);
            $mostrar_error_generico();
        }
    });
}

$host    = $config['db_host'];
$db      = $config['db_name'];
$user    = $config['db_user'];
$pass    = $config['db_pass'];
$charset = $config['db_charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opciones);
    // Sincronizar zona horaria PHP y MySQL (Argentina UTC-3).
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log('Error de conexión BD: ' . $e->getMessage());
    die('Error de conexión. Intentá más tarde.');
}
