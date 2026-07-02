<?php
require_once 'config.php';
session_start();
$usuario_input = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena_input = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

if ($usuario_input === '' || $contrasena_input === '') {
    $error = 'Usuario o contraseña faltantes';
} else {
    $stmt = $pdo->prepare("SELECT * FROM inicio_sesion WHERE usuario = :usuario");
    $stmt->execute(['usuario' => $usuario_input]);
    $row = $stmt->fetch();

    if ($row && isset($row['contrasena']) && password_verify($contrasena_input, $row['contrasena'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $row['cedula'];
        $_SESSION['username'] = $row['usuario'];

        header('Location: registro.php');
        exit;
    } 
    else {
        $error = 'Usuario o contraseña incorrectos';
        exit($error);
    }
}

?>