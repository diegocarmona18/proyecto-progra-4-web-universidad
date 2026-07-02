<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTRO DE USUARIO</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="card">
        <form action="registrar.php" method="post">
            <h1 class="sesion">REGISTRO DE USUARIO</h1>
            <h2 class="sesiontext">Numero de cedúla</h2>
            <input type="text" name="cedula" class="cedula" placeholder="Cedula" required>
            <h2 class="sesiontext">Usuario</h2>
            <input type="text" name="usuario" class="usuario" placeholder="Usuario" required>
            <h2 class="sesiontext">Correo electronico</h2>
            <input type="email" name="correo" class="correo" placeholder="Correo" required>
            <h2 class="sesiontext">Contraseña</h2>
            <input type="password" name="contrasena" class="contraseña" placeholder="Contraseña" required>
            <div class="form-actions">
                <button class="boton_registrarse" type="submit">Registrarse</button>
                <a class="boton_volver" href="index.php">Volver</a>
            </div>
        </form>
    </div>
</body>

</html>