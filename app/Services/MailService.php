<?php

namespace App\Services;

use App\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    protected $mailer;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        
        // Configurar el mailer
        $this->mailer->isSMTP();
        $this->mailer->Host = Config::get('mail.host');
        $this->mailer->SMTPAuth = Config::get('mail.smtp_auth', true);
        $this->mailer->Username = Config::get('mail.username');
        $this->mailer->Password = Config::get('mail.password');
        $this->mailer->SMTPSecure = Config::get('mail.encryption', 'tls');
        $this->mailer->Port = Config::get('mail.port', 587);
        $this->mailer->CharSet = 'UTF-8';
        
        // Configurar remitente por defecto
        $this->mailer->setFrom(Config::get('mail.from_address'), Config::get('mail.from_name'));
    }
    
    /**
     * Envía un correo electrónico
     * 
     * @param string|array $to Destinatario o array de destinatarios
     * @param string $subject Asunto del correo
     * @param string $body Cuerpo del correo
     * @param array $attachments Array de archivos adjuntos (opcional)
     * @param array $cc Array de destinatarios en copia (opcional)
     * @param array $bcc Array de destinatarios en copia oculta (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public function send($to, $subject, $body, $attachments = [], $cc = [], $bcc = [])
    {
        try {
            // Limpiar configuración previa
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearAttachments();
            
            // Configurar destinatarios
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name); // Solo email sin nombre
                    } else {
                        $this->mailer->addAddress($email, $name); // Email con nombre
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            // Configurar CC
            if (!empty($cc)) {
                foreach ($cc as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addCC($name);
                    } else {
                        $this->mailer->addCC($email, $name);
                    }
                }
            }
            
            // Configurar BCC
            if (!empty($bcc)) {
                foreach ($bcc as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addBCC($name);
                    } else {
                        $this->mailer->addBCC($email, $name);
                    }
                }
            }
            
            // Configurar asunto y cuerpo
            $this->mailer->Subject = $subject;
            
            // Determinar si el cuerpo es HTML
            if (preg_match('/<[^>]*>/', $body)) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $body;
                // Versión de texto plano como alternativa
                $this->mailer->AltBody = strip_tags($body);
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $body;
            }
            
            // Añadir archivos adjuntos
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $filename = isset($attachment['name']) ? $attachment['name'] : basename($attachment['path']);
                        $this->mailer->addAttachment($attachment['path'], $filename);
                    } else if (is_string($attachment) && file_exists($attachment)) {
                        $this->mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Enviar el correo
            return $this->mailer->send();
            
        } catch (Exception $e) {
            // Registrar el error
            error_log('Error al enviar correo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía un correo electrónico con plantilla
     * 
     * @param string|array $to Destinatario o array de destinatarios
     * @param string $subject Asunto del correo
     * @param string $template Nombre de la plantilla
     * @param array $data Datos para la plantilla
     * @param array $attachments Array de archivos adjuntos (opcional)
     * @param array $cc Array de destinatarios en copia (opcional)
     * @param array $bcc Array de destinatarios en copia oculta (opcional)
     * @return bool Éxito o fracaso del envío
     */
    public function sendTemplate($to, $subject, $template, $data = [], $attachments = [], $cc = [], $bcc = [])
    {
        // Cargar la plantilla
        $templatePath = APP_PATH . '/views/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            error_log('Plantilla de correo no encontrada: ' . $templatePath);
            return false;
        }
        
        // Extraer datos para la plantilla
        extract($data);
        
        // Capturar la salida de la plantilla
        ob_start();
        include $templatePath;
        $body = ob_get_clean();
        
        // Enviar el correo con la plantilla renderizada
        return $this->send($to, $subject, $body, $attachments, $cc, $bcc);
    }
}