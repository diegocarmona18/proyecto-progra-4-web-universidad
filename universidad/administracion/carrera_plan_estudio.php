<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Plan de Estudio';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idCarrera = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idCarrera) {
    $_SESSION['carreras_mensaje'] = [
        'tipo' => 'warning',
        'texto' => 'No fue posible identificar la carrera.'
    ];
    header('Location: carreras.php');
    exit;
}

/* Obtener la carrera seleccionada. */
$stmtCarrera = $pdo->prepare(
    "SELECT id_carrera, car_nombre, car_observacion, car_estado
     FROM t_carrera
     WHERE id_carrera = :id_carrera"
);
$stmtCarrera->execute([':id_carrera' => $idCarrera]);
$carrera = $stmtCarrera->fetch();

if (!$carrera) {
    $_SESSION['carreras_mensaje'] = [
        'tipo' => 'warning',
        'texto' => 'La carrera seleccionada no existe.'
    ];
    header('Location: carreras.php');
    exit;
}

/* Agregar o retirar un curso del plan. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $idCurso = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT);
        $idPeriodo = filter_input(INPUT_POST, 'id_perplan', FILTER_VALIDATE_INT);
        $codigo = trim($_POST['cac_codigo'] ?? '');
        $idDependencia = filter_input(INPUT_POST, 'cac_dependencia', FILTER_VALIDATE_INT);
        $idDependencia = $idDependencia !== false && $idDependencia !== null ? $idDependencia : null;

        if (!$idCurso || !$idPeriodo || $codigo === '') {
            $_SESSION['plan_mensaje'] = [
                'tipo' => 'danger',
                'texto' => 'Debe ingresar el código y seleccionar un curso y un cuatrimestre.'
            ];
        } elseif (mb_strlen($codigo, 'UTF-8') > 30) {
            $_SESSION['plan_mensaje'] = [
                'tipo' => 'danger',
                'texto' => 'El código no puede superar los 30 caracteres.'
            ];
        } else {
            /* Validar que el código no esté asignado a otro curso activo de la carrera. */
            $stmtCodigo = $pdo->prepare(
                "SELECT id_carreracurso
                 FROM t_carrera_curso
                 WHERE id_carrera = :id_carrera
                   AND cac_codigo = :cac_codigo
                   AND cac_estado = 'A'"
            );
            $stmtCodigo->execute([
                ':id_carrera' => $idCarrera,
                ':cac_codigo' => $codigo
            ]);

            /* La dependencia debe pertenecer al mismo plan y estar activa. */
            $dependenciaValida = true;
            if ($idDependencia !== null) {
                $stmtDependencia = $pdo->prepare(
                    "SELECT id_carreracurso
                     FROM t_carrera_curso
                     WHERE id_carreracurso = :id_carreracurso
                       AND id_carrera = :id_carrera
                       AND cac_estado = 'A'"
                );
                $stmtDependencia->execute([
                    ':id_carreracurso' => $idDependencia,
                    ':id_carrera' => $idCarrera
                ]);
                $dependenciaValida = (bool)$stmtDependencia->fetch();
            }

            /* Validar que el curso esté activo. */
            $stmtCurso = $pdo->prepare(
                "SELECT id_curso
                 FROM t_curso
                 WHERE id_curso = :id_curso
                   AND cur_estado = 'A'"
            );
            $stmtCurso->execute([':id_curso' => $idCurso]);

            /* Validar que el período esté activo. */
            $stmtPeriodo = $pdo->prepare(
                "SELECT id_perplan
                 FROM t_periodo_planestudio
                 WHERE id_perplan = :id_perplan
                   AND ppe_estado = 'A'"
            );
            $stmtPeriodo->execute([':id_perplan' => $idPeriodo]);

            if ($stmtCodigo->fetch()) {
                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'warning',
                    'texto' => 'El código indicado ya está asignado a otro curso de esta carrera.'
                ];
            } elseif (!$dependenciaValida) {
                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'danger',
                    'texto' => 'La materia seleccionada como requisito no pertenece al plan de estudio.'
                ];
            } elseif (!$stmtCurso->fetch()) {
                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'danger',
                    'texto' => 'El curso seleccionado no existe o está inactivo.'
                ];
            } elseif (!$stmtPeriodo->fetch()) {
                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'danger',
                    'texto' => 'El cuatrimestre seleccionado no existe o está inactivo.'
                ];
            } else {
                /* Revisar si el curso ya está activo dentro de la carrera. */
                $stmtExiste = $pdo->prepare(
                    "SELECT id_carreracurso
                     FROM t_carrera_curso
                     WHERE id_carrera = :id_carrera
                       AND id_curso = :id_curso
                       AND cac_estado = 'A'"
                );
                $stmtExiste->execute([
                    ':id_carrera' => $idCarrera,
                    ':id_curso' => $idCurso
                ]);

                if ($stmtExiste->fetch()) {
                    $_SESSION['plan_mensaje'] = [
                        'tipo' => 'warning',
                        'texto' => 'El curso seleccionado ya forma parte del plan de estudio.'
                    ];
                } else {
                    /* Si existía inactivo, se reactiva. Si no existe, se inserta. */
                    $stmtInactivo = $pdo->prepare(
                        "SELECT id_carreracurso
                         FROM t_carrera_curso
                         WHERE id_carrera = :id_carrera
                           AND id_curso = :id_curso
                           AND cac_estado = 'I'"
                    );
                    $stmtInactivo->execute([
                        ':id_carrera' => $idCarrera,
                        ':id_curso' => $idCurso
                    ]);
                    $registroInactivo = $stmtInactivo->fetch();

                    if ($registroInactivo) {
                        $stmtGuardar = $pdo->prepare(
                            "UPDATE t_carrera_curso
                             SET id_perplan = :id_perplan,
                                 cac_codigo = :cac_codigo,
                                 cac_dependencia = :cac_dependencia,
                                 cac_estado = 'A',
                                 cac_fechahorareg = NOW(),
                                 cac_usuarioreg = :cac_usuarioreg
                             WHERE id_carreracurso = :id_carreracurso"
                        );
                        $stmtGuardar->execute([
                            ':id_perplan' => $idPeriodo,
                            ':cac_codigo' => $codigo,
                            ':cac_dependencia' => $idDependencia,
                            ':cac_usuarioreg' => $_SESSION['id_usuario'] ?? null,
                            ':id_carreracurso' => $registroInactivo['id_carreracurso']
                        ]);
                    } else {
                        $stmtGuardar = $pdo->prepare(
                            "INSERT INTO t_carrera_curso (
                                id_carrera,
                                id_curso,
                                id_perplan,
                                cac_codigo,
                                cac_dependencia,
                                cac_estado,
                                cac_fechahorareg,
                                cac_usuarioreg
                             ) VALUES (
                                :id_carrera,
                                :id_curso,
                                :id_perplan,
                                :cac_codigo,
                                :cac_dependencia,
                                'A',
                                NOW(),
                                :cac_usuarioreg
                             )"
                        );
                        $stmtGuardar->execute([
                            ':id_carrera' => $idCarrera,
                            ':id_curso' => $idCurso,
                            ':id_perplan' => $idPeriodo,
                            ':cac_codigo' => $codigo,
                            ':cac_dependencia' => $idDependencia,
                            ':cac_usuarioreg' => $_SESSION['id_usuario'] ?? null
                        ]);
                    }

                    $_SESSION['plan_mensaje'] = [
                        'tipo' => 'success',
                        'texto' => 'El curso fue agregado correctamente al plan de estudio.'
                    ];
                }
            }
        }
    }

    if ($accion === 'retirar') {
        $idCarreraCurso = filter_input(INPUT_POST, 'id_carreracurso', FILTER_VALIDATE_INT);

        if ($idCarreraCurso) {
            $stmtUsoDependencia = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM t_carrera_curso
                 WHERE cac_dependencia = :id_carreracurso
                   AND id_carrera = :id_carrera
                   AND cac_estado = 'A'"
            );
            $stmtUsoDependencia->execute([
                ':id_carreracurso' => $idCarreraCurso,
                ':id_carrera' => $idCarrera
            ]);

            if ((int)$stmtUsoDependencia->fetchColumn() > 0) {
                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'warning',
                    'texto' => 'No puede retirar este curso porque es requisito de otra materia del plan.'
                ];
            } else {
                $stmtRetirar = $pdo->prepare(
                    "UPDATE t_carrera_curso
                     SET cac_estado = 'I'
                     WHERE id_carreracurso = :id_carreracurso
                       AND id_carrera = :id_carrera"
                );
                $stmtRetirar->execute([
                    ':id_carreracurso' => $idCarreraCurso,
                    ':id_carrera' => $idCarrera
                ]);

                $_SESSION['plan_mensaje'] = [
                    'tipo' => 'success',
                    'texto' => 'El curso fue retirado del plan de estudio.'
                ];
            }
        }
    }

    header('Location: carrera_plan_estudio.php?id=' . $idCarrera);
    exit;
}

$mensaje = $_SESSION['plan_mensaje'] ?? null;
unset($_SESSION['plan_mensaje']);

/* Cargar únicamente los períodos activos. */
$stmtPeriodos = $pdo->prepare(
    "SELECT id_perplan, ppe_nombre
     FROM t_periodo_planestudio
     WHERE ppe_estado = 'A'
     ORDER BY id_perplan"
);
$stmtPeriodos->execute();
$periodos = $stmtPeriodos->fetchAll();

/* Cargar cursos activos que todavía no están activos en esta carrera. */
$stmtCursos = $pdo->prepare(
    "SELECT id_curso, cur_nombre, cur_credito, cur_costo
     FROM t_curso
     WHERE cur_estado = 'A'
       AND id_curso NOT IN (
           SELECT id_curso
           FROM t_carrera_curso
           WHERE id_carrera = :id_carrera
             AND cac_estado = 'A'
       )
     ORDER BY cur_nombre"
);
$stmtCursos->execute([':id_carrera' => $idCarrera]);
$cursosDisponibles = $stmtCursos->fetchAll();

/* Cursos del plan que pueden utilizarse como dependencia. */
$stmtDependencias = $pdo->prepare(
    "SELECT cc.id_carreracurso, cc.cac_codigo, c.cur_nombre
     FROM t_carrera_curso cc
     INNER JOIN t_curso c ON c.id_curso = cc.id_curso
     WHERE cc.id_carrera = :id_carrera
       AND cc.cac_estado = 'A'
     ORDER BY c.cur_nombre"
);
$stmtDependencias->execute([':id_carrera' => $idCarrera]);
$dependenciasDisponibles = $stmtDependencias->fetchAll();

/* Cargar los cursos que ya forman parte del plan de estudio. */
$stmtPlan = $pdo->prepare(
    "SELECT
        cc.id_carreracurso,
        cc.id_perplan,
        cc.cac_codigo,
        cc.cac_dependencia,
        p.ppe_nombre,
        c.id_curso,
        c.cur_nombre,
        c.cur_credito,
        c.cur_costo,
        c.cur_observacion,
        dep.cac_codigo AS dependencia_codigo,
        curso_dep.cur_nombre AS dependencia_nombre
     FROM t_carrera_curso cc
     INNER JOIN t_curso c ON c.id_curso = cc.id_curso
     LEFT JOIN t_periodo_planestudio p ON p.id_perplan = cc.id_perplan
     LEFT JOIN t_carrera_curso dep ON dep.id_carreracurso = cc.cac_dependencia
     LEFT JOIN t_curso curso_dep ON curso_dep.id_curso = dep.id_curso
     WHERE cc.id_carrera = :id_carrera
       AND cc.cac_estado = 'A'
     ORDER BY cc.id_perplan, c.cur_nombre"
);
$stmtPlan->execute([':id_carrera' => $idCarrera]);
$cursosAsignados = $stmtPlan->fetchAll();

/* Agrupar los cursos por cuatrimestre usando PHP básico. */
$planAgrupado = [];
foreach ($cursosAsignados as $curso) {
    $nombrePeriodo = $curso['ppe_nombre'];
    if ($nombrePeriodo === null || trim($nombrePeriodo) === '') {
        $nombrePeriodo = 'Sin cuatrimestre asignado';
    }
    if (!isset($planAgrupado[$nombrePeriodo])) {
        $planAgrupado[$nombrePeriodo] = [];
    }
    $planAgrupado[$nombrePeriodo][] = $curso;
}

$totalCursos = count($cursosAsignados);
$totalCreditos = 0;
foreach ($cursosAsignados as $curso) {
    $totalCreditos += (int)$curso['cur_credito'];
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/plan_estudio.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero plan-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-journal-bookmark-fill"></i></span>
            <div>
                <h1 class="mb-0">Plan de Estudio</h1>
                <p class="mb-0 mt-1"><?= e($carrera['car_nombre']) ?></p>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap position-relative" style="z-index:1">
        <a href="carreras.php" class="btn btn-light">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
        <a href="plan_estudio_imprimir.php?id=<?= (int)$idCarrera ?>" target="_blank" class="btn btn-warning">
            <i class="bi bi-printer-fill me-2"></i>Imprimir plan
        </a>
    </div>
</section>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= e($mensaje['texto']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-4">
        <section class="plan-card add-course-card">
            <div class="plan-card-title">
                <span><i class="bi bi-plus-circle-fill"></i></span>
                <div>
                    <h4 class="mb-1">Agregar curso</h4>
                    <p class="mb-0 text-secondary">Seleccione un curso activo y el cuatrimestre.</p>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="accion" value="agregar">

                <div class="mb-4">
                    <label for="cac_codigo" class="form-label fw-semibold">Código de referencia</label>
                    <input type="text" class="form-control" id="cac_codigo" name="cac_codigo" maxlength="30" placeholder="Ej. MAT-101" required>
                    <div class="form-text">Código visible para identificar la materia dentro del plan.</div>
                </div>

                <div class="mb-4">
                    <label for="id_curso" class="form-label fw-semibold">Curso</label>
                    <select class="form-select" id="id_curso" name="id_curso" required>
                        <option value="">Seleccione un curso</option>
                        <?php foreach ($cursosDisponibles as $curso): ?>
                            <option value="<?= (int)$curso['id_curso'] ?>">
                                <?= e($curso['cur_nombre']) ?> - <?= (int)$curso['cur_credito'] ?> créditos
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$cursosDisponibles): ?>
                        <div class="form-text text-success">Todos los cursos activos ya fueron agregados.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="id_perplan" class="form-label fw-semibold">Cuatrimestre</label>
                    <select class="form-select" id="id_perplan" name="id_perplan" required>
                        <option value="">Seleccione un cuatrimestre</option>
                        <?php foreach ($periodos as $periodo): ?>
                            <option value="<?= (int)$periodo['id_perplan'] ?>">
                                <?= e($periodo['ppe_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="cac_dependencia" class="form-label fw-semibold">Materia requisito</label>
                    <select class="form-select" id="cac_dependencia" name="cac_dependencia">
                        <option value="">Sin requisito</option>
                        <?php foreach ($dependenciasDisponibles as $dependencia): ?>
                            <option value="<?= (int)$dependencia['id_carreracurso'] ?>">
                                <?= e($dependencia['cac_codigo']) ?> - <?= e($dependencia['cur_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Solo se muestran materias que ya pertenecen a este plan.</div>
                </div>

                <button type="submit" class="btn btn-primary w-100" <?= !$cursosDisponibles ? 'disabled' : '' ?>>
                    <i class="bi bi-plus-lg me-2"></i>Agregar al plan
                </button>
            </form>
        </section>

        <section class="plan-summary mt-4">
            <div>
                <span class="summary-icon"><i class="bi bi-book-fill"></i></span>
                <div><small>Cursos incluidos</small><strong><?= $totalCursos ?></strong></div>
            </div>
            <div>
                <span class="summary-icon"><i class="bi bi-award-fill"></i></span>
                <div><small>Total de créditos</small><strong><?= $totalCreditos ?></strong></div>
            </div>
        </section>
    </div>

    <div class="col-xl-8">
        <section class="plan-card">
            <div class="plan-card-title mb-4">
                <span><i class="bi bi-list-check"></i></span>
                <div>
                    <h4 class="mb-1">Cursos incluidos</h4>
                    <p class="mb-0 text-secondary">Información organizada por cuatrimestre.</p>
                </div>
            </div>

            <?php if (!$planAgrupado): ?>
                <div class="empty-plan">
                    <i class="bi bi-journal-x"></i>
                    <h5>El plan todavía no tiene cursos</h5>
                    <p>Utilice el formulario para agregar el primer curso.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($planAgrupado as $periodo => $cursos): ?>
                <div class="period-block">
                    <div class="period-header">
                        <div>
                            <i class="bi bi-calendar3"></i>
                            <strong><?= e($periodo) ?></strong>
                        </div>
                        <span><?= count($cursos) ?> curso<?= count($cursos) === 1 ? '' : 's' ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table plan-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:110px">Código</th>
                                    <th>Curso</th>
                                    <th>Requisito</th>
                                    <th class="text-center">Créditos</th>
                                    <th class="text-end">Costo</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><span class="badge bg-primary-subtle text-primary-emphasis"><?= e($curso['cac_codigo']) ?></span></td>
                                        <td>
                                            <div class="course-name"><i class="bi bi-book-half"></i><?= e($curso['cur_nombre']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($curso['dependencia_nombre'])): ?>
                                                <div class="small fw-semibold"><?= e($curso['dependencia_codigo']) ?></div>
                                                <small class="text-secondary"><?= e($curso['dependencia_nombre']) ?></small>
                                            <?php else: ?>
                                                <span class="text-secondary">Sin requisito</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><span class="credit-badge"><?= (int)$curso['cur_credito'] ?></span></td>
                                        <td class="text-end">₡<?= number_format((float)$curso['cur_costo'], 2, ',', '.') ?></td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#retirarCursoModal"
                                                data-id="<?= (int)$curso['id_carreracurso'] ?>"
                                                data-name="<?= e($curso['cur_nombre']) ?>"
                                            >
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</div>

<div class="modal fade confirm-modal" id="retirarCursoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="confirm-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="confirm-modal-icon"><i class="bi bi-trash3-fill"></i></span>
                    <div>
                        <h4 class="mb-1">Retirar curso</h4>
                        <p class="mb-0 opacity-75">El curso dejará de mostrarse en el plan.</p>
                    </div>
                </div>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <input type="hidden" name="accion" value="retirar">
                    <input type="hidden" name="id_carreracurso" id="retirarCarreraCursoId">
                    <p>¿Desea retirar el siguiente curso del plan de estudio?</p>
                    <div class="confirm-user-box">
                        <i class="bi bi-book-fill me-2"></i>
                        <strong id="retirarCursoNombre"></strong>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-confirm-delete">Retirar curso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('retirarCursoModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('retirarCarreraCursoId').value = button.getAttribute('data-id');
    document.getElementById('retirarCursoNombre').textContent = button.getAttribute('data-name');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
