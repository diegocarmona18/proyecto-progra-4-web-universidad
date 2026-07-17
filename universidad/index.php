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
    <div class="d-flex">
        <nav>
            <nav class="navbar w-100">
                <div class="container-fluid">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                        <span class="navbar-toggler-icon"></span> 
                    </button>
                </div>
            </nav>

            <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
                <div class="offcanvas-header">
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="nav flex-column">
                        <li class="nav-item mb-3"><a href="index.php" class="nav-link"><i class="bi bi-plus-circle"></i> Inicio</a></li>
                        <li class="nav-item mb-3"><a href="ver.php" class="nav-link"><i class="bi bi-eye"></i> Registrarse</a></li>
                        <li class="nav-item mb-3"><a href="actualizar.php" class="nav-link"><i class="bi bi-pencil-square"></i> Notas</a></li>
                        <li class="nav-item mb-3"><a href="eliminar.php" class="nav-link"><i class="bi bi-trash"></i> Docentes </a></li>
                        <li class="nav-item mb-3"><a href="eliminar.php" class="nav-link"><i class="bi bi-trash"></i> Estudiantes </a></li>
                    </ul>
                </div>
            </div>
        </nav>

    <div class="container py-4">
        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <form id="loginForm" action="consulta.php" method="post" novalidate>
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">INICIO DE SESIÓN</h1>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" required>
                        <div class="invalid-feedback">
                            El usuario es obligatorio.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" id="contrasena" class="form-control" required>
                        <div class="invalid-feedback">
                            La contraseña es obligatoria.
                        </div>
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
    <script>
        document.getElementById("loginForm").addEventListener("submit", function(e) {
            const usuario = document.getElementById("usuario").value.trim();
            const contrasena = document.getElementById("contrasena").value.trim();

            let valido = true;

            if (usuario === "") {
                document.getElementById("usuario").classList.add("is-invalid");
                valido = false;
            } else {
                document.getElementById("usuario").classList.remove("is-invalid");
            }

            if (contrasena === "") {
                document.getElementById("contrasena").classList.add("is-invalid");
                valido = false;
            } else {
                document.getElementById("contrasena").classList.remove("is-invalid");
            }

            if (!valido) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>