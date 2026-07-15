<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
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
                        <li class="nav-item mb-3"><a href="create.php" class="nav-link"><i class="bi bi-plus-circle"></i> Inicio</a></li>
                        <li class="nav-item mb-3"><a href="ver.php" class="nav-link"><i class="bi bi-eye"></i> Registrarse</a></li>
                        <li class="nav-item mb-3"><a href="actualizar.php" class="nav-link"><i class="bi bi-pencil-square"></i> Notas</a></li>
                        <li class="nav-item mb-3"><a href="eliminar.php" class="nav-link"><i class="bi bi-trash"></i> Docentes </a></li>
                        <li class="nav-item mb-3"><a href="eliminar.php" class="nav-link"><i class="bi bi-trash"></i> Estudiantes </a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="flex-grow-1 p-4">
            <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
                <div class="card shadow-sm w-100" style="max-width: 480px;">
                    <div class="card-body p-4">
                        <form action="registrar.php" method="post">
                            <a class="btn btn-outline-dark mb-2" href="index.php">Volver</a>
                            <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid mb-3 d-block mx-auto" style="max-width: 180px;">
                            <h1 class="sistemacrud">Sistema de Gestión de usuarios</h1>
                            <!-- Botones del CRUD: más abajo, separados y en azul -->
                            <div class="mt-4">
                                
                                <a class="btn btn-primary d-block mb-2" href="crear.php">Crear usuario</a>
                                <a class="btn btn-primary d-block mb-2" href="ver.php">Ver usuarios</a>
                                <a class="btn btn-primary d-block mb-2" href="actualizar.php">Actualizar usuario</a>
                                <a class="btn btn-primary d-block" href="eliminar.php">Eliminar usuario</a>
                            </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>