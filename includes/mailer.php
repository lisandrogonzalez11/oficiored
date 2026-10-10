<?php
// =============================================================
// includes/mailer.php — Wrapper de envío de email via PHPMailer
// =============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

/**
 * Envía un email transaccional.
 *
 * @param string $to_email   Destinatario
 * @param string $to_name    Nombre del destinatario
 * @param string $subject    Asunto
 * @param string $body_html  Cuerpo HTML
 * @return bool  true si se envió, false si falló
 */
function enviar_email(string $to_email, string $to_name, string $subject, string $body_html): bool
{
    $cfg = require __DIR__ . (file_exists(__DIR__ . '/config.local.php') ? '/config.local.php' : '/config.example.php');

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_user'];
        $mail->Password   = $cfg['smtp_pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';
        $mail->XMailer    = ' ';

        $mail->setFrom($cfg['smtp_from'], $cfg['smtp_from_name']);
        $mail->addReplyTo($cfg['smtp_from'], $cfg['smtp_from_name']);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body_html));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('PHPMailer error: ' . $e->getMessage());
        }
        return false;
    }
}
