<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Gestión de Roles';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlRoles(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);

    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }

    return 'roles.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

/* Activar o inactivar un rol. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idRol = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);

    if (!$idRol) {
        $_SESSION['roles_mensaje'] = [
            'tipo' => 'danger',
            'texto' => 'No fue posible identificar el rol seleccionado.'
        ];
        header('Location: ' . urlRoles());
        exit;
    }

    if ($accion === 'activar') {
        $stmt = $pdo->prepare(
            "UPDATE t_rol
             SET rol_estado = 'A'
             WHERE id_rol = :id_rol
               AND rol_estado = 'I'"
        );
        $stmt->execute([':id_rol' => $idRol]);

        $exito = $stmt->rowCount() > 0;
        $_SESSION['roles_mensaje'] = $exito
            ? ['tipo' => 'success', 'texto' => 'El rol fue activado correctamente.']
            : ['tipo' => 'warning', 'texto' => 'El rol ya se encontraba activo.'];

        if ($exito) {
            try {
                $stmtNombre = $pdo->prepare("SELECT rol_nombre FROM t_rol WHERE id_rol = :id_rol LIMIT 1");
                $stmtNombre->execute([':id_rol' => $idRol]);
                $nombre = $stmtNombre->fetchColumn();

                registrarBitacoraRol('rol_activado', [
                    'rol_id' => $idRol,
                    'rol_nombre' => $nombre ?: null,
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            } catch (Throwable $e) {
                // No bloquear la acción si falla la bitácora
            }
        }
    }

    if ($accion === 'inactivar') {
        $stmtUsuarios = $pdo->prepare(
            "SELECT COUNT(*)
             FROM t_usuario
             WHERE id_rol = :id_rol"
        );
        $stmtUsuarios->execute([':id_rol' => $idRol]);
        $usuariosAsignados = (int)$stmtUsuarios->fetchColumn();

        if ($usuariosAsignados > 0) {
            $_SESSION['roles_mensaje'] = [
                'tipo' => 'warning',
                'texto' => 'No se puede inactivar el rol porque tiene usuarios asignados.'
            ];

            try {
                $stmtNombre = $pdo->prepare("SELECT rol_nombre FROM t_rol WHERE id_rol = :id_rol LIMIT 1");
                $stmtNombre->execute([':id_rol' => $idRol]);
                $nombre = $stmtNombre->fetchColumn();

                registrarBitacoraRol('rol_inactivacion_intentada', [
                    'rol_id' => $idRol,
                    'rol_nombre' => $nombre ?: null,
                    'modulos' => 'usuarios_asignados=' . $usuariosAsignados,
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            } catch (Throwable $e) {
                // no bloquear
            }
        } else {
            $stmt = $pdo->prepare(
                "UPDATE t_rol
                 SET rol_estado = 'I'
                 WHERE id_rol = :id_rol
                   AND rol_estado = 'A'"
            );
            $stmt->execute([':id_rol' => $idRol]);

            $exito = $stmt->rowCount() > 0;
            $_SESSION['roles_mensaje'] = $exito
                ? ['tipo' => 'success', 'texto' => 'El rol fue inactivado correctamente.']
                : ['tipo' => 'warning', 'texto' => 'El rol ya se encontraba inactivo.'];

            if ($exito) {
                try {
                    $stmtNombre = $pdo->prepare("SELECT rol_nombre FROM t_rol WHERE id_rol = :id_rol LIMIT 1");
                    $stmtNombre->execute([':id_rol' => $idRol]);
                    $nombre = $stmtNombre->fetchColumn();

                    registrarBitacoraRol('rol_inactivado', [
                        'rol_id' => $idRol,
                        'rol_nombre' => $nombre ?: null,
                        'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                    ]);
                } catch (Throwable $e) {
                    // No bloquear la acción si falla la bitácora
                }
            }
        }
    }

    if ($accion === 'eliminar') {
        // Verificar si hay usuarios asignados (cualquier estado)
        $stmtUsuarios = $pdo->prepare(
            "SELECT COUNT(*) FROM t_usuario WHERE id_rol = :id_rol"
        );
        $stmtUsuarios->execute([':id_rol' => $idRol]);
        $usuariosAsignados = (int)$stmtUsuarios->fetchColumn();

        if ($usuariosAsignados > 0) {
            $_SESSION['roles_mensaje'] = [
                'tipo' => 'warning',
                'texto' => 'No se puede eliminar el rol porque tiene usuarios asignados.'
            ];

            try {
                $stmtNombre = $pdo->prepare("SELECT rol_nombre FROM t_rol WHERE id_rol = :id_rol LIMIT 1");
                $stmtNombre->execute([':id_rol' => $idRol]);
                $nombre = $stmtNombre->fetchColumn();

                registrarBitacoraRol('rol_eliminado_intentado', [
                    'rol_id' => $idRol,
                    'rol_nombre' => $nombre ?: null,
                    'modulos' => 'usuarios_asignados=' . $usuariosAsignados,
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            } catch (Throwable $e) {
                // no bloquear
            }
        } else {
            try {
                // eliminar permisos/modulos asociados primero (por FK restrict)
                $stmtDeletePerm = $pdo->prepare("DELETE FROM t_modulo_rol WHERE id_rol = :id_rol");
                $stmtDeletePerm->execute([':id_rol' => $idRol]);

                $stmtDelete = $pdo->prepare("DELETE FROM t_rol WHERE id_rol = :id_rol");
                $stmtDelete->execute([':id_rol' => $idRol]);

                $deleted = $stmtDelete->rowCount() > 0;

                if ($deleted) {
                    registrarBitacoraRol('rol_eliminado', [
                        'rol_id' => $idRol,
                        'rol_nombre' => null,
                        'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                    ]);
                    $_SESSION['roles_mensaje'] = ['tipo' => 'success', 'texto' => 'El rol fue eliminado correctamente.'];
                } else {
                    $_SESSION['roles_mensaje'] = ['tipo' => 'warning', 'texto' => 'No se pudo eliminar el rol.'];
                }
            } catch (Throwable $e) {
                $_SESSION['roles_mensaje'] = ['tipo' => 'danger', 'texto' => 'Ocurrió un error al eliminar el rol.'];
            }
        }
    }

    header('Location: ' . urlRoles());
    exit;
}

$mensaje = $_SESSION['roles_mensaje'] ?? null;
unset($_SESSION['roles_mensaje']);

$busqueda = trim($_GET['buscar'] ?? '');
$estadoFiltro = strtoupper(trim($_GET['estado'] ?? ''));

if (!in_array($estadoFiltro, ['A', 'I'], true)) {
    $estadoFiltro = '';
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = [];
$parametros = [];

if ($estadoFiltro !== '') {
    $where[] = 'r.rol_estado = :estado';
    $parametros[':estado'] = $estadoFiltro;
} else {
    $where[] = "r.rol_estado = 'A'";
}

if ($busqueda !== '') {
    $termino = '%' . $busqueda . '%';
    $where[] = "(
        CAST(r.id_rol AS CHAR) LIKE :buscar_id
        OR r.rol_nombre LIKE :buscar_nombre
        OR r.rol_dashboard LIKE :buscar_dashboard
    )";
    $parametros[':buscar_id'] = $termino;
    $parametros[':buscar_nombre'] = $termino;
    $parametros[':buscar_dashboard'] = $termino;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*)
     FROM t_rol r
     $whereSql"
);
$stmtTotal->execute($parametros);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$stmtRoles = $pdo->prepare(
    "SELECT
        r.id_rol,
        r.rol_nombre,
        r.rol_dashboard,
        r.rol_estado,
        r.rol_fechahorareg,
        COUNT(mr.id_modulorol) AS cantidad_modulos
     FROM t_rol r
     LEFT JOIN t_modulo_rol mr
       ON mr.id_rol = r.id_rol
      AND mr.mor_estado = 'A'
     $whereSql
     GROUP BY r.id_rol, r.rol_nombre, r.rol_dashboard, r.rol_estado, r.rol_fechahorareg
     ORDER BY r.rol_nombre
     LIMIT :limite OFFSET :offset"
);

foreach ($parametros as $nombre => $valor) {
    $stmtRoles->bindValue($nombre, $valor, PDO::PARAM_STR);
}
$stmtRoles->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtRoles->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtRoles->execute();
$roles = $stmtRoles->fetchAll();

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalRegistros);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-person-badge-fill"></i></span>
            <h1 class="mb-0">Gestión de Roles</h1>
        </div>
        <p class="mb-0">Administra los perfiles y los permisos de acceso al sistema.</p>
    </div>
    <a class="btn btn-light users-primary-action" href="rol_form.php">
        <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Rol
    </a>
</section>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= e($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<section class="users-card">
    <form method="get" class="users-filters">
        <div class="users-search flex-grow-1">
            <i class="bi bi-search"></i>
            <input type="search" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar por ID, nombre o dashboard...">
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-funnel-fill"></i>
            <select name="estado" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro === '' ? 'selected' : '' ?>>Roles activos</option>
                <option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activos</option>
                <option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <?php if ($busqueda !== '' || $estadoFiltro !== ''): ?>
            <a href="roles.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table users-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:72px"></th>
                    <th>Rol</th>
                    <th>Dashboard</th>
                    <th class="text-center">Módulos</th>
                    <th>Fecha de registro</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$roles): ?>
                    <tr><td colspan="7" class="text-center py-5 text-secondary">No se encontraron roles.</td></tr>
                <?php endif; ?>

                <?php foreach ($roles as $rol): ?>
                    <?php $activo = $rol['rol_estado'] === 'A'; ?>
                    <tr>
                        <td><span class="role-icon role-admin"><i class="bi bi-shield-lock-fill"></i></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($rol['rol_nombre']) ?></div>
                            <small class="text-secondary">ID: <?= (int)$rol['id_rol'] ?></small>
                        </td>
                        <td><?= $rol['rol_dashboard'] ? e($rol['rol_dashboard']) : '<span class="text-secondary">Sin definir</span>' ?></td>
                        <td class="text-center"><span class="credit-badge"><?= (int)$rol['cantidad_modulos'] ?></span></td>
                        <td><?= $rol['rol_fechahorareg'] ? e(date('d/m/Y H:i', strtotime($rol['rol_fechahorareg']))) : '—' ?></td>
                        <td>
                            <?php if ($activo): ?>
                                <span class="status-badge status-active"><span></span>Activo</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive"><span></span>Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end gap-2">
                                <a class="action-btn bg-primary text-white" href="rol_form.php?id=<?= (int)$rol['id_rol'] ?>" title="Modificar rol">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn bg-warning text-dark" href="rol_modulos.php?id=<?= (int)$rol['id_rol'] ?>" title="Configurar módulos">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                </a>
                                <button type="button"
                                    class="action-btn <?= $activo ? 'bg-danger' : 'bg-success' ?> text-white"
                                    data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                    data-action="<?= $activo ? 'inactivar' : 'activar' ?>"
                                    data-id="<?= (int)$rol['id_rol'] ?>"
                                    data-name="<?= e($rol['rol_nombre']) ?>"
                                    title="<?= $activo ? 'Inactivar rol' : 'Activar rol' ?>">
                                    <i class="bi <?= $activo ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?>"></i>
                                </button>
                                <button type="button" class="action-btn bg-danger text-white" data-bs-toggle="modal" data-bs-target="#confirmActionModal" data-action="eliminar" data-id="<?= (int)$rol['id_rol'] ?>" data-name="<?= e($rol['rol_nombre']) ?>" title="Eliminar rol"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="users-footer">
        <div>Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $totalRegistros ?> resultados</div>
        <?php if ($totalPaginas > 1): ?>
            <nav><ul class="pagination users-pagination mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(urlRoles(['pagina' => $pagina - 1])) ?>"><i class="bi bi-chevron-left"></i></a></li>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= e(urlRoles(['pagina' => $i])) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(urlRoles(['pagina' => $pagina + 1])) ?>"><i class="bi bi-chevron-right"></i></a></li>
            </ul></nav>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade confirm-modal" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="confirm-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="confirm-modal-icon"><i class="bi bi-shield-lock-fill"></i></span>
                    <div><h4 class="mb-1" id="confirmModalTitle">Confirmar acción</h4><p class="mb-0 opacity-75">Revise la información antes de continuar.</p></div>
                </div>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="accion" id="confirmAction">
                    <input type="hidden" name="id_rol" id="confirmRolId">
                    <p id="confirmModalText"></p>
                    <div class="confirm-user-box"><i class="bi bi-person-badge-fill me-2"></i><strong id="confirmRolName"></strong></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn" id="confirmSubmitButton">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmActionModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const action = button.getAttribute('data-action');
    document.getElementById('confirmAction').value = action;
    document.getElementById('confirmRolId').value = button.getAttribute('data-id');
    document.getElementById('confirmRolName').textContent = button.getAttribute('data-name');

    if (action === 'activar') {
        document.getElementById('confirmModalTitle').textContent = 'Activar rol';
        document.getElementById('confirmModalText').textContent = '¿Desea activar este rol?';
        document.getElementById('confirmSubmitButton').textContent = 'Activar rol';
        document.getElementById('confirmSubmitButton').className = 'btn btn-confirm-unlock';
    } else if (action === 'inactivar') {
        document.getElementById('confirmModalTitle').textContent = 'Inactivar rol';
        document.getElementById('confirmModalText').textContent = '¿Desea inactivar este rol?';
        document.getElementById('confirmSubmitButton').textContent = 'Inactivar rol';
        document.getElementById('confirmSubmitButton').className = 'btn btn-confirm-delete';
    } else if (action === 'eliminar') {
        document.getElementById('confirmModalTitle').textContent = 'Eliminar rol';
        document.getElementById('confirmModalText').textContent = '¿Desea eliminar este rol? Esta acción no se puede deshacer y eliminará los permisos asociados.';
        document.getElementById('confirmSubmitButton').textContent = 'Eliminar rol';
        document.getElementById('confirmSubmitButton').className = 'btn btn-confirm-delete';
    } else {
        document.getElementById('confirmModalTitle').textContent = 'Confirmar acción';
        document.getElementById('confirmModalText').textContent = 'Revise la información antes de continuar.';
        document.getElementById('confirmSubmitButton').textContent = 'Confirmar';
        document.getElementById('confirmSubmitButton').className = 'btn';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
