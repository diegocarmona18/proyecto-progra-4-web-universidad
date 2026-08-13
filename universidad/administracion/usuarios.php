<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Gestión de Usuarios';

/* --------------------------------------------------------------------------
 | Utilidades
 * -------------------------------------------------------------------------- */
function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlUsuarios(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);
    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }

    return 'usuarios.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

function iconoRol(string $rol): string
{
    $rol = mb_strtolower($rol, 'UTF-8');

    if (str_contains($rol, 'admin')) {
        return 'bi-shield-check';
    }
    if (str_contains($rol, 'prof') || str_contains($rol, 'doc')) {
        return 'bi-person-video3';
    }
    if (str_contains($rol, 'est')) {
        return 'bi-mortarboard-fill';
    }

    return 'bi-person-badge';
}

function claseRol(string $rol): string
{
    $rol = mb_strtolower($rol, 'UTF-8');

    if (str_contains($rol, 'admin')) {
        return 'role-admin';
    }
    if (str_contains($rol, 'prof') || str_contains($rol, 'doc')) {
        return 'role-profesor';
    }
    if (str_contains($rol, 'est')) {
        return 'role-estudiante';
    }

    return 'role-general';
}

/* --------------------------------------------------------------------------
 | Acciones
 * -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

    if (!$idUsuario) {
        $_SESSION['usuarios_mensaje'] = [
            'tipo' => 'danger',
            'texto' => 'No fue posible identificar el usuario seleccionado.'
        ];
        header('Location: ' . urlUsuarios());
        exit;
    }

    if ($accion === 'desbloquear') {
        $stmt = $pdo->prepare(
            "UPDATE t_usuario
             SET usu_estado = 'A', usu_intentofallido = 0
             WHERE id_usuario = :id_usuario
               AND usu_estado = 'B'"
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        if ($stmt->rowCount() > 0) {
            $stmtUsuario = $pdo->prepare("SELECT usu_codigo, usu_correo, id_rol FROM t_usuario WHERE id_usuario = :id_usuario LIMIT 1");
            $stmtUsuario->execute([':id_usuario' => $idUsuario]);
            $usuarioLog = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

            registrarBitacoraUsuario('usuario_desbloqueado', [
                'id_usuario' => $idUsuario,
                'usu_codigo' => $usuarioLog['usu_codigo'] ?? null,
                'usuario_correo' => $usuarioLog['usu_correo'] ?? null,
                'rol' => $usuarioLog['id_rol'] ?? null,
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
        }

        $_SESSION['usuarios_mensaje'] = $stmt->rowCount() > 0
            ? ['tipo' => 'success', 'texto' => 'El usuario fue desbloqueado correctamente.']
            : ['tipo' => 'warning', 'texto' => 'El usuario ya no se encuentra bloqueado.'];
    }

    if ($accion === 'inactivar') {
        if ((int)($_SESSION['id_usuario'] ?? 0) === $idUsuario) {
            $_SESSION['usuarios_mensaje'] = [
                'tipo' => 'warning',
                'texto' => 'No puede inactivar el usuario con el que inició sesión.'
            ];
        } else {
            $stmt = $pdo->prepare(
                "UPDATE t_usuario
                 SET usu_estado = 'I'
                 WHERE id_usuario = :id_usuario
                   AND usu_estado IN ('A', 'B')"
            );
            $stmt->execute([':id_usuario' => $idUsuario]);

            if ($stmt->rowCount() > 0) {
                $stmtUsuario = $pdo->prepare("SELECT usu_codigo, usu_correo, id_rol FROM t_usuario WHERE id_usuario = :id_usuario LIMIT 1");
                $stmtUsuario->execute([':id_usuario' => $idUsuario]);
                $usuarioLog = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

                registrarBitacoraUsuario('usuario_inactivado', [
                    'id_usuario' => $idUsuario,
                    'usu_codigo' => $usuarioLog['usu_codigo'] ?? null,
                    'usuario_correo' => $usuarioLog['usu_correo'] ?? null,
                    'rol' => $usuarioLog['id_rol'] ?? null,
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            }

            $_SESSION['usuarios_mensaje'] = $stmt->rowCount() > 0
                ? ['tipo' => 'success', 'texto' => 'El usuario fue inactivado correctamente.']
                : ['tipo' => 'warning', 'texto' => 'El usuario ya se encontraba inactivo.'];
        }
    }

    if ($accion === 'activar') {
        $stmt = $pdo->prepare(
            "UPDATE t_usuario
             SET usu_estado = 'A',
                 usu_intentofallido = 0
             WHERE id_usuario = :id_usuario
               AND usu_estado = 'I'"
        );
        $stmt->execute([':id_usuario' => $idUsuario]);

        if ($stmt->rowCount() > 0) {
            $stmtUsuario = $pdo->prepare("SELECT usu_codigo, usu_correo, id_rol FROM t_usuario WHERE id_usuario = :id_usuario LIMIT 1");
            $stmtUsuario->execute([':id_usuario' => $idUsuario]);
            $usuarioLog = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

            registrarBitacoraUsuario('usuario_activado', [
                'id_usuario' => $idUsuario,
                'usu_codigo' => $usuarioLog['usu_codigo'] ?? null,
                'usuario_correo' => $usuarioLog['usu_correo'] ?? null,
                'rol' => $usuarioLog['id_rol'] ?? null,
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
        }

        $_SESSION['usuarios_mensaje'] = $stmt->rowCount() > 0
            ? ['tipo' => 'success', 'texto' => 'El usuario fue activado correctamente.']
            : ['tipo' => 'warning', 'texto' => 'El usuario ya se encontraba activo.'];
    }

    header('Location: ' . urlUsuarios());
    exit;
}

$mensaje = $_SESSION['usuarios_mensaje'] ?? null;
unset($_SESSION['usuarios_mensaje']);

/* --------------------------------------------------------------------------
 | Filtros y paginación
 * -------------------------------------------------------------------------- */
$busqueda = trim($_GET['buscar'] ?? '');
$idRolFiltro = filter_input(INPUT_GET, 'rol', FILTER_VALIDATE_INT);
$estadoFiltro = strtoupper(trim($_GET['estado'] ?? ''));
$estadosPermitidos = ['A', 'B', 'I'];

if (!in_array($estadoFiltro, $estadosPermitidos, true)) {
    $estadoFiltro = '';
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = [];
$parametros = [];

/* Por defecto solo muestra activos y bloqueados. */
if ($estadoFiltro !== '') {
    $where[] = 'u.usu_estado = :estado';
    $parametros[':estado'] = $estadoFiltro;
} else {
    $where[] = "u.usu_estado IN ('A', 'B')";
}

if ($idRolFiltro) {
    $where[] = 'u.id_rol = :id_rol';
    $parametros[':id_rol'] = $idRolFiltro;
}

if ($busqueda !== '') {
    $terminoBusqueda = '%' . $busqueda . '%';

    $where[] = "(
        CAST(u.id_usuario AS CHAR) LIKE :busqueda_id
        OR u.usu_codigo LIKE :busqueda_codigo
        OR u.usu_nombre LIKE :busqueda_nombre
        OR u.usu_correo LIKE :busqueda_correo
        OR r.rol_nombre LIKE :busqueda_rol
        OR CASE u.usu_estado
            WHEN 'A' THEN 'Activo'
            WHEN 'B' THEN 'Bloqueado'
            WHEN 'I' THEN 'Inactivo'
            ELSE u.usu_estado
        END LIKE :busqueda_estado
    )";

    $parametros[':busqueda_id'] = $terminoBusqueda;
    $parametros[':busqueda_codigo'] = $terminoBusqueda;
    $parametros[':busqueda_nombre'] = $terminoBusqueda;
    $parametros[':busqueda_correo'] = $terminoBusqueda;
    $parametros[':busqueda_rol'] = $terminoBusqueda;
    $parametros[':busqueda_estado'] = $terminoBusqueda;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtRoles = $pdo->query(
    "SELECT id_rol, rol_nombre
     FROM t_rol
     WHERE rol_estado = 'A'
     ORDER BY rol_nombre"
);
$roles = $stmtRoles->fetchAll();

$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*)
     FROM t_usuario u
     INNER JOIN t_rol r ON r.id_rol = u.id_rol
     $whereSql"
);
$stmtTotal->execute($parametros);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$sqlUsuarios = "
    SELECT
        u.id_usuario,
        u.usu_codigo,
        u.usu_nombre,
        u.usu_correo,
        u.usu_estado,
        u.usu_intentofallido,
        u.id_rol,
        r.rol_nombre
    FROM t_usuario u
    INNER JOIN t_rol r ON r.id_rol = u.id_rol
    $whereSql
    ORDER BY u.usu_nombre, u.usu_codigo
    LIMIT :limite OFFSET :offset
";

$stmtUsuarios = $pdo->prepare($sqlUsuarios);
foreach ($parametros as $nombre => $valor) {
    $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmtUsuarios->bindValue($nombre, $valor, $tipo);
}
$stmtUsuarios->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtUsuarios->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll();

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalRegistros);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-people-fill"></i></span>
            <h1 class="mb-0">Gestión de Usuarios</h1>
        </div>
        <p class="mb-0">Administra los accesos, roles y perfiles del sistema.</p>
    </div>
    <a class="btn btn-light users-primary-action" href="usuario_form.php">
        <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
    </a>
</section>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= e($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<section class="users-card">
    <form method="get" class="users-filters" id="filtersForm">
        <div class="users-search flex-grow-1">
            <i class="bi bi-search"></i>
            <input
                type="search"
                name="buscar"
                value="<?= e($busqueda) ?>"
                placeholder="Buscar por ID, usuario, nombre, correo, rol o estado..."
                aria-label="Buscar usuario"
            >
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-person-lines-fill"></i>
            <select name="rol" aria-label="Filtrar por rol" onchange="this.form.submit()">
                <option value="">Todos los roles</option>
                <?php foreach ($roles as $rol): ?>
                    <option value="<?= (int)$rol['id_rol'] ?>" <?= (int)$idRolFiltro === (int)$rol['id_rol'] ? 'selected' : '' ?>>
                        <?= e($rol['rol_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-funnel-fill"></i>
            <select name="estado" aria-label="Filtrar por estado" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro === '' ? 'selected' : '' ?>>Activos y bloqueados</option>
                <option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activos</option>
                <option value="B" <?= $estadoFiltro === 'B' ? 'selected' : '' ?>>Bloqueados</option>
                <option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <?php if ($busqueda !== '' || $idRolFiltro || $estadoFiltro !== ''): ?>
            <a href="usuarios.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table users-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:72px"></th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$usuarios): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-secondary">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        No se encontraron usuarios con los filtros seleccionados.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($usuarios as $usuario): ?>
                <?php
                    $bloqueado = $usuario['usu_estado'] === 'B';
                    $icono = iconoRol($usuario['rol_nombre']);
                    $claseIcono = claseRol($usuario['rol_nombre']);
                ?>
                <tr>
                    <td>
                        <span class="role-icon <?= e($claseIcono) ?>" title="<?= e($usuario['rol_nombre']) ?>">
                            <i class="bi <?= e($icono) ?>"></i>
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($usuario['usu_nombre']) ?></div>
                        <small class="text-secondary">ID: <?= (int)$usuario['id_usuario'] ?></small>
                    </td>
                    <td>
                        <span class="user-code"><?= e($usuario['usu_codigo']) ?></span>
                    </td>
                    <td><?= e($usuario['usu_correo']) ?></td>
                    <td>
                        <span class="role-badge"><?= e($usuario['rol_nombre']) ?></span>
                    </td>
                    <td>
                        <?php if ($usuario['usu_estado'] === 'A'): ?>
                            <span class="status-badge status-active"><span></span>Activo</span>
                        <?php elseif ($usuario['usu_estado'] === 'B'): ?>
                            <span class="status-badge status-blocked"><i class="bi bi-lock-fill"></i>Bloqueado</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><span></span>Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a
                                class="action-btn action-edit"
                                href="usuario_form.php?id=<?= (int)$usuario['id_usuario'] ?>"
                                title="Modificar usuario"
                            >
                                <i class="bi bi-pencil-fill"></i>
                            </a>

                            <button
                                type="button"
                                class="action-btn action-unlock"
                                title="Desbloquear usuario"
                                <?= $bloqueado ? '' : 'disabled' ?>
                                data-bs-toggle="modal"
                                data-bs-target="#confirmActionModal"
                                data-action="desbloquear"
                                data-id="<?= (int)$usuario['id_usuario'] ?>"
                                data-name="<?= e($usuario['usu_nombre']) ?>"
                                data-code="<?= e($usuario['usu_codigo']) ?>"
                            >
                                <i class="bi bi-unlock-fill"></i>
                            </button>

                            <?php if ($usuario['usu_estado'] === 'I'): ?>
                                <button
                                    type="button"
                                    class="action-btn action-unlock"
                                    title="Activar usuario"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmActionModal"
                                    data-action="activar"
                                    data-id="<?= (int)$usuario['id_usuario'] ?>"
                                    data-name="<?= e($usuario['usu_nombre']) ?>"
                                    data-code="<?= e($usuario['usu_codigo']) ?>"
                                >
                                    <i class="bi bi-person-check-fill"></i>
                                </button>
                            <?php else: ?>
                                <button
                                    type="button"
                                    class="action-btn action-delete"
                                    title="Inactivar usuario"
                                    data-bs-toggle="modal"
                                    data-bs-target="#confirmActionModal"
                                    data-action="inactivar"
                                    data-id="<?= (int)$usuario['id_usuario'] ?>"
                                    data-name="<?= e($usuario['usu_nombre']) ?>"
                                    data-code="<?= e($usuario['usu_codigo']) ?>"
                                >
                                    <i class="bi bi-person-x-fill"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="users-footer">
        <div>
            Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $totalRegistros ?> resultados
        </div>

        <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Paginación de usuarios">
                <ul class="pagination users-pagination mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlUsuarios(['pagina' => $pagina - 1])) ?>" aria-label="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $inicioPagina = max(1, $pagina - 2);
                    $finPagina = min($totalPaginas, $pagina + 2);
                    ?>

                    <?php if ($inicioPagina > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlUsuarios(['pagina' => 1])) ?>">1</a></li>
                        <?php if ($inicioPagina > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicioPagina; $i <= $finPagina; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(urlUsuarios(['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($finPagina < $totalPaginas): ?>
                        <?php if ($finPagina < $totalPaginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlUsuarios(['pagina' => $totalPaginas])) ?>"><?= $totalPaginas ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlUsuarios(['pagina' => $pagina + 1])) ?>" aria-label="Siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>

<!-- Modal de confirmación. Sustituye window.confirm(), por lo que ya no aparece localhost:8080. -->
<div class="modal fade confirm-modal" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="confirm-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="confirm-modal-icon" id="confirmModalIcon">
                        <i class="bi bi-unlock-fill"></i>
                    </span>
                    <div>
                        <h4 class="mb-1" id="confirmModalTitle">Confirmar acción</h4>
                        <p class="mb-0 opacity-75">Revise la información antes de continuar.</p>
                    </div>
                </div>
            </div>

            <form method="post" id="confirmActionForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="accion" id="confirmAction">
                    <input type="hidden" name="id_usuario" id="confirmUserId">

                    <p class="mb-3" id="confirmModalQuestion"></p>

                    <div class="confirm-user-box">
                        <div class="fw-bold" id="confirmUserName"></div>
                        <small class="text-secondary">Usuario: <span id="confirmUserCode"></span></small>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-confirm-unlock" id="confirmSubmitButton">
                        <i class="bi bi-check-lg me-1"></i>
                        <span id="confirmSubmitText">Confirmar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('confirmActionModal');
    const actionInput = document.getElementById('confirmAction');
    const userIdInput = document.getElementById('confirmUserId');
    const title = document.getElementById('confirmModalTitle');
    const question = document.getElementById('confirmModalQuestion');
    const userName = document.getElementById('confirmUserName');
    const userCode = document.getElementById('confirmUserCode');
    const icon = document.getElementById('confirmModalIcon');
    const submitButton = document.getElementById('confirmSubmitButton');
    const submitText = document.getElementById('confirmSubmitText');

    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const action = button.dataset.action;
        const id = button.dataset.id;
        const name = button.dataset.name;
        const code = button.dataset.code;

        actionInput.value = action;
        userIdInput.value = id;
        userName.textContent = name;
        userCode.textContent = code;

        if (action === 'desbloquear') {
            title.textContent = 'Desbloquear usuario';
            question.textContent = '¿Desea desbloquear este usuario?';
            icon.innerHTML = '<i class="bi bi-unlock-fill"></i>';
            submitButton.className = 'btn btn-confirm-unlock';
            submitText.textContent = 'Sí, desbloquear';
        } else if (action === 'activar') {
            title.textContent = 'Activar usuario';
            question.textContent = '¿Desea activar este usuario?';
            icon.innerHTML = '<i class="bi bi-person-check-fill"></i>';
            submitButton.className = 'btn btn-confirm-unlock';
            submitText.textContent = 'Sí, activar';
        } else {
            title.textContent = 'Inactivar usuario';
            question.textContent = '¿Desea inactivar este usuario?';
            icon.innerHTML = '<i class="bi bi-person-x-fill"></i>';
            submitButton.className = 'btn btn-confirm-delete';
            submitText.textContent = 'Sí, inactivar';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
