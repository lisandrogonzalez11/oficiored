<?php
// =============================================================
// scripts/crear-admin.php — Creación del primer administrador
// Proyecto: OficioRed
//
// SOLO se ejecuta desde línea de comandos:
//   php scripts/crear-admin.php
//
// Requiere que la tabla `usuarios` esté vacía (un solo bootstrap).
// No existe endpoint web para esta operación.
// =============================================================

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.' . PHP_EOL);
}

require_once __DIR__ . '/../includes/db.php';

// ---------------------------------------------------
// Verificar que no existe ningún administrador aún
// ---------------------------------------------------
$total_admins = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
if ($total_admins > 0) {
    fwrite(STDERR, 'ERROR: Ya existe ' . $total_admins . ' administrador(es). '
        . 'Este script solo puede ejecutarse cuando la tabla usuarios está vacía.' . PHP_EOL);
    exit(1);
}

echo PHP_EOL;
echo '=====================================' . PHP_EOL;
echo '  OficioRed — Crear primer admin' . PHP_EOL;
echo '=====================================' . PHP_EOL . PHP_EOL;

// ---------------------------------------------------
// Helpers de lectura interactiva
// ---------------------------------------------------
function leer(string $prompt): string
{
    echo $prompt;
    $val = fgets(STDIN);
    $val = $val === false ? '' : trim($val);
    // Strip UTF-8 BOM if piped from a Windows tool
    return ltrim($val, "\xEF\xBB\xBF");
}

// ---------------------------------------------------
// Recoger datos
// ---------------------------------------------------
$nombre   = leer('Nombre:       ');
$apellido = leer('Apellido:     ');
$email    = leer('Email:        ');
$password = leer('Contraseña (mín. 10 caracteres, visible al escribir): ');

echo PHP_EOL;

// ---------------------------------------------------
// Validaciones
// ---------------------------------------------------
$errores = [];

if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
} elseif (mb_strlen($nombre) > 50) {
    $errores[] = 'El nombre no puede superar 50 caracteres.';
}

if ($apellido === '') {
    $errores[] = 'El apellido es obligatorio.';
} elseif (mb_strlen($apellido) > 50) {
    $errores[] = 'El apellido no puede superar 50 caracteres.';
}

if ($email === '') {
    $errores[] = 'El email es obligatorio.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El email no tiene un formato válido.';
} else {
    $stmtEmail = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE email = ?');
    $stmtEmail->execute([$email]);
    if ((int) $stmtEmail->fetchColumn() > 0) {
        $errores[] = 'El email ya está registrado.';
    }
}

if (strlen($password) < 10) {
    $errores[] = 'La contraseña debe tener al menos 10 caracteres.';
}

if (!empty($errores)) {
    fwrite(STDERR, 'No se pudo crear el administrador:' . PHP_EOL);
    foreach ($errores as $e) {
        fwrite(STDERR, '  · ' . $e . PHP_EOL);
    }
    exit(1);
}

// ---------------------------------------------------
// Insertar
// ---------------------------------------------------
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nombre, apellido, email, password)
     VALUES (?, ?, ?, ?)'
);
$stmt->execute([$nombre, $apellido, $email, $hash]);

echo '✓ Administrador creado exitosamente.' . PHP_EOL;
echo '  Nombre: ' . $nombre . ' ' . $apellido . PHP_EOL;
echo '  Email:  ' . $email . PHP_EOL . PHP_EOL;
echo 'Podés iniciar sesión en: admin/login.php' . PHP_EOL . PHP_EOL;
