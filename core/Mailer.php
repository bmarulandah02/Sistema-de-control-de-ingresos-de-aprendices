<?php
// ──────────────────────────────────────────────
//  core/Mailer.php — Servicio de Envío de Correo SMTP Ligero y Seguro
// ──────────────────────────────────────────────

declare(strict_types=1);

require_once __DIR__ . '/Env.php';

class Mailer {

    /**
     * Envía un correo electrónico mediante conexión directa SMTP (Socket con SSL/TLS).
     *
     * @param string $destinatario Correo del receptor
     * @param string $nombreDestinatario Nombre del receptor
     * @param string $asunto Asunto del correo
     * @param string $cuerpoHtml Contenido HTML
     * @param string $cuerpoTexto Contenido alternativo en texto plano
     * @return array{success: bool, error?: string}
     */
    public static function enviar(
        string $destinatario,
        string $nombreDestinatario,
        string $asunto,
        string $cuerpoHtml,
        string $cuerpoTexto = ''
    ): array {
        $host       = (string) env('MAIL_HOST', 'smtp.hostinger.com');
        $port       = (int)    env('MAIL_PORT', 465);
        $username   = (string) env('MAIL_USERNAME', 'maru@adsoproyectos.site');
        $password   = (string) env('MAIL_PASSWORD', ':mYk3ZQT$IJ1');
        $encryption = strtolower((string) env('MAIL_ENCRYPTION', 'ssl'));
        $fromEmail  = (string) env('MAIL_FROM_ADDRESS', $username);
        $fromName   = (string) env('MAIL_FROM_NAME', 'SENA — Control de Ingresos');

        $protocol = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
        $socketUri = $protocol . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $timeout = 10;
        $fp = @stream_socket_client($socketUri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$fp) {
            $errorMsg = "No se pudo conectar al servidor SMTP ({$socketUri}): {$errstr} ({$errno})";
            error_log($errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }

        stream_set_timeout($fp, $timeout);

        try {
            // Leer banner de bienvenida
            self::leerRespuesta($fp, '220');

            // Saludo EHLO
            self::ejecutarComando($fp, "EHLO " . gethostname(), '250');

            // Si es STARTTLS en puerto 587
            if ($encryption === 'tls' && $port !== 465) {
                self::ejecutarComando($fp, "STARTTLS", '220');
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException("Fallo al negociar cifrado TLS con el servidor SMTP.");
                }
                self::ejecutarComando($fp, "EHLO " . gethostname(), '250');
            }

            // Autenticación LOGIN
            self::ejecutarComando($fp, "AUTH LOGIN", '334');
            self::ejecutarComando($fp, base64_encode($username), '334');
            self::ejecutarComando($fp, base64_encode($password), '235');

            // Remitente y Destinatario
            self::ejecutarComando($fp, "MAIL FROM:<{$fromEmail}>", '250');
            self::ejecutarComando($fp, "RCPT TO:<{$destinatario}>", '250');

            // Contenido DATA
            self::ejecutarComando($fp, "DATA", '354');

            $boundary = "==Multipart_Boundary_x" . md5((string) microtime()) . "x";
            $asuntoCodificado = "=?UTF-8?B?" . base64_encode($asunto) . "?=";
            $fromNameCodificado = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
            $toNameCodificado   = "=?UTF-8?B?" . base64_encode($nombreDestinatario) . "?=";

            $headers  = "From: {$fromNameCodificado} <{$fromEmail}>\r\n";
            $headers .= "To: {$toNameCodificado} <{$destinatario}>\r\n";
            $headers .= "Subject: {$asuntoCodificado}\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "X-Mailer: PHP-SENA-Mailer\r\n\r\n";

            // Cuerpo del correo (Alternativo + HTML)
            $mensaje = $headers;
            $mensaje .= "--{$boundary}\r\n";
            $mensaje .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $mensaje .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $mensaje .= ($cuerpoTexto ?: strip_tags($cuerpoHtml)) . "\r\n\r\n";

            $mensaje .= "--{$boundary}\r\n";
            $mensaje .= "Content-Type: text/html; charset=UTF-8\r\n";
            $mensaje .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $mensaje .= $cuerpoHtml . "\r\n\r\n";

            $mensaje .= "--{$boundary}--\r\n";
            $mensaje .= ".\r\n";

            fputs($fp, $mensaje);
            self::leerRespuesta($fp, '250');

            // Cerrar sesión SMTP
            fputs($fp, "QUIT\r\n");
            fclose($fp);

            return ['success' => true];
        } catch (Throwable $e) {
            if (is_resource($fp)) {
                @fputs($fp, "QUIT\r\n");
                @fclose($fp);
            }
            error_log("Error SMTP al enviar correo: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envía el correo institucional de recuperación de contraseña con diseño oficial SENA.
     */
    public static function enviarRecuperacion(
        string $destinatario,
        string $nombreUsuario,
        string $token,
        string $codigo
    ): array {
        $baseUrl = rtrim((string) env('APP_URL', 'http://localhost/Sistema-Ingreso/Sistema-de-control-de-ingresos-de-aprendices'), '/');
        $enlaceRestablecer = $baseUrl . '/index.php?action=restablecer-password&token=' . urlencode($token);

        $asunto = "Recuperación de Contraseña — SENA CTA Cartago";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de Contraseña — SENA</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Encabezado Verde SENA -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #39A900 0%, #2e8b00 100%); padding: 28px 30px; text-align: center;">
                            <div style="color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                                SENA — CTA Cartago
                            </div>
                            <div style="color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 500;">
                                Sistema de Control de Ingresos y Asistencia
                            </div>
                        </td>
                    </tr>

                    <!-- Cuerpo del Mensaje -->
                    <tr>
                        <td style="padding: 32px 30px;">
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 14px;">
                                Solicitud de Restablecimiento de Contraseña
                            </h2>
                            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 18px;">
                                Hola <strong>{$nombreUsuario}</strong>,
                            </p>
                            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px;">
                                Hemos recibido una solicitud para cambiar la contraseña de tu cuenta asociada al correo <strong>{$destinatario}</strong>. Puedes restablecerla haciendo clic en el siguiente botón:
                            </p>

                            <!-- Botón de Acción Principal -->
                            <div style="text-align: center; margin: 28px 0;">
                                <a href="{$enlaceRestablecer}" target="_blank" style="display: inline-block; background-color: #39A900; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 28px; border-radius: 8px; box-shadow: 0 4px 12px rgba(57,169,0,0.3);">
                                    Restablecer mi Contraseña
                                </a>
                            </div>

                            <!-- O código de seguridad numérico -->
                            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 24px;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                                    Tu Código de Verificación
                                </div>
                                <div style="font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: 4px; font-family: monospace;">
                                    {$codigo}
                                </div>
                            </div>

                            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0 0 16px;">
                                ⏱️ <strong>Tiempo límite:</strong> Este enlace y código son válidos únicamente durante los próximos <strong>30 minutos</strong>.
                            </p>

                            <p style="font-size: 12px; line-height: 1.5; color: #94a3b8; margin: 0; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                                🛡️ Si no solicitaste este cambio, puedes ignorar este correo de forma segura. Tu contraseña actual no será modificada.
                            </p>
                        </td>
                    </tr>

                    <!-- Pie de Página Institucional -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
                            Servicio Nacional de Aprendizaje — SENA<br>
                            Centro de Tecnologías Agroindustriales • Regional Valle del Cauca • Cartago
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $textoPlano = "Hola {$nombreUsuario},\n\n"
                    . "Hemos recibido una solicitud para restablecer tu contraseña en el Sistema de Control de Ingresos SENA.\n\n"
                    . "Para restablecer tu contraseña, ingresa al siguiente enlace:\n"
                    . "{$enlaceRestablecer}\n\n"
                    . "O utiliza tu código de seguridad: {$codigo}\n\n"
                    . "Este enlace expira en 30 minutos.\n"
                    . "Si no hiciste esta solicitud, ignora este mensaje.";

        return self::enviar($destinatario, $nombreUsuario, $asunto, $html, $textoPlano);
    }

    /**
     * Ejecuta un comando SMTP y verifica el código de respuesta esperado.
     */
    private static function ejecutarComando($fp, string $comando, string $codigoEsperado): string {
        fputs($fp, $comando . "\r\n");
        return self::leerRespuesta($fp, $codigoEsperado);
    }

    /**
     * Lee la respuesta multilínea del servidor SMTP.
     */
    private static function leerRespuesta($fp, string $codigoEsperado): string {
        $respuesta = '';
        while ($linea = fgets($fp, 512)) {
            $respuesta .= $linea;
            if (strlen($linea) >= 4 && $linea[3] === ' ') {
                break;
            }
        }
        $codigoObtenido = substr($respuesta, 0, 3);
        if ($codigoObtenido !== $codigoEsperado) {
            throw new RuntimeException("Error SMTP [Esperado: {$codigoEsperado}, Recibido: {$codigoObtenido}]: {$respuesta}");
        }
        return $respuesta;
    }
}
