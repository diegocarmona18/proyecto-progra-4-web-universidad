<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Creación de Usuario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
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

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card shadow-sm w-100" style="max-width: 480px;">
            <div class="card-body p-4">
                <form action="registrar.php" method="post">
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid mb-3 d-block mx-auto" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">CREACIÓN DE USUARIO</h1>

                    <div class="mb-3">
                        <label class="form-label">Código de Usuario</label>
                        <input type="text"
                            name="usu_codigo"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text"
                            name="usu_nombre"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email"
                            name="usu_correo"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password"
                            name="usu_clave"
                            class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rol</label>

                        <select name="id_rol" class="form-select">
                            <option value="1">Administrador</option>
                            <option value="2">Profesor</option>
                            <option value="3">Estudiante</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <button class="btn btn-dark" type="submit">Crear Usuario</button>
                        <a class="btn btn-outline-dark" href="crud.php">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>