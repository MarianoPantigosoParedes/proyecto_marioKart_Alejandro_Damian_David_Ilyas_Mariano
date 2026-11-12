<?php

// Parámetros de conexión: se pueden sobrescribir con variables de entorno
// (DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME); si no existen, valores de WAMP local.
$servername = getenv('DB_HOST') ?: 'localhost';
$puerto = getenv('DB_PORT') ?: '3306';
$dbusername = getenv('DB_USER') ?: 'root';
$dbpassword = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'gamingCenter';

try {
    $conexion = new PDO(
        "mysql:host=$servername;port=$puerto;dbname=$dbname;charset=utf8mb4",
        $dbusername,
        $dbpassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // El detalle técnico va al log del servidor, no se muestra al usuario.
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Comprueba la configuración del servidor.');
}
