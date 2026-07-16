<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar usuario</title>
</head>
<body>
    <h1>Eliminar usuario</h1>
    <form action="eliminar_usuario.php" method="post">
        <label for="cedula">Número de cédula:</label>
        <input type="text" name="cedula" id="cedula" required>
        <button type="submit">Eliminar</button>
    </form>
</body>
</html>