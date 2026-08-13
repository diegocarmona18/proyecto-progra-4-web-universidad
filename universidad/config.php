<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Conexion reutilizable con PDO. Mantiene la configuracion suministrada.
$host = '127.0.0.1';
$dbname = 'universidad';
$user = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die('Error de conexion: ' . htmlspecialchars($e->getMessage()));
}

const UNIVERSITY_NAME = 'Universidad Central de Estudios Superiores';
define('APP_URL', '/' . basename(__DIR__));

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, -1);
        } elseif (($value[0] ?? '') === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }

        putenv($key . '=' . $value);
        $_SERVER[$key] = $value;
    }
}

loadEnvFile(__DIR__ . '/.env');

function registrarBitacoraLogin(string $accion, array $detalle = []): void
{
    $accionesPermitidas = [
        'login_exitoso',
        'login_bloqueado',
        'logout',
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        return;
    }

    global $pdo;

    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_sesiones'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;
    } catch (Throwable $th) {
        return;
    }

    if (!$tableExists) {
        return;
    }

    $usuarioCodigo = $detalle['usu_codigo'] ?? ($_SESSION['usu_codigo'] ?? null);
    $usuarioCorreo = $detalle['correo'] ?? ($detalle['usuario_correo'] ?? ($_SESSION['usu_correo'] ?? null));
    $usuarioRol = $detalle['rol'] ?? ($_SESSION['rol_nombre'] ?? null);
    $usuarioNombre = $detalle['usuario_nombre'] ?? ($_SESSION['usu_nombre'] ?? null);

    if (($usuarioCodigo || $usuarioCorreo) && $usuarioNombre === null) {
        $stmtUsuario = $pdo->prepare("SELECT u.usu_codigo, u.usu_correo, u.usu_nombre, u.id_rol, r.rol_nombre AS rol_nombre FROM t_usuario u LEFT JOIN t_rol r ON r.id_rol = u.id_rol WHERE u.usu_codigo = :usu_codigo OR u.usu_correo = :usu_correo LIMIT 1");
        $stmtUsuario->execute([
            ':usu_codigo' => $usuarioCodigo,
            ':usu_correo' => $usuarioCorreo,
        ]);
        $usuario = $stmtUsuario->fetch();

        if ($usuario) {
            $usuarioCodigo = $usuario['usu_codigo'] ?? $usuarioCodigo;
            $usuarioCorreo = $usuario['usu_correo'] ?? $usuarioCorreo;
            $usuarioRol = $usuario['rol_nombre'] ?? $usuarioRol;
            $usuarioNombre = $usuario['usu_nombre'] ?? $usuarioNombre;
            $_SESSION['usu_codigo'] = $_SESSION['usu_codigo'] ?? $usuarioCodigo;
            $_SESSION['usu_correo'] = $_SESSION['usu_correo'] ?? $usuarioCorreo;
            $_SESSION['rol_nombre'] = $_SESSION['rol_nombre'] ?? $usuarioRol;
            $_SESSION['usu_nombre'] = $_SESSION['usu_nombre'] ?? ($usuario['usu_nombre'] ?? 'Sistema');
        }
    }

    $intentos = (int) ($detalle['intentos'] ?? 0);

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
        ':usuario_nombre' => $usuarioNombre ?? 'Sistema',
        ':correo' => $usuarioCorreo,
        ':rol' => $usuarioRol,
        ':accion' => $accion,
        ':intentos_fallidos' => $intentos,
    ]);
}

function registrarBitacoraUsuario(string $accion, array $detalle = []): void
{
    $accionesPermitidas = [
        'usuario_creado',
        'usuario_actualizado',
        'usuario_inactivado',
        'usuario_activado',
        'usuario_desbloqueado',
        'usuario_bloqueado',
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        return;
    }

    global $pdo;

    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_usuarios'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;
    } catch (Throwable $th) {
        return;
    }

    if (!$tableExists) {
        return;
    }

    $usuarioId = $detalle['id_usuario'] ?? null;
    $usuarioCodigo = $detalle['usu_codigo'] ?? null;
    $usuarioCorreo = $detalle['usuario_correo'] ?? ($detalle['correo'] ?? null);
    $usuarioRol = $detalle['rol'] ?? null;
    $usuarioNombre = $detalle['usuario_nombre'] ?? null;
    $realizadoPor = $detalle['realizado_por'] ?? ($_SESSION['usu_codigo'] ?? 'Sistema');

    if ($usuarioId !== null && ($usuarioCodigo === null || $usuarioNombre === null || $usuarioCorreo === null || $usuarioRol === null)) {
        $stmtUsuario = $pdo->prepare("SELECT u.usu_codigo, u.usu_correo, u.usu_nombre, u.id_rol, r.rol_nombre AS rol_nombre FROM t_usuario u LEFT JOIN t_rol r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id_usuario LIMIT 1");
        $stmtUsuario->execute([':id_usuario' => $usuarioId]);
        $usuario = $stmtUsuario->fetch();

        if ($usuario) {
            $usuarioCodigo = $usuarioCodigo ?? $usuario['usu_codigo'];
            $usuarioCorreo = $usuarioCorreo ?? $usuario['usu_correo'];
            $usuarioNombre = $usuarioNombre ?? $usuario['usu_nombre'];
            $usuarioRol = $usuarioRol ?? $usuario['rol_nombre'];
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO bitacora_usuarios (
            usuario_id,
            usuario_codigo,
            usuario_nombre,
            usuario_correo,
            rol,
            realizado_por,
            accion
        ) VALUES (
            :usuario_id,
            :usuario_codigo,
            :usuario_nombre,
            :usuario_correo,
            :rol,
            :realizado_por,
            :accion
        )"
    );

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':usuario_codigo' => $usuarioCodigo,
        ':usuario_nombre' => $usuarioNombre,
        ':usuario_correo' => $usuarioCorreo,
        ':rol' => $usuarioRol,
        ':realizado_por' => $realizadoPor,
        ':accion' => $accion,
    ]);
}

function registrarBitacoraRol(string $accion, array $detalle = []): void
{
    $accionesPermitidas = [
        'rol_creado',
        'rol_actualizado',
        'rol_activado',
        'rol_inactivado',
        'rol_modulos_actualizados',
        'rol_eliminado',
        'rol_eliminado_intentado',
        'rol_inactivacion_intentada',
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        return;
    }

    global $pdo;

    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_roles'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;

        if (!$tableExists) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS bitacora_roles (
                    id INT NOT NULL AUTO_INCREMENT,
                    rol_id INT DEFAULT NULL,
                    rol_nombre VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    rol_dashboard VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    modulos TEXT DEFAULT NULL,
                    realizado_por VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    accion VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL,
                    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_bitacora_roles_accion (accion),
                    KEY idx_bitacora_roles_fecha (fecha)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $tableExists = true;
        }
    } catch (Throwable $th) {
        return;
    }

    if (!$tableExists) {
        return;
    }

    $rolId = $detalle['rol_id'] ?? null;
    $rolNombre = $detalle['rol_nombre'] ?? null;
    $rolDashboard = $detalle['rol_dashboard'] ?? null;
    $modulos = $detalle['modulos'] ?? null;
    $realizadoPor = $detalle['realizado_por'] ?? ($_SESSION['usu_codigo'] ?? 'Sistema');

    if ($rolId !== null && ($rolNombre === null || $rolDashboard === null)) {
        $stmtRol = $pdo->prepare("SELECT rol_nombre, rol_dashboard FROM t_rol WHERE id_rol = :id_rol LIMIT 1");
        $stmtRol->execute([':id_rol' => $rolId]);
        $rol = $stmtRol->fetch();

        if ($rol) {
            $rolNombre = $rolNombre ?? $rol['rol_nombre'];
            $rolDashboard = $rolDashboard ?? $rol['rol_dashboard'];
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO bitacora_roles (
            rol_id,
            rol_nombre,
            rol_dashboard,
            modulos,
            realizado_por,
            accion
        ) VALUES (
            :rol_id,
            :rol_nombre,
            :rol_dashboard,
            :modulos,
            :realizado_por,
            :accion
        )"
    );

    $stmt->execute([
        ':rol_id' => $rolId,
        ':rol_nombre' => $rolNombre,
        ':rol_dashboard' => $rolDashboard,
        ':modulos' => $modulos,
        ':realizado_por' => $realizadoPor,
        ':accion' => $accion,
    ]);
}

function registrarBitacoraCurso(string $accion, array $detalle = []): void
{
    $accionesPermitidas = [
        'curso_creado',
        'curso_actualizado',
        'curso_activado',
        'curso_inactivado',
        'curso_eliminado',
        'curso_eliminado_intentado',
        'curso_inactivacion_intentada',
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        return;
    }

    global $pdo;

    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_cursos'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;

        if (!$tableExists) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS bitacora_cursos (
                    id INT NOT NULL AUTO_INCREMENT,
                    curso_id INT DEFAULT NULL,
                    cur_nombre VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    cur_credito INT DEFAULT NULL,
                    cur_costo DECIMAL(12,2) DEFAULT NULL,
                    cur_observacion TEXT DEFAULT NULL,
                    cur_estado VARCHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    realizado_por VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    accion VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL,
                    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_bitacora_cursos_accion (accion),
                    KEY idx_bitacora_cursos_fecha (fecha)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $tableExists = true;
        }
    } catch (Throwable $th) {
        return;
    }

    if (!$tableExists) {
        return;
    }

    $cursoId = $detalle['curso_id'] ?? $detalle['id_curso'] ?? null;
    $curNombre = $detalle['cur_nombre'] ?? null;
    $curCredito = $detalle['cur_credito'] ?? null;
    $curCosto = $detalle['cur_costo'] ?? null;
    $curObservacion = $detalle['cur_observacion'] ?? null;
    $curEstado = $detalle['cur_estado'] ?? null;
    $realizadoPor = $detalle['realizado_por'] ?? ($_SESSION['usu_codigo'] ?? 'Sistema');

    if ($cursoId !== null && $curNombre === null) {
        $stmtCurso = $pdo->prepare("SELECT cur_nombre, cur_credito, cur_costo, cur_observacion, cur_estado FROM t_curso WHERE id_curso = :id_curso LIMIT 1");
        $stmtCurso->execute([':id_curso' => $cursoId]);
        $curso = $stmtCurso->fetch();

        if ($curso) {
            $curNombre = $curNombre ?? $curso['cur_nombre'];
            $curCredito = $curCredito ?? $curso['cur_credito'];
            $curCosto = $curCosto ?? $curso['cur_costo'];
            $curObservacion = $curObservacion ?? $curso['cur_observacion'];
            $curEstado = $curEstado ?? $curso['cur_estado'];
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO bitacora_cursos (
            curso_id,
            cur_nombre,
            cur_credito,
            cur_costo,
            cur_observacion,
            cur_estado,
            realizado_por,
            accion
        ) VALUES (
            :curso_id,
            :cur_nombre,
            :cur_credito,
            :cur_costo,
            :cur_observacion,
            :cur_estado,
            :realizado_por,
            :accion
        )"
    );

    $stmt->execute([
        ':curso_id' => $cursoId,
        ':cur_nombre' => $curNombre,
        ':cur_credito' => $curCredito,
        ':cur_costo' => $curCosto,
        ':cur_observacion' => $curObservacion,
        ':cur_estado' => $curEstado,
        ':realizado_por' => $realizadoPor,
        ':accion' => $accion,
    ]);
}

function registrarBitacoraCarrera(string $accion, array $detalle = []): void
{
    $accionesPermitidas = [
        'carrera_creada',
        'carrera_actualizada',
        'carrera_activada',
        'carrera_inactivada',
        'carrera_eliminada',
        'carrera_eliminada_intentada',
        'carrera_inactivacion_intentada',
    ];

    if (!in_array($accion, $accionesPermitidas, true)) {
        return;
    }

    global $pdo;

    try {
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'bitacora_carreras'");
        $tableExists = $stmtCheck && $stmtCheck->fetchColumn() !== false;

        if (!$tableExists) {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS bitacora_carreras (
                    id INT NOT NULL AUTO_INCREMENT,
                    carrera_id INT DEFAULT NULL,
                    car_nombre VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    id_grado INT DEFAULT NULL,
                    grado_nombre VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    car_observacion TEXT DEFAULT NULL,
                    car_estado VARCHAR(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    realizado_por VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    accion VARCHAR(60) COLLATE utf8mb4_unicode_ci NOT NULL,
                    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_bitacora_carreras_accion (accion),
                    KEY idx_bitacora_carreras_fecha (fecha)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $tableExists = true;
        }
    } catch (Throwable $th) {
        return;
    }

    if (!$tableExists) {
        return;
    }

    $carreraId = $detalle['carrera_id'] ?? $detalle['id_carrera'] ?? null;
    $carNombre = $detalle['car_nombre'] ?? null;
    $idGrado = $detalle['id_grado'] ?? null;
    $gradoNombre = $detalle['grado_nombre'] ?? null;
    $carObservacion = $detalle['car_observacion'] ?? null;
    $carEstado = $detalle['car_estado'] ?? null;
    $realizadoPor = $detalle['realizado_por'] ?? ($_SESSION['usu_codigo'] ?? 'Sistema');

    if ($carreraId !== null && ($carNombre === null || $idGrado === null || $gradoNombre === null)) {
        $stmtCarrera = $pdo->prepare("SELECT c.car_nombre, c.id_grado, g.gra_nombre AS grado_nombre, c.car_observacion, c.car_estado FROM t_carrera c LEFT JOIN t_grado g ON g.id_grado = c.id_grado WHERE c.id_carrera = :id_carrera LIMIT 1");
        $stmtCarrera->execute([':id_carrera' => $carreraId]);
        $carrera = $stmtCarrera->fetch();

        if ($carrera) {
            $carNombre = $carNombre ?? $carrera['car_nombre'];
            $idGrado = $idGrado ?? $carrera['id_grado'];
            $gradoNombre = $gradoNombre ?? $carrera['grado_nombre'];
            $carObservacion = $carObservacion ?? $carrera['car_observacion'];
            $carEstado = $carEstado ?? $carrera['car_estado'];
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO bitacora_carreras (
            carrera_id,
            car_nombre,
            id_grado,
            grado_nombre,
            car_observacion,
            car_estado,
            realizado_por,
            accion
        ) VALUES (
            :carrera_id,
            :car_nombre,
            :id_grado,
            :grado_nombre,
            :car_observacion,
            :car_estado,
            :realizado_por,
            :accion
        )"
    );

    $stmt->execute([
        ':carrera_id' => $carreraId,
        ':car_nombre' => $carNombre,
        ':id_grado' => $idGrado,
        ':grado_nombre' => $gradoNombre,
        ':car_observacion' => $carObservacion,
        ':car_estado' => $carEstado,
        ':realizado_por' => $realizadoPor,
        ':accion' => $accion,
    ]);
}

function appBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443 ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return rtrim($protocol . '://' . $host, '/');
}

function appFullUrl(string $path = ''): string
{
    $base = appBaseUrl();
    $relative = trim((string) $path, '/');

    return $relative === '' ? $base : $base . '/' . $relative;
}

function oauthProviderConfig(string $provider): array
{
    $provider = strtolower(trim($provider));
    $baseUrl = appFullUrl(APP_URL . '/oauth_callback.php');

    $configs = [
        'google' => [
            'client_id' => getenv('GOOGLE_CLIENT_ID') ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? ''),
            'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: ($_ENV['GOOGLE_CLIENT_SECRET'] ?? ''),
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'redirect_uri' => $baseUrl,
            'scope' => 'openid email profile',
            'extra' => [
                'access_type' => 'offline',
                'prompt' => 'consent',
            ],
        ],
        'microsoft' => [
            'client_id' => getenv('MICROSOFT_CLIENT_ID') ?: ($_ENV['MICROSOFT_CLIENT_ID'] ?? ''),
            'client_secret' => getenv('MICROSOFT_CLIENT_SECRET') ?: ($_ENV['MICROSOFT_CLIENT_SECRET'] ?? ''),
            'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'userinfo_url' => 'https://graph.microsoft.com/oidc/userinfo',
            'redirect_uri' => $baseUrl,
            'scope' => 'openid profile email User.Read',
            'extra' => [],
        ],
    ];

    return $configs[$provider] ?? ['client_id' => '', 'client_secret' => '', 'auth_url' => '', 'token_url' => '', 'userinfo_url' => '', 'redirect_uri' => $baseUrl, 'scope' => '', 'extra' => []];
}

function url(string $path = ''): string
{
    return APP_URL . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function roleNameFromId(int $roleId): string
{
    return match ($roleId) {
        1 => 'administrativo',
        2 => 'estudiante',
        3 => 'profesor',
        default => 'usuario',
    };
}

function roleLabel(string $role): string
{
    return match ($role) {
        'administrativo' => 'Administrativo',
        'estudiante' => 'Estudiante',
        'profesor' => 'Profesor',
        default => 'Usuario',
    };
}

function currentUser(): ?array
{
    if (!isset($_SESSION['id_usuario'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['id_usuario'],
        'codigo' => $_SESSION['usu_codigo'] ?? '',
        'nombre' => $_SESSION['usu_nombre'] ?? 'Usuario',
        'id_rol' => (int) ($_SESSION['id_rol'] ?? 0),
        'rol' => roleNameFromId((int) ($_SESSION['id_rol'] ?? 0)),
    ];
}

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

function requireLogin(): void
{
    if (currentUser() === null) {
        $_SESSION['error'] = 'Debe iniciar sesion para ingresar al sistema.';
        header('Location: ' . url('index.php'));
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    $role = currentUser()['rol'] ?? '';
    if (!in_array($role, $roles, true)) {
        header('Location: ' . url('inicio.php?error=acceso'));
        exit;
    }
}
