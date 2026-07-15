<?php
require_once 'config.php';
session_start();
$usuario_input = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena_input = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
 $_SESSION['intentos'] = 0;;

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
        header('Location: inicio.php');
        exit;
    }
    else {
        $_SESSION['error'] = 'Usuario o contraseña incorrectos';
         $_SESSION['intentos']++;
        echo "Intentos:".$intentos;
        header('Location: index.php');  
    
}
        if ($_SESSION['intentos'] > 3) {
        $stmt = $pdo->prepare("UPDATE inicio_sesion SET estado = 'B' WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario_input]);
        $_SESSION['error'] = 'Usuario bloqueado por demasiados intentos fallidos';
        header('Location: index.php');
        ##exit($error);
    }
}
z