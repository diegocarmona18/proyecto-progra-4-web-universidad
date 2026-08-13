<?php
require_once __DIR__ . '/../config.php';

function registrarSesion($pdo, $id_usuario, $correo, $accion, $intentos = 0) {
    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_sesiones'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;
    } catch (Throwable $th) {
        $tableExists = false;
    }

    if (!$tableExists) {
        return;
    }

    $usuarioNombre = 'Sistema';
    $correoUsuario = $correo;
    $rol = $_SESSION['rol_nombre'] ?? null;

    if ($id_usuario !== null) {
        $stmtUsuario = $pdo->prepare("SELECT u.usu_codigo, u.usu_correo, u.usu_nombre, u.id_rol, r.rol_nombre AS rol_nombre FROM t_usuario u LEFT JOIN t_rol r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id_usuario LIMIT 1");
        $stmtUsuario->execute([':id_usuario' => $id_usuario]);
        $usuario = $stmtUsuario->fetch();

        if ($usuario) {
            $correoUsuario = $usuario['usu_correo'] ?? $correoUsuario;
            $usuarioNombre = $usuario['usu_nombre'] ?? $usuarioNombre;
            $rol = $usuario['rol_nombre'] ?? $rol;
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO bitacora_sesiones (
            usuario_nombre,
            correo,
            rol,
            accion,
            intentos_fallidos
        ) VALUES (
            :usuario_nombre,
            :correo,
            :rol,
            :accion,
            :intentos_fallidos
        )"
    );

    $stmt->execute([
        ':usuario_nombre' => $usuarioNombre,
        ':correo' => $correoUsuario,
        ':rol' => $rol,
        ':accion' => $accion,
        ':intentos_fallidos' => $intentos,
    ]);
}

if (!function_exists('e')) {
    function e($valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== basename(__FILE__)) {
    return;
}

requireRole(['administrativo']);
$pageTitle = 'Bitácora de Login';

$busqueda = trim((string)($_GET['buscar'] ?? ''));
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
$accionesPermitidas = ['login_exitoso', 'login_bloqueado', 'logout'];

try {
    $tableExists = false;
    $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_sesiones'");
    $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;

    if ($tableExists) {
        $sqlCount = "SELECT COUNT(*) FROM bitacora_sesiones";
        $sqlData = "SELECT * FROM bitacora_sesiones";
        $params = [];

        if ($busqueda !== '') {
            $condicion = " WHERE usuario_nombre LIKE :buscar OR correo LIKE :buscar OR rol LIKE :buscar OR accion LIKE :buscar";
            $sqlCount .= $condicion;
            $sqlData .= $condicion;
            $params[':buscar'] = '%' . $busqueda . '%';
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
                    $fila['usuario_nombre'] ?? '',
                    $fila['correo'] ?? '',
                    $fila['rol'] ?? '',
                    $fila['accion'] ?? '',
                    $fila['fecha'] ?? '',
                ]);

                return mb_stripos(mb_strtolower($texto, 'UTF-8'), $termino, 0, 'UTF-8') !== false;
            }));
            $totalRegistros = count($logRows);
        }
    }

    $totalRegistros = (int) $totalRegistros;
    if (!$tableExists) {
        $totalPaginas = max(1, (int) ceil($totalRegistros / $registrosPorPagina));
    } else {
        $totalPaginas = max(1, (int) ceil(($totalRegistros ?? 0) / $registrosPorPagina));
    }
} catch (Throwable $th) {
    $logError = 'No se pudo cargar la bitácora en este momento.';
    $logRows = [];
    $totalPaginas = 1;
    $totalRegistros = 0;
}

include __DIR__ . '/../includes/header.php';
?>

<section class="users-hero mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="users-hero-icon"><i class="bi bi-shield-lock-fill"></i></span>
            <h1 class="mb-0">Bitácora de Login</h1>
        </div>
        <p class="mb-0">Consulta los accesos, intentos fallidos y eventos de sesión registrados en el sistema.</p>
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
            <input type="search" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar por usuario, acción, IP o fecha...">
        </div>

        <button type="submit" class="btn btn-primary px-4 rounded-3">
            <i class="bi bi-search me-1"></i>Buscar
        </button>

        <?php if ($busqueda !== ''): ?>
            <a href="bitacora_login.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table users-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Acción</th>
                    <th class="text-center">Intentos</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logRows === []): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">No hay registros en la bitácora.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logRows as $registro): ?>
                        <?php
                        $fecha = $registro['fecha'] ?? $registro['created_at'] ?? $registro['fechahora'] ?? null;
                        $nombreUsuario = $registro['usuario_nombre'] ?? 'Sin nombre';
                        $correo = $registro['correo'] ?? 'Sin correo';
                        $rol = $registro['rol'] ?? 'Sin rol';
                        $accion = $registro['accion'] ?? 'login';
                        $accion = str_replace('_', ' ', $accion);
                        $intentos = $registro['intentos_fallidos'] ?? $registro['intentos'] ?? 0;
                        $modulo = $registro['modulo'] ?? $registro['ruta'] ?? $registro['pagina'] ?? null;
                        ?>
                        <tr>
                            <td>
                                <span class="fw-semibold">
                                    <?= e($fecha ? date('d/m/Y H:i:s', strtotime((string)$fecha)) : 'Sin fecha') ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e((string)$nombreUsuario) ?></div>
                            </td>
                            <td><?= e((string)$nombreUsuario) ?></td>
                            <td><?= e($correo) ?></td>
                            <td><?= e($rol) ?></td>
                            <td>
                                <span class="status-badge status-active">
                                    <span></span>
                                    <?= e(ucwords($accion)) ?>
                                    <?php if ($modulo !== null && $modulo !== ''): ?>
                                        <small class="d-block text-secondary mt-1"><?= e((string) $modulo) ?></small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="credit-badge"><?= (int)$intentos ?></span>
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
            <nav aria-label="Paginación de bitácora">
                <ul class="pagination mb-0">
                    <?php $prevPagina = max(1, (int) $paginaActual - 1); ?>
                    <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e('bitacora_login.php?buscar=' . urlencode($busqueda) . '&pagina=' . $prevPagina) ?>">Anterior</a>
                    </li>

                    <?php for ($i = 1; $i <= (int) $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i === $paginaActual ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e('bitacora_login.php?buscar=' . urlencode($busqueda) . '&pagina=' . $i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php $nextPagina = min((int) $totalPaginas, (int) $paginaActual + 1); ?>
                    <li class="page-item <?= $paginaActual >= (int) $totalPaginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e('bitacora_login.php?buscar=' . urlencode($busqueda) . '&pagina=' . $nextPagina) ?>">Siguiente</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>