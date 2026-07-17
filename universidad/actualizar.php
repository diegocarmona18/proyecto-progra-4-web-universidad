<?php
require_once 'config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usu_codigo = trim($_POST['usu_codigo'] ?? '');
    $usu_nombre = trim($_POST['usu_nombre'] ?? '');
    $usu_correo = filter_input(INPUT_POST, 'usu_correo', FILTER_VALIDATE_EMAIL);
    $id_rol = intval($_POST['id_rol'] ?? 0);
    $usu_estado = trim($_POST['usu_estado'] ?? 'A');

    if (empty($usu_codigo) || empty($usu_nombre) || !$usu_correo || $id_rol <= 0) {
        $mensaje = 'Complete los datos obligatorios para actualizar el usuario.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE t_usuario SET usu_nombre = :usu_nombre, usu_correo = :usu_correo, id_rol = :id_rol, usu_estado = :usu_estado WHERE usu_codigo = :usu_codigo');
            $stmt->execute([
                ':usu_nombre' => $usu_nombre,
                ':usu_correo' => $usu_correo,
                ':id_rol' => $id_rol,
                ':usu_estado' => $usu_estado,
                ':usu_codigo' => $usu_codigo,
            ]);

            $mensaje = $stmt->rowCount() > 0 ? 'Usuario actualizado correctamente.' : 'No se encontró un usuario con ese código.';
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 540px;">
        <h1 class="h3 mb-4">Actualizar usuario</h1>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <form action="actualizar.php" method="post">
            <div class="mb-3">
                <label for="usu_codigo" class="form-label">Código de usuario:</label>
                <input type="text" class="form-control" name="usu_codigo" id="usu_codigo" required>
            </div>

            <div class="mb-3">
                <label for="usu_nombre" class="form-label">Nombre completo:</label>
                <input type="text" class="form-control" name="usu_nombre" id="usu_nombre" required>
            </div>

            <div class="mb-3">
                <label for="usu_correo" class="form-label">Correo:</label>
                <input type="email" class="form-control" name="usu_correo" id="usu_correo" required>
            </div>

            <div class="mb-3">
                <label for="id_rol" class="form-label">Rol:</label>
                <select name="id_rol" id="id_rol" class="form-select">
                    <option value="1">Administrador</option>
                    <option value="2">Profesor</option>
                    <option value="3">Estudiante</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="usu_estado" class="form-label">Estado:</label>
                <select name="usu_estado" id="usu_estado" class="form-select">
                    <option value="A">Activo</option>
                    <option value="B">Bloqueado</option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="crud.php" class="btn btn-outline-dark">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>