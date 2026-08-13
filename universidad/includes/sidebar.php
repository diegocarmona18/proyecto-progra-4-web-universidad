<?php
$user = currentUser();
$idRol = (int)($user['id_rol'] ?? 0);

/*
|--------------------------------------------------------------------------
| Obtener los módulos permitidos para el rol
|--------------------------------------------------------------------------
| Se trae también la información del módulo padre.
| De esta forma, cuando el rol tiene acceso a un hijo, el padre se muestra
| automáticamente aunque no tenga un permiso directo en t_modulo_rol.
*/
$modulosPermitidos = [];
$stmtModulos = $pdo->prepare(
    "SELECT
        m.id_modulo,
        m.mod_nombre,
        m.mod_url,
        m.mod_padre,
        m.mod_icono,
        p.id_modulo AS padre_id,
        p.mod_nombre AS padre_nombre,
        p.mod_url AS padre_url,
        p.mod_icono AS padre_icono
     FROM t_modulo_rol mr
     INNER JOIN t_modulo m
        ON m.id_modulo = mr.id_modulo
     LEFT JOIN t_modulo p
        ON p.id_modulo = m.mod_padre
     WHERE mr.id_rol = :id_rol
       AND mr.mor_estado = 'A'
       AND m.mod_estado = 'A'
       AND (
            p.id_modulo IS NULL
            OR p.mod_estado = 'A'
       )
     ORDER BY
        p.mod_nombre,
        m.mod_nombre"
);

$stmtModulos->execute([
    ':id_rol' => $idRol
]);

$modulosPermitidos = $stmtModulos->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Organizar módulos principales e hijos
|--------------------------------------------------------------------------
*/
$grupos = [];
$modulosDirectos = [];

foreach ($modulosPermitidos as $modulo) {
    $idPadre = (int)($modulo['mod_padre'] ?? 0);

    if ($idPadre > 0) {
        if (!isset($grupos[$idPadre])) {
            $grupos[$idPadre] = [
                'id_modulo' => $idPadre,
                'mod_nombre' => $modulo['padre_nombre'],
                'mod_url' => $modulo['padre_url'],
                'mod_icono' => $modulo['padre_icono'],
                'hijos' => []
            ];
        }

        $grupos[$idPadre]['hijos'][] = [
            'id_modulo' => $modulo['id_modulo'],
            'mod_nombre' => $modulo['mod_nombre'],
            'mod_url' => $modulo['mod_url'],
            'mod_icono' => $modulo['mod_icono']
        ];
    } else {
        /*
         | Los módulos sin padre y con URL se presentan como opciones directas.
         | Los módulos sin URL pueden ser padres de otros módulos y se agregan
         | cuando se procesa alguno de sus hijos.
         */
        $urlModulo = trim((string)($modulo['mod_url'] ?? ''));

        if ($urlModulo !== '') {
            $modulosDirectos[] = [
                'id_modulo' => $modulo['id_modulo'],
                'mod_nombre' => $modulo['mod_nombre'],
                'mod_url' => $modulo['mod_url'],
                'mod_icono' => $modulo['mod_icono']
            ];
        }
    }
}

/* Ordenar grupos alfabéticamente. */
uasort($grupos, function ($a, $b) {
    return strcasecmp($a['mod_nombre'], $b['mod_nombre']);
});

/* Ordenar hijos alfabéticamente. */
foreach ($grupos as $idGrupo => $grupo) {
    usort($grupo['hijos'], function ($a, $b) {
        return strcasecmp($a['mod_nombre'], $b['mod_nombre']);
    });

    $grupos[$idGrupo]['hijos'] = $grupo['hijos'];
}

/* Ordenar opciones directas alfabéticamente. */
usort($modulosDirectos, function ($a, $b) {
    return strcasecmp($a['mod_nombre'], $b['mod_nombre']);
});

/*
|--------------------------------------------------------------------------
| Obtener la ruta actual
|--------------------------------------------------------------------------
*/
$rutaActual = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$rutaBase = rtrim(APP_URL, '/');
$rutaActual = str_replace($rutaBase . '/', '', $rutaActual);

/*
|--------------------------------------------------------------------------
| Obtener el icono guardado en t_modulo
|--------------------------------------------------------------------------
*/
function iconoModulo($icono)
{
    $icono = trim((string)$icono);

    if ($icono === '') {
        return 'bi-circle-fill';
    }

    return $icono;
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div>
            <img
                class="brand-logo"
                src="<?= htmlspecialchars(url('assets/img/logotransparente.png'), ENT_QUOTES, 'UTF-8') ?>"
                alt="Logo de la universidad"
            >
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>

        <a
            class="nav-item <?= $rutaActual === 'inicio.php' ? 'active' : '' ?>"
            href="<?= htmlspecialchars(url('inicio.php'), ENT_QUOTES, 'UTF-8') ?>"
            title="Inicio"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Inicio</span>
        </a>

        <?php if (!empty($grupos) || !empty($modulosDirectos)): ?>
            <div class="nav-section-label mt-2">Opciones</div>
        <?php endif; ?>

        <?php foreach ($grupos as $grupo): ?>
            <?php
            $idGrupo = (int)$grupo['id_modulo'];
            $grupoAbierto = false;

            foreach ($grupo['hijos'] as $hijo) {
                $urlHijo = trim((string)($hijo['mod_url'] ?? ''));

                if ($urlHijo !== '' && $urlHijo === $rutaActual) {
                    $grupoAbierto = true;
                    break;
                }
            }
            ?>

            <button
                class="nav-item submenu-toggle <?= $grupoAbierto ? 'active' : '' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuModulo<?= $idGrupo ?>"
                aria-expanded="<?= $grupoAbierto ? 'true' : 'false' ?>"
                aria-controls="menuModulo<?= $idGrupo ?>"
            >
                <i class="bi <?= htmlspecialchars(iconoModulo($grupo['mod_icono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
                <span><?= htmlspecialchars($grupo['mod_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                <i class="bi bi-chevron-down arrow"></i>
            </button>

            <div
                class="collapse <?= $grupoAbierto ? 'show' : '' ?> submenu"
                id="menuModulo<?= $idGrupo ?>"
            >
                <?php foreach ($grupo['hijos'] as $hijo): ?>
                    <?php
                    $urlHijo = trim((string)($hijo['mod_url'] ?? ''));

                    if ($urlHijo === '') {
                        continue;
                    }
                    ?>

                    <a
                        href="<?= htmlspecialchars(url($urlHijo), ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= $rutaActual === $urlHijo ? 'active' : '' ?>"
                    >
                        <i class="bi <?= htmlspecialchars(iconoModulo($hijo['mod_icono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= htmlspecialchars($hijo['mod_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php foreach ($modulosDirectos as $modulo): ?>
            <?php $urlModulo = trim((string)$modulo['mod_url']); ?>

            <a
                class="nav-item <?= $rutaActual === $urlModulo ? 'active' : '' ?>"
                href="<?= htmlspecialchars(url($urlModulo), ENT_QUOTES, 'UTF-8') ?>"
            >
                <i class="bi <?= htmlspecialchars(iconoModulo($modulo['mod_icono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
                <span><?= htmlspecialchars($modulo['mod_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
