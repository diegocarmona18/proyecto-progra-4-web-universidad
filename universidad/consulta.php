<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bitacoras/bitacora_login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('index.php'));
    exit;
}

$usuarioInput = trim($_POST['usuario'] ?? '');
$contrasenaInput = trim($_POST['contrasena'] ?? '');

if ($usuarioInput === '' || $contrasenaInput === '') {
    $_SESSION['error'] = 'Debe ingresar usuario y contraseña.';
    header('Location: ' . url('index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar el usuario y obtener la información de su rol
|--------------------------------------------------------------------------
*/
try {
    $stmt = $pdo->prepare(
        "SELECT
            u.id_usuario,
            u.usu_codigo,
            u.usu_nombre,
            u.usu_clave,
            u.usu_estado,
            u.usu_intentofallido,
            u.id_rol,
            r.rol_nombre,
            r.rol_dashboard,
            r.rol_estado
            FROM t_usuario u
            INNER JOIN t_rol r
            ON r.id_rol = u.id_rol
            WHERE u.usu_codigo = :usuario
            LIMIT 1"
    );
    $stmt->execute([
        ':usuario' => $usuarioInput
    ]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    $errorCode = $e->errorInfo[0] ?? null;
    $driverError = $e->errorInfo[1] ?? null;

    if ($errorCode === '42S22' || $driverError === 1054) {
        $stmt = $pdo->prepare(
            "SELECT
                u.id_usuario,
                u.usu_codigo,
                u.usu_nombre,
                u.usu_clave,
                u.usu_estado,
                u.usu_intentofallido,
                u.id_rol,
                r.rol_nombre,
                r.rol_estado
                FROM t_usuario u
                INNER JOIN t_rol r
                ON r.id_rol = u.id_rol
                WHERE u.usu_codigo = :usuario
                LIMIT 1"
        );
        $stmt->execute([
            ':usuario' => $usuarioInput
        ]);
        $usuario = $stmt->fetch();
        if ($usuario) {
            $usuario['rol_dashboard'] = '';
        }
    } else {
        throw $e;
    }
}

if (!$usuario) {
    $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
    header('Location: ' . url('index.php'));
    exit;
}

if ($usuario['usu_estado'] === 'B') {
    $_SESSION['error'] = 'Usuario bloqueado. Comuníquese con el administrador.';
    registrarSesion($pdo, $usuario['id_usuario'], $usuario['usu_correo'], 'login_bloqueado', $usuario['usu_intentofallido']);
    header('Location: ' . url('index.php'));
    exit;
}

if ($usuario['usu_estado'] !== 'A') {
    $_SESSION['error'] = 'El usuario se encuentra inactivo.';
    header('Location: ' . url('index.php'));
    exit;
}

if ($usuario['rol_estado'] !== 'A') {
    $_SESSION['error'] = 'El rol asignado al usuario se encuentra inactivo.';
    header('Location: ' . url('index.php'));
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar la contraseña y controlar los intentos fallidos
|--------------------------------------------------------------------------
*/
if (!password_verify($contrasenaInput, $usuario['usu_clave'])) {
    $intentos = ((int)$usuario['usu_intentofallido']) + 1;
    if ($intentos >= 3) {
        $stmt = $pdo->prepare(
            "UPDATE t_usuario
            SET usu_intentofallido = 3,
                    usu_estado = 'B'
            WHERE id_usuario = :id_usuario"
        );
        $stmt->execute([
            ':id_usuario' => $usuario['id_usuario']
        ]);

        $_SESSION['error'] = 'Usuario bloqueado por exceder los intentos permitidos.';
        registrarSesion($pdo, $usuario['id_usuario'], $usuario['usu_correo'], 'login_bloqueado', $intentos);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE t_usuario
            SET usu_intentofallido = :intentos
            WHERE id_usuario = :id_usuario"
        );
        $stmt->execute([
            ':intentos' => $intentos,
            ':id_usuario' => $usuario['id_usuario']
        ]);

        $_SESSION['error'] = "Usuario o contraseña incorrectos. Intento ($intentos/3).";
    }

    header('Location: ' . url('index.php'));
    exit;
}

/* Reiniciar los intentos fallidos después de un ingreso correcto. */
$stmt = $pdo->prepare(
    "UPDATE t_usuario
    SET usu_intentofallido = 0
    WHERE id_usuario = :id_usuario"
);
$stmt->execute([
    ':id_usuario' => $usuario['id_usuario']
]);

/*
|--------------------------------------------------------------------------
| Guardar los datos del usuario en la sesión
|--------------------------------------------------------------------------
*/
session_regenerate_id(true);

$_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
$_SESSION['usu_codigo'] = $usuario['usu_codigo'];
$_SESSION['usu_nombre'] = $usuario['usu_nombre'];
$_SESSION['usu_correo'] = $usuario['usu_correo'];
$_SESSION['id_rol'] = (int)$usuario['id_rol'];
$_SESSION['rol_nombre'] = $usuario['rol_nombre'];
$_SESSION['rol_dashboard'] = trim((string)($usuario['rol_dashboard'] ?? ''));

/*
|--------------------------------------------------------------------------
| Abrir el dashboard configurado para el rol
|--------------------------------------------------------------------------
| Ejemplos de valores válidos en rol_dashboard:
| inicio.php
| administracion/dashboard.php
*/
$dashboard = trim((string)($usuario['rol_dashboard'] ?? ''));

registrarBitacoraLogin('login_exitoso', ['usuario' => $usuarioInput, 'rol' => $usuario['rol_nombre'] ?? null, 'dashboard' => $dashboard]);

if ($dashboard === '') {
    $dashboard = 'inicio.php';
}

/* Evitar que se guarden direcciones externas o rutas hacia atrás. */
if (strpos($dashboard, 'http://') === 0 ||
    strpos($dashboard, 'https://') === 0 ||
    strpos($dashboard, '..') !== false) {
    $dashboard = 'inicio.php';
}

header('Location: ' . url($dashboard));
exit;
