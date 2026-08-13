<?php
require_once __DIR__ . '/config.php';
requireLogin();

$pageTitle = 'Inicio';
$idRol = (int)($_SESSION['id_rol'] ?? 0);
$nombreUsuario = $_SESSION['usu_nombre'] ?? 'Usuario';
$nombreRol = $_SESSION['rol_nombre'] ?? 'Usuario';

/*
|--------------------------------------------------------------------------
| Totales de registros activos
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("SELECT COUNT(*) FROM t_estudiante WHERE est_estado = 'A'");
$totalEstudiantes = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM t_profesor WHERE pro_estado = 'A'");
$totalProfesores = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM t_carrera WHERE car_estado = 'A'");
$totalCarreras = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM t_curso WHERE cur_estado = 'A'");
$totalCursos = (int)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Accesos rápidos permitidos para el rol
|--------------------------------------------------------------------------
| Solo se muestran módulos activos, asignados al rol y que tengan una URL.
*/
 $stmtAccesos = $pdo->prepare(
    "SELECT
        m.mod_nombre,
        m.mod_url,
        m.mod_icono
     FROM t_modulo_rol mr
     INNER JOIN t_modulo m
        ON m.id_modulo = mr.id_modulo
     WHERE mr.id_rol = :id_rol
       AND mr.mor_estado = 'A'
       AND m.mod_estado = 'A'
       AND m.mod_url IS NOT NULL
       AND m.mod_url <> ''
     ORDER BY m.mod_nombre"
 );
 $stmtAccesos->execute([
     ':id_rol' => $idRol
 ]);
 $accesosRapidos = $stmtAccesos->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<section class="hero mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <span class="hero-badge mb-3">
                <i class="bi bi-stars"></i>
                Panel <?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <h1 class="fw-bold">
                Bienvenido, <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?> 👋
            </h1>

                <p class="mb-0 opacity-75 fs-5">
                Consulte la información principal del sistema académico de <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?>.
            </p>
        </div>
    </div>
</section>

<section class="mb-4">
    <h5 class="section-title fw-bold mb-3">Resumen general</h5>

    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-secondary">Estudiantes activos</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= number_format($totalEstudiantes) ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-secondary">Profesores activos</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= number_format($totalProfesores) ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-person-video3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-secondary">Carreras activas</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= number_format($totalCarreras) ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-secondary">Cursos activos</small>
                        <h3 class="fw-bold mt-2 mb-0"><?= number_format($totalCursos) ?></h3>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section>
    <h5 class="section-title fw-bold mb-3">Accesos rápidos</h5>

    <?php if ($accesosRapidos): ?>
        <div class="row g-4">
            <?php foreach ($accesosRapidos as $acceso): ?>
                <?php
                $icono = trim((string)($acceso['mod_icono'] ?? ''));
                if ($icono === '') {
                    $icono = 'bi-box-arrow-up-right';
                }
                ?>

                <div class="col-md-6 col-xl-4">
                    <a class="quick-card" href="<?= htmlspecialchars(url($acceso['mod_url']), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="quick-icon">
                            <i class="bi <?= htmlspecialchars($icono, ENT_QUOTES, 'UTF-8') ?>"></i>
                        </div>

                        <h5 class="mt-3 fw-bold">
                            <?= htmlspecialchars($acceso['mod_nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </h5>

                        <p class="text-secondary mb-0">
                            Ingresar a la opción <?= htmlspecialchars($acceso['mod_nombre'], ENT_QUOTES, 'UTF-8') ?>.
                        </p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel-card p-4 text-center">
            <i class="bi bi-info-circle fs-2 text-primary"></i>
            <h5 class="fw-bold mt-3">Sin accesos rápidos</h5>
            <p class="text-secondary mb-0">
                El rol actual no tiene opciones con acceso directo asignadas.
            </p>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
