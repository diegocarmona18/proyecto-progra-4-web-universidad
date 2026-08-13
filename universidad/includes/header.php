<?php
require_once dirname(__DIR__) . '/config.php';

if (!function_exists('routeFromScriptName')) {
    function routeFromScriptName(): string
    {
        $script = trim((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        $script = str_replace('\\', '/', $script);
        $base = rtrim((string) APP_URL, '/');

        if ($base !== '' && $script !== '' && str_starts_with($script, $base . '/')) {
            $script = substr($script, strlen($base) + 1);
        }

        return ltrim($script, '/');
    }
}

if (!function_exists('moduloActualDesdeRuta')) {
    function moduloActualDesdeRuta(): ?string
    {
        global $pdo;

        $ruta = routeFromScriptName();
        if ($ruta === '') {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT mod_nombre FROM t_modulo WHERE mod_url = :ruta AND mod_estado = 'A' LIMIT 1");
            $stmt->execute([':ruta' => $ruta]);
            $modulo = $stmt->fetchColumn();
            return $modulo !== false ? (string) $modulo : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

requireLogin();
$user = currentUser();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Universidad' ?> | <?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('assets/img/iconotab.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(url('assets/css/styles.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <button class="btn sidebar-button" id="sidebarToggle" aria-label="Contraer menú"><i class="bi bi-list"></i></button>
        <div class="topbar-title">
            <span class="university-name"><?= htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8') ?></span>
            <small><?= $pageTitle ?? 'Panel principal' ?></small>
        </div>
        <div class="dropdown ms-auto">
            <button class="btn user-menu dropdown-toggle" data-bs-toggle="dropdown">
                <span class="avatar"><?= strtoupper(substr($user['nombre'], 0, 1)) ?></span>
                <span class="user-data"><strong><?= htmlspecialchars($user['nombre']) ?></strong><small><?= roleLabel($user['rol']) ?></small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><span class="dropdown-item-text small text-secondary"><?= htmlspecialchars($user['codigo']) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
            </ul>
        </div>
    </header>
    <main class="content-area container-fluid">
