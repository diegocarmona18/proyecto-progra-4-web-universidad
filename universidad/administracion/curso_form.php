<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idCurso = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esEdicion = $idCurso !== false && $idCurso !== null;
$pageTitle = $esEdicion ? 'Modificar Curso' : 'Nuevo Curso';
$errores = [];

$curso = [
    'id_curso' => '',
    'cur_nombre' => '',
    'cur_credito' => '',
    'cur_costo' => '',
    'cur_observacion' => '',
    'cur_estado' => 'A',
];

if ($esEdicion) {
    $stmt = $pdo->prepare(
        "SELECT
            id_curso,
            cur_nombre,
            cur_credito,
            cur_costo,
            cur_observacion,
            cur_estado
         FROM t_curso
         WHERE id_curso = :id_curso
         LIMIT 1"
    );
    $stmt->execute([':id_curso' => $idCurso]);
    $registro = $stmt->fetch();

    if (!$registro) {
        $_SESSION['cursos_mensaje'] = [
            'tipo' => 'warning',
            'texto' => 'El curso solicitado no existe.'
        ];
        header('Location: cursos.php');
        exit;
    }

    $curso = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_input(INPUT_POST, 'id_curso', FILTER_VALIDATE_INT);
    $esEdicion = $idPost !== false && $idPost !== null;
    $idCurso = $esEdicion ? $idPost : null;

    $credito = filter_input(INPUT_POST, 'cur_credito', FILTER_VALIDATE_INT);
    $costoTexto = str_replace(',', '.', trim($_POST['cur_costo'] ?? ''));
    $costo = filter_var($costoTexto, FILTER_VALIDATE_FLOAT);

    $curso = [
        'id_curso' => $idCurso ?? '',
        'cur_nombre' => trim($_POST['cur_nombre'] ?? ''),
        'cur_credito' => $credito !== false && $credito !== null ? $credito : '',
        'cur_costo' => $costo !== false ? $costoTexto : '',
        'cur_observacion' => trim($_POST['cur_observacion'] ?? ''),
        'cur_estado' => strtoupper(trim($_POST['cur_estado'] ?? 'A')),
    ];

    if ($curso['cur_nombre'] === '') {
        $errores[] = 'El nombre del curso es obligatorio.';
    } elseif (mb_strlen($curso['cur_nombre'], 'UTF-8') > 60) {
        $errores[] = 'El nombre no puede superar los 60 caracteres.';
    }

    if ($credito === false || $credito === null || $credito <= 0) {
        $errores[] = 'La cantidad de créditos debe ser un número entero mayor que cero.';
    }

    if ($costo === false || $costo < 0) {
        $errores[] = 'El costo debe ser un número válido igual o mayor que cero.';
    }

    if (mb_strlen($curso['cur_observacion'], 'UTF-8') > 255) {
        $errores[] = 'La observación no puede superar los 255 caracteres.';
    }

    if (!in_array($curso['cur_estado'], ['A', 'I'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    /* Validar que no exista otro curso con el mismo nombre. */
    if ($esEdicion) {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_curso
             FROM t_curso
             WHERE LOWER(cur_nombre) = LOWER(:cur_nombre)
               AND id_curso <> :id_curso
             LIMIT 1"
        );
        $stmtDuplicado->execute([
            ':cur_nombre' => $curso['cur_nombre'],
            ':id_curso' => $idCurso,
        ]);
    } else {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_curso
             FROM t_curso
             WHERE LOWER(cur_nombre) = LOWER(:cur_nombre)
             LIMIT 1"
        );
        $stmtDuplicado->execute([
            ':cur_nombre' => $curso['cur_nombre'],
        ]);
    }

    if ($stmtDuplicado->fetch()) {
        $errores[] = 'Ya existe un curso con el mismo nombre.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            if ($esEdicion) {
                $stmt = $pdo->prepare(
                    "UPDATE t_curso
                     SET cur_nombre = :cur_nombre,
                         cur_credito = :cur_credito,
                         cur_costo = :cur_costo,
                         cur_observacion = :cur_observacion,
                         cur_estado = :cur_estado
                     WHERE id_curso = :id_curso"
                );
                $stmt->execute([
                    ':cur_nombre' => $curso['cur_nombre'],
                    ':cur_credito' => $credito,
                    ':cur_costo' => $costo,
                    ':cur_observacion' => $curso['cur_observacion'] !== '' ? $curso['cur_observacion'] : null,
                    ':cur_estado' => $curso['cur_estado'],
                    ':id_curso' => $idCurso,
                ]);

                registrarBitacoraCurso('curso_actualizado', [
                    'curso_id' => $idCurso,
                    'cur_nombre' => $curso['cur_nombre'],
                    'cur_credito' => $credito,
                    'cur_costo' => $costo,
                    'cur_observacion' => $curso['cur_observacion'] !== '' ? $curso['cur_observacion'] : null,
                    'cur_estado' => $curso['cur_estado'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);

                $mensaje = 'El curso fue actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO t_curso (
                        cur_nombre,
                        cur_credito,
                        cur_costo,
                        cur_observacion,
                        cur_estado,
                        cur_fechahorareg,
                        cur_usuarioreg
                     ) VALUES (
                        :cur_nombre,
                        :cur_credito,
                        :cur_costo,
                        :cur_observacion,
                        :cur_estado,
                        NOW(),
                        :cur_usuarioreg
                     )"
                );
                $stmt->execute([
                    ':cur_nombre' => $curso['cur_nombre'],
                    ':cur_credito' => $credito,
                    ':cur_costo' => $costo,
                    ':cur_observacion' => $curso['cur_observacion'] !== '' ? $curso['cur_observacion'] : null,
                    ':cur_estado' => $curso['cur_estado'],
                    ':cur_usuarioreg' => $_SESSION['id_usuario'] ?? null,
                ]);

                $nuevoId = (int)$pdo->lastInsertId();
                registrarBitacoraCurso('curso_creado', [
                    'curso_id' => $nuevoId,
                    'cur_nombre' => $curso['cur_nombre'],
                    'cur_credito' => $credito,
                    'cur_costo' => $costo,
                    'cur_observacion' => $curso['cur_observacion'] !== '' ? $curso['cur_observacion'] : null,
                    'cur_estado' => $curso['cur_estado'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);

                $mensaje = 'El curso fue creado correctamente.';
            }

            $pdo->commit();

            $_SESSION['cursos_mensaje'] = [
                'tipo' => 'success',
                'texto' => $mensaje
            ];
            header('Location: cursos.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No fue posible guardar el curso. Verifique la información e intente nuevamente.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="user-form-hero mb-4">
    <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
        <span class="hero-form-icon">
            <i class="bi <?= $esEdicion ? 'bi-journal-bookmark-fill' : 'bi-plus-circle-fill' ?>"></i>
        </span>
        <div>
            <h1 class="h2 fw-bold mb-1"><?= $esEdicion ? 'Modificar curso' : 'Crear nuevo curso' ?></h1>
            <p class="mb-0 opacity-75">
                <?= $esEdicion
                    ? 'Actualice el nombre, créditos, costo, observación y estado del curso.'
                    : 'Registre un nuevo curso dentro de la oferta académica.' ?>
            </p>
        </div>
    </div>
</section>

<?php if ($errores): ?>
    <div class="alert alert-danger shadow-sm border-0 rounded-4" role="alert">
        <div class="d-flex gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>No fue posible guardar el curso:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="post" class="form-shell" autocomplete="off">
    <input type="hidden" name="id_curso" value="<?= e($curso['id_curso']) ?>">

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-journal-bookmark-fill"></i></span>
            <div>
                <h4 class="mb-1">Información del curso</h4>
                <p class="text-secondary mb-0">Datos principales que identificarán el curso dentro del sistema.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <label for="cur_nombre" class="form-label fw-semibold">Nombre del curso</label>
                <div class="field-wrap">
                    <i class="bi bi-journal-text"></i>
                    <input
                        type="text"
                        class="form-control"
                        id="cur_nombre"
                        name="cur_nombre"
                        maxlength="60"
                        value="<?= e($curso['cur_nombre']) ?>"
                        placeholder="Ej. Programación I"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="col-md-6">
                <label for="cur_credito" class="form-label fw-semibold">Créditos</label>
                <div class="field-wrap">
                    <i class="bi bi-award-fill"></i>
                    <input
                        type="number"
                        class="form-control"
                        id="cur_credito"
                        name="cur_credito"
                        min="1"
                        step="1"
                        value="<?= e($curso['cur_credito']) ?>"
                        placeholder="Ej. 3"
                        required
                    >
                </div>
            </div>

            <div class="col-md-6">
                <label for="cur_costo" class="form-label fw-semibold">Costo</label>
                <div class="field-wrap">
                    <i class="bi bi-cash-coin"></i>
                    <input
                        type="number"
                        class="form-control"
                        id="cur_costo"
                        name="cur_costo"
                        min="0"
                        step="0.01"
                        value="<?= e($curso['cur_costo']) ?>"
                        placeholder="Ej. 75000.00"
                        required
                    >
                </div>
                <div class="form-text">Digite el costo sin separadores de miles.</div>
            </div>

            <div class="col-12">
                <label for="cur_observacion" class="form-label fw-semibold">Observación</label>
                <textarea
                    class="form-control"
                    id="cur_observacion"
                    name="cur_observacion"
                    rows="5"
                    maxlength="255"
                    placeholder="Agregue una observación sobre el curso."
                ><?= e($curso['cur_observacion']) ?></textarea>
                <div class="form-text">Este campo es opcional. Máximo 255 caracteres.</div>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-toggle-on"></i></span>
            <div>
                <h4 class="mb-1">Estado</h4>
                <p class="text-secondary mb-0">Controle si el curso se encuentra disponible para su uso.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="status-option">
                    <input type="radio" name="cur_estado" value="A" <?= $curso['cur_estado'] === 'A' ? 'checked' : '' ?>>
                    <span class="status-card status-active-card">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <strong class="d-block">Activo</strong>
                            <small class="text-secondary">Disponible para procesos académicos.</small>
                        </span>
                    </span>
                </label>
            </div>

            <div class="col-md-6">
                <label class="status-option">
                    <input type="radio" name="cur_estado" value="I" <?= $curso['cur_estado'] === 'I' ? 'checked' : '' ?>>
                    <span class="status-card status-inactive-card">
                        <i class="bi bi-pause-circle-fill"></i>
                        <span>
                            <strong class="d-block">Inactivo</strong>
                            <small class="text-secondary">Se conserva, pero no estará disponible.</small>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="cursos.php" class="btn btn-cancel-user">
                <i class="bi bi-arrow-left me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save-user">
                <i class="bi bi-floppy-fill me-2"></i><?= $esEdicion ? 'Guardar cambios' : 'Crear curso' ?>
            </button>
        </div>
    </section>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
