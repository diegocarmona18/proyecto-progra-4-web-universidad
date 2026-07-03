<?php
session_start();

$error = "";

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Universidad</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
       <div class="card">
        <form action="consulta.php" method="post">
        <h1 class="sesion">INICIO DE SESIÓN</h1>
      
        <h2 class="sesiontext">Usuario</h2>
        <input type="text" name="usuario" class="usuario" required>
        <h2 class="sesiontext">Contraseña</h2>
        <input type="password" name="contrasena" class="contraseña" required>
        <button class="boton_iniciar" type="submit">Iniciar Sesion</button>
       
    
        <?php
        // muestra el error
        if ($error != "") { ?>
                <div>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>
        <a href="registro.php" class="enla_registrarse">Registrarse</a>
    </form>
    </div>
</body>

</html>