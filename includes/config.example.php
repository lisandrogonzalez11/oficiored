<?php
// =============================================================
// includes/config.example.php — Plantilla de configuracion (C3)
// Copiar a config.local.php y completar con los valores del entorno.
// config.local.php NO debe versionarse; este ejemplo SI.
// Si config.local.php no existe, db.php usa este archivo como fallback.
// =============================================================
return [
    'db_host'    => 'localhost',
    'db_name'    => 'oficiored',
    'db_user'    => 'root',
    'db_pass'    => '',
    'db_charset' => 'utf8mb4',
    'app_debug'  => true,

    // SMTP — Configurar con una cuenta Gmail y una Contraseña de Aplicación.
    // Pasos: Google Account → Seguridad → Verificación en 2 pasos → Contraseñas de aplicación.
    // Crear una contraseña para "Correo" y copiar los 16 caracteres que genera Google.
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_user'     => 'tucuenta@gmail.com',   // dirección Gmail de OficioRed
    'smtp_pass'     => '',                      // Contraseña de Aplicación de 16 caracteres
    'smtp_from'     => 'tucuenta@gmail.com',    // mismo valor que smtp_user
    'smtp_from_name'=> 'OficioRed',

    // Twilio WhatsApp OTP — completar con las credenciales de la consola de Twilio.
    // En sandbox: twilio_from = 'whatsapp:+14155238886'
    'twilio_sid'   => '',
    'twilio_token' => '',
    'twilio_from'  => '', // número Twilio en formato E.164: +17372508034
];
