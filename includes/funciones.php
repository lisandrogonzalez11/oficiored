<?php
// =============================================================
// includes/funciones.php — Funciones globales reutilizables
// Proyecto: OficioRed
// Se incluye siempre despues de includes/db.php.
// =============================================================

/**
 * Limpia y escapa un texto para imprimirlo de forma segura en HTML.
 */
function sanitizar($texto)
{
    if ($texto === null) {
        return '';
    }
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige a la URL indicada y detiene la ejecucion.
 */
function redirigir($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Inicia la sesion de forma segura (C4): fija los flags de la cookie
 * de sesion (HttpOnly, SameSite=Lax, y Secure solo bajo HTTPS) antes
 * de arrancarla. Es idempotente: si la sesion ya esta activa, no hace
 * nada, evitando el warning "session already started".
 */
function iniciar_sesion()
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Indica si hay una sesion de profesional activa.
 * Asume que iniciar_sesion() (o session_start()) ya fue llamado.
 */
function esta_logueado()
{
    return isset($_SESSION['profesional_id']);
}

/**
 * Obliga a tener sesion activa: si no la hay, redirige a login.php.
 */
function requerir_login()
{
    if (!esta_logueado()) {
        redirigir('login.php');
    }
}

/**
 * Devuelve el token CSRF de la sesion, generandolo si aun no existe.
 * Asume que session_start() ya fue llamado.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF del POST contra el de la sesion.
 * Si no coincide, corta la ejecucion con un mensaje generico.
 */
function verificar_csrf()
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Token de seguridad inválido. Recargá la página.');
    }
}

/**
 * Valida los datos de un profesional y devuelve un array de errores
 * (clave = nombre del campo, valor = mensaje). Array vacio = sin errores.
 * Centraliza las reglas de validacion compartidas por registro.php y
 * editar-perfil.php, sin cambiar ninguna regla ni ningun mensaje.
 *
 * $datos: nombre, apellido, email, oficio, zona_cobertura, biografia,
 *         telefono y (solo en alta) password, password_conf.
 * $opciones:
 *   'validar_password' => bool  incluir chequeo de password/confirmacion
 *                          (true en el alta, false en la edicion).
 *   'excluir_id'       => int|null  id a excluir del chequeo de email unico.
 *                          null   = alta: el email debe ser totalmente unico.
 *                          int    = edicion: el email es unico salvo el propio.
 */
function validar_datos_profesional($pdo, array $datos, array $opciones = [])
{
    $validar_password = $opciones['validar_password'] ?? false;
    $excluir_id       = $opciones['excluir_id'] ?? null;

    $nombre         = $datos['nombre'] ?? '';
    $apellido       = $datos['apellido'] ?? '';
    $email          = $datos['email'] ?? '';
    $oficio         = $datos['oficio'] ?? '';
    $zona_cobertura = $datos['zona_cobertura'] ?? '';
    $biografia      = $datos['biografia'] ?? '';
    $telefono       = $datos['telefono'] ?? '';

    $errores = [];

    if ($nombre === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($nombre) > 50) {
        $errores['nombre'] = 'El nombre no puede superar los 50 caracteres.';
    }

    if ($apellido === '') {
        $errores['apellido'] = 'El apellido es obligatorio.';
    } elseif (mb_strlen($apellido) > 50) {
        $errores['apellido'] = 'El apellido no puede superar los 50 caracteres.';
    }

    if ($email === '') {
        $errores['email'] = 'El email es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El email no tiene un formato válido.';
    } else {
        if ($excluir_id === null) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM profesionales WHERE email = ?"
            );
            $stmt->execute([$email]);
            $mensaje_email_duplicado = 'El email ya está registrado.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM profesionales
             WHERE email = ? AND id_profesional != ?"
            );
            $stmt->execute([$email, $excluir_id]);
            $mensaje_email_duplicado = 'El email ya está en uso por otra cuenta.';
        }
        if ((int) $stmt->fetchColumn() > 0) {
            $errores['email'] = $mensaje_email_duplicado;
        }
    }

    if ($validar_password) {
        $password      = $datos['password'] ?? '';
        $password_conf = $datos['password_conf'] ?? '';

        if (strlen($password) < 8) {
            $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($password_conf !== $password) {
            $errores['password_conf'] = 'Las contraseñas no coinciden.';
        }
    }

    if ($oficio === '') {
        $errores['oficio'] = 'Seleccioná un oficio.';
    }

    // zona_cobertura: validada por separado (pivot profesional_zonas).

    if ($biografia !== '' && mb_strlen($biografia) > 500) {
        $errores['biografia'] = 'La biografía no puede superar los 500 caracteres.';
    }

    if ($telefono !== '' && !preg_match('/^[0-9\s\-]+$/', $telefono)) {
        $errores['telefono'] = 'El teléfono solo puede tener dígitos, espacios y guiones.';
    }

    return $errores;
}

/**
 * Devuelve las zonas activas ordenadas por orden/nombre.
 */
function obtener_zonas(PDO $pdo): array
{
    try {
        return $pdo->query(
            "SELECT id_zona, nombre, es_todo
             FROM zonas WHERE activa = 1
             ORDER BY orden ASC, nombre ASC"
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('obtener_zonas: ' . $e->getMessage());
        return [];
    }
}

/**
 * Devuelve los IDs de zona asociados a un profesional.
 */
function obtener_ids_zonas_profesional(PDO $pdo, int $profesional_id): array
{
    $stmt = $pdo->prepare(
        "SELECT zona_id FROM profesional_zonas WHERE profesional_id = ?"
    );
    $stmt->execute([$profesional_id]);
    return array_column($stmt->fetchAll(), 'zona_id');
}

/**
 * Devuelve las filas completas de zona de un profesional (para mostrar chips).
 */
function obtener_zonas_profesional(PDO $pdo, int $profesional_id): array
{
    $stmt = $pdo->prepare(
        "SELECT z.id_zona, z.nombre, z.es_todo
         FROM profesional_zonas pz
         JOIN zonas z ON z.id_zona = pz.zona_id
         WHERE pz.profesional_id = ?
         ORDER BY z.orden ASC"
    );
    $stmt->execute([$profesional_id]);
    return $stmt->fetchAll();
}

/**
 * Guarda las zonas de un profesional (borra las anteriores e inserta las nuevas).
 * Devuelve el texto resumen para el campo zona_cobertura.
 */
function guardar_zonas_profesional(PDO $pdo, int $profesional_id, array $zona_ids): string
{
    $pdo->prepare("DELETE FROM profesional_zonas WHERE profesional_id = ?")
        ->execute([$profesional_id]);

    if (empty($zona_ids)) return '';

    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO profesional_zonas (profesional_id, zona_id) VALUES (?, ?)"
    );
    foreach ($zona_ids as $id) {
        $stmt->execute([$profesional_id, (int)$id]);
    }

    // Generar texto resumen para la columna zona_cobertura
    $rows = obtener_zonas_profesional($pdo, $profesional_id);
    foreach ($rows as $r) {
        if ($r['es_todo']) return 'Todo El Colorado';
    }
    return implode(', ', array_column($rows, 'nombre'));
}

/**
 * Devuelve todas las categorias ordenadas por nombre.
 * Si falla la consulta, devuelve un array vacio.
 */
function obtener_categorias($pdo)
{
    try {
        $stmt = $pdo->query(
            "SELECT id_categoria, nombre, icono_lucide
             FROM categorias
             ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Politica unificada (C1/I8): no se traga el error en silencio,
        // se registra en el log. Se conserva la degradacion elegante
        // (array vacio) porque la vista esta preparada para no tener
        // categorias y la spec pide no romper la pagina que la invoca.
        error_log('Error al obtener categorías: ' . $e->getMessage());
        return [];
    }
}

/**
 * Escapa los metacaracteres LIKE de MariaDB/MySQL usando '#' como caracter
 * de escape. Debe usarse junto con la clausula ESCAPE '#' en la query SQL.
 * Interna: no llamar desde fuera de este archivo.
 */
function escapar_like(string $valor): string
{
    return str_replace(['#', '%', '_'], ['##', '#%', '#_'], $valor);
}

/**
 * Normaliza un numero de telefono argentino al formato wa.me: 549XXXXXXXXXX.
 * Retorna null si el formato no es reconocible.
 *
 * Cubre: con/sin trunk 0, con/sin prefijo 15 (areas de 3 digitos),
 * con codigo de pais 54 o 549, con simbolo +, con espacios y guiones.
 * Limitacion documentada: deteccion de '15' asume area de 3 digitos
 * (Chaco, Formosa, etc.). Generalizar en revision posterior con mas casos.
 */
function normalizar_telefono_wa(string $tel): ?string
{
    $s = preg_replace('/\D/', '', $tel);

    if ($s === '') {
        return null;
    }

    // Ya completo: 549 + 10 digitos
    if (strlen($s) === 13 && substr($s, 0, 3) === '549') {
        return $s;
    }

    // Con codigo de pais sin 9 movil: 54 + 10 digitos
    if (strlen($s) === 12 && substr($s, 0, 2) === '54') {
        return '549' . substr($s, 2);
    }

    // Quitar trunk 0 local
    if (substr($s, 0, 1) === '0') {
        $s = substr($s, 1);
    }

    // Quitar prefijo movil 15 para area de 3 digitos:
    // area(3) + '15' + numero(7) = 12 digitos
    if (strlen($s) === 12 && substr($s, 3, 2) === '15') {
        $s = substr($s, 0, 3) . substr($s, 5);
    }

    // Debe quedar exactamente 10 digitos (area + numero)
    if (strlen($s) === 10) {
        return '549' . $s;
    }

    return null;
}

/**
 * Convierte una fecha MySQL DATETIME a formato 'mes año' en espanol.
 * Ejemplo: '2026-06-15 10:30:00' -> 'junio 2026'.
 */
/**
 * Convierte un valor de php.ini con sufijo (K/M/G) a bytes enteros.
 * Ejemplo: '40M' -> 41943040.
 */
function ini_bytes(string $val): int
{
    $val  = trim($val);
    $last = strtolower(substr($val, -1));
    $num  = (int) $val;
    switch ($last) {
        case 'g': return $num * 1073741824;
        case 'm': return $num * 1048576;
        case 'k': return $num * 1024;
    }
    return $num;
}

/**
 * Devuelve un mensaje legible para un codigo de error de subida de archivo.
 */
function upload_error_message(int $error): string
{
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta. Intentá de nuevo.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Error interno del servidor (sin carpeta temporal).',
        UPLOAD_ERR_CANT_WRITE => 'Error interno del servidor (no se pudo escribir).',
        UPLOAD_ERR_EXTENSION  => 'Subida bloqueada por una extensión del servidor.',
    ];
    return $msgs[$error] ?? 'Error desconocido al subir el archivo.';
}

/**
 * Verifica que un nombre de foto cumpla el patron [a-f0-9]{32}.jpg.
 * Usar siempre antes de cualquier operacion de filesystem sobre foto_perfil.
 */
function foto_nombre_valido(string $nombre): bool
{
    return (bool) preg_match('/^[a-f0-9]{32}\.jpg$/', $nombre);
}

/**
 * Corrige la orientacion EXIF de una imagen GD rotando segun el valor
 * de Orientation. Devuelve la imagen corregida (puede ser la misma instancia
 * si no requiere rotacion, o una nueva si rotó — el caller destruye solo la original).
 */
function corregir_orientacion_exif(\GdImage $img, int $orientacion): \GdImage
{
    switch ($orientacion) {
        case 3: return imagerotate($img, 180, 0);
        case 6: return imagerotate($img, -90, 0);
        case 8: return imagerotate($img, 90, 0);
    }
    return $img;
}

/**
 * Valida y procesa un archivo de imagen subido para foto de perfil.
 * Devuelve ['ok'=>false,'error'=>string] o
 *          ['ok'=>true,'imagen'=>GdImage,'nombre'=>string].
 * El caller debe imagedestroy($resultado['imagen']) tras guardarla con imagejpeg().
 */
function procesar_foto_perfil(array $file): array
{
    $err = static function (string $msg): array {
        return ['ok' => false, 'error' => $msg];
    };

    // 1. Error de subida
    if (!isset($file['error'])) {
        return $err('No se recibió ningún archivo.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $err(upload_error_message($file['error']));
    }

    // 2. is_uploaded_file
    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
        return $err('Subida de archivo no válida.');
    }

    // 3. Tamaño: máximo 5 MB (o upload_max_filesize si es menor)
    $max_bytes = min(5 * 1048576, ini_bytes(ini_get('upload_max_filesize')));
    if ($file['size'] === 0) {
        return $err('El archivo está vacío.');
    }
    if ($file['size'] > $max_bytes) {
        return $err('El archivo supera los 5 MB.');
    }

    // 4. MIME real via finfo
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mime     = $finfo->file($file['tmp_name']);
    $mimes_ok = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $mimes_ok, true)) {
        return $err('El archivo no es una imagen válida (JPEG, PNG o WebP).');
    }

    // 5. getimagesize y tipo
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return $err('No se pudo leer la imagen. Verificá que el archivo no esté corrupto.');
    }
    $tipos_ok = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
    if (!in_array($info[2], $tipos_ok, true)) {
        return $err('Formato de imagen no soportado.');
    }

    // 6. Dimensiones mín/máx
    [$ancho, $alto] = $info;
    if ($ancho < 200 || $alto < 200) {
        return $err('La imagen debe tener al menos 200×200 píxeles.');
    }
    if ($ancho > 6000 || $alto > 6000) {
        return $err('La imagen no puede superar los 6000×6000 píxeles.');
    }

    // 7. Decodificar con GD (confirma que es imagen real)
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($file['tmp_name']); break;
        case IMAGETYPE_PNG:  $src = @imagecreatefrompng($file['tmp_name']);  break;
        case IMAGETYPE_WEBP: $src = @imagecreatefromwebp($file['tmp_name']); break;
        default:             $src = false;
    }
    if ($src === false) {
        return $err('No se pudo procesar la imagen. Verificá que no esté corrupta.');
    }

    // 8. Correccion EXIF de orientacion (solo JPEG, degradacion gracil)
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        if ($exif && isset($exif['Orientation']) && (int) $exif['Orientation'] !== 1) {
            $src_rot = corregir_orientacion_exif($src, (int) $exif['Orientation']);
            if ($src_rot !== $src) {
                imagedestroy($src);
                $src = $src_rot;
            }
        }
    }

    // Dimensiones reales post-rotacion
    $ancho_real = imagesx($src);
    $alto_real  = imagesy($src);

    // 9. Recorte cuadrado centrado + resize a 400x400
    $lado   = min($ancho_real, $alto_real);
    $ox     = (int) (($ancho_real - $lado) / 2);
    $oy     = (int) (($alto_real  - $lado) / 2);

    $canvas = imagecreatetruecolor(400, 400);
    if ($canvas === false) {
        imagedestroy($src);
        return $err('Error interno al procesar la imagen.');
    }

    imagecopyresampled($canvas, $src, 0, 0, $ox, $oy, 400, 400, $lado, $lado);
    imagedestroy($src);

    $nombre = bin2hex(random_bytes(16)) . '.jpg';

    return ['ok' => true, 'imagen' => $canvas, 'nombre' => $nombre];
}

/**
 * Calcula el porcentaje de completitud del perfil de un profesional.
 * 5 items x 20%: telefono + biografia + servicio activo + foto + identidad verificada.
 */
function calcular_completitud(array $profesional, int $servicios_activos): array
{
    $items = [
        [
            'label'    => 'Teléfono de contacto',
            'completo' => !empty($profesional['telefono']),
            'accion'   => 'editar-perfil.php',
            'puntos'   => 25,
        ],
        [
            'label'    => 'Biografía (mínimo 30 caracteres)',
            'completo' => mb_strlen($profesional['biografia'] ?? '') >= 30,
            'accion'   => 'editar-perfil.php',
            'puntos'   => 25,
        ],
        [
            'label'    => 'Al menos un servicio activo',
            'completo' => $servicios_activos >= 1,
            'accion'   => 'mis-servicios.php',
            'puntos'   => 25,
        ],
        [
            'label'    => 'Foto de perfil',
            'completo' => !empty($profesional['foto_perfil']),
            'accion'   => 'editar-perfil.php',
            'puntos'   => 25,
        ],
    ];

    $porcentaje = 0;
    foreach ($items as $item) {
        if ($item['completo']) {
            $porcentaje += $item['puntos'];
        }
    }

    return ['porcentaje' => $porcentaje, 'items' => $items];
}

function esta_logueado_admin(): bool
{
    return isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0;
}

function requerir_admin(): void
{
    if (!esta_logueado_admin()) {
        redirigir('login.php');
    }
}

function formatear_fecha($fecha_mysql)
{
    if (empty($fecha_mysql)) {
        return '';
    }

    $meses = [
        1  => 'enero',
        2  => 'febrero',
        3  => 'marzo',
        4  => 'abril',
        5  => 'mayo',
        6  => 'junio',
        7  => 'julio',
        8  => 'agosto',
        9  => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    $timestamp = strtotime($fecha_mysql);
    if ($timestamp === false) {
        return '';
    }

    $numero_mes = (int) date('n', $timestamp);
    $anio       = date('Y', $timestamp);

    return $meses[$numero_mes] . ' ' . $anio;
}

// =============================================================
// Recuperación de contraseña
// =============================================================

/**
 * Genera un token seguro de 64 caracteres hex y lo guarda en la BD.
 * Expira en 1 hora. Invalida tokens anteriores del mismo email.
 */
function crear_token_recuperacion(PDO $pdo, string $email): string
{
    // Invalidar tokens anteriores no usados del mismo email.
    $pdo->prepare("DELETE FROM tokens_recuperacion WHERE email = ? AND usado = 0")
        ->execute([$email]);

    $token = bin2hex(random_bytes(32)); // 64 chars hex
    $expira = date('Y-m-d H:i:s', time() + 3600); // 1 hora

    $pdo->prepare(
        "INSERT INTO tokens_recuperacion (email, token, expira_en) VALUES (?, ?, ?)"
    )->execute([$email, $token, $expira]);

    return $token;
}

/**
 * Valida un token: existe, no usado, no expirado.
 * Devuelve el email asociado o null si es inválido.
 */
function validar_token_recuperacion(PDO $pdo, string $token): ?string
{
    $stmt = $pdo->prepare(
        "SELECT email FROM tokens_recuperacion
         WHERE token = ? AND usado = 0 AND expira_en > NOW()"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ? $row['email'] : null;
}

/**
 * Marca un token como usado.
 */
function consumir_token_recuperacion(PDO $pdo, string $token): void
{
    $pdo->prepare("UPDATE tokens_recuperacion SET usado = 1 WHERE token = ?")
        ->execute([$token]);
}

/**
 * Devuelve una cadena legible del tiempo transcurrido desde $fecha_mysql.
 * Ej: "hace 5 minutos", "hace 2 días", "hace 3 meses".
 */
function tiempo_relativo(string $fecha_mysql): string
{
    $ts  = strtotime($fecha_mysql);
    if ($ts === false) return '';
    $diff = time() - $ts;

    if ($diff < 60)        return 'hace un momento';
    if ($diff < 3600)      return 'hace ' . floor($diff / 60)   . ' minuto'  . (floor($diff / 60)   > 1 ? 's' : '');
    if ($diff < 86400)     return 'hace ' . floor($diff / 3600)  . ' hora'    . (floor($diff / 3600)  > 1 ? 's' : '');
    if ($diff < 604800)    return 'hace ' . floor($diff / 86400)  . ' día'     . (floor($diff / 86400)  > 1 ? 's' : '');
    if ($diff < 2592000)   return 'hace ' . floor($diff / 604800) . ' semana'  . (floor($diff / 604800) > 1 ? 's' : '');
    if ($diff < 31536000)  return 'hace ' . floor($diff / 2592000) . ' mes'    . (floor($diff / 2592000) > 1 ? 'es' : '');
    return 'hace ' . floor($diff / 31536000) . ' año' . (floor($diff / 31536000) > 1 ? 's' : '');
}

/**
 * Construye el HTML del email de recuperación.
 */
function email_recuperacion_html(string $nombre, string $enlace, string $tipo = 'profesional'): string
{
    $rol = $tipo === 'admin' ? 'como administrador' : 'como profesional';
    return '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Recuperar contraseña – OficioRed</title></head>
<body style="font-family:sans-serif;background:#f4f4f4;padding:24px;">
  <div style="max-width:520px;margin:auto;background:#fff;border-radius:8px;padding:32px;">
    <h2 style="color:#C41E3A;margin-top:0;">OficioRed</h2>
    <p>Hola <strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
    <p>Recibimos una solicitud para restablecer tu contraseña ' . $rol . '.</p>
    <p style="margin:24px 0;">
      <a href="' . $enlace . '"
         style="background:#C41E3A;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">
        Restablecer contraseña
      </a>
    </p>
    <p style="color:#666;font-size:0.875rem;">
      Este enlace expira en <strong>1 hora</strong>.<br>
      Si no solicitaste este cambio, podés ignorar este email.
    </p>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
  </div>
</body>
</html>';
}
