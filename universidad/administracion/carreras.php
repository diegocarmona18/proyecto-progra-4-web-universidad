<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Gestión de Carreras';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlCarreras(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);

    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }

    return 'carreras.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

/* Activar o inactivar una carrera. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idCarrera = filter_input(INPUT_POST, 'id_carrera', FILTER_VALIDATE_INT);

    if (!$idCarrera) {
        $_SESSION['carreras_mensaje'] = [
            'tipo' => 'danger',
            'texto' => 'No fue posible identificar la carrera seleccionada.'
        ];
        header('Location: ' . urlCarreras());
        exit;
    }

    if ($accion === 'activar') {
        $stmt = $pdo->prepare(
            "UPDATE t_carrera
             SET car_estado = 'A'
             WHERE id_carrera = :id_carrera
               AND car_estado = 'I'"
        );
        $stmt->execute([':id_carrera' => $idCarrera]);

        if ($stmt->rowCount() > 0) {
            registrarBitacoraCarrera('carrera_activada', [
                'carrera_id' => $idCarrera,
                'car_estado' => 'A',
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $_SESSION['carreras_mensaje'] = ['tipo' => 'success', 'texto' => 'La carrera fue activada correctamente.'];
        } else {
            $_SESSION['carreras_mensaje'] = ['tipo' => 'warning', 'texto' => 'La carrera ya se encontraba activa.'];
        }
    } elseif ($accion === 'inactivar') {
        $stmt = $pdo->prepare(
            "UPDATE t_carrera
            SET car_estado = 'I'
            WHERE id_carrera = :id_carrera
            AND car_estado = 'A'"
        );
        $stmt->execute([':id_carrera' => $idCarrera]);

        if ($stmt->rowCount() > 0) {
            registrarBitacoraCarrera('carrera_inactivada', [
                'carrera_id' => $idCarrera,
                'car_estado' => 'I',
                'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
            ]);
            $_SESSION['carreras_mensaje'] = ['tipo' => 'success', 'texto' => 'La carrera fue inactivada correctamente.'];
        } else {
            $_SESSION['carreras_mensaje'] = ['tipo' => 'warning', 'texto' => 'La carrera ya se encontraba inactiva.'];
        }
    }

    header('Location: ' . urlCarreras());
    exit;
}

$mensaje = $_SESSION['carreras_mensaje'] ?? null;
unset($_SESSION['carreras_mensaje']);

/* Cargar grados para el filtro. */
$stmtGrados = $pdo->prepare(
    "SELECT id_grado, gra_nombre
    FROM t_grado
    ORDER BY gra_nombre"
);
$stmtGrados->execute();
$grados = $stmtGrados->fetchAll();

/* Filtros y paginación. */
$busqueda = trim($_GET['buscar'] ?? '');
$estadoFiltro = strtoupper(trim($_GET['estado'] ?? ''));
$idGradoFiltro = filter_input(INPUT_GET, 'grado', FILTER_VALIDATE_INT);
$idGradoFiltro = $idGradoFiltro !== false && $idGradoFiltro !== null ? $idGradoFiltro : null;

if (!in_array($estadoFiltro, ['A', 'I'], true)) {
    $estadoFiltro = '';
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = [];
$parametros = [];

/* Por defecto solo se muestran las carreras activas. */
if ($estadoFiltro !== '') {
    $where[] = 'c.car_estado = :estado';
    $parametros[':estado'] = $estadoFiltro;
} else {
    $where[] = "c.car_estado = 'A'";
}

if ($idGradoFiltro !== null) {
    $where[] = 'c.id_grado = :id_grado';
    $parametros[':id_grado'] = $idGradoFiltro;
}

if ($busqueda !== '') {
    $termino = '%' . $busqueda . '%';

    $where[] = "(
        CAST(c.id_carrera AS CHAR) LIKE :buscar_id
        OR c.car_nombre LIKE :buscar_nombre
        OR COALESCE(c.car_observacion, '') LIKE :buscar_observacion
        OR COALESCE(g.gra_nombre, '') LIKE :buscar_grado
        OR CASE c.car_estado
            WHEN 'A' THEN 'Activo'
            WHEN 'I' THEN 'Inactivo'
            ELSE c.car_estado
        END LIKE :buscar_estado
    )";

    $parametros[':buscar_id'] = $termino;
    $parametros[':buscar_nombre'] = $termino;
    $parametros[':buscar_observacion'] = $termino;
    $parametros[':buscar_grado'] = $termino;
    $parametros[':buscar_estado'] = $termino;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtTotal = $pdo->prepare(
    "SELECT COUNT(*)
     FROM t_carrera c
     LEFT JOIN t_grado g ON g.id_grado = c.id_grado
     $whereSql"
);
$stmtTotal->execute($parametros);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$stmtCarreras = $pdo->prepare(
    "SELECT
        c.id_carrera,
        c.car_nombre,
        c.car_observacion,
        c.car_estado,
        c.car_fechahorareg,
        c.car_usuarioreg,
        c.id_grado,
        g.gra_nombre
     FROM t_carrera c
     LEFT JOIN t_grado g ON g.id_grado = c.id_grado
     $whereSql
     ORDER BY c.car_nombre
     LIMIT :limite OFFSET :offset"
);

foreach ($parametros as $nombre => $valor) {
    $stmtCarreras->bindValue($nombre, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmtCarreras->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtCarreras->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtCarreras->execute();
$carreras = $stmtCarreras->fetchAll();

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalRegistros);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-mortarboard-fill"></i></span>
            <h1 class="mb-0">Gestión de Carreras</h1>
        </div>
        <p class="mb-0">Administra la oferta de carreras y el grado académico asociado.</p>
    </div>
    <a class="btn btn-light users-primary-action" href="carrera_form.php">
        <i class="bi bi-plus-circle-fill me-2"></i>Nueva Carrera
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
                placeholder="Buscar por ID, carrera, grado, descripción o estado..."
                aria-label="Buscar carrera"
            >
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-award-fill"></i>
            <select name="grado" aria-label="Filtrar por grado" onchange="this.form.submit()">
                <option value="">Todos los grados</option>
                <?php foreach ($grados as $grado): ?>
                    <option
                        value="<?= (int)$grado['id_grado'] ?>"
                        <?= $idGradoFiltro === (int)$grado['id_grado'] ? 'selected' : '' ?>
                    >
                        <?= e($grado['gra_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-funnel-fill"></i>
            <select name="estado" aria-label="Filtrar por estado" onchange="this.form.submit()">
                <option value="" <?= $estadoFiltro === '' ? 'selected' : '' ?>>Carreras activas</option>
                <option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activas</option>
                <option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivas</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <?php if ($busqueda !== '' || $estadoFiltro !== '' || $idGradoFiltro !== null): ?>
            <a href="carreras.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
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
                    <th>Grado</th>
                    <th>Descripción</th>
                    <th>Fecha de registro</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$carreras): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-secondary">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        No se encontraron carreras con los filtros seleccionados.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($carreras as $carrera): ?>
                <?php $activa = $carrera['car_estado'] === 'A'; ?>
                <tr>
                    <td>
                        <span class="role-icon role-profesor" title="Carrera universitaria">
                            <i class="bi bi-buildings-fill"></i>
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($carrera['car_nombre']) ?></div>
                        <small class="text-secondary">ID: <?= (int)$carrera['id_carrera'] ?></small>
                    </td>
                    <td>
                        <span class="role-badge role-badge-estudiante">
                            <?= $carrera['gra_nombre'] ? e($carrera['gra_nombre']) : 'Sin grado asignado' ?>
                        </span>
                    </td>
                    <td>
                        <?= $carrera['car_observacion'] !== null && trim($carrera['car_observacion']) !== ''
                            ? e($carrera['car_observacion'])
                            : '<span class="text-secondary">Sin descripción</span>' ?>
                    </td>
                    <td>
                        <?= $carrera['car_fechahorareg']
                            ? e(date('d/m/Y H:i', strtotime($carrera['car_fechahorareg'])))
                            : '<span class="text-secondary">—</span>' ?>
                    </td>
                    <td>
                        <?php if ($activa): ?>
                            <span class="status-badge status-active"><span></span>Activa</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><span></span>Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-2">
                            <a
                                class="action-btn action-edit bg-primary text-white"
                                href="carrera_form.php?id=<?= (int)$carrera['id_carrera'] ?>"
                                title="Modificar carrera"
                            >
                                <i class="bi bi-pencil-fill"></i>
                            </a>

                            <a
                                class="action-btn bg-warning text-dark"
                                href="carrera_plan_estudio.php?id=<?= (int)$carrera['id_carrera'] ?>"
                                title="Administrar plan de estudio"
                            >
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </a>

                            <button
                                type="button"
                                class="action-btn <?= $activa ? 'action-delete bg-danger text-white' : 'action-unlock bg-success text-white' ?>"
                                title="<?= $activa ? 'Inactivar carrera' : 'Activar carrera' ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmActionModal"
                                data-action="<?= $activa ? 'inactivar' : 'activar' ?>"
                                data-id="<?= (int)$carrera['id_carrera'] ?>"
                                data-name="<?= e($carrera['car_nombre']) ?>"
                            >
                                <i class="bi <?= $activa ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?>"></i>
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
            <nav aria-label="Paginación de carreras">
                <ul class="pagination users-pagination mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlCarreras(['pagina' => $pagina - 1])) ?>" aria-label="Anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $inicioPagina = max(1, $pagina - 2);
                    $finPagina = min($totalPaginas, $pagina + 2);
                    ?>

                    <?php if ($inicioPagina > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlCarreras(['pagina' => 1])) ?>">1</a></li>
                        <?php if ($inicioPagina > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicioPagina; $i <= $finPagina; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(urlCarreras(['pagina' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($finPagina < $totalPaginas): ?>
                        <?php if ($finPagina < $totalPaginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= e(urlCarreras(['pagina' => $totalPaginas])) ?>"><?= $totalPaginas ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(urlCarreras(['pagina' => $pagina + 1])) ?>" aria-label="Siguiente">
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
                    <input type="hidden" name="id_carrera" id="confirmCarreraId">

                    <p class="mb-3" id="confirmModalText"></p>
                    <div class="confirm-user-box">
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        <strong id="confirmCarreraName"></strong>
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
    document.getElementById('confirmCarreraId').value = id;
    document.getElementById('confirmCarreraName').textContent = name;

    if (action === 'activar') {
        title.textContent = 'Activar carrera';
        text.textContent = '¿Desea activar esta carrera para que vuelva a estar disponible?';
        icon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        submit.textContent = 'Activar carrera';
        submit.className = 'btn btn-confirm-unlock';
    } else {
        title.textContent = 'Inactivar carrera';
        text.textContent = '¿Desea inactivar esta carrera? No se eliminará de la base de datos.';
        icon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
        submit.textContent = 'Inactivar carrera';
        submit.className = 'btn btn-confirm-delete';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
