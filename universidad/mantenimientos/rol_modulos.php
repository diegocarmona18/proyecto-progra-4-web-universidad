<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Permisos del Rol';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idRol = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idRol) {
    header('Location: roles.php');
    exit;
}

$stmtRol = $pdo->prepare("SELECT id_rol, rol_nombre, rol_estado FROM t_rol WHERE id_rol = :id_rol");
$stmtRol->execute([':id_rol'=>$idRol]);
$rol = $stmtRol->fetch();
if (!$rol) {
    $_SESSION['roles_mensaje'] = ['tipo'=>'warning','texto'=>'El rol seleccionado no existe.'];
    header('Location: roles.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seleccionados = $_POST['modulos'] ?? [];
    $idsSeleccionados = [];

    foreach ($seleccionados as $idModulo) {
        $idModulo = (int)$idModulo;
        if ($idModulo > 0) {
            $idsSeleccionados[] = $idModulo;
        }
    }

    /* Si se seleccionó un hijo, agregar también su padre. */
    foreach ($idsSeleccionados as $idModulo) {
        $stmtPadre = $pdo->prepare("SELECT mod_padre FROM t_modulo WHERE id_modulo = :id_modulo AND mod_estado = 'A'");
        $stmtPadre->execute([':id_modulo'=>$idModulo]);
        $idPadre = $stmtPadre->fetchColumn();
        if ($idPadre && !in_array((int)$idPadre, $idsSeleccionados, true)) {
            $idsSeleccionados[] = (int)$idPadre;
        }
    }

    /* Primero dejar todos los permisos del rol inactivos. */
    $stmt = $pdo->prepare("UPDATE t_modulo_rol SET mor_estado = 'I' WHERE id_rol = :id_rol");
    $stmt->execute([':id_rol'=>$idRol]);

    /* Activar o crear los permisos seleccionados. */
    foreach ($idsSeleccionados as $idModulo) {
        $stmtExiste = $pdo->prepare("SELECT id_modulorol FROM t_modulo_rol WHERE id_rol = :id_rol AND id_modulo = :id_modulo");
        $stmtExiste->execute([':id_rol'=>$idRol, ':id_modulo'=>$idModulo]);
        $idModuloRol = $stmtExiste->fetchColumn();

        if ($idModuloRol) {
            $stmtGuardar = $pdo->prepare("UPDATE t_modulo_rol SET mor_estado = 'A', mor_fechahorareg = NOW(), mor_usuarioreg = :usuario WHERE id_modulorol = :id_modulorol");
            $stmtGuardar->execute([':usuario'=>$_SESSION['id_usuario'] ?? null, ':id_modulorol'=>$idModuloRol]);
        } else {
            $stmtGuardar = $pdo->prepare("INSERT INTO t_modulo_rol (id_modulo, id_rol, mor_estado, mor_fechahorareg, mor_usuarioreg) VALUES (:id_modulo, :id_rol, 'A', NOW(), :usuario)");
            $stmtGuardar->execute([':id_modulo'=>$idModulo, ':id_rol'=>$idRol, ':usuario'=>$_SESSION['id_usuario'] ?? null]);
        }
    }

    /* Registrar en bitácora: módulos actualizados */
    try {
        $modulosTexto = 'N/A';
        if (!empty($idsSeleccionados)) {
            $in = implode(',', array_fill(0, count($idsSeleccionados), '?'));
            $stmtNombres = $pdo->prepare("SELECT mod_nombre FROM t_modulo WHERE id_modulo IN ($in) ORDER BY mod_nombre");
            foreach ($idsSeleccionados as $k => $idMod) {
                $stmtNombres->bindValue($k+1, $idMod, PDO::PARAM_INT);
            }
            $stmtNombres->execute();
            $nombres = $stmtNombres->fetchAll(PDO::FETCH_COLUMN);
            if ($nombres) {
                $modulosTexto = implode(', ', $nombres);
            } else {
                $modulosTexto = implode(', ', $idsSeleccionados);
            }
        } else {
            $modulosTexto = '';
        }

        registrarBitacoraRol('rol_modulos_actualizados', [
            'rol_id' => $idRol,
            'rol_nombre' => $rol['rol_nombre'] ?? null,
            'modulos' => $modulosTexto,
            'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
        ]);
    } catch (Throwable $e) {
        // No interrumpir el flujo si falla la bitácora
    }

    $_SESSION['roles_mensaje'] = ['tipo'=>'success','texto'=>'Los permisos del rol fueron actualizados correctamente.'];
    header('Location: roles.php');
    exit;
}

$stmtPadres = $pdo->prepare("SELECT id_modulo, mod_nombre, mod_url FROM t_modulo WHERE mod_estado = 'A' AND mod_padre IS NULL ORDER BY mod_nombre");
$stmtPadres->execute();
$padres = $stmtPadres->fetchAll();

$stmtHijos = $pdo->prepare("SELECT id_modulo, mod_nombre, mod_url, mod_padre FROM t_modulo WHERE mod_estado = 'A' AND mod_padre IS NOT NULL ORDER BY mod_nombre");
$stmtHijos->execute();
$hijos = $stmtHijos->fetchAll();

$stmtPermisos = $pdo->prepare("SELECT id_modulo FROM t_modulo_rol WHERE id_rol = :id_rol AND mor_estado = 'A'");
$stmtPermisos->execute([':id_rol'=>$idRol]);
$permisos = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);
$permisos = array_map('intval', $permisos);

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">
<section class="users-hero mb-4"><div><div class="d-flex align-items-center gap-3 mb-2"><span class="users-hero-icon"><i class="bi bi-key-fill"></i></span><div><h1 class="mb-0">Permisos del Rol</h1><p class="mb-0 mt-1"><?= e($rol['rol_nombre']) ?></p></div></div></div><a class="btn btn-light" href="roles.php"><i class="bi bi-arrow-left me-2"></i>Volver</a></section>

<form method="post" class="form-shell">
<section class="form-section"><div class="section-heading"><span><i class="bi bi-grid-3x3-gap-fill"></i></span><div><h4 class="mb-1">Módulos disponibles</h4><p class="text-secondary mb-0">Marque las opciones a las que tendrá acceso este rol. Al seleccionar una opción hija también se habilita su agrupador.</p></div></div>

<div class="row g-4">
<?php foreach ($padres as $padre): ?>
    <div class="col-lg-6">
        <div class="border rounded-4 p-4 h-100 bg-white">
            <div class="form-check mb-3">
                <input class="form-check-input parent-check" type="checkbox" name="modulos[]" value="<?= (int)$padre['id_modulo'] ?>" id="modulo<?= (int)$padre['id_modulo'] ?>" <?= in_array((int)$padre['id_modulo'], $permisos, true) ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="modulo<?= (int)$padre['id_modulo'] ?>"><i class="bi bi-folder-fill text-warning me-2"></i><?= e($padre['mod_nombre']) ?></label>
            </div>
            <?php $tieneHijos = false; ?>
            <?php foreach ($hijos as $hijo): ?>
                <?php if ((int)$hijo['mod_padre'] === (int)$padre['id_modulo']): $tieneHijos = true; ?>
                    <div class="form-check ms-4 mb-2">
                        <input class="form-check-input child-check" data-parent="modulo<?= (int)$padre['id_modulo'] ?>" type="checkbox" name="modulos[]" value="<?= (int)$hijo['id_modulo'] ?>" id="modulo<?= (int)$hijo['id_modulo'] ?>" <?= in_array((int)$hijo['id_modulo'], $permisos, true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="modulo<?= (int)$hijo['id_modulo'] ?>"><i class="bi bi-file-earmark-code me-2 text-primary"></i><?= e($hijo['mod_nombre']) ?><?php if ($hijo['mod_url']): ?><small class="d-block text-secondary ms-4"><?= e($hijo['mod_url']) ?></small><?php endif; ?></label>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$tieneHijos): ?><small class="text-secondary ms-4">Este módulo no tiene opciones hijas.</small><?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
</section>
<section class="form-section"><div class="d-flex justify-content-end gap-3"><a href="roles.php" class="btn btn-cancel-user">Cancelar</a><button type="submit" class="btn btn-save-user"><i class="bi bi-floppy-fill me-2"></i>Guardar permisos</button></div></section>
</form>
<script>
document.querySelectorAll('.child-check').forEach(function(check){check.addEventListener('change',function(){if(this.checked){document.getElementById(this.getAttribute('data-parent')).checked=true;}});});
document.querySelectorAll('.parent-check').forEach(function(check){check.addEventListener('change',function(){if(!this.checked){const id=this.id;document.querySelectorAll('.child-check[data-parent="'+id+'"]').forEach(function(child){child.checked=false;});}});});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
