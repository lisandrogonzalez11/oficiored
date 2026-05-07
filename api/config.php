<?php
// api/config.php
$host = "127.0.0.1";
$username = "root"; // Usuario por defecto de XAMPP
$password = "45169210"; // Contraseña actualizada
$database = "oficiored"; // Nombre de tu base de datos

// Habilitar reporte de errores de MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8");
} catch (mysqli_sql_exception $e) {
    die(json_encode([
        "status" => "error",
        "message" => "Error de conexión a la base de datos: " . $e->getMessage()
    ]));
}
?>
