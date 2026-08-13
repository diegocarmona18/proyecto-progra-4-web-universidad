<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idRol = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esEdicion = $idRol !== false && $idRol !== null;
$pageTitle = $esEdicion ? 'Modificar Rol' : 'Nuevo Rol';
$errores = [];

$stmtDashboards = $pdo->prepare(
    "SELECT mod_nombre, mod_url
     FROM t_modulo
     WHERE mod_estado = 'A'
       AND mod_url IS NOT NULL
       AND mod_url <> ''
     ORDER BY mod_nombre"
);
$stmtDashboards->execute();
$dashboards = $stmtDashboards->fetchAll();

$rol = [
    'id_rol' => '',
    'rol_nombre' => '',
    'rol_dashboard' => '',
    'rol_estado' => 'A'
];

if ($esEdicion) {
    $stmt = $pdo->prepare(
        "SELECT id_rol, rol_nombre, rol_dashboard, rol_estado
         FROM t_rol
         WHERE id_rol = :id_rol"
    );
    $stmt->execute([':id_rol' => $idRol]);
    $registro = $stmt->fetch();

    if (!$registro) {
        $_SESSION['roles_mensaje'] = ['tipo' => 'warning', 'texto' => 'El rol solicitado no existe.'];
        header('Location: roles.php');
        exit;
    }
    $rol = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
    $esEdicion = $idPost !== false && $idPost !== null;
    $idRol = $esEdicion ? $idPost : null;

    $rol['id_rol'] = $idRol ?? '';
    $rol['rol_nombre'] = trim($_POST['rol_nombre'] ?? '');
    $rol['rol_dashboard'] = trim($_POST['rol_dashboard'] ?? '');
    $rol['rol_estado'] = strtoupper(trim($_POST['rol_estado'] ?? 'A'));

    if ($rol['rol_nombre'] === '') {
        $errores[] = 'El nombre del rol es obligatorio.';
    } elseif (mb_strlen($rol['rol_nombre']) > 20) {
        $errores[] = 'El nombre del rol no puede superar 20 caracteres.';
    }

    if ($rol['rol_dashboard'] === '') {
        $errores[] = 'Debe seleccionar el dashboard del rol.';
    }

    if (!in_array($rol['rol_estado'], ['A', 'I'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if ($esEdicion) {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_rol
             FROM t_rol
             WHERE rol_nombre = :rol_nombre
               AND id_rol <> :id_rol"
        );
        $stmtDuplicado->execute([
            ':rol_nombre' => $rol['rol_nombre'],
            ':id_rol' => $idRol
        ]);
    } else {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_rol
             FROM t_rol
             WHERE rol_nombre = :rol_nombre"
        );
        $stmtDuplicado->execute([':rol_nombre' => $rol['rol_nombre']]);
    }

    if ($stmtDuplicado->fetch()) {
        $errores[] = 'Ya existe un rol con el mismo nombre.';
    }

    if (!$errores) {
        if ($esEdicion) {
            $stmt = $pdo->prepare(
                "UPDATE t_rol
                 SET rol_nombre = :rol_nombre,
                     rol_dashboard = :rol_dashboard,
                     rol_estado = :rol_estado
                 WHERE id_rol = :id_rol"
            );
            $stmt->execute([
                ':rol_nombre' => $rol['rol_nombre'],
                ':rol_dashboard' => $rol['rol_dashboard'],
                ':rol_estado' => $rol['rol_estado'],
                ':id_rol' => $idRol
            ]);
            registrarBitacoraRol('rol_actualizado', [
                'rol_id' => $idRol,
                'rol_nombre' => $rol['rol_nombre'],
                'rol_dashboard' => $rol['rol_dashboard'],
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $mensaje = 'El rol fue actualizado correctamente.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO t_rol (
                    rol_nombre,
                    rol_dashboard,
                    rol_estado,
                    rol_fechahorareg,
                    rol_usuarioreg
                 ) VALUES (
                    :rol_nombre,
                    :rol_dashboard,
                    :rol_estado,
                    NOW(),
                    :rol_usuarioreg
                 )"
            );
            $stmt->execute([
                ':rol_nombre' => $rol['rol_nombre'],
                ':rol_dashboard' => $rol['rol_dashboard'],
                ':rol_estado' => $rol['rol_estado'],
                ':rol_usuarioreg' => $_SESSION['id_usuario'] ?? null
            ]);
            $nuevoId = (int)$pdo->lastInsertId();
            registrarBitacoraRol('rol_creado', [
                'rol_id' => $nuevoId,
                'rol_nombre' => $rol['rol_nombre'],
                'rol_dashboard' => $rol['rol_dashboard'],
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $mensaje = 'El rol fue creado correctamente.';
        }

        $_SESSION['roles_mensaje'] = ['tipo' => 'success', 'texto' => $mensaje];
        header('Location: roles.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="user-form-hero mb-4">
    <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
        <span class="hero-form-icon"><i class="bi <?= $esEdicion ? 'bi-pencil-square' : 'bi-person-badge-fill' ?>"></i></span>
        <div><h1 class="h2 fw-bold mb-1"><?= $esEdicion ? 'Modificar rol' : 'Crear nuevo rol' ?></h1><p class="mb-0 opacity-75">Defina el nombre, dashboard y estado del perfil.</p></div>
    </div>
</section>

<?php if ($errores): ?>
<div class="alert alert-danger rounded-4"><strong>Revise la información:</strong><ul class="mb-0 mt-2"><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="form-shell" autocomplete="off">
    <input type="hidden" name="id_rol" value="<?= e($rol['id_rol']) ?>">

    <section class="form-section">
        <div class="section-heading"><span><i class="bi bi-person-vcard-fill"></i></span><div><h4 class="mb-1">Información del rol</h4><p class="text-secondary mb-0">Datos principales del perfil de acceso.</p></div></div>
        <div class="row g-4">
            <div class="col-md-6">
                <label for="rol_nombre" class="form-label fw-semibold">Nombre del rol</label>
                <div class="field-wrap"><i class="bi bi-person-badge"></i><input type="text" class="form-control" id="rol_nombre" name="rol_nombre" maxlength="20" value="<?= e($rol['rol_nombre']) ?>" required></div>
            </div>
            <div class="col-md-6">
                <label for="rol_dashboard" class="form-label fw-semibold">Pantalla de inicio</label>
                <select class="form-select" id="rol_dashboard" name="rol_dashboard" required>
                    <option value="">Seleccione una pantalla</option>
                    <?php foreach ($dashboards as $dashboard): ?>
                        <option value="<?= e($dashboard['mod_url']) ?>" <?= $rol['rol_dashboard'] === $dashboard['mod_url'] ? 'selected' : '' ?>><?= e($dashboard['mod_nombre']) ?> — <?= e($dashboard['mod_url']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><span><i class="bi bi-toggle-on"></i></span><div><h4 class="mb-1">Estado</h4><p class="text-secondary mb-0">Indique si el rol estará disponible.</p></div></div>
        <div class="row g-3">
            <div class="col-md-6"><label class="status-option"><input type="radio" name="rol_estado" value="A" <?= $rol['rol_estado'] === 'A' ? 'checked' : '' ?>><span class="status-card status-active-card"><i class="bi bi-check-circle-fill"></i><span><strong class="d-block">Activo</strong><small class="text-secondary">Disponible para asignar a usuarios.</small></span></span></label></div>
            <div class="col-md-6"><label class="status-option"><input type="radio" name="rol_estado" value="I" <?= $rol['rol_estado'] === 'I' ? 'checked' : '' ?>><span class="status-card status-inactive-card"><i class="bi bi-pause-circle-fill"></i><span><strong class="d-block">Inactivo</strong><small class="text-secondary">No estará disponible para nuevos usuarios.</small></span></span></label></div>
        </div>
    </section>

    <section class="form-section"><div class="d-flex justify-content-end gap-3"><a href="roles.php" class="btn btn-cancel-user"><i class="bi bi-arrow-left me-2"></i>Cancelar</a><button type="submit" class="btn btn-save-user"><i class="bi bi-floppy-fill me-2"></i><?= $esEdicion ? 'Guardar cambios' : 'Crear rol' ?></button></div></section>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
