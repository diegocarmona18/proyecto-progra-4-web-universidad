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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universidad</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="styles.css?v=2">
</head>

<body>
    <div class="container py-4">
        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <form action="consulta.php" method="post">
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">INICIO DE SESIÓN</h1>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" id="contrasena" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-dark" type="submit">Iniciar Sesión</button>
                    </div>

                    <?php if ($error != "") { ?>
                        <div class="alert alert-danger py-2" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php } ?>

                    <div class="text-center">
                        <a href="registro.php" class="link-primary">Registrarse</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>