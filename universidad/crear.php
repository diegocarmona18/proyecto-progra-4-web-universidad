<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario</title>
</head>
<body>
       <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card shadow-sm w-100" style="max-width: 480px;">
            <div class="card-body p-4">
                <form action="registrar.php" method="post">
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid mb-3 d-block mx-auto" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">Creación de usuarios</h1>

                    <div class="mb-3">
                        <label for="cedula" class="form-label">Número de cédula</label>
                        <input type="text" name="cedula" id="cedula" class="form-control" placeholder="Cédula" required>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Usuario" required>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="correo" class="form-control" placeholder="Correo" required>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" id="contrasena" class="form-control" placeholder="Contraseña" required>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <button class="btn btn-dark" type="submit">crear</button>
                        <a class="btn btn-outline-dark" href="index.php">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>