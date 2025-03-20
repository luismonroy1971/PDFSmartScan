<?php

namespace App\Core;

class Mailer
{
    /**
     * Envía un correo electrónico simple
     * 
     * @param string $to Dirección de correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $message Contenido del correo
     * @param array $headers Cabeceras adicionales (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public static function send($to, $subject, $message, $headers = [])
    {
        // Configuración básica de cabeceras
        $defaultHeaders = [
            'From' => Config::get('mail.from_address', 'noreply@pdfsmarscan.com'),
            'Reply-To' => Config::get('mail.reply_to', 'noreply@pdfsmarscan.com'),
            'X-Mailer' => 'PHP/' . phpversion()
        ];
        
        // Combinar cabeceras predeterminadas con las proporcionadas
        $headers = array_merge($defaultHeaders, $headers);
        
        // Formatear cabeceras para mail()
        $headerString = '';
        foreach ($headers as $name => $value) {
            $headerString .= "$name: $value\r\n";
        }
        
        // Intentar enviar el correo
        try {
            // Si está disponible PHPMailer y configurado, usarlo
            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && 
                Config::get('mail.use_smtp', false)) {
                return self::sendWithPHPMailer($to, $subject, $message);
            }
            
            // De lo contrario, usar la función mail() nativa
            return mail($to, $subject, $message, $headerString);
        } catch (\Exception $e) {
            // Registrar el error
            error_log('Error al enviar correo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía un correo usando PHPMailer si está disponible
     * 
     * @param string $to Dirección de correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $message Contenido del correo
     * @return bool Éxito o fracaso del envío
     */
    private static function sendWithPHPMailer($to, $subject, $message)
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurar SMTP
            $mail->isSMTP();
            $mail->Host = Config::get('mail.host', 'localhost');
            $mail->SMTPAuth = Config::get('mail.smtp_auth', true);
            $mail->Username = Config::get('mail.username', '');
            $mail->Password = Config::get('mail.password', '');
            $mail->SMTPSecure = Config::get('mail.encryption', 'tls');
            $mail->Port = Config::get('mail.port', 587);
            $mail->CharSet = 'UTF-8';
            
            // Remitente
            $mail->setFrom(
                Config::get('mail.from_address', 'noreply@pdfsmarscan.com'),
                Config::get('mail.from_name', 'PDF Smart Scan')
            );
            
            // Destinatario
            $mail->addAddress($to);
            
            // Contenido
            $mail->Subject = $subject;
            
            // Determinar si el mensaje es HTML
            if (preg_match('/<[^>]*>/', $message)) {
                $mail->isHTML(true);
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);
            } else {
                $mail->isHTML(false);
                $mail->Body = $message;
            }
            
            return $mail->send();
        } catch (\Exception $e) {
            error_log('Error al enviar correo con PHPMailer: ' . $e->getMessage());
            return false;
        }
    }
}