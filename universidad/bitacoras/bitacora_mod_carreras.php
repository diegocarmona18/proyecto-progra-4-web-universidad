<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

if (!function_exists('e')) {
    function e($valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== basename(__FILE__)) {
    return;
}

$pageTitle = 'Bitácora de Carreras';
$busqueda = trim((string)($_GET['buscar'] ?? ''));
$accionFiltro = trim((string)($_GET['accion'] ?? ''));
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';
$paginaActual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
if ($paginaActual === false || $paginaActual === null || $paginaActual < 1) {
    $paginaActual = 1;
}
$paginaActual = (int) $paginaActual;
$registrosPorPagina = 5;
$logRows = [];
$logError = '';
$totalRegistros = 0;
$totalPaginas = 1;
$accionesPermitidas = [
    'carrera_creada',
    'carrera_actualizada',
    'carrera_activada',
    'carrera_inactivada',
    'carrera_eliminada',
    'carrera_eliminada_intentada',
    'carrera_inactivacion_intentada',
];

try {
    $tableExists = false;
    $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_carreras'");
    $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;

    if ($tableExists) {
        $sqlCount = "SELECT COUNT(*) FROM bitacora_carreras";
        $sqlData = "SELECT * FROM bitacora_carreras";
        $params = [];

        if ($busqueda !== '') {
            $condicion = " WHERE carrera_id LIKE :buscar OR car_nombre LIKE :buscar OR grado_nombre LIKE :buscar OR car_observacion LIKE :buscar OR realizado_por LIKE :buscar OR accion LIKE :buscar";
            $sqlCount .= $condicion;
            $sqlData .= $condicion;
            $params[':buscar'] = '%' . $busqueda . '%';
        }

        if ($accionFiltro !== '') {
            if (in_array($accionFiltro, $accionesPermitidas, true)) {
                $sqlCount .= ($busqueda !== '' ? ' AND ' : ' WHERE ') . ' accion = :accion_filter';
                $sqlData .= ($busqueda !== '' ? ' AND ' : ' WHERE ') . ' accion = :accion_filter';
                $params[':accion_filter'] = $accionFiltro;
            }
        }

        if ($exportCsv) {
            $sqlExport = $sqlData . " ORDER BY id DESC";
            $stmtExport = $pdo->prepare($sqlExport);
            foreach ($params as $key => $value) {
                $stmtExport->bindValue($key, $value);
            }
            $stmtExport->execute();
            $exportRows = $stmtExport->fetchAll(PDO::FETCH_ASSOC);

            $exportRows = array_values(array_filter($exportRows, function ($fila) use ($accionesPermitidas) {
                $accion = (string) ($fila['accion'] ?? '');
                return in_array($accion, $accionesPermitidas, true);
            }));

            if ($busqueda !== '') {
                $termino = mb_strtolower($busqueda, 'UTF-8');
                $exportRows = array_values(array_filter($exportRows, function ($fila) use ($termino) {
                    $texto = implode(' ', [
                        $fila['carrera_id'] ?? '',
                        $fila['car_nombre'] ?? '',
                        $fila['grado_nombre'] ?? '',
                        $fila['car_observacion'] ?? '',
                        $fila['realizado_por'] ?? '',
                        $fila['accion'] ?? '',
                        $fila['fecha'] ?? '',
                    ]);
                    return mb_stripos(mb_strtolower($texto, 'UTF-8'), $termino, 0, 'UTF-8') !== false;
                }));
            }

            $filename = 'bitacora_carreras_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['fecha', 'carrera_id', 'car_nombre', 'grado_nombre', 'car_estado', 'realizado_por', 'accion']);
            foreach ($exportRows as $row) {
                fputcsv($output, [
                    $row['fecha'] ?? $row['fechahora'] ?? '',
                    $row['carrera_id'] ?? '',
                    $row['car_nombre'] ?? '',
                    $row['grado_nombre'] ?? '',
                    $row['car_estado'] ?? '',
                    $row['realizado_por'] ?? '',
                    $row['accion'] ?? '',
                ]);
            }
            fclose($output);
            exit;
        }

        $stmtTotal = $pdo->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value);
        }
        $stmtTotal->execute();
        $totalRegistros = (int) $stmtTotal->fetchColumn();

        $sqlData .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sqlData);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $registrosPorPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($paginaActual - 1) * $registrosPorPagina, PDO::PARAM_INT);
        $stmt->execute();
        $logRows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $logRows = array_values(array_filter($logRows, function ($fila) use ($accionesPermitidas) {
            $accion = (string) ($fila['accion'] ?? '');
            return in_array($accion, $accionesPermitidas, true);
        }));

        if ($busqueda !== '') {
            $termino = mb_strtolower($busqueda, 'UTF-8');
            $logRows = array_values(array_filter($logRows, function ($fila) use ($termino) {
                $texto = implode(' ', [
                    $fila['carrera_id'] ?? '',
                    $fila['car_nombre'] ?? '',
                    $fila['grado_nombre'] ?? '',
                    $fila['car_observacion'] ?? '',
                    $fila['realizado_por'] ?? '',
                    $fila['accion'] ?? '',
                    $fila['fecha'] ?? '',
                ]);
                return mb_stripos(mb_strtolower($texto, 'UTF-8'), $termino, 0, 'UTF-8') !== false;
            }));
            $totalRegistros = count($logRows);
        }
    }

    $totalPaginas = max(1, (int) ceil((int) $totalRegistros / $registrosPorPagina));
} catch (Throwable $th) {
    $logError = 'No se pudo cargar la bitácora de carreras en este momento.';
    $logRows = [];
    $totalPaginas = 1;
    $totalRegistros = 0;
}

include __DIR__ . '/../includes/header.php';
?>

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-mortarboard-fill"></i></span>
            <h1 class="mb-0">Bitácora de Carreras</h1>
        </div>
        <p class="mb-0">Consulta la creación, actualización, activación, inactivación y eliminación de carreras.</p>
    </div>
</section>

<?php if ($logError !== ''): ?>
    <div class="alert alert-warning shadow-sm" role="alert">
        <?= e($logError) ?>
    </div>
<?php endif; ?>

<section class="users-card">
    <form method="get" class="users-filters">
        <div class="users-search flex-grow-1">
            <i class="bi bi-search"></i>
            <input type="search" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar por carrera, grado, descripción, acción o usuario...">
        </div>

        <div class="users-select-wrap">
            <i class="bi bi-filter"></i>
            <select name="accion" onchange="this.form.submit()">
                <option value="">Todas las acciones</option>
                <?php foreach ($accionesPermitidas as $act): ?>
                    <option value="<?= e($act) ?>" <?= $accionFiltro === $act ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $act))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <a href="?<?= e(http_build_query(array_filter(['buscar'=> $busqueda, 'accion'=>$accionFiltro, 'export'=>'csv']))) ?>" class="btn btn-outline-secondary rounded-3" title="Exportar CSV">
            <i class="bi bi-download"></i>
        </a>

        <?php if ($busqueda !== '' || $accionFiltro !== ''): ?>
            <a href="bitacora_mod_carreras.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table users-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Carrera</th>
                    <th>Grado</th>
                    <th>Descripción</th>
                    <th>Realizado por</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logRows === []): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">No hay registros en la bitácora de carreras.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logRows as $registro): ?>
                        <?php
                        $fecha = $registro['fecha'] ?? $registro['created_at'] ?? $registro['fechahora'] ?? null;
                        $carreraNombre = $registro['car_nombre'] ?? ($registro['carrera_id'] ?? 'Sin nombre');
                        $gradoNombre = $registro['grado_nombre'] ?? 'Sin grado';
                        $descripcion = $registro['car_observacion'] ?? 'Sin descripción';
                        $realizadoPor = $registro['realizado_por'] ?? 'Sistema';
                        $accion = $registro['accion'] ?? 'carrera';
                        $accion = str_replace('_', ' ', $accion);
                        ?>
                        <tr>
                            <td>
                                <span class="fw-semibold">
                                    <?= e($fecha ? date('d/m/Y H:i:s', strtotime((string)$fecha)) : 'Sin fecha') ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php if (!empty($registro['carrera_id'])): ?><a href="<?= e('../administracion/carrera_form.php?id=' . (int)$registro['carrera_id']) ?>"><?= e((string)$carreraNombre) ?></a><?php else: ?><?= e((string)$carreraNombre) ?><?php endif; ?></div>
                            </td>
                            <td>
                                <small><?= e((string)$gradoNombre) ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= e((string)$descripcion) ?></small>
                            </td>
                            <td><?= e((string)$realizadoPor) ?></td>
                            <td>
                                <span class="status-badge status-active">
                                    <span></span>
                                    <?= e(ucwords($accion)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (($totalRegistros ?? 0) > 0): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="small text-secondary">
                Mostrando <?= count($logRows) ?> de <?= (int)($totalRegistros ?? 0) ?> registros
            </div>
            <nav aria-label="Paginación de bitácora de carreras">
                <ul class="pagination mb-0">
                    <?php $prevPagina = max(1, (int) $paginaActual - 1); ?>
                    <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e('bitacora_mod_carreras.php?buscar=' . urlencode($busqueda) . '&pagina=' . $prevPagina) ?>">Anterior</a>
                    </li>

                    <?php for ($i = 1; $i <= (int) $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i === $paginaActual ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e('bitacora_mod_carreras.php?buscar=' . urlencode($busqueda) . '&pagina=' . $i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php $nextPagina = min((int) $totalPaginas, (int) $paginaActual + 1); ?>
                    <li class="page-item <?= $paginaActual >= (int) $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e('bitacora_mod_carreras.php?buscar=' . urlencode($busqueda) . '&pagina=' . $nextPagina) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
