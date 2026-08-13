<?php
require_once dirname(__DIR__) . '/config.php';
requireRole($allowedRoles);
$pageTitle = $moduleTitle;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <h2 class="fw-bold mb-1"><?= $moduleTitle ?></h2>
        <p class="text-secondary mb-0">
            <?= $moduleDescription ?>
        </p>
    </div>
    <button class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-2"></i>Nuevo registro
    </button>
</div>
<div class="panel-card p-3">
    <div class="d-flex gap-2 mb-3">
        <input class="form-control" placeholder="Buscar...">
        <button class="btn btn-outline-primary">
            <i class="bi bi-search"></i>
        </button>
    </div>
    <div class="table-responsive table-modern">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Registro de ejemplo</td>
                    <td><span class="badge badge-soft">Activo</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-light">
                            <i class="bi bi-pencil"></i>
                        </button> 
                        <button class="btn btn-sm btn-light text-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
