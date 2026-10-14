<?php
// =============================================================
// lib/EmailOTP.php — Generación y envío de OTP por email
// =============================================================

class EmailOTP
{
    /**
     * Genera un código OTP de 6 dígitos con cero a la izquierda si hace falta.
     */
    public static function generarCodigo(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Guarda el OTP en la BD, invalidando el anterior del mismo profesional.
     * Expira en 10 minutos.
     */
    public static function guardarOTP(PDO $pdo, int $profesional_id, string $codigo): void
    {
        // Invalidar OTPs anteriores no usados.
        $pdo->prepare("UPDATE otp_whatsapp SET usado = 1 WHERE profesional_id = ? AND usado = 0")
            ->execute([$profesional_id]);

        $expira = date('Y-m-d H:i:s', time() + 600); // 10 minutos
        $pdo->prepare(
            "INSERT INTO otp_whatsapp (profesional_id, codigo, expira_en) VALUES (?, ?, ?)"
        )->execute([$profesional_id, $codigo, $expira]);
    }

    /**
     * Envía el código OTP por email via PHPMailer.
     * Devuelve true si se envió, false si hubo error.
     */
    public static function enviarCodigo(string $email, string $nombre, string $codigo): bool
    {
        require_once __DIR__ . '/../includes/mailer.php';

        $html = '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;background:#f4f4f4;padding:24px;">
  <div style="max-width:480px;margin:auto;background:#fff;border-radius:8px;padding:32px;">
    <h2 style="color:#C41E3A;margin-top:0;">OficioRed</h2>
    <p>Hola <strong>' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
    <p>Tu código de verificación es:</p>
    <div style="font-size:2.5rem;font-weight:700;letter-spacing:0.3em;
                text-align:center;padding:20px;background:#f8f8f8;
                border-radius:8px;margin:20px 0;color:#1e293b;">'
        . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') .
    '</div>
    <p style="color:#666;font-size:0.875rem;">
      Válido por <strong>10 minutos</strong>.<br>
      Si no creaste una cuenta en OficioRed, ignorá este email.
    </p>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="color:#999;font-size:0.75rem;">OficioRed · El Colorado, Formosa</p>
  </div>
</body>
</html>';

        return enviar_email($email, $nombre, 'Tu código de verificación – OficioRed', $html);
    }
}
