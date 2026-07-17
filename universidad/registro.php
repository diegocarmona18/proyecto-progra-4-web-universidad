<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Registro de Usuario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=2">
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
        <div class="card shadow-sm w-100" style="max-width: 480px;">
            <div class="card-body p-4">
                <form id="registroForm" action="registrar.php" method="post" novalidate>
                    <img src="../img/logo.png" alt="Logo de la universidad" class="img-fluid mb-3 d-block mx-auto" style="max-width: 180px;">
                    <h1 class="h3 text-center mb-4">REGISTRO DE USUARIO</h1>

                    <div class="mb-3">
                        <label for="cedula" class="form-label">Número de cédula</label>
                        <input type="text" name="cedula" id="cedula" class="form-control" placeholder="Cédula" required>
                        <div class="invalid-feedback">
                            La cédula es obligatoria.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Usuario" required>
                        <div class="invalid-feedback">
                            El usuario es obligatorio.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="correo" class="form-control" placeholder="Correo" required>
                        <div class="invalid-feedback">
                            El correo es obligatorio.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" name="contrasena" id="contrasena" class="form-control" placeholder="Contraseña" required>
                        <div class="invalid-feedback">
                            La contraseña es obligatoria.
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <button class="btn btn-dark" type="submit">Registrarse</button>
                        <a class="btn btn-outline-dark" href="index.php">Volver</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById("registroForm").addEventListener("submit", function(e) {
            const cedula = document.getElementById("cedula").value.trim();
            const usuario = document.getElementById("usuario").value.trim();
            const correo = document.getElementById("correo").value.trim();
            const contrasena = document.getElementById("contrasena").value.trim();

            let valido = true;

            if (cedula === "") {
                document.getElementById("cedula").classList.add("is-invalid");
                valido = false;
            } else {
                document.getElementById("cedula").classList.remove("is-invalid");
            }

            if (usuario === "") {
                document.getElementById("usuario").classList.add("is-invalid");
                valido = false;
            } else {
                document.getElementById("usuario").classList.remove("is-invalid");
            }

            if (correo === "") {
                document.getElementById("correo").classList.add("is-invalid");
                valido = false;
            } else {
                document.getElementById("correo").classList.remove("is-invalid");
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