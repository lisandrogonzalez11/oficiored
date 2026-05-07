<?php
/**
 * Archivo: conexion.php
 * Ubicación: Raíz del proyecto (htdocs/OficioRed/conexion.php)
 * Propósito: Probar la conexión a la base de datos y realizar un SELECT de prueba.
 */

// 1. Definir los datos de conexión
$host = "127.0.0.1";
$puerto = "3306";
$usuario = "root";
$password = "45169210";
$base_de_datos = "oficiored";

// 2. Crear la conexión usando la extensión MySQLi (Orientado a Objetos)
// Utilizamos try-catch para manejar errores de forma elegante
try {
    $conexion = new mysqli($host, $usuario, $password, $base_de_datos, $puerto);
    
    // Configurar el conjunto de caracteres a UTF-8
    $conexion->set_charset("utf8");

    // Si llegamos aquí, la conexión fue exitosa
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif;'>";
    echo "<h3>✅ ¡Conexión a MySQL exitosa!</h3>";
    echo "Base de datos conectada: <strong>" . $base_de_datos . "</strong>";
    echo "</div>";

    // 3. Ejemplo de SELECT para probar que la base de datos responde
    echo "<div style='font-family: sans-serif;'>";
    echo "<h4>Ejemplo de SELECT (Listando Categorías):</h4>";
    
    $query = "SELECT id_categoria, nombre, descripcion FROM categorias LIMIT 5";
    $resultado = $conexion->query($query);

    if ($resultado && $resultado->num_rows > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f8f9fa;'><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
        
        while($fila = $resultado->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $fila["id_categoria"] . "</td>";
            echo "<td>" . $fila["nombre"] . "</td>";
            echo "<td>" . ($fila["descripcion"] ?? 'Sin descripción') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><em>La conexión funciona, pero la tabla 'categorias' actualmente está vacía.</em></p>";
    }
    echo "</div>";

    // 4. Cerrar la conexión
    $conexion->close();

} catch (mysqli_sql_exception $e) {
    // Si hay un error (ej: MySQL apagado, contraseña incorrecta)
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; font-family: sans-serif;'>";
    echo "<h3>❌ Error de Conexión</h3>";
    echo "<p>No se pudo conectar a la base de datos. Detalle del error:</p>";
    echo "<code>" . $e->getMessage() . "</code>";
    echo "</div>";
}
?>
