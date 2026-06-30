<?php
require_once 'config.php';
$sql = 'INSERT INTO inicio_sesion(cedula, usuario, correo, contrasena) VALUES(:cedula, :usuario, :correo, :contrasena)';
$stmt = $pdo->prepare($sql);
$stmt->execute(['cedula' => $_POST['cedula'], 'usuario' => $_POST['usuario'], 'correo' => $_POST['correo'], 'contrasena' => $_POST['contrasena']]);
header('Location: index.php');
exit;
?>