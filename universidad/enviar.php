
<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Solo procesar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    // Recibir y limpiar los datos del formulario
    $destinatario = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $asunto       = htmlspecialchars($_POST['asunto'] ?? '');
    $mensaje      = htmlspecialchars($_POST['mensaje'] ?? '');

    // Validar que el correo sea válido
    if (!$destinatario) {
        die('❌ El correo ingresado no es válido');
    }

    $mail = new PHPMailer(true);

    try {
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
        $mail->Body    = '<h1>Cambio de contraseña de la Universidad Central</h1><p>Estimado usuario, se ha solicitado un cambio de contraseña para su cuenta.</p><p>Por favor, haga clic en el siguiente enlace para restablecer su contraseña:</p><p><a href="http://localhost/u/universidad/nueva_contraseña.php">Restablecer contraseña</a></p>';
        $mail->AltBody = '<h1>Cambio de contraseña de la Universidad Central</h1><p>Estimado usuario, se ha solicitado un cambio de contraseña para su cuenta.</p><p>Por favor, haga clic en el siguiente enlace para restablecer su contraseña:</p><p><a href="http://localhost/u/universidad/nueva_contraseña.php">Restablecer contraseña</a></p>';

        $mail->send();
        echo '✅ Correo enviado con éxito a ' . htmlspecialchars($destinatario);
    } catch (Exception $e) {
        echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
    }
} else {
    echo 'Este script debe recibir datos por POST desde el formulario.';
}