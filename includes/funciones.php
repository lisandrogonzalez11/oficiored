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

    if ($zona_cobertura === '') {
        $errores['zona_cobertura'] = 'La zona de cobertura es obligatoria.';
    } elseif (mb_strlen($zona_cobertura) > 80) {
        $errores['zona_cobertura'] = 'La zona no puede superar los 80 caracteres.';
    }

    if ($biografia !== '' && mb_strlen($biografia) > 500) {
        $errores['biografia'] = 'La biografía no puede superar los 500 caracteres.';
    }

    if ($telefono !== '' && !preg_match('/^[0-9\s\-]+$/', $telefono)) {
        $errores['telefono'] = 'El teléfono solo puede tener dígitos, espacios y guiones.';
    }

    return $errores;
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
 * Convierte una fecha MySQL DATETIME a formato 'mes año' en espanol.
 * Ejemplo: '2026-06-15 10:30:00' -> 'junio 2026'.
 */
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
