<?php
// api/config.php
$host = "localhost";
$username = "root"; // Usuario por defecto de XAMPP
$password = ""; // Contraseña por defecto de XAMPP (vacía)
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
