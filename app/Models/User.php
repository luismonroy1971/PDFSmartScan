<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $remember_token;
    public $created_at;
    public $updated_at;
    
    /**
     * Guarda un usuario nuevo o actualiza uno existente
     */
    public function save()
    {
        $db = Database::getInstance();
        
        // Preparar datos
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role ?? 'user',
            'remember_token' => $this->remember_token
        ];
        
        if (isset($this->id)) {
            // Actualizar usuario existente
            $data['id'] = $this->id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $sql = "UPDATE users SET 
                    name = :name,
                    email = :email,
                    password = :password,
                    role = :role,
                    remember_token = :remember_token,
                    updated_at = :updated_at
                    WHERE id = :id";
        } else {
            // Crear nuevo usuario
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = $data['created_at'];
            
            $sql = "INSERT INTO users 
                    (name, email, password, role, remember_token, created_at, updated_at) 
                    VALUES 
                    (:name, :email, :password, :role, :remember_token, :created_at, :updated_at)";
        }
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!isset($this->id) && $result) {
                $this->id = $db->lastInsertId();
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error al guardar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un usuario
     */
    public function delete()
    {
        if (!isset($this->id)) {
            return false;
        }
        
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            return $stmt->execute(['id' => $this->id]);
        } catch (\PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un usuario por su ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar usuario: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca un usuario por su email
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar usuario por email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si un email ya está registrado
     */
    public static function emailExists($email, $excludeId = null)
    {
        $db = Database::getInstance();
        
        try {
            if ($excludeId) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
                $stmt->execute(['email' => $email, 'id' => $excludeId]);
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
            }
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error al verificar existencia de email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza el token de recordar
     */
    public function updateRememberToken($token)
    {
        $this->remember_token = $token;
        
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("UPDATE users SET remember_token = :token, updated_at = :updated_at WHERE id = :id");
            return $stmt->execute([
                'token' => $token,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $this->id
            ]);
        } catch (\PDOException $e) {
            error_log("Error al actualizar token de recordar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca un usuario por su token de recordar
     */
    public static function findByRememberToken($token)
    {
        if (empty($token)) {
            return null;
        }
        
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = :token");
            $stmt->execute(['token' => $token]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return self::createFromArray($row);
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Error al buscar usuario por token: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si el usuario es administrador
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Crea una instancia de User a partir de un array
     */
    private static function createFromArray($data)
    {
        $user = new self();
        $user->id = $data['id'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->role = $data['role'];
        $user->remember_token = $data['remember_token'];
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        
        return $user;
    }
    
    /**
     * Obtiene todos los usuarios (para administradores)
     */
    public static function getAll()
    {
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("SELECT * FROM users ORDER BY name ASC");
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = self::createFromArray($row);
            }
            
            return $users;
        } catch (\PDOException $e) {
            error_log("Error al obtener todos los usuarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Genera un hash seguro para la contraseña
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verifica si una contraseña coincide con el hash almacenado
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }
}