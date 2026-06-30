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
<a href="index.php" class="volver">Volver</a>
<h1 class="sesion">REGISTRO DE USUARIO</h1>
<h2 class="sesiontext">Numero de cedúla</h2>
 <input type="text" name="cedula" class="cedula" placeholder="cedula" required>
 <h2 class="sesiontext">Usuario</h2>
 <input type="text" name="usuario" class="usuario" placeholder="usuario" required>
  <h2 class="sesiontext">Correo electronico</h2>
 <input type="email" name="correo" class="correo" placeholder="correo" required>
 <h2 class="sesiontext">Contraseña</h2>
 <input type="password" name="contrasena" class="contraseña" placeholder="contraseña" required>
 <button class="boton">Registrarse</button>
 </form>
    </div>
</body>
</html>