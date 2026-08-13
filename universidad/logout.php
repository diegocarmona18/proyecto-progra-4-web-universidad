<?php
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/bitacoras/bitacora_login.php';

$usuarioId = $_SESSION['id_usuario'] ?? ($_SESSION['usuario_id'] ?? null);
$usuarioCorreo = $_SESSION['usu_correo'] ?? ($_SESSION['usuario_correo'] ?? null);
registrarSesion($pdo, $usuarioId, $usuarioCorreo, 'logout');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: ' . url('index.php'));
exit;
