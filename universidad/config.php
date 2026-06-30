<?php
// config.php - Conexion reutilizable con PDO.
// Ajuste usuario, clave y base de datos segun su ambiente de Laragon/XAMPP.
$host = '127.0.0.1';
$dbname = 'universidad';
$user = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die('Error de conexion: ' . htmlspecialchars($e->getMessage()));
}
?>