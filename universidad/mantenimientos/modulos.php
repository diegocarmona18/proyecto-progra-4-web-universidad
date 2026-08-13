<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);
$pageTitle = 'Gestión de Módulos';

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlModulos(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);
    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }
    return 'modulos.php' . ($parametros ? '?' . http_build_query($parametros) : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $idModulo = filter_input(INPUT_POST, 'id_modulo', FILTER_VALIDATE_INT);

    if (!$idModulo) {
        $_SESSION['modulos_mensaje'] = ['tipo' => 'danger', 'texto' => 'No fue posible identificar el módulo.'];
        header('Location: ' . urlModulos());
        exit;
    }

    if ($accion === 'activar') {
        $stmt = $pdo->prepare("UPDATE t_modulo SET mod_estado = 'A' WHERE id_modulo = :id_modulo");
        $stmt->execute([':id_modulo' => $idModulo]);
        $_SESSION['modulos_mensaje'] = ['tipo' => 'success', 'texto' => 'El módulo fue activado correctamente.'];
    }

    if ($accion === 'inactivar') {
        $stmtHijos = $pdo->prepare("SELECT COUNT(*) FROM t_modulo WHERE mod_padre = :id_modulo AND mod_estado = 'A'");
        $stmtHijos->execute([':id_modulo' => $idModulo]);

        if ((int)$stmtHijos->fetchColumn() > 0) {
            $_SESSION['modulos_mensaje'] = ['tipo' => 'warning', 'texto' => 'No puede inactivar el módulo porque tiene opciones hijas activas.'];
        } else {
            $stmt = $pdo->prepare("UPDATE t_modulo SET mod_estado = 'I' WHERE id_modulo = :id_modulo");
            $stmt->execute([':id_modulo' => $idModulo]);
            $_SESSION['modulos_mensaje'] = ['tipo' => 'success', 'texto' => 'El módulo fue inactivado correctamente.'];
        }
    }

    header('Location: ' . urlModulos());
    exit;
}

$mensaje = $_SESSION['modulos_mensaje'] ?? null;
unset($_SESSION['modulos_mensaje']);

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

if ($estadoFiltro !== '') {
    $where[] = 'm.mod_estado = :estado';
    $parametros[':estado'] = $estadoFiltro;
} else {
    $where[] = "m.mod_estado = 'A'";
}

if ($busqueda !== '') {
    $termino = '%' . $busqueda . '%';
    $where[] = "(CAST(m.id_modulo AS CHAR) LIKE :buscar_id OR m.mod_nombre LIKE :buscar_nombre OR m.mod_url LIKE :buscar_url OR padre.mod_nombre LIKE :buscar_padre)";
    $parametros[':buscar_id'] = $termino;
    $parametros[':buscar_nombre'] = $termino;
    $parametros[':buscar_url'] = $termino;
    $parametros[':buscar_padre'] = $termino;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM t_modulo m LEFT JOIN t_modulo padre ON padre.id_modulo = m.mod_padre $whereSql");
$stmtTotal->execute($parametros);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$stmt = $pdo->prepare(
    "SELECT m.id_modulo, m.mod_nombre, m.mod_url, m.mod_padre, m.mod_estado, m.mod_fechahorareg, padre.mod_nombre AS nombre_padre
     FROM t_modulo m
     LEFT JOIN t_modulo padre ON padre.id_modulo = m.mod_padre
     $whereSql
     ORDER BY padre.mod_nombre, m.mod_nombre
     LIMIT :limite OFFSET :offset"
);
foreach ($parametros as $nombre => $valor) {
    $stmt->bindValue($nombre, $valor, PDO::PARAM_STR);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$modulos = $stmt->fetchAll();

$desde = $totalRegistros > 0 ? $offset + 1 : 0;
$hasta = min($offset + $porPagina, $totalRegistros);
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="users-hero mb-4">
    <div><div class="d-flex align-items-center gap-3 mb-2"><span class="users-hero-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span><h1 class="mb-0">Gestión de Módulos</h1></div><p class="mb-0">Administra las opciones y agrupaciones del menú lateral.</p></div>
    <a class="btn btn-light users-primary-action" href="modulo_form.php"><i class="bi bi-plus-circle-fill me-2"></i>Nuevo Módulo</a>
</section>

<?php if ($mensaje): ?><div class="alert alert-<?= e($mensaje['tipo']) ?> alert-dismissible fade show shadow-sm"><?= e($mensaje['texto']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<section class="users-card">
    <form method="get" class="users-filters">
        <div class="users-search flex-grow-1"><i class="bi bi-search"></i><input type="search" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar por ID, nombre, URL o módulo padre..."></div>
        <div class="users-select-wrap"><i class="bi bi-funnel-fill"></i><select name="estado" onchange="this.form.submit()"><option value="" <?= $estadoFiltro === '' ? 'selected' : '' ?>>Módulos activos</option><option value="A" <?= $estadoFiltro === 'A' ? 'selected' : '' ?>>Activos</option><option value="I" <?= $estadoFiltro === 'I' ? 'selected' : '' ?>>Inactivos</option></select></div>
        <button class="btn btn-primary px-4"><i class="bi bi-search me-1"></i>Buscar</button>
        <?php if ($busqueda !== '' || $estadoFiltro !== ''): ?><a href="modulos.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a><?php endif; ?>
    </form>

    <div class="table-responsive"><table class="table users-table align-middle mb-0"><thead><tr><th style="width:72px"></th><th>Módulo</th><th>Módulo padre</th><th>URL</th><th>Tipo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
    <?php if (!$modulos): ?><tr><td colspan="7" class="text-center py-5 text-secondary">No se encontraron módulos.</td></tr><?php endif; ?>
    <?php foreach ($modulos as $modulo): $activo = $modulo['mod_estado'] === 'A'; ?>
        <tr>
            <td><span class="role-icon <?= $modulo['mod_padre'] ? 'role-profesor' : 'role-admin' ?>"><i class="bi <?= $modulo['mod_padre'] ? 'bi-file-earmark-code-fill' : 'bi-folder-fill' ?>"></i></span></td>
            <td><div class="fw-bold text-dark"><?= e($modulo['mod_nombre']) ?></div><small class="text-secondary">ID: <?= (int)$modulo['id_modulo'] ?></small></td>
            <td><?= $modulo['nombre_padre'] ? e($modulo['nombre_padre']) : '<span class="text-secondary">Sin padre</span>' ?></td>
            <td><?= $modulo['mod_url'] ? e($modulo['mod_url']) : '<span class="text-secondary">Sin URL</span>' ?></td>
            <td><span class="role-badge role-badge-estudiante"><?= $modulo['mod_padre'] ? 'Opción' : 'Agrupador' ?></span></td>
            <td><?= $activo ? '<span class="status-badge status-active"><span></span>Activo</span>' : '<span class="status-badge status-inactive"><span></span>Inactivo</span>' ?></td>
            <td><div class="d-flex justify-content-end gap-2"><a class="action-btn bg-primary text-white" href="modulo_form.php?id=<?= (int)$modulo['id_modulo'] ?>"><i class="bi bi-pencil-fill"></i></a><button type="button" class="action-btn <?= $activo ? 'bg-danger' : 'bg-success' ?> text-white" data-bs-toggle="modal" data-bs-target="#confirmActionModal" data-action="<?= $activo ? 'inactivar' : 'activar' ?>" data-id="<?= (int)$modulo['id_modulo'] ?>" data-name="<?= e($modulo['mod_nombre']) ?>"><i class="bi <?= $activo ? 'bi-x-circle-fill' : 'bi-check-circle-fill' ?>"></i></button></div></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>

    <div class="users-footer"><div>Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $totalRegistros ?> resultados</div><?php if ($totalPaginas > 1): ?><nav><ul class="pagination users-pagination mb-0"><?php for ($i=1; $i <= $totalPaginas; $i++): ?><li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= e(urlModulos(['pagina'=>$i])) ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav><?php endif; ?></div>
</section>

<div class="modal fade confirm-modal" id="confirmActionModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="confirm-modal-header"><h4 id="confirmModalTitle">Confirmar acción</h4></div><form method="post"><div class="modal-body p-4"><input type="hidden" name="accion" id="confirmAction"><input type="hidden" name="id_modulo" id="confirmModuloId"><p id="confirmModalText"></p><div class="confirm-user-box"><i class="bi bi-grid-fill me-2"></i><strong id="confirmModuloName"></strong></div></div><div class="modal-footer border-0"><button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn" id="confirmSubmitButton">Confirmar</button></div></form></div></div></div>
<script>
document.getElementById('confirmActionModal').addEventListener('show.bs.modal', function(event){const b=event.relatedTarget;const a=b.getAttribute('data-action');document.getElementById('confirmAction').value=a;document.getElementById('confirmModuloId').value=b.getAttribute('data-id');document.getElementById('confirmModuloName').textContent=b.getAttribute('data-name');document.getElementById('confirmModalTitle').textContent=a==='activar'?'Activar módulo':'Inactivar módulo';document.getElementById('confirmModalText').textContent=a==='activar'?'¿Desea activar este módulo?':'¿Desea inactivar este módulo?';const s=document.getElementById('confirmSubmitButton');s.textContent=a==='activar'?'Activar módulo':'Inactivar módulo';s.className=a==='activar'?'btn btn-confirm-unlock':'btn btn-confirm-delete';});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
