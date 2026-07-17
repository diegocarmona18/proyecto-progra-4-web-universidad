<?php

require_once("config.php");
session_start();

$usuario_input = trim($_POST['usuario'] ?? '');
$contrasena_input = trim($_POST['contrasena'] ?? '');

if (empty($usuario_input) || empty($contrasena_input)) {
    $_SESSION['error'] = "Debe ingresar usuario y contraseña";
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Buscar usuario por usu_codigo
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM t_usuario
    WHERE usu_codigo = :usuario
");

$stmt->execute([
    ':usuario' => $usuario_input
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    $_SESSION['error'] = "Usuario no encontrado";
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Validar si está bloqueado
|--------------------------------------------------------------------------
*/

if ($usuario['usu_estado'] == 'B') {
    $_SESSION['error'] = "Usuario bloqueado";
    header("Location: index.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Verificar contraseña
|--------------------------------------------------------------------------
*/

if (password_verify($contrasena_input, $usuario['usu_clave'])) {
    /*
    | Reiniciar intentos
    */

    $stmt = $pdo->prepare("
        UPDATE t_usuario
        SET usu_intentofallido = 0
        WHERE id_usuario = :id
    ");

    $stmt->execute([
        ':id' => $usuario['id_usuario']
    ]);

    session_regenerate_id(true);

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['usu_codigo'] = $usuario['usu_codigo'];
    $_SESSION['usu_nombre'] = $usuario['usu_nombre'];
    $_SESSION['id_rol'] = $usuario['id_rol'];
    if ($usuario['id_rol'] == 1) {
        header("Location: crud.php");
        exit();
    } elseif ($usuario['id_rol'] == 2) {
        header("Location: inicio.php");
        exit();
    } elseif ($usuario['id_rol'] == 3) {
        header("Location: inicio.php");
        exit();
    } else {
        $intentos = $usuario['usu_intentofallido'] + 1;

        /*
        |--------------------------------------------------------------------------
        | Si llega a 3 intentos bloquea
        |--------------------------------------------------------------------------
        */

        if ($intentos >= 3) {
            $stmt = $pdo->prepare("
            UPDATE t_usuario
            SET
                usu_intentofallido = 3,
                usu_estado = 'B'
            WHERE id_usuario = :id
        ");

            $stmt->execute([
                ':id' => $usuario['id_usuario']
            ]);

            $_SESSION['error'] =
                "Usuario bloqueado por exceder los intentos permitidos";
        } else {
            $stmt = $pdo->prepare("
            UPDATE t_usuario
            SET usu_intentofallido = :intentos
            WHERE id_usuario = :id
        ");

            $stmt->execute([
                ':intentos' => $intentos,
                ':id' => $usuario['id_usuario']
            ]);

            $_SESSION['error'] =
                "Usuario o contraseña incorrectos. Intento ($intentos/3)";
        }

        header("Location: index.php");
        exit();
    }
}

