<?php

/* Conexion de sesion */
require_once 'config.php';
session_start();

/* Datos del Formulario */
$usuario_input = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$contrasena_input = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

/* Validacion que los Campos No esten Vacios*/
if ($usuario_input === '' || $contrasena_input === '') {

    $_SESSION['error'] = 'Usuario o contraseña faltantes';
    header('Location: index.php');
    exit;
}

/* Busqueda del Usuario en BD */
$stmt = $pdo->prepare("
    SELECT *
    FROM inicio_sesion
    WHERE usuario = :usuario
");

$stmt->execute([
    'usuario' => $usuario_input
]);

$row = $stmt->fetch();


/* Si usuario no Existe*/
if (!$row) {

    $_SESSION['error'] = 'Usuario o contraseña incorrectos';
    header('Location: index.php');
    exit;
}

/* Verificar si el usuario ya está bloqueado */
if (
    isset($row['estado']) &&
    $row['estado'] === 'B'
) {

    $_SESSION['error'] = 'Usuario bloqueado por demasiados intento fallidos';
    header('Location: index.php');
    exit;
}

/* Verificar contraseña */
if (
    isset($row['contrasena']) &&
    password_verify($contrasena_input, $row['contrasena'])
) {

     /*Reiniciar contador de intentos cuando digita bien la contraseña*/
    $stmt = $pdo->prepare("
        UPDATE inicio_sesion
        SET intentos = 0
        WHERE usuario = :usuario
    ");

    $stmt->execute([
        'usuario' => $usuario_input
    ]);

    session_regenerate_id(true);

    $_SESSION['user_id'] = $row['cedula'];
    $_SESSION['username'] = $row['usuario'];
    
    
    /* Redirige la pagina */
    header('Location: inicio.php');
    exit;
}

/* Incrementa cantidad de intentos*/
$intento = $row['intentos'] + 1;

if ($intento > 3) {
    $intento = 3;
}

/* Actualiza el contador*/
$stmt = $pdo->prepare("
    UPDATE inicio_sesion
    SET intentos = :intento
    WHERE usuario = :usuario
");

$stmt->execute([
    'intento' => $intento,
    'usuario' => $usuario_input
]);

/* Bloquedo de Usuario */
if ($intento > 3) {

    $stmt = $pdo->prepare("
        UPDATE inicio_sesion
        SET estado = 'B'
        WHERE usuario = :usuario
    ");

    $stmt->execute([
        'usuario' => $usuario_input
    ]);

    $_SESSION['error'] = 'Usuario bloqueado por demasiados intento fallidos';
    header('Location: index.php');
    exit;
}

$_SESSION['error'] = "Usuario o contraseña incorrectos. Queda $intento intentos de 3";
header('Location: index.php');
exit;
