<?php

namespace App\Core;

class Database
{
    private static $instance = null;
    private $pdo;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $database = $_ENV['DB_DATABASE'] ?? 'pdf_extract';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$database;charset=$charset";
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \Exception('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtiene la instancia única de la base de datos (Singleton)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Ejecuta una consulta SQL con parámetros
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception('Error en la consulta: ' . $e->getMessage());
        }
    }
    
    /**
     * Inicia una transacción
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * Revierte una transacción
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Prepara una consulta SQL
     */
    public function prepare($sql)
    {
        try {
            return $this->pdo->prepare($sql);
        } catch (\PDOException $e) {
            throw new \Exception('Error al preparar la consulta: ' . $e->getMessage());
        }
    }
}