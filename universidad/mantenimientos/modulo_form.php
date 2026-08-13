<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idModulo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esEdicion = $idModulo !== false && $idModulo !== null;
$pageTitle = $esEdicion ? 'Modificar Módulo' : 'Nuevo Módulo';
$errores = [];

$stmtPadres = $pdo->prepare(
    "SELECT id_modulo, mod_nombre
     FROM t_modulo
     WHERE mod_estado = 'A'
       AND mod_padre IS NULL
     ORDER BY mod_nombre"
);
$stmtPadres->execute();
$padres = $stmtPadres->fetchAll();

$modulo = [
    'id_modulo' => '',
    'mod_nombre' => '',
    'mod_url' => '',
    'mod_padre' => '',
    'mod_estado' => 'A'
];

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT id_modulo, mod_nombre, mod_url, mod_padre, mod_estado FROM t_modulo WHERE id_modulo = :id_modulo");
    $stmt->execute([':id_modulo' => $idModulo]);
    $registro = $stmt->fetch();
    if (!$registro) {
        $_SESSION['modulos_mensaje'] = ['tipo'=>'warning','texto'=>'El módulo solicitado no existe.'];
        header('Location: modulos.php');
        exit;
    }
    $modulo = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_input(INPUT_POST, 'id_modulo', FILTER_VALIDATE_INT);
    $esEdicion = $idPost !== false && $idPost !== null;
    $idModulo = $esEdicion ? $idPost : null;

    $modulo['id_modulo'] = $idModulo ?? '';
    $modulo['mod_nombre'] = trim($_POST['mod_nombre'] ?? '');
    $modulo['mod_url'] = trim($_POST['mod_url'] ?? '');
    $modulo['mod_padre'] = filter_input(INPUT_POST, 'mod_padre', FILTER_VALIDATE_INT) ?: null;
    $modulo['mod_estado'] = strtoupper(trim($_POST['mod_estado'] ?? 'A'));

    if ($modulo['mod_nombre'] === '') {
        $errores[] = 'El nombre del módulo es obligatorio.';
    } elseif (mb_strlen($modulo['mod_nombre']) > 60) {
        $errores[] = 'El nombre no puede superar 60 caracteres.';
    }

    if ($modulo['mod_url'] !== '' && mb_strlen($modulo['mod_url']) > 150) {
        $errores[] = 'La URL no puede superar 150 caracteres.';
    }

    if ($modulo['mod_padre'] && $esEdicion && (int)$modulo['mod_padre'] === (int)$idModulo) {
        $errores[] = 'Un módulo no puede ser su propio padre.';
    }

    if (!in_array($modulo['mod_estado'], ['A','I'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if ($esEdicion) {
        $stmtDuplicado = $pdo->prepare("SELECT id_modulo FROM t_modulo WHERE mod_nombre = :mod_nombre AND id_modulo <> :id_modulo");
        $stmtDuplicado->execute([':mod_nombre'=>$modulo['mod_nombre'], ':id_modulo'=>$idModulo]);
    } else {
        $stmtDuplicado = $pdo->prepare("SELECT id_modulo FROM t_modulo WHERE mod_nombre = :mod_nombre");
        $stmtDuplicado->execute([':mod_nombre'=>$modulo['mod_nombre']]);
    }
    if ($stmtDuplicado->fetch()) {
        $errores[] = 'Ya existe un módulo con el mismo nombre.';
    }

    if (!$errores) {
        if ($esEdicion) {
            $stmt = $pdo->prepare(
                "UPDATE t_modulo
                 SET mod_nombre = :mod_nombre,
                     mod_url = :mod_url,
                     mod_padre = :mod_padre,
                     mod_estado = :mod_estado
                 WHERE id_modulo = :id_modulo"
            );
            $stmt->execute([
                ':mod_nombre'=>$modulo['mod_nombre'],
                ':mod_url'=>$modulo['mod_url'] !== '' ? $modulo['mod_url'] : null,
                ':mod_padre'=>$modulo['mod_padre'],
                ':mod_estado'=>$modulo['mod_estado'],
                ':id_modulo'=>$idModulo
            ]);
            $mensaje = 'El módulo fue actualizado correctamente.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO t_modulo (mod_nombre, mod_url, mod_padre, mod_estado, mod_fechahorareg, mod_usuarioreg)
                 VALUES (:mod_nombre, :mod_url, :mod_padre, :mod_estado, NOW(), :mod_usuarioreg)"
            );
            $stmt->execute([
                ':mod_nombre'=>$modulo['mod_nombre'],
                ':mod_url'=>$modulo['mod_url'] !== '' ? $modulo['mod_url'] : null,
                ':mod_padre'=>$modulo['mod_padre'],
                ':mod_estado'=>$modulo['mod_estado'],
                ':mod_usuarioreg'=>$_SESSION['id_usuario'] ?? null
            ]);
            $mensaje = 'El módulo fue creado correctamente.';
        }

        $_SESSION['modulos_mensaje'] = ['tipo'=>'success','texto'=>$mensaje];
        header('Location: modulos.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">
<section class="user-form-hero mb-4"><div class="d-flex align-items-center gap-3"><span class="hero-form-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span><div><h1 class="h2 fw-bold mb-1"><?= $esEdicion ? 'Modificar módulo' : 'Crear nuevo módulo' ?></h1><p class="mb-0 opacity-75">Configure el nombre, URL, agrupación y estado.</p></div></div></section>
<?php if ($errores): ?><div class="alert alert-danger rounded-4"><strong>Revise la información:</strong><ul class="mb-0 mt-2"><?php foreach($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form-shell" autocomplete="off">
<input type="hidden" name="id_modulo" value="<?= e($modulo['id_modulo']) ?>">
<section class="form-section"><div class="section-heading"><span><i class="bi bi-grid-fill"></i></span><div><h4 class="mb-1">Información del módulo</h4><p class="text-secondary mb-0">Datos utilizados para construir el menú.</p></div></div><div class="row g-4">
<div class="col-md-6"><label class="form-label fw-semibold" for="mod_nombre">Nombre</label><div class="field-wrap"><i class="bi bi-type"></i><input class="form-control" id="mod_nombre" name="mod_nombre" maxlength="60" value="<?= e($modulo['mod_nombre']) ?>" required></div></div>
<div class="col-md-6"><label class="form-label fw-semibold" for="mod_padre">Módulo padre</label><select class="form-select" id="mod_padre" name="mod_padre"><option value="">Sin módulo padre</option><?php foreach($padres as $padre): if ($esEdicion && (int)$padre['id_modulo'] === (int)$idModulo) continue; ?><option value="<?= (int)$padre['id_modulo'] ?>" <?= (int)$modulo['mod_padre'] === (int)$padre['id_modulo'] ? 'selected' : '' ?>><?= e($padre['mod_nombre']) ?></option><?php endforeach; ?></select><div class="form-text">Deje vacío si será una agrupación principal.</div></div>
<div class="col-12"><label class="form-label fw-semibold" for="mod_url">URL del PHP</label><div class="field-wrap"><i class="bi bi-link-45deg"></i><input class="form-control" id="mod_url" name="mod_url" maxlength="150" value="<?= e($modulo['mod_url']) ?>" placeholder="Ej. administracion/usuarios.php"></div><div class="form-text">Los módulos agrupadores pueden quedar sin URL.</div></div>
</div></section>
<section class="form-section"><div class="section-heading"><span><i class="bi bi-toggle-on"></i></span><div><h4 class="mb-1">Estado</h4></div></div><div class="row g-3"><div class="col-md-6"><label class="status-option"><input type="radio" name="mod_estado" value="A" <?= $modulo['mod_estado']==='A'?'checked':'' ?>><span class="status-card status-active-card"><i class="bi bi-check-circle-fill"></i><span><strong class="d-block">Activo</strong><small class="text-secondary">Disponible en permisos y menú.</small></span></span></label></div><div class="col-md-6"><label class="status-option"><input type="radio" name="mod_estado" value="I" <?= $modulo['mod_estado']==='I'?'checked':'' ?>><span class="status-card status-inactive-card"><i class="bi bi-pause-circle-fill"></i><span><strong class="d-block">Inactivo</strong><small class="text-secondary">No estará disponible.</small></span></span></label></div></div></section>
<section class="form-section"><div class="d-flex justify-content-end gap-3"><a href="modulos.php" class="btn btn-cancel-user"><i class="bi bi-arrow-left me-2"></i>Cancelar</a><button class="btn btn-save-user"><i class="bi bi-floppy-fill me-2"></i><?= $esEdicion?'Guardar cambios':'Crear módulo' ?></button></div></section>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
