<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idCarrera = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$idCarrera) {
    header('Location: carreras.php');
    exit;
}

$stmtCarrera = $pdo->prepare(
    "SELECT c.id_carrera, c.car_nombre, c.car_observacion, g.gra_nombre
     FROM t_carrera c
     LEFT JOIN t_grado g ON g.id_grado = c.id_grado
     WHERE c.id_carrera = :id_carrera"
);
$stmtCarrera->execute([':id_carrera' => $idCarrera]);
$carrera = $stmtCarrera->fetch();

if (!$carrera) {
    header('Location: carreras.php');
    exit;
}

$stmtPlan = $pdo->prepare(
    "SELECT
        p.id_perplan,
        p.ppe_nombre,
        cc.cac_codigo,
        cc.cac_dependencia,
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
     ORDER BY p.id_perplan, c.cur_nombre"
);
$stmtPlan->execute([':id_carrera' => $idCarrera]);
$cursos = $stmtPlan->fetchAll();

$planAgrupado = [];
$totalCreditos = 0;
$totalCosto = 0;

foreach ($cursos as $curso) {
    $periodo = $curso['ppe_nombre'];
    if ($periodo === null || trim($periodo) === '') {
        $periodo = 'Sin cuatrimestre asignado';
    }
    if (!isset($planAgrupado[$periodo])) {
        $planAgrupado[$periodo] = [];
    }
    $planAgrupado[$periodo][] = $curso;
    $totalCreditos += (int)$curso['cur_credito'];
    $totalCosto += (float)$curso['cur_costo'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plan de estudio - <?= e($carrera['car_nombre']) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('assets/img/favicon.png'), ENT_QUOTES, 'UTF-8') ?>">
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef4f8;color:#17324d;font-family:Arial,sans-serif}
        .toolbar{max-width:1000px;margin:22px auto 0;display:flex;justify-content:flex-end;gap:10px}
        .toolbar a,.toolbar button{border:0;border-radius:10px;padding:11px 18px;font-weight:bold;cursor:pointer;text-decoration:none}
        .back{background:#fff;color:#17324d}.print{background:#0b73b9;color:#fff}
        .document{max-width:1000px;margin:18px auto 35px;background:#fff;box-shadow:0 16px 45px rgba(9,43,76,.15);border-radius:20px;overflow:hidden}
        .header{padding:28px 38px;background:linear-gradient(120deg,#092b4c,#0b73b9);color:#fff;display:flex;align-items:center;gap:24px}
        .header img{width:110px;height:110px;object-fit:contain;background:#fff;border-radius:16px;padding:7px}
        .header h1{margin:0 0 7px;font-size:29px}.header p{margin:4px 0;opacity:.9}
        .content{padding:32px 38px}.career-info{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:25px}.info-box{background:#f4f8fb;border-radius:12px;padding:14px 16px}.info-box small{display:block;color:#6b8296;margin-bottom:5px}.info-box strong{font-size:17px}
        .period{border:1px solid #dbe7ef;border-radius:14px;overflow:hidden;margin-bottom:22px;page-break-inside:avoid}.period-title{background:#eaf6f7;padding:14px 18px;display:flex;justify-content:space-between;color:#092b4c}.period-title strong{font-size:18px}
        table{width:100%;border-collapse:collapse}th,td{padding:12px 15px;border-bottom:1px solid #e8eef2;text-align:left}th{background:#f8fbfc;font-size:12px;text-transform:uppercase;color:#5b7184}td.center,th.center{text-align:center}td.right,th.right{text-align:right}
        .summary{display:flex;justify-content:flex-end;gap:14px;margin-top:25px}.summary div{min-width:160px;background:#092b4c;color:#fff;padding:14px 17px;border-radius:12px}.summary small{display:block;opacity:.75}.summary strong{font-size:21px}
        .footer{text-align:center;padding:18px;color:#71879a;font-size:12px;border-top:1px solid #e5edf2}
        .empty{text-align:center;padding:45px;color:#71879a}
        @media print{body{background:#fff}.toolbar{display:none}.document{margin:0;max-width:none;box-shadow:none;border-radius:0}.header{-webkit-print-color-adjust:exact;print-color-adjust:exact}.period-title,.info-box,.summary div{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
        @media(max-width:700px){.document{margin:0;border-radius:0}.header{padding:22px;flex-direction:column;text-align:center}.content{padding:22px}.career-info{grid-template-columns:1fr}.summary{flex-direction:column}.summary div{width:100%}}
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="back" href="carrera_plan_estudio.php?id=<?= (int)$idCarrera ?>">Volver</a>
        <button class="print" onclick="window.print()">Imprimir</button>
    </div>

    <main class="document">
        <header class="header">
            <img src="<?= htmlspecialchars(url('assets/img/logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo de la universidad">
            <div>
                <h1>Plan de Estudio</h1>
                <p><strong><?= e(UNIVERSITY_NAME) ?></strong></p>
                <p><?= e($carrera['car_nombre']) ?></p>
            </div>
        </header>

        <div class="content">
            <section class="career-info">
                <div class="info-box"><small>Carrera</small><strong><?= e($carrera['car_nombre']) ?></strong></div>
                <div class="info-box"><small>Grado académico</small><strong><?= e($carrera['gra_nombre'] ?? 'No asignado') ?></strong></div>
            </section>

            <?php if (!$planAgrupado): ?>
                <div class="empty">Esta carrera todavía no tiene cursos asignados.</div>
            <?php endif; ?>

            <?php foreach ($planAgrupado as $periodo => $listaCursos): ?>
                <?php
                $creditosPeriodo = 0;
                foreach ($listaCursos as $curso) {
                    $creditosPeriodo += (int)$curso['cur_credito'];
                }
                ?>
                <section class="period">
                    <div class="period-title">
                        <strong><?= e($periodo) ?></strong>
                        <span><?= count($listaCursos) ?> cursos · <?= $creditosPeriodo ?> créditos</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:100px">Código</th>
                                <th>Curso</th>
                                <th>Requisito</th>
                                <th class="center" style="width:100px">Créditos</th>
                                <th class="right" style="width:140px">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaCursos as $curso): ?>
                                <tr>
                                    <td><strong><?= e($curso['cac_codigo']) ?></strong></td>
                                    <td>
                                        <strong><?= e($curso['cur_nombre']) ?></strong>
                                        <?php if (!empty($curso['cur_observacion'])): ?>
                                            <br><small><?= e($curso['cur_observacion']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($curso['dependencia_nombre'])): ?>
                                            <strong><?= e($curso['dependencia_codigo']) ?></strong><br>
                                            <small><?= e($curso['dependencia_nombre']) ?></small>
                                        <?php else: ?>
                                            <span>Sin requisito</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="center"><?= (int)$curso['cur_credito'] ?></td>
                                    <td class="right">₡<?= number_format((float)$curso['cur_costo'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>

            <?php if ($cursos): ?>
                <section class="summary">
                    <div><small>Total de cursos</small><strong><?= count($cursos) ?></strong></div>
                    <div><small>Total de créditos</small><strong><?= $totalCreditos ?></strong></div>
                    <div><small>Costo estimado</small><strong>₡<?= number_format($totalCosto, 2, ',', '.') ?></strong></div>
                </section>
            <?php endif; ?>
        </div>

        <footer class="footer">
            Documento generado el <?= date('d/m/Y H:i') ?> · <?= e(UNIVERSITY_NAME) ?>
        </footer>
    </main>
</body>
</html>
