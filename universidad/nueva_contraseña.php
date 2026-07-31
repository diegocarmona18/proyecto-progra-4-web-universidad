<?php
require_once("config.php");
session_start();

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$usu_codigo = $_SESSION['usu_codigo'] ?? '';
$usu_correo = $_SESSION['usu_correo'] ?? '';

unset($_SESSION['error'], $_SESSION['success'], $_SESSION['usu_codigo'], $_SESSION['usu_correo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usu_codigo = trim($_POST['usu_codigo'] ?? '');
    $usu_correo = filter_input(INPUT_POST, 'usu_correo', FILTER_VALIDATE_EMAIL);
    $usu_clave = trim($_POST['usu_clave'] ?? '');
    $confirmar_clave = trim($_POST['confirmar_clave'] ?? '');

    if (empty($usu_codigo) || !$usu_correo || empty($usu_clave) || empty($confirmar_clave)) {
        $_SESSION['error'] = 'Todos los campos son obligatorios.';
    } elseif ($usu_clave !== $confirmar_clave) {
        $_SESSION['error'] = 'Las contraseñas no coinciden.';
    } elseif (strlen($usu_clave) < 6) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM t_usuario WHERE usu_codigo = :usu_codigo AND usu_correo = :usu_correo LIMIT 1");
        $stmt->execute([
            ':usu_codigo' => $usu_codigo,
            ':usu_correo' => $usu_correo,
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado.';
        } else {
            try {
                $clave_hash = password_hash($usu_clave, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE t_usuario SET usu_clave = :usu_clave WHERE id_usuario = :id_usuario');
                $stmt->execute([
                    ':usu_clave' => $clave_hash,
                    ':id_usuario' => $usuario['id_usuario'],
                ]);

                $_SESSION['success'] = 'Contraseña actualizada con éxito. Ahora puedes iniciar sesión con tu nueva contraseña.';
                $usu_codigo = '';
                $usu_correo = '';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error de base de datos: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    $_SESSION['usu_codigo'] = $usu_codigo;
    $_SESSION['usu_correo'] = $usu_correo;
    header('Location: nueva_contraseña.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card shadow-sm w-100" style="max-width: 480px;">
            <div class="card-body p-4">
                <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid mb-3 d-block mx-auto" style="max-width: 180px;">
                <h1 class="h3 text-center mb-4">Cambiar Contraseña</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form action="nueva_contraseña.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Código de Usuario</label>
                        <input type="text" name="usu_codigo" class="form-control" value="<?php echo htmlspecialchars($usu_codigo); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="usu_correo" class="form-control" value="<?php echo htmlspecialchars($usu_correo); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="usu_clave" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="confirmar_clave" class="form-control" required>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <button class="btn btn-dark" type="submit">Actualizar contraseña</button>
                        <a class="btn btn-outline-dark" href="index.php">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
