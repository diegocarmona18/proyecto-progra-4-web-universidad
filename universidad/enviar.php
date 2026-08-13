<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('olvide_contrasena.php'));
    exit;
}

$destinatario = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$destinatario) {
    $_SESSION['error'] = 'El correo ingresado no es válido.';
    header('Location: ' . url('olvide_contrasena.php'));
    exit;
}

// Preparar enlace. En este flujo sencillo usamos la página de restablecimiento sin token.
$link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('restablecer_contrasena.php');

// Intentar enviar con PHPMailer si está disponible
try {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        throw new \Exception('PHPMailer no instalado');
    }
    require __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'universidadcentralestsup@gmail.com';
    $mail->Password   = 'msyktkzzeqjtsfll';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('universidadcentralestsup@gmail.com', 'Universidad Central');
    $mail->addAddress($destinatario);
    $mail->isHTML(true);
    $mail->Subject = 'Cambio de contraseña';
    $mail->Body    = '<h1>Restablecer contraseña</h1><p>Estimado usuario, haga clic en el siguiente enlace para cambiar su contraseña:</p><p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
    $mail->AltBody = 'Visite ' . $link . ' para restablecer su contraseña.';

    $mail->send();
    $_SESSION['success'] = 'Correo enviado con éxito. Revisa tu bandeja de entrada.';
} catch (Throwable $e) {
    // Si falla PHPMailer o no está instalado, dejamos un mensaje neutral y redirigimos.
    $_SESSION['success'] = 'Si el correo existe, recibirás instrucciones para restablecer la contraseña.';
}

$_SESSION['recuperar_email'] = $destinatario;
header('Location: ' . url('olvide_contrasena.php'));
exit;
