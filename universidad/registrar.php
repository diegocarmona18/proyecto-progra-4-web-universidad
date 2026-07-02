<?php
require_once 'config.php';

// Tomar y validar entradas
$cedula = filter_input(INPUT_POST, 'cedula', FILTER_SANITIZE_NUMBER_INT);
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

if (empty($cedula) || $usuario === '' || $correo === false || $contrasena === '') {
	header('Location: registro.php?error=missing');
	exit;
}

// Hashear la contraseña antes de guardar
$hashed = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = 'INSERT INTO inicio_sesion (cedula, usuario, correo, contrasena) VALUES (:cedula, :usuario, :correo, :contrasena)';
$stmt = $pdo->prepare($sql);

try {
	$stmt->execute([
		'cedula' => $cedula,
		'usuario' => $usuario,
		'correo' => $correo,
		'contrasena' => $hashed,
	]);
	header('Location: index.php');
	exit;
} catch (PDOException $e) {
	header('Location: registro.php?error=' . urlencode($e->getMessage()));
	exit;
}
?>