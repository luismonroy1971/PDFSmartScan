<?php
namespace App\Controllers;

use App\Models\User;
use Core\Session;
use Core\Request;
use Core\Response;
use function Core\view;

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function showLogin() {
        if (Session::get('user_id')) {
            Response::redirect('/dashboard');
        }
        
        return view('auth/login');
    }
    
    public function login(Request $request) {
        $data = $request->getBody();
        
        // Validación básica
        if (empty($data['email']) || empty($data['password'])) {
            Session::setFlash('error', 'Por favor completa todos los campos');
            return Response::redirect('/login');
        }
        
        // Verificar si el usuario existe
        $user = $this->userModel->findByEmail($data['email']);
        
        // Agregar registro para depuración
        error_log("Intento de login para email: " . $data['email'] . " - Usuario encontrado: " . ($user ? 'SÍ' : 'NO'));
        
        if (!$user) {
            Session::setFlash('error', 'El usuario no existe o las credenciales son incorrectas');
            return Response::redirect('/login');
        }
        
        // Verificar la contraseña 
        $passwordValid = password_verify($data['password'], $user['password']);
        
        // Agregar registro para depuración
        error_log("Verificación de contraseña para " . $data['email'] . " - Resultado: " . ($passwordValid ? 'VÁLIDO' : 'INVÁLIDO'));
        
        if (!$passwordValid) {
            Session::setFlash('error', 'El usuario no existe o las credenciales son incorrectas');
            return Response::redirect('/login');
        }
        
        // Crear sesión de usuario
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
        
        // Si recordar sesión está marcado, crear cookie
        if (isset($data['remember']) && $data['remember'] === '1') {
            $token = bin2hex(random_bytes(32));
            // Guardar token en la base de datos con fecha de expiración
            // y crear cookie con el token
        }
        
        Session::setFlash('success', 'Has iniciado sesión correctamente');
        return Response::redirect('/dashboard');
    }
    
    public function showRegister() {
        if (Session::get('user_id')) {
            Response::redirect('/dashboard');
        }
        
        return view('auth/register');
    }
    
    public function register(Request $request) {
        $data = $request->getBody();
        
        // Validación básica
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['password_confirm'])) {
            Session::setFlash('error', 'Por favor completa todos los campos');
            return Response::redirect('/register');
        }
        
        if ($data['password'] !== $data['password_confirm']) {
            Session::setFlash('error', 'Las contraseñas no coinciden');
            return Response::redirect('/register');
        }
        
        // Verificar si el email ya está registrado
        $existingUser = $this->userModel->findByEmail($data['email']);
        
        if ($existingUser) {
            Session::setFlash('error', 'El email ya está registrado');
            return Response::redirect('/register');
        }
        
        // Crear usuario
        $userId = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user' // Por defecto, rol de usuario estándar
        ]);
        
        if ($userId) {
            Session::setFlash('success', 'Registro exitoso, ahora puedes iniciar sesión');
            return Response::redirect('/login');
        } else {
            Session::setFlash('error', 'Error al registrar usuario');
            return Response::redirect('/register');
        }
    }
    
    public function showResetPassword() {
        return view('auth/reset-password');
    }
    
    public function sendResetLink(Request $request) {
        $data = $request->getBody();
        
        if (empty($data['email'])) {
            Session::setFlash('error', 'Por favor ingresa tu email');
            return Response::redirect('/reset-password');
        }
        
        $user = $this->userModel->findByEmail($data['email']);
        
        if ($user) {
            // Generar token de restablecimiento
            $token = bin2hex(random_bytes(32));
            
            // Guardar token en la base de datos con fecha de expiración (24 horas)
            $expiration = date('Y-m-d H:i:s', time() + 86400); // 24 horas desde ahora
            
            // Eliminar tokens anteriores de este usuario
            $this->userModel->deletePasswordResetTokens($user['email']);
            
            // Guardar el nuevo token
            $this->userModel->savePasswordResetToken($user['email'], $token, $expiration);
            
            // Enviar email con enlace de restablecimiento
            $resetUrl = $_ENV['APP_URL'] . '/reset-password/verify?token=' . $token . '&email=' . urlencode($user['email']);
            
            // Construir el mensaje de correo
            $subject = 'Restablecer contraseña - Sistema PDF a Excel';
            $message = "Hola {$user['name']},\n\n";
            $message .= "Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace o cópialo en tu navegador:\n\n";
            $message .= "{$resetUrl}\n\n";
            $message .= "Este enlace expirará en 24 horas.\n\n";
            $message .= "Si no solicitaste restablecer tu contraseña, puedes ignorar este mensaje.\n\n";
            $message .= "Saludos,\nEquipo Tema Litoclean";
            
            // Configurar cabeceras
            $headers = "From: no-reply@temalitoclean.pe\r\n";
            $headers .= "Reply-To: soporte@temalitoclean.pe\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Enviar el correo
            mail($user['email'], $subject, $message, $headers);
            
            // Registrar el intento de restablecimiento en logs
            error_log("Solicitud de restablecimiento de contraseña para {$user['email']} enviada. Token: {$token}");
        }
        
        // Siempre mostrar mensaje de éxito para evitar enumerar emails
        Session::setFlash('success', 'Si tu email está registrado, recibirás un enlace para restablecer tu contraseña');
        return Response::redirect('/login');
    }
    
    public function logout() {
        Session::destroy();
        return Response::redirect('/login');
    }

    /**
     * Muestra la vista para restablecer la contraseña con un token
     */
    public function verifyResetToken(Request $request) {
        $token = $request->getQueryParam('token');
        $email = $request->getQueryParam('email');
        
        if (empty($token) || empty($email)) {
            Session::setFlash('error', 'Enlace de restablecimiento no válido');
            return Response::redirect('/login');
        }
        
        // Verificar si el token es válido
        if (!$this->userModel->verifyPasswordResetToken($email, $token)) {
            Session::setFlash('error', 'El enlace de restablecimiento ha expirado o no es válido');
            return Response::redirect('/login');
        }
        
        // Mostrar formulario para nueva contraseña
        return view('auth/reset-password', [
            'token' => $token,
            'email' => $email
        ]);
    }

    /**
     * Actualiza la contraseña del usuario después de verificar el token
     */
    public function updatePassword(Request $request) {
        $data = $request->getBody();
        
        // Validar datos
        if (empty($data['token']) || empty($data['email']) || 
            empty($data['new_password']) || empty($data['new_password_confirm'])) {
            Session::setFlash('error', 'Por favor completa todos los campos');
            return Response::redirect('/reset-password');
        }
        
        if ($data['new_password'] !== $data['new_password_confirm']) {
            Session::setFlash('error', 'Las contraseñas no coinciden');
            return Response::redirect('/reset-password/verify?token=' . $data['token'] . '&email=' . urlencode($data['email']));
        }
        
        // Verificar token
        if (!$this->userModel->verifyPasswordResetToken($data['email'], $data['token'])) {
            Session::setFlash('error', 'El enlace de restablecimiento ha expirado o no es válido');
            return Response::redirect('/login');
        }
        
        // Obtener usuario
        $user = $this->userModel->findByEmail($data['email']);
        
        if (!$user) {
            Session::setFlash('error', 'Usuario no encontrado');
            return Response::redirect('/login');
        }
        
        // Actualizar contraseña
        $result = $this->userModel->updatePassword($user['id'], $data['new_password']);
        
        if ($result) {
            // Eliminar tokens usados
            $this->userModel->deletePasswordResetTokens($data['email']);
            
            Session::setFlash('success', 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.');
            return Response::redirect('/login');
        } else {
            Session::setFlash('error', 'Error al actualizar la contraseña. Por favor intenta nuevamente.');
            return Response::redirect('/reset-password/verify?token=' . $data['token'] . '&email=' . urlencode($data['email']));
        }
    }
}