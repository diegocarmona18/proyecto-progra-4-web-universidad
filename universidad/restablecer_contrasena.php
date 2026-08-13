<?php
require_once __DIR__ . '/config.php';

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
    } elseif (strlen($usu_clave) < 8) {
        $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM t_usuario WHERE usu_codigo = :usu_codigo AND usu_correo = :usu_correo LIMIT 1");
        $stmt->execute([
            ':usu_codigo' => $usu_codigo,
            ':usu_correo' => $usu_correo,
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['error'] = 'Usuario o correo no encontrado.';
        } else {
            try {
                $clave_hash = password_hash($usu_clave, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE t_usuario SET usu_clave = :usu_clave WHERE id_usuario = :id_usuario');
                $stmt->execute([
                    ':usu_clave' => $clave_hash,
                    ':id_usuario' => $usuario['id_usuario'],
                ]);

                $_SESSION['success'] = 'Contraseña actualizada con éxito. Ahora puede iniciar sesión con su nueva contraseña.';
                $usu_codigo = '';
                $usu_correo = '';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'No se pudo actualizar la contraseña. Inténtelo nuevamente.';
            }
        }
    }

    $_SESSION['usu_codigo'] = $usu_codigo;
    $_SESSION['usu_correo'] = $usu_correo;
    header('Location: ' . url('restablecer_contrasena.php'));
    exit();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <title>Nueva contraseña | <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('assets/img/iconotab.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="<?= htmlspecialchars(url('assets/css/styles.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-brand-panel">
        <div class="auth-brand-content">
            <span class="auth-tag"><i class="bi bi-mortarboard-fill"></i> Plataforma Académica</span>
            <h1>Configura tu nueva contraseña</h1>
            <p>Completa los datos para acceder nuevamente de forma segura.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-shield-check"></i> Seguridad reforzada</span>
                <span><i class="bi bi-lock"></i> Contraseña nueva</span>
                <span><i class="bi bi-person-check"></i> Acceso restaurado</span>
            </div>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="login-card recovery-card">
            <img src="<?= htmlspecialchars(url('assets/img/logo.png'), ENT_QUOTES, 'UTF-8') ?>" class="login-logo" alt="Logo de la universidad">
            <h2>RESTABLECER CONTRASEÑA</h2>
            <p class="login-subtitle">Escribe tu nueva clave para continuar</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars(url('restablecer_contrasena.php'), ENT_QUOTES, 'UTF-8') ?>" method="post" novalidate>
                <div class="mb-3">
                    <label for="usu_codigo" class="form-label">Código de usuario</label>
                    <div class="input-icon">
                        <i class="bi bi-person-vcard"></i>
                        <input type="text" name="usu_codigo" id="usu_codigo" class="form-control" value="<?= htmlspecialchars($usu_codigo) ?>" placeholder="Ej. admim" required>
                    </div>
                    <div class="invalid-feedback">El código es obligatorio.</div>
                </div>

                <div class="mb-3">
                    <label for="usu_correo" class="form-label">Correo electrónico</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="usu_correo" id="usu_correo" class="form-control" value="<?= htmlspecialchars($usu_correo) ?>" placeholder="correo@dominio.com" required>
                    </div>
                    <div class="invalid-feedback">El correo es obligatorio.</div>
                </div>

                <div class="mb-3">
                    <label for="usu_clave" class="form-label">Nueva contraseña</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="usu_clave" id="usu_clave" class="form-control" minlength="8" placeholder="Mínimo 8 caracteres" required>
                    </div>
                    <div class="invalid-feedback">La contraseña es obligatoria.</div>
                </div>

                <div class="mb-4">
                    <label for="confirmar_clave" class="form-label">Confirmar contraseña</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="confirmar_clave" id="confirmar_clave" class="form-control" minlength="8" placeholder="Repita la contraseña" required>
                    </div>
                    <div class="invalid-feedback">Debe confirmar la contraseña.</div>
                </div>

                <div class="d-grid mt-2">
                    <button class="btn login-btn w-100" type="submit">Actualizar contraseña</button>
                </div>

                <div class="text-center mt-3">
                    <a class="forgot-link" href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
document.querySelector('form').addEventListener('submit', function (event) {
    const campos = ['usu_codigo', 'usu_correo', 'usu_clave', 'confirmar_clave'];
    let valido = true;

    campos.forEach(function (campoId) {
        const input = document.getElementById(campoId);
        const vacio = input.value.trim() === '';
        input.classList.toggle('is-invalid', vacio);
        if (vacio) {
            valido = false;
        }
    });

    if (!valido) {
        event.preventDefault();
    }
});
</script>
</body>
</html>
