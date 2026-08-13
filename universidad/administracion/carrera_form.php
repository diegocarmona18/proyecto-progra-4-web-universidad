<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idCarrera = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esEdicion = $idCarrera !== false && $idCarrera !== null;
$pageTitle = $esEdicion ? 'Modificar Carrera' : 'Nueva Carrera';
$errores = [];

/* Cargar los grados desde la base de datos. */
$stmtGrados = $pdo->prepare(
    "SELECT id_grado, gra_nombre
     FROM t_grado
     ORDER BY gra_nombre"
);
$stmtGrados->execute();
$grados = $stmtGrados->fetchAll();

$carrera = [
    'id_carrera' => '',
    'car_nombre' => '',
    'car_observacion' => '',
    'car_estado' => 'A',
    'id_grado' => '',
];

if ($esEdicion) {
    $stmt = $pdo->prepare(
        "SELECT id_carrera, car_nombre, car_observacion, car_estado, id_grado
         FROM t_carrera
         WHERE id_carrera = :id_carrera
         LIMIT 1"
    );
    $stmt->execute([':id_carrera' => $idCarrera]);
    $registro = $stmt->fetch();

    if (!$registro) {
        $_SESSION['carreras_mensaje'] = [
            'tipo' => 'warning',
            'texto' => 'La carrera solicitada no existe.'
        ];
        header('Location: carreras.php');
        exit;
    }

    $carrera = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_input(INPUT_POST, 'id_carrera', FILTER_VALIDATE_INT);
    $esEdicion = $idPost !== false && $idPost !== null;
    $idCarrera = $esEdicion ? $idPost : null;

    $idGrado = filter_input(INPUT_POST, 'id_grado', FILTER_VALIDATE_INT);
    $idGrado = $idGrado !== false && $idGrado !== null ? $idGrado : null;

    $carrera = [
        'id_carrera' => $idCarrera ?? '',
        'car_nombre' => trim($_POST['car_nombre'] ?? ''),
        'car_observacion' => trim($_POST['car_observacion'] ?? ''),
        'car_estado' => strtoupper(trim($_POST['car_estado'] ?? 'A')),
        'id_grado' => $idGrado ?? '',
    ];

    if ($carrera['car_nombre'] === '') {
        $errores[] = 'El nombre de la carrera es obligatorio.';
    } elseif (mb_strlen($carrera['car_nombre'], 'UTF-8') > 60) {
        $errores[] = 'El nombre no puede superar los 60 caracteres.';
    }

    if (mb_strlen($carrera['car_observacion'], 'UTF-8') > 255) {
        $errores[] = 'La descripción no puede superar los 255 caracteres.';
    }

    if (!in_array($carrera['car_estado'], ['A', 'I'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if ($idGrado === null) {
        $errores[] = 'Debe seleccionar un grado académico.';
    } else {
        $stmtGrado = $pdo->prepare(
            "SELECT id_grado
             FROM t_grado
             WHERE id_grado = :id_grado
             LIMIT 1"
        );
        $stmtGrado->execute([':id_grado' => $idGrado]);

        if (!$stmtGrado->fetch()) {
            $errores[] = 'El grado seleccionado no existe.';
        }
    }

    /* Validar que no exista otra carrera con el mismo nombre. */
    if ($esEdicion) {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_carrera
             FROM t_carrera
             WHERE LOWER(car_nombre) = LOWER(:car_nombre)
               AND id_carrera <> :id_carrera
             LIMIT 1"
        );
        $stmtDuplicado->execute([
            ':car_nombre' => $carrera['car_nombre'],
            ':id_carrera' => $idCarrera,
        ]);
    } else {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_carrera
             FROM t_carrera
             WHERE LOWER(car_nombre) = LOWER(:car_nombre)
             LIMIT 1"
        );
        $stmtDuplicado->execute([
            ':car_nombre' => $carrera['car_nombre'],
        ]);
    }

    if ($stmtDuplicado->fetch()) {
        $errores[] = 'Ya existe una carrera con el mismo nombre.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            if ($esEdicion) {
                $stmt = $pdo->prepare(
                    "UPDATE t_carrera
                     SET car_nombre = :car_nombre,
                         car_observacion = :car_observacion,
                         car_estado = :car_estado,
                         id_grado = :id_grado
                     WHERE id_carrera = :id_carrera"
                );
                $stmt->execute([
                    ':car_nombre' => $carrera['car_nombre'],
                    ':car_observacion' => $carrera['car_observacion'] !== '' ? $carrera['car_observacion'] : null,
                    ':car_estado' => $carrera['car_estado'],
                    ':id_grado' => $idGrado,
                    ':id_carrera' => $idCarrera,
                ]);

                registrarBitacoraCarrera('carrera_actualizada', [
                    'carrera_id' => $idCarrera,
                    'car_nombre' => $carrera['car_nombre'],
                    'id_grado' => $idGrado,
                    'grado_nombre' => $grados[0]['gra_nombre'] ?? null,
                    'car_observacion' => $carrera['car_observacion'] !== '' ? $carrera['car_observacion'] : null,
                    'car_estado' => $carrera['car_estado'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);

                $mensaje = 'La carrera fue actualizada correctamente.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO t_carrera (
                        car_nombre,
                        car_observacion,
                        car_estado,
                        car_fechahorareg,
                        car_usuarioreg,
                        id_grado
                     ) VALUES (
                        :car_nombre,
                        :car_observacion,
                        :car_estado,
                        NOW(),
                        :car_usuarioreg,
                        :id_grado
                     )"
                );
                $stmt->execute([
                    ':car_nombre' => $carrera['car_nombre'],
                    ':car_observacion' => $carrera['car_observacion'] !== '' ? $carrera['car_observacion'] : null,
                    ':car_estado' => $carrera['car_estado'],
                    ':car_usuarioreg' => $_SESSION['id_usuario'] ?? null,
                    ':id_grado' => $idGrado,
                ]);

                $nuevoId = (int)$pdo->lastInsertId();
                $gradoNombre = null;
                foreach ($grados as $grado) {
                    if ((int)$grado['id_grado'] === (int)$idGrado) {
                        $gradoNombre = $grado['gra_nombre'];
                        break;
                    }
                }

                registrarBitacoraCarrera('carrera_creada', [
                    'carrera_id' => $nuevoId,
                    'car_nombre' => $carrera['car_nombre'],
                    'id_grado' => $idGrado,
                    'grado_nombre' => $gradoNombre,
                    'car_observacion' => $carrera['car_observacion'] !== '' ? $carrera['car_observacion'] : null,
                    'car_estado' => $carrera['car_estado'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);

                $mensaje = 'La carrera fue creada correctamente.';
            }

            $pdo->commit();

            $_SESSION['carreras_mensaje'] = [
                'tipo' => 'success',
                'texto' => $mensaje
            ];
            header('Location: carreras.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No fue posible guardar la carrera. Verifique la información e intente nuevamente.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="user-form-hero mb-4">
    <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
        <span class="hero-form-icon">
            <i class="bi <?= $esEdicion ? 'bi-mortarboard-fill' : 'bi-plus-circle-fill' ?>"></i>
        </span>
        <div>
            <h1 class="h2 fw-bold mb-1"><?= $esEdicion ? 'Modificar carrera' : 'Crear nueva carrera' ?></h1>
            <p class="mb-0 opacity-75">
                <?= $esEdicion
                    ? 'Actualice el nombre, grado académico, descripción y estado de la carrera.'
                    : 'Registre una nueva carrera dentro de la oferta académica.' ?>
            </p>
        </div>
    </div>
</section>

<?php if ($errores): ?>
    <div class="alert alert-danger shadow-sm border-0 rounded-4" role="alert">
        <div class="d-flex gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>No fue posible guardar la carrera:</strong>
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
    <input type="hidden" name="id_carrera" value="<?= e($carrera['id_carrera']) ?>">

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-mortarboard-fill"></i></span>
            <div>
                <h4 class="mb-1">Información de la carrera</h4>
                <p class="text-secondary mb-0">Datos que identificarán la carrera dentro del sistema.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-7">
                <label for="car_nombre" class="form-label fw-semibold">Nombre de la carrera</label>
                <div class="field-wrap">
                    <i class="bi bi-buildings"></i>
                    <input
                        type="text"
                        class="form-control"
                        id="car_nombre"
                        name="car_nombre"
                        maxlength="60"
                        value="<?= e($carrera['car_nombre']) ?>"
                        placeholder="Ej. Ingeniería de Sistemas"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="col-md-5">
                <label for="id_grado" class="form-label fw-semibold">Grado académico</label>
                <div class="field-wrap">
                    <i class="bi bi-award-fill"></i>
                    <select class="form-select" id="id_grado" name="id_grado" required>
                        <option value="">Seleccione un grado</option>
                        <?php foreach ($grados as $grado): ?>
                            <option
                                value="<?= (int)$grado['id_grado'] ?>"
                                <?= (int)$carrera['id_grado'] === (int)$grado['id_grado'] ? 'selected' : '' ?>
                            >
                                <?= e($grado['gra_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="col-12">
                <label for="car_observacion" class="form-label fw-semibold">Descripción</label>
                <textarea
                    class="form-control"
                    id="car_observacion"
                    name="car_observacion"
                    rows="5"
                    maxlength="255"
                    placeholder="Describa brevemente el propósito o alcance de la carrera."
                ><?= e($carrera['car_observacion']) ?></textarea>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-toggle-on"></i></span>
            <div>
                <h4 class="mb-1">Estado</h4>
                <p class="text-secondary mb-0">Controle si la carrera se encuentra disponible para su uso.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="status-option">
                    <input type="radio" name="car_estado" value="A" <?= $carrera['car_estado'] === 'A' ? 'checked' : '' ?>>
                    <span class="status-card status-active-card">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <strong class="d-block">Activa</strong>
                            <small class="text-secondary">Disponible para procesos académicos.</small>
                        </span>
                    </span>
                </label>
            </div>

            <div class="col-md-6">
                <label class="status-option">
                    <input type="radio" name="car_estado" value="I" <?= $carrera['car_estado'] === 'I' ? 'checked' : '' ?>>
                    <span class="status-card status-inactive-card">
                        <i class="bi bi-pause-circle-fill"></i>
                        <span>
                            <strong class="d-block">Inactiva</strong>
                            <small class="text-secondary">Se conserva, pero no estará disponible.</small>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="carreras.php" class="btn btn-cancel-user">
                <i class="bi bi-arrow-left me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save-user">
                <i class="bi bi-floppy-fill me-2"></i><?= $esEdicion ? 'Guardar cambios' : 'Crear carrera' ?>
            </button>
        </div>
    </section>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
