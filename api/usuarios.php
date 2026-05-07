<?php
// api/usuarios.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Leer usuarios (Ocultamos password)
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, email, telefono, direccion, fecha_registro FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
            }
        } else {
            $result = $conn->query("SELECT id_usuario, nombre, apellido, email, telefono, direccion, fecha_registro FROM usuarios ORDER BY id_usuario DESC");
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $usuarios]);
        }
        break;

    case 'POST':
        // Crear usuario
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['nombre']) && !empty($data['apellido']) && !empty($data['email']) && !empty($data['password'])) {
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $apellido = htmlspecialchars(strip_tags($data['apellido']));
            $email = htmlspecialchars(strip_tags($data['email']));
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $telefono = isset($data['telefono']) ? htmlspecialchars(strip_tags($data['telefono'])) : null;
            $direccion = isset($data['direccion']) ? htmlspecialchars(strip_tags($data['direccion'])) : null;
            $fecha_registro = date('Y-m-d H:i:s');

            // Check if email exists
            $stmt_check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "El email ya está registrado."]);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, password, telefono, direccion, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nombre, $apellido, $email, $password, $telefono, $direccion, $fecha_registro);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Usuario creado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo crear el usuario"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'PUT':
        // Actualizar usuario
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_usuario']) && !empty($data['nombre']) && !empty($data['apellido']) && !empty($data['email'])) {
            $id = intval($data['id_usuario']);
            $nombre = htmlspecialchars(strip_tags($data['nombre']));
            $apellido = htmlspecialchars(strip_tags($data['apellido']));
            $email = htmlspecialchars(strip_tags($data['email']));
            $telefono = isset($data['telefono']) ? htmlspecialchars(strip_tags($data['telefono'])) : null;
            $direccion = isset($data['direccion']) ? htmlspecialchars(strip_tags($data['direccion'])) : null;

            // Check if email exists for other user
            $stmt_check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ? LIMIT 1");
            $stmt_check->bind_param("si", $email, $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "El email ya está registrado por otro usuario."]);
                break;
            }

            if (!empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, password = ?, telefono = ?, direccion = ? WHERE id_usuario = ?");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $email, $password, $telefono, $direccion, $id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ?, direccion = ? WHERE id_usuario = ?");
                $stmt->bind_param("sssssi", $nombre, $apellido, $email, $telefono, $direccion, $id);
            }

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo actualizar el usuario"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'DELETE':
        // Eliminar usuario
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_usuario'])) {
            $id = intval($data['id_usuario']);
            
            // Verificaciones: comprobar si tiene transacciones o reseñas
            $stmt_check = $conn->prepare("SELECT id_transaccion FROM transacciones WHERE id_usuario = ? UNION SELECT id_reseña FROM reseñas WHERE id_usuario = ? LIMIT 1");
            $stmt_check->bind_param("ii", $id, $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "No se puede eliminar el usuario porque tiene transacciones o reseñas asociadas."]);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Usuario eliminado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar el usuario"]);
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
