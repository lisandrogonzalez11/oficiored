<?php
// api/categorias.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Leer categorías
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM categorias WHERE id_categoria = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Categoría no encontrada"]);
            }
        } else {
            $result = $conn->query("SELECT * FROM categorias ORDER BY id_categoria DESC");
            $categorias = [];
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $categorias]);
        }
        break;

    case 'POST':
        // Crear categoría
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['nombre']) && !empty($data['descripcion'])) {
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $descripcion = htmlspecialchars(strip_tags($data['descripcion']));

            $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $descripcion);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Categoría creada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo crear la categoría"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        }
        break;

    case 'PUT':
        // Actualizar categoría
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_categoria']) && !empty($data['nombre']) && !empty($data['descripcion'])) {
            $id = intval($data['id_categoria']);
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $descripcion = htmlspecialchars(strip_tags($data['descripcion']));

            $stmt = $conn->prepare("UPDATE categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?");
            $stmt->bind_param("ssi", $nombre, $descripcion, $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Categoría actualizada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo actualizar la categoría"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        }
        break;

    case 'DELETE':
        // Eliminar categoría
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_categoria'])) {
            $id = intval($data['id_categoria']);
            
            // Verificar si hay servicios asociados antes de eliminar
            $stmt_check = $conn->prepare("SELECT id_servicio_ofrecido FROM servicios_ofrecidos WHERE id_categoria = ? LIMIT 1");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "No se puede eliminar la categoría porque tiene servicios asociados."]);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Categoría eliminada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar la categoría"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Método no soportado"]);
        break;
}
$conn->close();
?>
