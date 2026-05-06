<?php
// api/transacciones.php
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
                SELECT t.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, 
                       s.titulo as servicio_titulo, p.nombre as profesional_nombre, p.apellido as profesional_apellido
                FROM transacciones t
                JOIN usuarios u ON t.id_usuario = u.id_usuario
                JOIN servicios_ofrecidos s ON t.id_servicio_ofrecido = s.id_servicio_ofrecido
                JOIN profesionales p ON s.id_profesional = p.id_profesional
                WHERE t.id_transaccion = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo json_encode(["status" => "success", "data" => $result->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error", "message" => "Transacción no encontrada"]);
            }
        } else {
            $query = "
                SELECT t.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido, 
                       s.titulo as servicio_titulo, p.nombre as profesional_nombre, p.apellido as profesional_apellido
                FROM transacciones t
                JOIN usuarios u ON t.id_usuario = u.id_usuario
                JOIN servicios_ofrecidos s ON t.id_servicio_ofrecido = s.id_servicio_ofrecido
                JOIN profesionales p ON s.id_profesional = p.id_profesional
                ORDER BY t.id_transaccion DESC
            ";
            $result = $conn->query($query);
            $transacciones = [];
            while ($row = $result->fetch_assoc()) {
                $transacciones[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $transacciones]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_usuario']) && !empty($data['id_servicio_ofrecido'])) {
            $id_usuario = intval($data['id_usuario']);
            $id_servicio_ofrecido = intval($data['id_servicio_ofrecido']);
            $monto_acordado = isset($data['monto_acordado']) ? floatval($data['monto_acordado']) : 0.00;
            $estado = 'pendiente';
            $fecha_solicitud = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO transacciones (id_usuario, id_servicio_ofrecido, fecha_solicitud, monto_acordado, estado) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisds", $id_usuario, $id_servicio_ofrecido, $fecha_solicitud, $monto_acordado, $estado);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Transacción creada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo crear la transacción"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_transaccion']) && !empty($data['estado'])) {
            $id = intval($data['id_transaccion']);
            $estado = htmlspecialchars(strip_tags($data['estado']));
            $monto_acordado = isset($data['monto_acordado']) ? floatval($data['monto_acordado']) : 0.00;
            
            // Si el estado es finalizado, setear la fecha
            if ($estado === 'finalizado') {
                $fecha_finalizacion = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("UPDATE transacciones SET estado = ?, monto_acordado = ?, fecha_finalizacion = ? WHERE id_transaccion = ?");
                $stmt->bind_param("sdsi", $estado, $monto_acordado, $fecha_finalizacion, $id);
            } else {
                $stmt = $conn->prepare("UPDATE transacciones SET estado = ?, monto_acordado = ? WHERE id_transaccion = ?");
                $stmt->bind_param("sdi", $estado, $monto_acordado, $id);
            }

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Estado de transacción actualizado"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo actualizar la transacción"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id_transaccion'])) {
            $id = intval($data['id_transaccion']);
            
            // Ver si hay pagos o reseñas asociadas
            $stmt_check = $conn->prepare("SELECT id_pago FROM pagos WHERE id_transaccion = ? UNION SELECT id_reseña FROM reseñas WHERE id_transaccion = ? LIMIT 1");
            $stmt_check->bind_param("ii", $id, $id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "No se puede eliminar la transacción porque tiene pagos o reseñas asociadas."]);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM transacciones WHERE id_transaccion = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Transacción eliminada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "No se pudo eliminar la transacción"]);
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
