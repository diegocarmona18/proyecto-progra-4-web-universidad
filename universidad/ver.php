<?php
require_once 'config.php';

$codigo = trim($_GET['codigo'] ?? '');
$usuarios = [];
$error = '';

function rolTexto(int $idRol): string
{
    return match ($idRol) {
        1 => 'Administrador',
        2 => 'Profesor',
        3 => 'Estudiante',
        default => 'Sin rol',
    };
}

try {
    if ($codigo !== '') {
        $stmt = $pdo->prepare('SELECT id_usuario, usu_codigo, usu_nombre, usu_correo, id_rol, usu_estado, usu_fechahorareg FROM t_usuario WHERE usu_codigo = :codigo');
        $stmt->execute([':codigo' => $codigo]);
    } else {
        $stmt = $pdo->query('SELECT id_usuario, usu_codigo, usu_nombre, usu_correo, id_rol, usu_estado, usu_fechahorareg FROM t_usuario ORDER BY usu_fechahorareg DESC');
    }

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error en la consulta: ' . $e->getMessage();
}

if (!$error && count($usuarios) === 0) {
    $error = $codigo !== '' ? 'No se encontró ningún usuario con ese código.' : 'No hay usuarios registrados.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Ver usuarios</h1>
            <a class="btn btn-outline-dark" href="crud.php">Volver</a>
        </div>

        <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-md-6">
                <label for="codigo" class="form-label">Código de usuario</label>
                <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($codigo); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark">Buscar</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($usuarios) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha de registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['usu_codigo']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usu_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usu_correo']); ?></td>
                                <td><?php echo htmlspecialchars(rolTexto((int) $usuario['id_rol'])); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usu_estado']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['usu_fechahorareg']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>