<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Request;
use App\Models\User;

class UserController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Verificar si el usuario está autenticado para la mayoría de las acciones
        $publicActions = ['login', 'register', 'doLogin', 'doRegister', 'forgotPassword', 'resetPassword'];
        
        if (!in_array($this->getCurrentAction(), $publicActions) && !Session::get('user_id')) {
            Session::flash('error', 'Debe iniciar sesión para acceder a esta sección.');
            $this->redirect('/login');
        }
    }
    
    /**
     * Muestra el formulario de inicio de sesión
     */
    public function login()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Session::get('user_id')) {
            $this->redirect('/dashboard');
        }
        
        return $this->view('auth/login');
    }
    
    /**
     * Procesa el inicio de sesión
     */
    public function doLogin()
    {
        $request = new Request();
        
        $email = $request->post('email');
        $password = $request->post('password');
        $remember = $request->post('remember') ? true : false;
        
        // Validar datos
        if (empty($email) || empty($password)) {
            Session::flash('error', 'Por favor, complete todos los campos.');
            return $this->redirect('/login');
        }
        
        // Intentar autenticar al usuario
        $user = User::findByEmail($email);
        
        if (!$user || !password_verify($password, $user->password)) {
            Session::flash('error', 'Credenciales inválidas. Por favor, intente nuevamente.');
            return $this->redirect('/login');
        }
        
        // Iniciar sesión
        Session::set('user_id', $user->id);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_role', $user->role);
        
        // Si seleccionó "recordarme", generar token de recordatorio
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $user->remember_token = $token;
            $user->save();
            
            // Establecer cookie que dura 30 días
            setcookie('remember_token', $token, time() + (86400 * 30), '/');
        }
        
        Session::flash('success', '¡Bienvenido, ' . $user->name . '!');
        return $this->redirect('/dashboard');
    }
    
    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Eliminar token de recordatorio si existe
        if (Session::get('user_id')) {
            $user = User::find(Session::get('user_id'));
            if ($user) {
                $user->remember_token = null;
                $user->save();
            }
            
            // Eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destruir sesión
        Session::destroy();
        
        return $this->redirect('/login');
    }
    
    /**
     * Muestra el formulario de registro
     */
    public function register()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Session::get('user_id')) {
            $this->redirect('/dashboard');
        }
        
        return $this->view('auth/register');
    }
    
    /**
     * Procesa el registro de un nuevo usuario
     */
    public function doRegister()
    {
        $request = new Request();
        
        $name = $request->post('name');
        $email = $request->post('email');
        $password = $request->post('password');
        $passwordConfirm = $request->post('password_confirm');
        
        // Validar datos
        if (empty($name) || empty($email) || empty($password) || empty($passwordConfirm)) {
            Session::flash('error', 'Por favor, complete todos los campos.');
            return $this->redirect('/register');
        }
        
        if ($password !== $passwordConfirm) {
            Session::flash('error', 'Las contraseñas no coinciden.');
            return $this->redirect('/register');
        }
        
        // Verificar si el correo ya está registrado
        $existingUser = User::findByEmail($email);
        if ($existingUser) {
            Session::flash('error', 'El correo electrónico ya está registrado.');
            return $this->redirect('/register');
        }
        
        // Crear nuevo usuario
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->role = 'user'; // Por defecto, rol de usuario estándar
        
        if ($user->save()) {
            Session::flash('success', 'Registro exitoso. Ahora puede iniciar sesión.');
            return $this->redirect('/login');
        } else {
            Session::flash('error', 'Error al registrar el usuario. Por favor, intente nuevamente.');
            return $this->redirect('/register');
        }
    }
    
    /**
     * Muestra el formulario para recuperar contraseña
     */
    public function forgotPassword()
    {
        return $this->view('auth/forgot_password');
    }
    
    /**
     * Procesa la solicitud de recuperación de contraseña
     */
    public function doForgotPassword()
    {
        $request = new Request();
        $email = $request->post('email');
        
        if (empty($email)) {
            Session::flash('error', 'Por favor, ingrese su correo electrónico.');
            return $this->redirect('/forgot-password');
        }
        
        $user = User::findByEmail($email);
        
        // Siempre mostrar el mismo mensaje para evitar enumerar usuarios
        if (!$user) {
            Session::flash('success', 'Si su correo está registrado, recibirá instrucciones para restablecer su contraseña.');
            return $this->redirect('/login');
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        
        // Guardar token en la base de datos
        $db = \App\Core\Database::getInstance();
        $sql = "INSERT INTO password_resets (email, token, created_at) VALUES (:email, :token, :created_at)";
        $params = [
            ':email' => $email,
            ':token' => $token,
            ':created_at' => date('Y-m-d H:i:s')
        ];
        $db->execute($sql, $params);
        
        // Enviar correo con enlace para restablecer contraseña
        $resetLink = BASE_URL . '/reset-password/' . $token;
        $subject = 'Recuperación de contraseña - PDF Smart Scan';
        $message = "Hola,\n\nHa solicitado restablecer su contraseña. Haga clic en el siguiente enlace para continuar:\n\n{$resetLink}\n\nSi no solicitó este cambio, puede ignorar este correo.\n\nSaludos,\nEquipo PDF Smart Scan";
        
        // Enviar correo usando el servicio de correo
        $mailService = new \App\Services\MailService();
        $mailSent = $mailService->send($email, $subject, $message);
        
        if ($mailSent) {
            Session::flash('success', 'Se han enviado instrucciones a su correo electrónico para restablecer su contraseña.');
        } else {
            Session::flash('error', 'Error al enviar el correo. Por favor, intente nuevamente más tarde.');
        }
        
        return $this->redirect('/login');
    }
    
    /**
     * Muestra el formulario para restablecer contraseña
     */
    public function resetPassword($token)
    {
        // Verificar si el token es válido
        $db = \App\Core\Database::getInstance();
        $sql = "SELECT * FROM password_resets WHERE token = :token AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $params = [':token' => $token];
        $result = $db->query($sql, $params);
        
        if (!$result) {
            Session::flash('error', 'El enlace para restablecer la contraseña es inválido o ha expirado.');
            return $this->redirect('/login');
        }
        
        return $this->view('auth/reset_password', ['token' => $token]);
    }
    
    /**
     * Procesa el restablecimiento de contraseña
     */
    public function doResetPassword()
    {
        $request = new Request();
        
        $token = $request->post('token');
        $password = $request->post('password');
        $passwordConfirm = $request->post('password_confirm');
        
        // Validar datos
        if (empty($password) || empty($passwordConfirm)) {
            Session::flash('error', 'Por favor, complete todos los campos.');
            return $this->redirect('/reset-password/' . $token);
        }
        
        if ($password !== $passwordConfirm) {
            Session::flash('error', 'Las contraseñas no coinciden.');
            return $this->redirect('/reset-password/' . $token);
        }
        
        // Verificar si el token es válido
        $db = \App\Core\Database::getInstance();
        $sql = "SELECT * FROM password_resets WHERE token = :token AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $params = [':token' => $token];
        $result = $db->query($sql, $params);
        
        if (!$result) {
            Session::flash('error', 'El enlace para restablecer la contraseña es inválido o ha expirado.');
            return $this->redirect('/login');
        }
        
        // Actualizar contraseña del usuario
        $email = $result['email'];
        $user = User::findByEmail($email);
        
        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            return $this->redirect('/login');
        }
        
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($user->save()) {
            // Eliminar todos los tokens de restablecimiento para este usuario
            $sql = "DELETE FROM password_resets WHERE email = :email";
            $params = [':email' => $email];
            $db->execute($sql, $params);
            
            Session::flash('success', 'Contraseña restablecida correctamente. Ahora puede iniciar sesión.');
            return $this->redirect('/login');
        } else {
            Session::flash('error', 'Error al restablecer la contraseña. Por favor, intente nuevamente.');
            return $this->redirect('/reset-password/' . $token);
        }
    }
    
    /**
     * Muestra el perfil del usuario
     */
    public function profile()
    {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            return $this->redirect('/dashboard');
        }
        
        return $this->view('users/profile', ['user' => $user]);
    }
    
    /**
     * Actualiza el perfil del usuario
     */
    public function updateProfile()
    {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            Session::flash('error', 'Usuario no encontrado.');
            return $this->redirect('/dashboard');
        }
        
        $request = new Request();
        
        $name = $request->post('name');
        $currentPassword = $request->post('current_password');
        $newPassword = $request->post('new_password');
        $newPasswordConfirm = $request->post('new_password_confirm');
        
        // Validar datos
        if (empty($name)) {
            Session::flash('error', 'El nombre es obligatorio.');
            return $this->redirect('/profile');
        }
        
        // Actualizar nombre
        $user->name = $name;
        
        // Si se proporciona contraseña actual, actualizar contraseña
        if (!empty($currentPassword)) {
            // Verificar contraseña actual
            if (!password_verify($currentPassword, $user->password)) {
                Session::flash('error', 'La contraseña actual es incorrecta.');
                return $this->redirect('/profile');
            }
            
            // Validar nueva contraseña
            if (empty($newPassword) || empty($newPasswordConfirm)) {
                Session::flash('error', 'Por favor, complete todos los campos de contraseña.');
                return $this->redirect('/profile');
            }
            
            if ($newPassword !== $newPasswordConfirm) {
                Session::flash('error', 'Las nuevas contraseñas no coinciden.');
                return $this->redirect('/profile');
            }
            
            // Actualizar contraseña
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        if ($user->save()) {
            // Actualizar nombre en la sesión
            Session::set('user_name', $user->name);
            
            Session::flash('success', 'Perfil actualizado correctamente.');
        } else {
            Session::flash('error', 'Error al actualizar el perfil. Por favor, intente nuevamente.');
        }
        
        return $this->redirect('/profile');
    }
    
    /**
     * Obtiene la acción actual del controlador
     */
    private function getCurrentAction()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        return $action;
    }
}