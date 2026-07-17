<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
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

    <h1 class="h1_bienvenida">Bienvenido a la Universidad</h1>
</body>
</html>