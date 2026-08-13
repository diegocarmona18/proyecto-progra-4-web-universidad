<?php
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roles = ['administrativo','estudiante','profesor'];
    $role = $_POST['rol'] ?? '';
    if (in_array($role, $roles, true)) {
        $_SESSION['usuario']['rol'] = $role;
        $_SESSION['usuario']['nombre'] = match ($role) {
            'administrativo' => 'María Fernández',
            'estudiante' => 'Carlos Jiménez',
            'profesor' => 'Ana Rodríguez'
        };
    }
    header('Location: index.php'); exit;
}
$pageTitle='Cambiar perfil'; include __DIR__.'/includes/header.php';
?>
<div class="page-header"><div><h2 class="fw-bold">Seleccionar perfil</h2><p class="text-secondary mb-0">Pantalla de demostración para probar el menú por rol.</p></div></div>
<form method="post"><div class="row g-4">
<?php foreach ([['administrativo','Administrativo','bi-shield-check','Mantenimientos y administración'],['estudiante','Estudiante','bi-backpack2','Notas y matrícula'],['profesor','Profesor','bi-person-video3','Rubros y asignación de notas']] as $r): ?>
<div class="col-md-4"><button class="role-card panel-card p-4 w-100 text-start" name="rol" value="<?= $r[0] ?>"><i class="bi <?= $r[2] ?> fs-1 text-primary"></i><h4 class="mt-3"><?= $r[1] ?></h4><p class="text-secondary mb-0"><?= $r[3] ?></p></button></div>
<?php endforeach; ?>
</div></form>
<?php include __DIR__.'/includes/footer.php'; ?>
