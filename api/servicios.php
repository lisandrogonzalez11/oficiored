<?php
// api/servicios.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("
                SELECT s.*, p.nombre as profesional_nombre, p.apellido as profesional_apellido, c.nombre as categoria_nombre
                FROM servicios_ofrecidos s
                JOIN profesionales p ON s.id_profesional = p.id_profesional
                JOIN categorias c ON s.id_categoria = c.id_categoria
                WHERE s.id_servicio_ofrecido = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Servicio no encontrado"]);
            }
        } else {
            $query = "
                SELECT s.*, p.nombre as profesional_nombre, p.apellido as profesional_apellido, c.nombre as categoria_nombre
                FROM servicios_ofrecidos s
                JOIN profesionales p ON s.id_profesional = p.id_profesional
                JOIN categorias c ON s.id_categoria = c.id_categoria
                ORDER BY s.id_servicio_ofrecido DESC
            ";
            $result = $conn->query($query);
            $servicios = [];
            while ($row = $result->fetch_assoc()) {
                $servicios[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $servicios]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_profesional']) && !empty($data['id_categoria']) && !empty($data['titulo']) && !empty($data['descripcion'])) {
            $id_profesional = intval($data['id_profesional']);
            $id_categoria = intval($data['id_categoria']);
            $titulo = htmlspecialchars(strip_tags($data['titulo']));
            $descripcion = htmlspecialchars(strip_tags($data['descripcion']));
            $zona_cobertura = isset($data['zona_cobertura']) ? htmlspecialchars(strip_tags($data['zona_cobertura'])) : null;
            $precio_base = isset($data['precio_base']) ? floatval($data['precio_base']) : 0.00;
            $estado = isset($data['estado']) ? htmlspecialchars(strip_tags($data['estado'])) : 'activo';

            $stmt = $conn->prepare("INSERT INTO servicios_ofrecidos (id_profesional, id_categoria, titulo, descripcion, zona_cobertura, precio_base, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssds", $id_profesional, $id_categoria, $titulo, $descripcion, $zona_cobertura, $precio_base, $estado);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Servicio creado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo crear el servicio"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_servicio_ofrecido']) && !empty($data['id_profesional']) && !empty($data['id_categoria']) && !empty($data['titulo']) && !empty($data['descripcion'])) {
            $id = intval($data['id_servicio_ofrecido']);
            $id_profesional = intval($data['id_profesional']);
            $id_categoria = intval($data['id_categoria']);
            $titulo = htmlspecialchars(strip_tags($data['titulo']));
            $descripcion = htmlspecialchars(strip_tags($data['descripcion']));
            $zona_cobertura = isset($data['zona_cobertura']) ? htmlspecialchars(strip_tags($data['zona_cobertura'])) : null;
            $precio_base = isset($data['precio_base']) ? floatval($data['precio_base']) : 0.00;
            $estado = isset($data['estado']) ? htmlspecialchars(strip_tags($data['estado'])) : 'activo';

            $stmt = $conn->prepare("UPDATE servicios_ofrecidos SET id_profesional = ?, id_categoria = ?, titulo = ?, descripcion = ?, zona_cobertura = ?, precio_base = ?, estado = ? WHERE id_servicio_ofrecido = ?");
            $stmt->bind_param("iisssdsi", $id_profesional, $id_categoria, $titulo, $descripcion, $zona_cobertura, $precio_base, $estado, $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Servicio actualizado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo actualizar el servicio"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_servicio_ofrecido'])) {
            $id = intval($data['id_servicio_ofrecido']);
            
            // Check transactions
            $stmt_check = $conn->prepare("SELECT id_transaccion FROM transacciones WHERE id_servicio_ofrecido = ? LIMIT 1");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "No se puede eliminar el servicio porque tiene transacciones asociadas. Considere cambiar su estado a inactivo."]);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM servicios_ofrecidos WHERE id_servicio_ofrecido = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Servicio eliminado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar el servicio"]);
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
