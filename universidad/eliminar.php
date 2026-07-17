<?php
require_once 'config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usu_codigo = trim($_POST['usu_codigo'] ?? '');

    if (empty($usu_codigo)) {
        $mensaje = 'Ingrese el código del usuario a eliminar.';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM t_usuario WHERE usu_codigo = :usu_codigo');
            $stmt->execute([':usu_codigo' => $usu_codigo]);

            $mensaje = $stmt->rowCount() > 0 ? 'Usuario eliminado correctamente.' : 'No se encontró un usuario con ese código.';
        } catch (PDOException $e) {
            $mensaje = 'Error al eliminar el usuario: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5" style="max-width: 420px;">
        <h1 class="h3 mb-4">Eliminar usuario</h1>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <form action="eliminar.php" method="post">
            <label for="usu_codigo" class="form-label">Código de usuario:</label>
            <input type="text" name="usu_codigo" id="usu_codigo" class="form-control" required>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-danger">Eliminar</button>
                <a href="crud.php" class="btn btn-outline-dark">Volver</a>
            </div>
        </form>
    </div>
</body>
</html>