<?php

namespace App\Controllers;

use App\Models\User;
use App\Core\Session;
use App\Core\Validator;
use App\Core\Mailer;

class AuthController extends BaseController
{
    /**
     * Muestra la página de inicio de sesión
     */
    public function showLogin()
    {
        return $this->view('auth/login');
    }
    
    /**
     * Procesa el inicio de sesión
     */
    public function login()
    {
        // Validar datos de entrada
        $validator = new Validator($_POST);
        $validator->required(['email', 'password'])
                  ->email('email');
        
        if (!$validator->isValid()) {
            Session::flash('error', 'Por favor, complete todos los campos correctamente.');
            Session::flash('errors', $validator->getErrors());
            Session::flash('old', $_POST);
            return $this->redirect('/login');
        }
        
        // Intentar autenticar al usuario
        $user = User::findByEmail($_POST['email']);
        
        if (!$user || !password_verify($_POST['password'], $user->password)) {
            Session::flash('error', 'Credenciales incorrectas. Por favor, inténtelo de nuevo.');
            Session::flash('old', ['email' => $_POST['email']]);
            return $this->redirect('/login');
        }
        
        // Iniciar sesión
        Session::set('user_id', $user->id);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_role', $user->role);
        
        // Recordar usuario si se solicitó
        if (isset($_POST['remember']) && $_POST['remember'] == '1') {
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            $user->save();
            
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', false, true);
        }
        
        return $this->redirect('/dashboard');
    }
    
    /**
     * Muestra la página de registro
     */
    public function showRegister()
    {
        return $this->view('auth/register');
    }
    
    /**
     * Procesa el registro de un nuevo usuario
     */
    public function register()
    {
        // Validar datos de entrada
        $validator = new Validator($_POST);
        $validator->required(['name', 'email', 'password', 'password_confirmation'])
                  ->email('email')
                  ->min('password', 8)
                  ->match('password', 'password_confirmation');
        
        if (!$validator->isValid()) {
            Session::flash('error', 'Por favor, complete todos los campos correctamente.');
            Session::flash('errors', $validator->getErrors());
            Session::flash('old', $_POST);
            return $this->redirect('/register');
        }
        
        // Verificar si el email ya está registrado
        if (User::findByEmail($_POST['email'])) {
            Session::flash('error', 'El correo electrónico ya está registrado.');
            Session::flash('old', $_POST);
            return $this->redirect('/register');
        }
        
        // Crear nuevo usuario
        $user = new User();
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user->role = 'user'; // Por defecto, todos los usuarios nuevos son estándar
        $user->save();
        
        // Iniciar sesión automáticamente
        Session::set('user_id', $user->id);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_role', $user->role);
        
        Session::flash('success', '¡Registro exitoso! Bienvenido a PDF Smart Scan.');
        return $this->redirect('/dashboard');
    }
    
    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Eliminar cookie de "recordarme" si existe
        if (isset($_COOKIE['remember_token'])) {
            $user = User::find(Session::get('user_id'));
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
            
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Destruir sesión
        Session::destroy();
        
        return $this->redirect('/login');
    }
    
    /**
     * Muestra la página de recuperación de contraseña
     */
    public function showForgotPassword()
    {
        return $this->view('auth/forgot-password');
    }
    
    /**
     * Procesa la solicitud de recuperación de contraseña
     */
    public function forgotPassword()
    {
        // Validar email
        $validator = new Validator($_POST);
        $validator->required(['email'])
                  ->email('email');
        
        if (!$validator->isValid()) {
            Session::flash('error', 'Por favor, ingrese un correo electrónico válido.');
            Session::flash('old', $_POST);
            return $this->redirect('/forgot-password');
        }
        
        // Buscar usuario
        $user = User::findByEmail($_POST['email']);
        
        // Siempre mostrar mensaje de éxito para evitar enumerar usuarios
        if ($user) {
            // Generar token de recuperación
            $token = bin2hex(random_bytes(32));
            
            // Guardar token en la base de datos
            $this->db->query(
                "INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())",
                [$user->email, $token]
            );
            
            // Enviar correo con enlace de recuperación
            $resetUrl = $_SERVER['HTTP_HOST'] . "/reset-password/{$token}";
            $subject = "Recuperación de contraseña - PDF Smart Scan";
            $message = "Hola {$user->name},\n\n";
            $message .= "Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:\n\n";
            $message .= "http://{$resetUrl}\n\n";
            $message .= "Si no solicitaste este cambio, puedes ignorar este correo.\n\n";
            $message .= "Saludos,\nEquipo de PDF Smart Scan";
            
            Mailer::send($user->email, $subject, $message);
        }
        
        Session::flash('success', 'Si el correo existe en nuestra base de datos, recibirás instrucciones para restablecer tu contraseña.');
        return $this->redirect('/login');
    }
    
    /**
     * Muestra la página de restablecimiento de contraseña
     */
    public function showResetPassword($token)
    {
        // Verificar si el token es válido
        $reset = $this->db->query(
            "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$token]
        )->fetch();
        
        if (!$reset) {
            Session::flash('error', 'El enlace de recuperación es inválido o ha expirado.');
            return $this->redirect('/login');
        }
        
        return $this->view('auth/reset-password', ['token' => $token]);
    }
    
    /**
     * Procesa el restablecimiento de contraseña
     */
    public function resetPassword()
    {
        // Validar datos
        $validator = new Validator($_POST);
        $validator->required(['token', 'password', 'password_confirmation'])
                  ->min('password', 8)
                  ->match('password', 'password_confirmation');
        
        if (!$validator->isValid()) {
            Session::flash('error', 'Por favor, complete todos los campos correctamente.');
            Session::flash('errors', $validator->getErrors());
            return $this->redirect('/reset-password/' . $_POST['token']);
        }
        
        // Verificar token
        $reset = $this->db->query(
            "SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$_POST['token']]
        )->fetch();
        
        if (!$reset) {
            Session::flash('error', 'El enlace de recuperación es inválido o ha expirado.');
            return $this->redirect('/login');
        }
        
        // Actualizar contraseña
        $user = User::findByEmail($reset['email']);
        
        if ($user) {
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $user->save();
            
            // Eliminar token usado
            $this->db->query("DELETE FROM password_resets WHERE email = ?", [$reset['email']]);
            
            Session::flash('success', 'Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.');
        } else {
            Session::flash('error', 'No se pudo encontrar el usuario asociado a este token.');
        }
        
        return $this->redirect('/login');
    }
}