<?php
// api/profesionales.php
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
            $stmt = $conn->prepare("SELECT id_profesional, nombre, apellido, email, telefono, foto_perfil, biografia, fecha_registro FROM profesionales WHERE id_profesional = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Profesional no encontrado"]);
            }
        } else {
            $result = $conn->query("SELECT id_profesional, nombre, apellido, email, telefono, foto_perfil, biografia, fecha_registro FROM profesionales ORDER BY id_profesional DESC");
            $profesionales = [];
            while ($row = $result->fetch_assoc()) {
                $profesionales[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $profesionales]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['nombre']) && !empty($data['apellido']) && !empty($data['email']) && !empty($data['password'])) {
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $apellido = htmlspecialchars(strip_tags($data['apellido']));
            $email = htmlspecialchars(strip_tags($data['email']));
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $telefono = isset($data['telefono']) ? htmlspecialchars(strip_tags($data['telefono'])) : null;
            $foto_perfil = isset($data['foto_perfil']) ? htmlspecialchars(strip_tags($data['foto_perfil'])) : null;
            $biografia = isset($data['biografia']) ? htmlspecialchars(strip_tags($data['biografia'])) : null;
            $fecha_registro = date('Y-m-d H:i:s');

            $stmt_check = $conn->prepare("SELECT id_profesional FROM profesionales WHERE email = ? LIMIT 1");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "El email ya está registrado."]);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO profesionales (nombre, apellido, email, password, telefono, foto_perfil, biografia, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $nombre, $apellido, $email, $password, $telefono, $foto_perfil, $biografia, $fecha_registro);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Profesional creado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo crear el profesional"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_profesional']) && !empty($data['nombre']) && !empty($data['apellido']) && !empty($data['email'])) {
            $id = intval($data['id_profesional']);
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $apellido = htmlspecialchars(strip_tags($data['apellido']));
            $email = htmlspecialchars(strip_tags($data['email']));
            $telefono = isset($data['telefono']) ? htmlspecialchars(strip_tags($data['telefono'])) : null;
            $foto_perfil = isset($data['foto_perfil']) ? htmlspecialchars(strip_tags($data['foto_perfil'])) : null;
            $biografia = isset($data['biografia']) ? htmlspecialchars(strip_tags($data['biografia'])) : null;

            $stmt_check = $conn->prepare("SELECT id_profesional FROM profesionales WHERE email = ? AND id_profesional != ? LIMIT 1");
            $stmt_check->bind_param("si", $email, $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "El email ya está registrado por otro profesional."]);
                break;
            }

            if (!empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE profesionales SET nombre = ?, apellido = ?, email = ?, password = ?, telefono = ?, foto_perfil = ?, biografia = ? WHERE id_profesional = ?");
                $stmt->bind_param("sssssssi", $nombre, $apellido, $email, $password, $telefono, $foto_perfil, $biografia, $id);
            } else {
                $stmt = $conn->prepare("UPDATE profesionales SET nombre = ?, apellido = ?, email = ?, telefono = ?, foto_perfil = ?, biografia = ? WHERE id_profesional = ?");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $telefono, $foto_perfil, $biografia, $id);
            }

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Profesional actualizado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo actualizar el profesional"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_profesional'])) {
            $id = intval($data['id_profesional']);
            
            $stmt_check = $conn->prepare("SELECT id_servicio_ofrecido FROM servicios_ofrecidos WHERE id_profesional = ? LIMIT 1");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "No se puede eliminar el profesional porque tiene servicios publicados."]);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM profesionales WHERE id_profesional = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Profesional eliminado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar el profesional"]);
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
