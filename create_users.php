<?php
/**
 * Script para crear usuarios de prueba en la base de datos
 * 2 administradores y 3 usuarios normales
 */

// Configuración de la base de datos
$config = [
    'host'     => 'localhost',
    'dbname'   => 'pdf_extract',
    'username' => 'root',     // Cambia esto por tu usuario de MySQL
    'password' => '',         // Cambia esto por tu contraseña de MySQL
    'charset'  => 'utf8mb4'
];

try {
    // Conectar a la base de datos
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "Conexión establecida con éxito.\n";
    
    // Usuarios a crear
    $users = [
        // Administradores
        [
            'name'     => 'Admin Principal',
            'email'    => 'admin@temalitoclean.pe',
            'password' => 'admin123',
            'role'     => 'admin'
        ],
        [
            'name'     => 'Supervisor',
            'email'    => 'supervisor@temalitoclean.pe',
            'password' => 'super123',
            'role'     => 'admin'
        ],
        // Usuarios normales
        [
            'name'     => 'Usuario Normal',
            'email'    => 'usuario@temalitoclean.pe',
            'password' => 'user123',
            'role'     => 'user'
        ],
        [
            'name'     => 'Analista',
            'email'    => 'analista@temalitoclean.pe',
            'password' => 'analista123',
            'role'     => 'user'
        ],
        [
            'name'     => 'Técnico',
            'email'    => 'tecnico@temalitoclean.pe',
            'password' => 'tecnico123',
            'role'     => 'user'
        ]
    ];
    
    // Preparar la consulta SQL
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, created_at, updated_at)
        VALUES (:name, :email, :password, :role, NOW(), NOW())
    ");
    
    // Contar usuarios creados
    $adminsCreated = 0;
    $usersCreated = 0;
    
    // Insertar cada usuario
    foreach ($users as $user) {
        // Verificar si el usuario ya existe
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkStmt->execute(['email' => $user['email']]);
        
        if ($checkStmt->fetch()) {
            echo "Usuario con email {$user['email']} ya existe. Omitiendo...\n";
            continue;
        }
        
        // Cifrar la contraseña
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Insertar el usuario
        $stmt->execute([
            'name'     => $user['name'],
            'email'    => $user['email'],
            'password' => $hashedPassword,
            'role'     => $user['role']
        ]);
        
        if ($user['role'] === 'admin') {
            $adminsCreated++;
        } else {
            $usersCreated++;
        }
        
        echo "Usuario {$user['name']} ({$user['email']}) creado correctamente.\n";
    }
    
    echo "\nResumen de la operación:\n";
    echo "- Administradores creados: $adminsCreated\n";
    echo "- Usuarios normales creados: $usersCreated\n";
    echo "- Total de usuarios creados: " . ($adminsCreated + $usersCreated) . "\n";
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}