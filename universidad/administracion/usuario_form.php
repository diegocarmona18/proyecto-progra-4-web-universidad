<?php
require_once __DIR__ . '/../config.php';
requireRole(['administrativo']);

function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$idUsuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$esEdicion = $idUsuario !== false && $idUsuario !== null;
$pageTitle = $esEdicion ? 'Modificar Usuario' : 'Nuevo Usuario';
$errores = [];

$stmtRoles = $pdo->query(
    "SELECT id_rol, rol_nombre
     FROM t_rol
     WHERE rol_estado = 'A'
     ORDER BY rol_nombre"
);
$roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

$usuario = [
    'id_usuario' => '',
    'usu_codigo' => '',
    'usu_nombre' => '',
    'usu_correo' => '',
    'id_rol' => '',
    'usu_estado' => 'A',
    'usu_intentofallido' => 0,
];

if ($esEdicion) {
    $stmt = $pdo->prepare(
        "SELECT id_usuario, usu_codigo, usu_nombre, usu_correo,
                id_rol, usu_estado, usu_intentofallido
         FROM t_usuario
         WHERE id_usuario = :id_usuario"
    );
    $stmt->execute([':id_usuario' => $idUsuario]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        $_SESSION['usuarios_mensaje'] = [
            'tipo' => 'warning',
            'texto' => 'El usuario solicitado no existe.'
        ];
        header('Location: usuarios.php');
        exit;
    }

    $usuario = $registro;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $esEdicion = $idPost !== false && $idPost !== null;
    $idUsuario = $esEdicion ? $idPost : null;

    $usuario['id_usuario'] = $idUsuario ?? '';
    $usuario['usu_codigo'] = trim($_POST['usu_codigo'] ?? '');
    $usuario['usu_nombre'] = trim($_POST['usu_nombre'] ?? '');
    $usuario['usu_correo'] = trim($_POST['usu_correo'] ?? '');
    $usuario['id_rol'] = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT) ?: '';
    $usuario['usu_estado'] = strtoupper(trim($_POST['usu_estado'] ?? 'A'));

    $clave = (string)($_POST['usu_clave'] ?? '');
    $confirmarClave = (string)($_POST['confirmar_clave'] ?? '');

    if ($usuario['usu_codigo'] === '') {
        $errores[] = 'El código de usuario es obligatorio.';
    } elseif (mb_strlen($usuario['usu_codigo']) > 15) {
        $errores[] = 'El código de usuario no puede superar 15 caracteres.';
    }

    if ($usuario['usu_nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($usuario['usu_nombre']) > 60) {
        $errores[] = 'El nombre no puede superar 60 caracteres.';
    }

    if ($usuario['usu_correo'] === '') {
        $errores[] = 'El correo es obligatorio.';
    } elseif (
        !filter_var($usuario['usu_correo'], FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $usuario['usu_correo'])
    ) {
        $errores[] = 'Ingrese un correo válido, por ejemplo: usuario@dominio.com.';
    } elseif (mb_strlen($usuario['usu_correo']) > 60) {
        $errores[] = 'El correo no puede superar 60 caracteres.';
    }

    if (!$usuario['id_rol']) {
        $errores[] = 'Debe seleccionar un rol.';
    } else {
        $stmtRol = $pdo->prepare(
            "SELECT COUNT(*)
             FROM t_rol
             WHERE id_rol = :id_rol
               AND rol_estado = 'A'"
        );
        $stmtRol->execute([':id_rol' => $usuario['id_rol']]);
        if ((int)$stmtRol->fetchColumn() === 0) {
            $errores[] = 'El rol seleccionado no es válido o está inactivo.';
        }
    }

    if (!in_array($usuario['usu_estado'], ['A', 'I', 'B'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if (!$esEdicion && $clave === '') {
        $errores[] = 'La contraseña es obligatoria para un usuario nuevo.';
    }

    if ($clave !== '') {
        if (strlen($clave) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($clave !== $confirmarClave) {
            $errores[] = 'La contraseña y su confirmación no coinciden.';
        }
    }

    /*
     * Validar códigos o correos duplicados.
     * Se utilizan consultas diferentes para creación y edición porque,
     * con PDO::ATTR_EMULATE_PREPARES desactivado, no debe reutilizarse
     * el mismo marcador nombrado varias veces dentro de una consulta.
     */
    if ($esEdicion) {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_usuario
             FROM t_usuario
             WHERE (
                    LOWER(usu_codigo) = LOWER(:usu_codigo)
                    OR LOWER(usu_correo) = LOWER(:usu_correo)
                   )
               AND id_usuario <> :id_usuario
             LIMIT 1"
        );

        $stmtDuplicado->execute([
            ':usu_codigo' => $usuario['usu_codigo'],
            ':usu_correo' => $usuario['usu_correo'],
            ':id_usuario' => $idUsuario,
        ]);
    } else {
        $stmtDuplicado = $pdo->prepare(
            "SELECT id_usuario
             FROM t_usuario
             WHERE LOWER(usu_codigo) = LOWER(:usu_codigo)
                OR LOWER(usu_correo) = LOWER(:usu_correo)
             LIMIT 1"
        );

        $stmtDuplicado->execute([
            ':usu_codigo' => $usuario['usu_codigo'],
            ':usu_correo' => $usuario['usu_correo'],
        ]);
    }

    if ($stmtDuplicado->fetch()) {
        $errores[] = 'Ya existe otro usuario con el mismo código o correo electrónico.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            if ($esEdicion) {
                $stmtActual = $pdo->prepare(
                    "SELECT usu_estado
                     FROM t_usuario
                     WHERE id_usuario = :id_usuario
                     FOR UPDATE"
                );
                $stmtActual->execute([':id_usuario' => $idUsuario]);
                $estadoActual = $stmtActual->fetchColumn();

                if ($estadoActual === false) {
                    throw new RuntimeException('El usuario ya no existe.');
                }

                /* Los usuarios bloqueados solo se desbloquean desde el botón de candado. */
                if ($estadoActual === 'B') {
                    $usuario['usu_estado'] = 'B';
                }

                $sql = "UPDATE t_usuario
                        SET usu_codigo = :usu_codigo,
                            usu_nombre = :usu_nombre,
                            usu_correo = :usu_correo,
                            id_rol = :id_rol,
                            usu_estado = :usu_estado";

                $parametros = [
                    ':usu_codigo' => $usuario['usu_codigo'],
                    ':usu_nombre' => $usuario['usu_nombre'],
                    ':usu_correo' => $usuario['usu_correo'],
                    ':id_rol' => $usuario['id_rol'],
                    ':usu_estado' => $usuario['usu_estado'],
                    ':id_usuario' => $idUsuario,
                ];

                if ($clave !== '') {
                    $sql .= ", usu_clave = :usu_clave";
                    $parametros[':usu_clave'] = password_hash($clave, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id_usuario = :id_usuario";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($parametros);

                $mensaje = 'La información del usuario fue actualizada correctamente.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO t_usuario (
                        usu_codigo,
                        usu_nombre,
                        usu_correo,
                        usu_clave,
                        id_rol,
                        usu_intentofallido,
                        usu_estado,
                        usu_fechahorareg,
                        usu_usuarioreg
                    ) VALUES (
                        :usu_codigo,
                        :usu_nombre,
                        :usu_correo,
                        :usu_clave,
                        :id_rol,
                        0,
                        :usu_estado,
                        NOW(),
                        :usu_usuarioreg
                    )"
                );

                $stmt->execute([
                    ':usu_codigo' => $usuario['usu_codigo'],
                    ':usu_nombre' => $usuario['usu_nombre'],
                    ':usu_correo' => $usuario['usu_correo'],
                    ':usu_clave' => password_hash($clave, PASSWORD_DEFAULT),
                    ':id_rol' => $usuario['id_rol'],
                    ':usu_estado' => $usuario['usu_estado'] === 'B' ? 'A' : $usuario['usu_estado'],
                    ':usu_usuarioreg' => $_SESSION['id_usuario'] ?? null,
                ]);

                $mensaje = 'El usuario fue creado correctamente.';
            }

            $pdo->commit();

            if ($esEdicion) {
                registrarBitacoraUsuario('usuario_actualizado', [
                    'id_usuario' => $idUsuario,
                    'usu_codigo' => $usuario['usu_codigo'],
                    'usuario_nombre' => $usuario['usu_nombre'],
                    'usuario_correo' => $usuario['usu_correo'],
                    'rol' => $usuario['id_rol'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            } else {
                $nuevoId = (int) $pdo->lastInsertId();
                registrarBitacoraUsuario('usuario_creado', [
                    'id_usuario' => $nuevoId,
                    'usu_codigo' => $usuario['usu_codigo'],
                    'usuario_nombre' => $usuario['usu_nombre'],
                    'usuario_correo' => $usuario['usu_correo'],
                    'rol' => $usuario['id_rol'],
                    'realizado_por' => $_SESSION['usu_codigo'] ?? 'Sistema',
                ]);
            }

            $_SESSION['usuarios_mensaje'] = [
                'tipo' => 'success',
                'texto' => $mensaje
            ];
            header('Location: usuarios.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No fue posible guardar la información. Verifique los datos e intente nuevamente.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars(url('assets/css/mantenimientos.css'), ENT_QUOTES, 'UTF-8') ?>">

<section class="user-form-hero mb-4">
    <div class="d-flex align-items-center gap-3 position-relative" style="z-index:1">
        <span class="hero-form-icon">
            <i class="bi <?= $esEdicion ? 'bi-person-gear' : 'bi-person-plus-fill' ?>"></i>
        </span>
        <div>
            <h1 class="h2 fw-bold mb-1"><?= $esEdicion ? 'Modificar usuario' : 'Crear nuevo usuario' ?></h1>
            <p class="mb-0 opacity-75">
                <?= $esEdicion
                    ? 'Actualice los datos personales, credenciales y permisos del usuario.'
                    : 'Registre la información necesaria para habilitar un nuevo acceso.' ?>
            </p>
        </div>
    </div>
</section>

<?php if ($errores): ?>
    <div class="alert alert-danger shadow-sm border-0 rounded-4" role="alert">
        <div class="d-flex gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>No fue posible guardar el usuario:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="post" class="form-shell" autocomplete="off" id="userForm">
    <input type="hidden" name="id_usuario" value="<?= e($usuario['id_usuario']) ?>">

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-person-vcard"></i></span>
            <div>
                <h4 class="mb-1">Información del usuario</h4>
                <p class="text-secondary mb-0">Datos que identificarán al usuario dentro del sistema.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <label for="usu_nombre" class="form-label fw-semibold">Nombre completo</label>
                <div class="field-wrap">
                    <i class="bi bi-person"></i>
                    <input
                        type="text"
                        class="form-control"
                        id="usu_nombre"
                        name="usu_nombre"
                        maxlength="60"
                        value="<?= e($usuario['usu_nombre']) ?>"
                        placeholder="Ej. Pedro Perez Juarez"
                        required
                    >
                </div>
            </div>

            <div class="col-md-6">
                <label for="usu_correo" class="form-label fw-semibold">Correo electrónico</label>
                <div class="field-wrap">
                    <i class="bi bi-envelope"></i>
                    <input
                        type="email"
                        class="form-control"
                        id="usu_correo"
                        name="usu_correo"
                        maxlength="60"
                        pattern="^[^\s@]+@[^\s@]+\.[^\s@]{2,}$"
                        title="Ingrese un correo válido, por ejemplo: usuario@dominio.com"
                        value="<?= e($usuario['usu_correo']) ?>"
                        placeholder="usuario@universidad.edu"
                        required
                    >
                </div>
            </div>

            <div class="col-md-6">
                <label for="usu_codigo" class="form-label fw-semibold">Código de usuario</label>
                <div class="field-wrap">
                    <i class="bi bi-at"></i>
                    <input
                        type="text"
                        class="form-control"
                        id="usu_codigo"
                        name="usu_codigo"
                        maxlength="15"
                        value="<?= e($usuario['usu_codigo']) ?>"
                        placeholder="Ej. shernandez"
                        required
                    >
                </div>
                <small class="text-secondary">Este será el usuario utilizado para iniciar sesión.</small>
            </div>

            <div class="col-md-6">
                <label for="id_rol" class="form-label fw-semibold">Rol del sistema</label>
                <div class="field-wrap">
                    <i class="bi bi-person-badge"></i>
                    <select class="form-select" id="id_rol" name="id_rol" required>
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option
                                value="<?= (int)$rol['id_rol'] ?>"
                                <?= (int)$usuario['id_rol'] === (int)$rol['id_rol'] ? 'selected' : '' ?>
                            >
                                <?= e($rol['rol_nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-shield-lock"></i></span>
            <div>
                <h4 class="mb-1">Credenciales de acceso</h4>
                <p class="text-secondary mb-0">
                    <?= $esEdicion
                        ? 'Deje las contraseñas vacías para conservar la contraseña actual.'
                        : 'Defina una contraseña inicial para el nuevo usuario.' ?>
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <label for="usu_clave" class="form-label fw-semibold">
                    <?= $esEdicion ? 'Nueva contraseña' : 'Contraseña' ?>
                </label>
                <div class="field-wrap">
                    <i class="bi bi-key"></i>
                    <input
                        type="password"
                        class="form-control pe-5"
                        id="usu_clave"
                        name="usu_clave"
                        minlength="8"
                        placeholder="Mínimo 8 caracteres"
                        <?= $esEdicion ? '' : 'required' ?>
                    >
                    <button type="button" class="password-toggle" data-target="usu_clave" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="col-md-6">
                <label for="confirmar_clave" class="form-label fw-semibold">Confirmar contraseña</label>
                <div class="field-wrap">
                    <i class="bi bi-key-fill"></i>
                    <input
                        type="password"
                        class="form-control pe-5"
                        id="confirmar_clave"
                        name="confirmar_clave"
                        minlength="8"
                        placeholder="Repita la contraseña"
                        <?= $esEdicion ? '' : 'required' ?>
                    >
                    <button type="button" class="password-toggle" data-target="confirmar_clave" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="col-12">
                <div class="password-help">
                    <i class="bi bi-info-circle me-2"></i>
                    Utilice al menos 8 caracteres. Se recomienda combinar mayúsculas, minúsculas, números y símbolos.
                </div>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading">
            <span><i class="bi bi-toggle-on"></i></span>
            <div>
                <h4 class="mb-1">Estado de la cuenta</h4>
                <p class="text-secondary mb-0">Determine si el usuario puede ingresar al sistema.</p>
            </div>
        </div>

        <?php if ($esEdicion && $usuario['usu_estado'] === 'B'): ?>
            <input type="hidden" name="usu_estado" value="B">
            <div class="blocked-notice">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-lock-fill fs-4"></i>
                    <div>
                        <strong>Usuario bloqueado</strong>
                        <div class="small">Puede modificar sus datos, pero debe desbloquearlo desde el botón de candado de la lista.</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="status-option">
                        <input type="radio" name="usu_estado" value="A" <?= $usuario['usu_estado'] === 'A' ? 'checked' : '' ?>>
                        <span class="status-card status-active-card">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>
                                <strong class="d-block">Activo</strong>
                                <small class="text-secondary">El usuario podrá iniciar sesión.</small>
                            </span>
                        </span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="status-option">
                        <input type="radio" name="usu_estado" value="I" <?= $usuario['usu_estado'] === 'I' ? 'checked' : '' ?>>
                        <span class="status-card status-inactive-card">
                            <i class="bi bi-pause-circle-fill"></i>
                            <span>
                                <strong class="d-block">Inactivo</strong>
                                <small class="text-secondary">El usuario no podrá iniciar sesión.</small>
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="form-section bg-light-subtle">
        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="usuarios.php" class="btn btn-cancel-user">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-save-user">
                <i class="bi bi-floppy-fill me-2"></i>
                <?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
        </div>
    </section>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.target);
            const icon = button.querySelector('i');
            const mostrar = input.type === 'password';

            input.type = mostrar ? 'text' : 'password';
            icon.className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
            button.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    const form = document.getElementById('userForm');
    form.addEventListener('submit', function (event) {
        const correo = document.getElementById('usu_correo');
        const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        const clave = document.getElementById('usu_clave').value;
        const confirmar = document.getElementById('confirmar_clave').value;

        correo.setCustomValidity('');

        if (!patronCorreo.test(correo.value.trim())) {
            event.preventDefault();
            correo.setCustomValidity('Ingrese un correo válido, por ejemplo: usuario@dominio.com');
            correo.reportValidity();
            return;
        }

        if ((clave !== '' || confirmar !== '') && clave !== confirmar) {
            event.preventDefault();
            document.getElementById('confirmar_clave').setCustomValidity('Las contraseñas no coinciden.');
            document.getElementById('confirmar_clave').reportValidity();
        } else {
            document.getElementById('confirmar_clave').setCustomValidity('');
        }
    });

    document.getElementById('usu_correo').addEventListener('input', function () {
        this.setCustomValidity('');
    });

    document.getElementById('confirmar_clave').addEventListener('input', function () {
        this.setCustomValidity('');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
