<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
</head>
<body>
    <div class="container py-4">
        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <form action="enviar.php" method="post">
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">RECUPERAR CONTRASEÑA</h1>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-dark" type="submit">Recuperar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>