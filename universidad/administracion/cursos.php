<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Gestión de Cursos';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlCursos(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);

    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }

    return 'cursos.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

/* Activar o inactivar un curso. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idCurso = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT);

    if (!$idCurso) {
        $_SESSION['cursos_mensaje'] = [
            'tipo' => 'danger',
            'texto' => 'No fue posible identificar el curso seleccionado.'
        ];
        header('Location: ' . urlCursos());
        exit;
    }

    if ($accion === 'activar') {
        $stmt = $pdo->prepare(
            "UPDATE t_curso
             SET cur_estado = 'A'
             WHERE id_curso = :id_curso
               AND cur_estado = 'I'"
        );
        $stmt->execute([':id_curso' => $idCurso]);

        if ($stmt->rowCount() > 0) {
            registrarBitacoraCurso('curso_activado', [
                'curso_id' => $idCurso,
                'cur_estado' => 'A',
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $_SESSION['cursos_mensaje'] = ['tipo' => 'success', 'texto' => 'El curso fue activado correctamente.'];
        } else {
            $_SESSION['cursos_mensaje'] = ['tipo' => 'warning', 'texto' => 'El curso ya se encontraba activo.'];
        }
    } elseif ($accion === 'inactivar') {
        $stmt = $pdo->prepare(
            "UPDATE t_curso
             SET cur_estado = 'I'
             WHERE id_curso = :id_curso
               AND cur_estado = 'A'"
        );
        $stmt->execute([':id_curso' => $idCurso]);

        if ($stmt->rowCount() > 0) {
            registrarBitacoraCurso('curso_inactivado', [
                'curso_id' => $idCurso,
                'cur_estado' => 'I',
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $_SESSION['cursos_mensaje'] = ['tipo' => 'success', 'texto' => 'El curso fue inactivado correctamente.'];
        } else {
            $_SESSION['cursos_mensaje'] = ['tipo' => 'warning', 'texto' => 'El curso ya se encontraba inactivo.'];
        }
    }

    header('Location: ' . urlCursos());
    exit;
}

$mensaje = $_SESSION['cursos_mensaje'] ?? null;
unset($_SESSION['cursos_mensaje']);

/* Filtros y paginación. */
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

/* Por defecto solo se muestran cursos activos. */
if ($estadoFiltro !== '') {
    $where[] = 'c.cur_estado = :estado';
    $parametros[':estado'] = $estadoFiltro;
} else {
    $where[] = "c.cur_estado = 'A'";
}

if ($busqueda !== '') {
    $termino = '%' . $busqueda . '%';

    $where[] = "(
        CAST(c.id_curso AS CHAR) LIKE :buscar_id
        OR c.cur_nombre LIKE :buscar_nombre
        OR COALESCE(c.cur_observacion, '') LIKE :buscar_observacion
        OR CAST(c.cur_credito AS CHAR) LIKE :buscar_credito
        OR CAST(c.cur_costo AS CHAR) LIKE :buscar_costo
        OR CASE c.cur_estado
            WHEN 'A' THEN 'Activo'
            WHEN 'I' THEN 'Inactivo'
            ELSE c.cur_estado
        END LIKE :buscar_estado
    )";

    $parametros[':buscar_id'] = $termino;
    $parametros[':buscar_nombre'] = $termino;
    $parametros[':buscar_observacion'] = $termino;
    $parametros[':buscar_credito'] = $termino;
    $parametros[':buscar_costo'] = $termino;
    $parametros[':buscar_estado'] = $termino;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*)
     FROM t_curso c
     $whereSql"
);
$stmtTotal->execute($parametros);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$stmtCursos = $pdo->prepare(
    "SELECT
        c.id_curso,
        c.cur_nombre,
        c.cur_credito,
        c.cur_costo,
        c.cur_observacion,
        c.cur_estado,
        c.cur_fechahorareg,
        c.cur_usuarioreg
     FROM t_curso c
     $whereSql
     ORDER BY c.cur_nombre
     LIMIT :limite OFFSET :offset"
);

foreach ($parametros as $nombre => $valor) {
    $stmtCursos->bindValue($nombre, $valor, PDO::PARAM_STR);
}
$stmtCursos->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtCursos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtCursos->execute();
$cursos = $stmtCursos->fetchAll();

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalRegistros);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            <h1 class="mb-0">Gestión de Cursos</h1>
        </div>
        <p class="mb-0">Administra los cursos, créditos, costos y disponibilidad.</p>
    </div>
    <a class="btn btn-light users-primary-action" href="curso_form.php">
        <i class="bi bi-plus-circle-fill me-2"></i>Nuevo Curso
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
            <input
                type="search"
                name="buscar"
                value="<?= e($busqueda) ?>"
                placeholder="Buscar por ID, nombre, créditos, costo, observación o estado..."
                aria-label="Buscar curso"
            >
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-funnel-fill"></i>
            <select name="estado" aria-label="Filtrar por estado" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro === '' ? 'selected' : '' ?>>Cursos activos</option>
                <option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activos</option>
                <option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <?php if ($busqueda !== '' || $estadoFiltro !== ''): ?>
            <a href="cursos.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
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
                    <th>Créditos</th>
                    <th>Costo</th>
                    <th>Observación</th>
                    <th>Fecha de registro</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$cursos): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-secondary">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        No se encontraron cursos con los filtros seleccionados.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($cursos as $curso): ?>
                <?php $activo = $curso['cur_estado'] === 'A'; ?>
                <tr>
                    <td>
                        <span class="role-icon role-profesor" title="Curso">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($curso['cur_nombre']) ?></div>
                        <small class="text-secondary">ID: <?= (int)$curso['id_curso'] ?></small>
                    </td>
                    <td>
                        <span class="badge rounded-pill text-bg-light border">
                            <?= (int)$curso['cur_credito'] ?>
                        </span>
                    </td>
                    <td>₡<?= number_format((float)$curso['cur_costo'], 2, ',', '.') ?></td>
                    <td>
                        <?= $curso['cur_observacion'] !== null && trim($curso['cur_observacion']) !== ''
                            ? e($curso['cur_observacion'])
                            : '<span class="text-secondary">Sin observación</span>' ?>
                    </td>
                    <td>
                        <?= $curso['cur_fechahorareg']
                            ? e(date('d/m/Y H:i', strtotime($curso['cur_fechahorareg'])))
                            : '<span class="text-secondary">—</span>' ?>
                    </td>
                    <td>
                        <?php if ($activo): ?>
                            <span class="status-badge status-active"><span></span>Activo</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><span></span>Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a
                                class="action-btn action-edit"
                                href="curso_form.php?id=<?= (int)$curso['id_curso'] ?>"
                                title="Modificar curso"
                            >
                                <i class="bi bi-pencil-fill"></i>
                            </a>

                            <button
                                type="button"
                                class="action-btn <?= $activo ? 'action-delete' : 'action-unlock' ?>"
                                title="<?= $activo ? 'Inactivar curso' : 'Activar curso' ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmActionModal"
                                data-action="<?= $activo ? 'inactivar' : 'activar' ?>"
                                data-id="<?= (int)$curso['id_curso'] ?>"
                                data-name="<?= e($curso['cur_nombre']) ?>"
                            >
                                <i class="bi <?= $activo ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?>"></i>
                            </button>
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
            <nav aria-label="Paginación de cursos">
                <ul class="pagination users-pagination mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlCursos(['pagina' => $pagina - 1])) ?>" aria-label="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $inicioPagina = max(1, $pagina - 2);
                    $finPagina = min($totalPaginas, $pagina + 2);
                    ?>

                    <?php if ($inicioPagina > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlCursos(['pagina' => 1])) ?>">1</a></li>
                        <?php if ($inicioPagina > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicioPagina; $i <= $finPagina; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(urlCursos(['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($finPagina < $totalPaginas): ?>
                        <?php if ($finPagina < $totalPaginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlCursos(['pagina' => $totalPaginas])) ?>"><?= $totalPaginas ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlCursos(['pagina' => $pagina + 1])) ?>" aria-label="Siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade confirm-modal" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="confirm-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="confirm-modal-icon" id="confirmModalIcon">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                    <div>
                        <h4 class="mb-1" id="confirmModalTitle">Confirmar acción</h4>
                        <p class="mb-0 opacity-75">Revise la información antes de continuar.</p>
                    </div>
                </div>
            </div>

            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="accion" id="confirmAction">
                    <input type="hidden" name="id_curso" id="confirmCursoId">

                    <p class="mb-3" id="confirmModalText"></p>
                    <div class="confirm-user-box">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>
                        <strong id="confirmCursoName"></strong>
                    </div>
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
    const id = button.getAttribute('data-id');
    const name = button.getAttribute('data-name');

    const title = document.getElementById('confirmModalTitle');
    const text = document.getElementById('confirmModalText');
    const icon = document.getElementById('confirmModalIcon');
    const submit = document.getElementById('confirmSubmitButton');

    document.getElementById('confirmAction').value = action;
    document.getElementById('confirmCursoId').value = id;
    document.getElementById('confirmCursoName').textContent = name;

    if (action === 'activar') {
        title.textContent = 'Activar curso';
        text.textContent = '¿Desea activar este curso para que vuelva a estar disponible?';
        icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        submit.textContent = 'Activar curso';
        submit.className = 'btn btn-confirm-unlock';
    } else {
        title.textContent = 'Inactivar curso';
        text.textContent = '¿Desea inactivar este curso? No se eliminará de la base de datos.';
        icon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
        submit.textContent = 'Inactivar curso';
        submit.className = 'btn btn-confirm-delete';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
