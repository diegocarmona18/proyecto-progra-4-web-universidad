<?php

require_once 'config.php';

$usu_codigo  = trim($_POST['usu_codigo'] ?? '');
$usu_nombre  = trim($_POST['usu_nombre'] ?? '');
$usu_correo  = filter_input(INPUT_POST,'usu_correo',FILTER_VALIDATE_EMAIL);
$usu_clave   = $_POST['usu_clave'] ?? '';
$id_rol      = intval($_POST['id_rol'] ?? 0);

if(
    empty($usu_codigo) ||
    empty($usu_nombre) ||
    !$usu_correo ||
    empty($usu_clave) ||
    $id_rol <= 0
)
{
    header("Location: registro.php?error=datos");
    exit();
}

$clave_hash = password_hash(
    $usu_clave,
    PASSWORD_DEFAULT
);

$sql = "
INSERT INTO t_usuario
(
    usu_codigo,
    usu_nombre,
    usu_correo,
    usu_clave,
    id_rol,
    usu_intentofallido,
    usu_estado,
    usu_fechahorareg,
    usu_usuarioreg
)
VALUES
(
    :usu_codigo,
    :usu_nombre,
    :usu_correo,
    :usu_clave,
    :id_rol,
    0,
    'A',
    NOW(),
    1
)
";

$stmt = $pdo->prepare($sql);

try
{
    $stmt->execute([

        ':usu_codigo' => $usu_codigo,
        ':usu_nombre' => $usu_nombre,
        ':usu_correo' => $usu_correo,
        ':usu_clave'  => $clave_hash,
        ':id_rol'     => $id_rol

    ]);

    header("Location: index.php");
    exit();

}
catch(PDOException $e)
{
    die($e->getMessage());
}
