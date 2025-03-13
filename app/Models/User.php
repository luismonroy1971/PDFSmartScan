<?php
namespace App\Models;

use Core\Database;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch();
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        // Usar hash bcrypt para la contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        $sql = "INSERT INTO users (name, email, password, role, created_at) 
                VALUES (:name, :email, :password, :role, NOW())";
        
        $params = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $passwordHash,
            'role' => $data['role'] ?? 'user' // Por defecto, rol de usuario estándar
        ];
        
        $this->db->query($sql, $params);
        return $this->db->getLastInsertId();
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        return $this->db->query($sql, $params);
    }
    
    public function updatePassword($id, $password) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";
        return $this->db->query($sql, ['id' => $id, 'password' => $passwordHash]);
    }
    /**
     * Elimina todos los tokens de restablecimiento de contraseña para un email
     * 
     * @param string $email Email del usuario
     * @return bool Resultado de la operación
     */
    public function deletePasswordResetTokens($email) {
        $sql = "DELETE FROM password_resets WHERE email = :email";
        return $this->db->query($sql, ['email' => $email]);
    }

    /**
     * Guarda un token de restablecimiento de contraseña
     * 
     * @param string $email Email del usuario
     * @param string $token Token de restablecimiento
     * @param string $expiration Fecha de expiración (Y-m-d H:i:s)
     * @return bool Resultado de la operación
     */
    public function savePasswordResetToken($email, $token, $expiration) {
        $sql = "INSERT INTO password_resets (email, token, created_at, expires_at) 
                VALUES (:email, :token, NOW(), :expires_at)";
        
        return $this->db->query($sql, [
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiration
        ]);
    }

    /**
     * Verifica si un token de restablecimiento es válido
     * 
     * @param string $email Email del usuario
     * @param string $token Token a verificar
     * @return bool True si el token es válido, false en caso contrario
     */
    public function verifyPasswordResetToken($email, $token) {
        $sql = "SELECT * FROM password_resets 
                WHERE email = :email AND token = :token AND expires_at > NOW() 
                LIMIT 1";
        
        $stmt = $this->db->query($sql, [
            'email' => $email,
            'token' => $token
        ]);
        
        return $stmt->rowCount() > 0;
    }
}