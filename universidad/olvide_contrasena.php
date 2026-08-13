<?php
require_once __DIR__ . '/config.php';

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$email = $_SESSION['recuperar_email'] ?? '';
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['recuperar_email']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <title>Recuperar contraseña | <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('assets/img/iconotab.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="<?= htmlspecialchars(url('assets/css/styles.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body class="auth-page">
<main class="auth-layout">
    <section class="auth-brand-panel">
        <div class="auth-brand-content">
            <span class="auth-tag"><i class="bi bi-mortarboard-fill"></i> Plataforma Académica</span>
            <h1>Recupera tu acceso</h1>
            <p>Te ayudamos a restablecer tu contraseña para continuar con tus actividades académicas.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-shield-check"></i> Seguridad</span>
                <span><i class="bi bi-envelope-check"></i> Correo institucional</span>
                <span><i class="bi bi-arrow-clockwise"></i> Recuperación rápida</span>
            </div>
        </div>
    </section>

    <section class="auth-form-panel">
        <div class="login-card recovery-card">
            <img src="<?= htmlspecialchars(url('assets/img/logo.png'), ENT_QUOTES, 'UTF-8') ?>" class="login-logo" alt="Logo de la universidad">
            <h2>RECUPERAR CONTRASEÑA</h2>
            <p class="login-subtitle">Ingresa tu correo para recibir instrucciones</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars(url('enviar.php'), ENT_QUOTES, 'UTF-8') ?>" method="post" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="correo@dominio.com" required autofocus>
                    </div>
                    <div class="invalid-feedback">El correo es obligatorio.</div>
                </div>

                <div class="d-grid mt-3">
                    <button class="btn login-btn w-100" type="submit">Recuperar contraseña</button>
                </div>

                <div class="text-center mt-3">
                    <a class="forgot-link" href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio de sesión</a>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
document.querySelector('form').addEventListener('submit', function (event) {
    const emailInput = document.getElementById('email');
    const vacio = emailInput.value.trim() === '';
    emailInput.classList.toggle('is-invalid', vacio);

    if (vacio) {
        event.preventDefault();
    }
});
</script>
</body>
</html>
