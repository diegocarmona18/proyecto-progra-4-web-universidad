<?php
require_once __DIR__ . '/config.php';

$error = $_SESSION['error'] ?? '';
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['error'], $_SESSION['mensaje']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <title>Inicio de sesión | <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('assets/img/iconotab.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="<?= htmlspecialchars(url('assets/css/styles.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-brand-panel">
        <div class="auth-brand-content">
            <span class="auth-tag"><i class="bi bi-mortarboard-fill"></i> Plataforma Académica</span>
            <h1>Educación que impulsa tu futuro</h1>
            <p>Accede de forma segura a los servicios académicos de <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?>.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-shield-check"></i> Acceso seguro</span>
                <span><i class="bi bi-journal-check"></i> Gestión académica</span>
                <span><i class="bi bi-people"></i> Perfiles por rol</span>
            </div>
        </div>
    </section>
    <section class="auth-form-panel">
        <div class="login-card">
            <img src="<?= htmlspecialchars(url('assets/img/logo.png'), ENT_QUOTES, 'UTF-8') ?>" class="login-logo" alt="Logo de la universidad">
            <h2>INICIO DE SESIÓN</h2>
            <p class="login-subtitle">Ingrese sus credenciales institucionales</p>
            <?php if ($error !== ''): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($mensaje !== ''): ?><div class="alert alert-success py-2"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
            <form id="loginForm" action="<?= htmlspecialchars(url('consulta.php'), ENT_QUOTES, 'UTF-8') ?>" method="post" novalidate>
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-icon"><i class="bi bi-person"></i><input type="text" name="usuario" id="usuario" class="form-control" autocomplete="username" required></div>
                    <div class="invalid-feedback">El usuario es obligatorio.</div>
                </div>
                <div class="mb-2">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <div class="input-icon"><i class="bi bi-lock"></i><input type="password" name="contrasena" id="contrasena" class="form-control" autocomplete="current-password" required></div>
                    <div class="invalid-feedback">La contraseña es obligatoria.</div>
                </div>
                <div class="text-end mb-3"><a class="forgot-link" href="<?= htmlspecialchars(url('olvide_contrasena.php'), ENT_QUOTES, 'UTF-8') ?>">¿Olvidó su contraseña?</a></div>
                <button class="btn login-btn w-100" type="submit">Iniciar sesión</button>
            </form>
        </div>
    </section>
</main>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    let valido = true;
    ['usuario','contrasena'].forEach(function(id) {
        const input = document.getElementById(id);
        const vacio = input.value.trim() === '';
        input.classList.toggle('is-invalid', vacio);
        if (vacio) valido = false;
    });
    if (!valido) e.preventDefault();
});
</script>
</body>
</html>
