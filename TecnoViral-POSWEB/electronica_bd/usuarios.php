<?php
session_start();
require_once 'conexion.php';

// Solo administradores
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['user_rol'] != 'administrador') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TecnoViral — Acceso Denegado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root { --navy:#07172e; --navy2:#0d2347; --blue:#0052cc; --accent:#00c2ff; --danger:#ff4d4d; --white:#ffffff; }
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            html, body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--navy); color: var(--white); display: flex; align-items: center; justify-content: center; overflow: hidden; }
            .bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; background: radial-gradient(ellipse at 20% 20%, rgba(255,77,77,.08) 0%, transparent 50%), radial-gradient(ellipse at 80% 80%, rgba(0,82,204,.12) 0%, transparent 50%), radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%); }
            .bg-grid { position: fixed; inset: 0; z-index: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px); background-size: 48px 48px; }
            .deny-card { position: relative; z-index: 1; background: rgba(255,255,255,.04); border: 1px solid rgba(255,77,77,.2); border-radius: 28px; padding: 56px 60px; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 0 0 1px rgba(255,77,77,.08), 0 32px 80px rgba(0,0,0,.5); animation: fadeUp .6s ease both; }
            @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
            .deny-icon-wrap { width: 100px; height: 100px; border-radius: 28px; background: rgba(255,77,77,.12); border: 2px solid rgba(255,77,77,.25); display: flex; align-items: center; justify-content: center; margin: 0 auto 28px; font-size: 2.8rem; color: var(--danger); position: relative; animation: pulseRed 2.5s ease infinite; }
            @keyframes pulseRed { 0%,100% { box-shadow: 0 0 0 0 rgba(255,77,77,.2); } 50% { box-shadow: 0 0 0 16px rgba(255,77,77,0); } }
            .deny-code { font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 900; color: rgba(255,77,77,.15); line-height: 1; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; }
            .deny-title { font-family: 'Playfair Display', serif; font-size: 1.7rem; font-weight: 700; color: var(--white); margin-bottom: 10px; }
            .deny-sub { font-size: .9rem; color: rgba(255,255,255,.45); line-height: 1.7; margin-bottom: 32px; }
            .deny-user { display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 50px; padding: 8px 20px 8px 8px; margin-bottom: 32px; }
            .deny-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; }
            .deny-uname { font-size: .82rem; font-weight: 600; }
            .deny-urole { font-size: .65rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1px; }
            .btn-volver { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--blue), var(--accent)); color: white; border: none; border-radius: 14px; padding: 14px 32px; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; text-decoration: none; cursor: pointer; transition: all .3s; box-shadow: 0 8px 24px rgba(0,82,204,.3); }
            .btn-volver:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,82,204,.4); color: white; }
            .countdown-bar { width: 100%; height: 3px; background: rgba(255,255,255,.06); border-radius: 4px; margin-top: 24px; overflow: hidden; }
            .countdown-fill { height: 100%; background: linear-gradient(90deg, var(--blue), var(--accent)); border-radius: 4px; animation: shrink 5s linear forwards; }
            @keyframes shrink { from { width: 100%; } to { width: 0%; } }
            .countdown-txt { font-size: .7rem; color: rgba(255,255,255,.25); margin-top: 8px; letter-spacing: 1px; }
        </style>
    </head>
    <body>
        <div class="bg-canvas"></div>
        <div class="bg-grid"></div>
        <div class="deny-card">
            <div class="deny-icon-wrap">
                <i class="fas fa-lock"></i>
                <div class="deny-code">403</div>
            </div>
            <div class="deny-title">Acceso Restringido</div>
            <div class="deny-sub">
                Este módulo es exclusivo para <strong style="color:var(--danger);">administradores</strong>.<br>
                Tu cuenta no tiene los permisos para gestionar usuarios del sistema.
            </div>
            <div class="deny-user">
                <div class="deny-avatar"><?php echo strtoupper(substr($_SESSION['user_nombre'], 0, 1)); ?></div>
                <div>
                    <div class="deny-uname"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></div>
                    <div class="deny-urole">Vendedor · Sin acceso</div>
                </div>
            </div>
            <br>
            <a href="menu_principal.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Regresar al Menú
            </a>
            <div class="countdown-bar"><div class="countdown-fill"></div></div>
            <div class="countdown-txt">Redirigiendo automáticamente en 5 segundos...</div>
        </div>
        <script>setTimeout(() => { window.location.href = 'menu_principal.php'; }, 5000);</script>
    </body>
    </html>
    <?php
    exit();
}

$user_nombre = $_SESSION['user_nombre'];
$user_rol    = $_SESSION['user_rol'];
$mensaje = '';
$error   = '';

// ── CREAR USUARIO ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] == 'crear') {
        $nombre           = mysqli_real_escape_string($conn, trim($_POST['nombre']));
        $apellido_paterno = mysqli_real_escape_string($conn, trim($_POST['apellido_paterno']));
        $apellido_materno = mysqli_real_escape_string($conn, trim($_POST['apellido_materno']));
        $telefono         = mysqli_real_escape_string($conn, trim($_POST['telefono']));
        $direccion        = mysqli_real_escape_string($conn, trim($_POST['direccion']));
        $email            = mysqli_real_escape_string($conn, trim($_POST['email']));
        $nombre_usuario   = mysqli_real_escape_string($conn, trim($_POST['nombre_usuario']));
        $contrasena       = $_POST['contrasena'];
        $confirmar        = $_POST['confirmar_contrasena'];
        $roles_validos = ['vendedor','supervisor','administrador'];
        $rol = in_array($_POST['rol'], $roles_validos) ? $_POST['rol'] : 'vendedor';

        if ($contrasena !== $confirmar) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($contrasena) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } else {
            $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE nombre_usuario = '$nombre_usuario'");
            if (mysqli_num_rows($check) > 0) {
                $error = "El nombre de usuario '$nombre_usuario' ya está en uso.";
            } else {
                $hash  = hash('sha256', $contrasena);
                $query = "INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, telefono, direccion, email, nombre_usuario, contrasena, rol)
                          VALUES ('$nombre','$apellido_paterno','$apellido_materno','$telefono','$direccion','$email','$nombre_usuario','$hash','$rol')";
                if (mysqli_query($conn, $query)) {
                    $mensaje = "Usuario '$nombre_usuario' creado correctamente como $rol.";
                } else {
                    $error = "Error al crear usuario: " . mysqli_error($conn);
                }
            }
        }
    }

    // ── CAMBIAR ROL ──
    if ($_POST['accion'] == 'cambiar_rol') {
        $id      = intval($_POST['id']);
        $roles_ok = ['vendedor','supervisor','administrador'];
        $nuevo = in_array($_POST['nuevo_rol'], $roles_ok) ? $_POST['nuevo_rol'] : 'vendedor';
        if ($id == $_SESSION['user_id']) {
            $error = "No puedes cambiar tu propio rol.";
        } else {
            mysqli_query($conn, "UPDATE usuarios SET rol = '$nuevo' WHERE id = $id");
            $mensaje = "Rol actualizado correctamente.";
        }
    }

    // ── ACTIVAR / DESACTIVAR ──
    if ($_POST['accion'] == 'toggle_activo') {
        $id     = intval($_POST['id']);
        $activo = intval($_POST['activo_actual']) == 1 ? 0 : 1;
        if ($id == $_SESSION['user_id']) {
            $error = "No puedes desactivar tu propia cuenta.";
        } else {
            mysqli_query($conn, "UPDATE usuarios SET activo = $activo WHERE id = $id");
            $mensaje = $activo ? "Usuario activado." : "Usuario desactivado.";
        }
    }

    // ── RESETEAR CONTRASEÑA ──
    if ($_POST['accion'] == 'reset_password') {
        $id          = intval($_POST['id']);
        $nueva_pass  = $_POST['nueva_password'];
        $confirmar2  = $_POST['confirmar_password'];
        if (strlen($nueva_pass) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($nueva_pass !== $confirmar2) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $hash = hash('sha256', $nueva_pass);
            mysqli_query($conn, "UPDATE usuarios SET contrasena = '$hash' WHERE id = $id");
            $mensaje = "Contraseña actualizada correctamente.";
        }
    }

    // ── ELIMINAR ──
    if ($_POST['accion'] == 'eliminar') {
        $id = intval($_POST['id']);
        if ($id == $_SESSION['user_id']) {
            $error = "No puedes eliminar tu propia cuenta.";
        } else {
            mysqli_query($conn, "DELETE FROM usuarios WHERE id = $id");
            $mensaje = "Usuario eliminado.";
        }
    }
}

// Obtener lista de usuarios
$result_usuarios = mysqli_query($conn, "SELECT * FROM usuarios ORDER BY rol DESC, nombre ASC");
$total_usuarios  = mysqli_num_rows($result_usuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Usuarios</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:   #07172e;
            --navy2:  #0d2347;
            --blue:   #0052cc;
            --accent: #00c2ff;
            --gold:   #f5c518;
            --white:  #ffffff;
            --muted:  #7a8ba0;
            --border: rgba(255,255,255,.1);
            --danger: #ff4d4d;
            --success:#00d68f;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
        }

        .bg-canvas {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse at 10% 15%, rgba(0,194,255,.1) 0%, transparent 50%),
                radial-gradient(ellipse at 88% 80%, rgba(0,82,204,.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%);
        }
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .page-wrap {
            position: relative; z-index: 1;
            padding: 24px 28px 50px;
            max-width: 1400px;
            margin: 0 auto;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim   { animation: fadeUp .5s ease both; }
        .anim-1 { animation-delay: .05s; }
        .anim-2 { animation-delay: .12s; }
        .anim-3 { animation-delay: .20s; }

        /* ── TOPBAR ── */
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap;
            background: rgba(13,35,71,.7);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 14px 22px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .logo-img {
            width: 68px; height: 68px; border-radius: 18px; object-fit: cover;
            border: 2px solid rgba(0,194,255,.3);
            box-shadow: 0 0 0 4px rgba(0,194,255,.08), 0 8px 20px rgba(0,0,0,.5);
            transition: transform .4s cubic-bezier(.34,1.56,.64,1);
        }
        .logo-img:hover { transform: scale(1.08) rotate(-2deg); }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem; font-weight: 900; letter-spacing: 3px;
        }
        .brand-name span { color: var(--accent); }
        .brand-sub { font-size: .68rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.35); margin-top: 3px; }

        .page-pill {
            display: flex; align-items: center; gap: 10px;
            background: rgba(245,197,24,.08); border: 1px solid rgba(245,197,24,.2);
            border-radius: 40px; padding: 8px 20px;
        }
        .page-pill i   { color: var(--gold); font-size: .9rem; }
        .page-pill span{ font-size: .78rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.75); }

        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .user-chip {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.05); border: 1px solid var(--border);
            border-radius: 50px; padding: 6px 16px 6px 6px;
        }
        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--blue), var(--accent));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .9rem;
        }
        .u-name { font-size: .82rem; font-weight: 600; }
        .u-role { font-size: .62rem; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; }

        .btn-back {
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.7);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all .25s;
        }
        .btn-back:hover { background: var(--blue); color: white; border-color: var(--blue); transform: translateX(-3px); }

        /* ── ALERTAS ── */
        .tv-alert {
            display: flex; align-items: center; gap: 12px;
            border-radius: 16px; padding: 14px 20px;
            font-size: .88rem; font-weight: 500;
            margin-bottom: 22px; animation: fadeUp .4s ease both;
        }
        .tv-alert.success { background: rgba(0,214,143,.1); border: 1px solid rgba(0,214,143,.25); color: #00d68f; }
        .tv-alert.danger  { background: rgba(255,77,77,.1);  border: 1px solid rgba(255,77,77,.25);  color: #ff8585; }

        /* ── GLASS CARD ── */
        .glass-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 24px; padding: 32px 36px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 40px rgba(0,0,0,.3);
            margin-bottom: 24px;
        }

        .card-header-tv {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 28px; padding-bottom: 18px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .ch-ico {
            width: 46px; height: 46px; border-radius: 14px;
            background: rgba(245,197,24,.15);
            display: flex; align-items: center; justify-content: center;
            color: var(--gold); font-size: 1.1rem;
        }
        .ch-title { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; }
        .ch-sub   { font-size: .72rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 2px; }

        /* ── FORMULARIO ── */
        .field-label {
            font-size: .68rem; font-weight: 700; letter-spacing: 2.5px;
            text-transform: uppercase; color: rgba(255,255,255,.5);
            margin-bottom: 8px; display: block;
        }
        .field-wrap { position: relative; margin-bottom: 20px; }
        .field-wrap .f-ico {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%); color: var(--muted); font-size: .88rem;
            pointer-events: none; transition: color .25s; z-index: 1;
        }
        .field-wrap input,
        .field-wrap select {
            width: 100%; padding: 14px 16px 14px 44px;
            border: 1px solid rgba(255,255,255,.1); border-radius: 14px;
            font-size: .92rem; font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.05); color: var(--white);
            transition: border-color .25s, box-shadow .25s; outline: none;
            -webkit-appearance: none;
        }
        .field-wrap select option { background: var(--navy2); color: white; }
        .field-wrap input::placeholder { color: rgba(255,255,255,.2); }
        .field-wrap input:focus,
        .field-wrap select:focus {
            border-color: var(--accent);
            background: rgba(0,194,255,.05);
            box-shadow: 0 0 0 4px rgba(0,194,255,.1);
        }

        /* Rol selector especial */
        .rol-selector { display: flex; gap: 12px; }
        .rol-option { flex: 1; }
        .rol-option input[type="radio"] { display: none; }
        .rol-option label {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 8px; padding: 16px 12px;
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 16px; cursor: pointer;
            transition: all .25s; text-align: center;
            background: rgba(255,255,255,.03);
        }
        .rol-option label i { font-size: 1.4rem; }
        .rol-option label span { font-size: .8rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
        .rol-option label small { font-size: .68rem; color: rgba(255,255,255,.35); }

        .rol-option input[type="radio"]:checked + label.vendedor-lbl {
            border-color: var(--accent); background: rgba(0,194,255,.1);
        }
        .rol-option input[type="radio"]:checked + label.vendedor-lbl i { color: var(--accent); }

        .rol-option input[type="radio"]:checked + label.supervisor-lbl {
            border-color: #3498db; background: rgba(52,152,219,.1);
        }
        .rol-option input[type="radio"]:checked + label.supervisor-lbl i { color: #3498db; }
        .rol-option input[type="radio"]:checked + label.admin-lbl {
            border-color: var(--gold); background: rgba(245,197,24,.1);
        }
        .rol-option input[type="radio"]:checked + label.admin-lbl i { color: var(--gold); }

        /* Botones */
        .btn-tv {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 13px 28px; border-radius: 14px; border: none;
            font-family: 'DM Sans', sans-serif; font-size: .85rem;
            font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
            cursor: pointer; text-decoration: none; transition: all .25s;
        }
        .btn-save {
            background: linear-gradient(135deg, var(--blue), var(--accent));
            color: white; box-shadow: 0 6px 20px rgba(0,82,204,.35);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,82,204,.45); color: white; }

        /* ── SECTION HEAD ── */
        .section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-head .s-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(245,197,24,.3), transparent); }
        .section-head .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: var(--gold); }

        /* ── TABLA ── */
        .table-wrap { overflow-x: auto; border-radius: 16px; }
        table.tv-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .tv-table thead tr th {
            background: rgba(245,197,24,.1); color: rgba(255,255,255,.6);
            font-size: .65rem; font-weight: 700; letter-spacing: 2.5px;
            text-transform: uppercase; padding: 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08); white-space: nowrap;
        }
        .tv-table thead tr th:first-child { border-radius: 16px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius: 0 16px 0 0; }
        .tv-table tbody tr { transition: background .2s; animation: fadeUp .4s ease both; }
        .tv-table tbody tr:hover { background: rgba(245,197,24,.04); }
        .tv-table tbody td {
            padding: 16px 18px; border-bottom: 1px solid rgba(255,255,255,.05);
            vertical-align: middle; font-size: .88rem; color: rgba(255,255,255,.8);
        }

        .user-name { font-weight: 600; color: var(--white); font-size: .92rem; }
        .user-sub  { font-size: .75rem; color: rgba(255,255,255,.35); margin-top: 2px; }

        /* Avatar tabla */
        .tbl-avatar {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1rem; flex-shrink: 0;
        }
        .tbl-avatar.admin  { background: linear-gradient(135deg, #c8a200, var(--gold)); color: #1a0a00; }
        .tbl-avatar.vendedor    { background: linear-gradient(135deg, var(--blue), var(--accent)); color: white; }
        .tbl-avatar.supervisor  { background: linear-gradient(135deg, #2980b9, #3498db); color: white; }

        /* Rol badge */
        .rol-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 20px;
            font-size: .72rem; font-weight: 700; letter-spacing: .5px;
        }
        .rol-admin      { background: rgba(245,197,24,.15);  color: var(--gold);   border: 1px solid rgba(245,197,24,.3); }
        .rol-supervisor { background: rgba(52,152,219,.15);  color: #3498db;       border: 1px solid rgba(52,152,219,.3); }
        .rol-vendedor   { background: rgba(0,194,255,.12);   color: var(--accent); border: 1px solid rgba(0,194,255,.25); }

        /* Estado activo */
        .estado-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px;
            font-size: .72rem; font-weight: 700;
        }
        .estado-on  { background: rgba(0,214,143,.12); color: #00d68f; border: 1px solid rgba(0,214,143,.25); }
        .estado-off { background: rgba(255,77,77,.12);  color: var(--danger); border: 1px solid rgba(255,77,77,.25); }

        /* Acciones */
        .action-btns { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .btn-act {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; border: none; cursor: pointer;
            transition: all .25s; text-decoration: none;
        }
        .btn-rol   { background: rgba(245,197,24,.15); border: 1px solid rgba(245,197,24,.25); color: var(--gold); }
        .btn-rol:hover   { background: var(--gold); color: #1a0a00; transform: scale(1.1); }
        .btn-pass  { background: rgba(0,82,204,.2); border: 1px solid rgba(0,82,204,.3); color: var(--accent); }
        .btn-pass:hover  { background: var(--blue); color: white; transform: scale(1.1); }
        .btn-toggle{ background: rgba(0,214,143,.12); border: 1px solid rgba(0,214,143,.25); color: #00d68f; }
        .btn-toggle:hover{ background: #00d68f; color: white; transform: scale(1.1); }
        .btn-toggle.off  { background: rgba(255,77,77,.12); border-color: rgba(255,77,77,.25); color: var(--danger); }
        .btn-toggle.off:hover { background: var(--danger); color: white; }
        .btn-del   { background: rgba(255,77,77,.12); border: 1px solid rgba(255,77,77,.25); color: var(--danger); }
        .btn-del:hover   { background: var(--danger); color: white; transform: scale(1.1); }

        /* YO badge */
        .yo-badge {
            display: inline-block; background: rgba(0,194,255,.15);
            border: 1px solid rgba(0,194,255,.3); color: var(--accent);
            font-size: .6rem; font-weight: 700; letter-spacing: 1px;
            padding: 2px 8px; border-radius: 20px; text-transform: uppercase; margin-left: 6px;
        }

        /* ── MODAL ── */
        .modal-dark .modal-content {
            background: var(--navy2);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px; color: white;
        }
        .modal-dark .modal-header {
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: 20px 24px;
        }
        .modal-dark .modal-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; }
        .modal-dark .modal-footer { border-top: 1px solid rgba(255,255,255,.08); padding: 16px 24px; }
        .modal-dark .btn-close { filter: invert(1); opacity: .5; }

        .modal-dark .field-wrap input,
        .modal-dark .field-wrap select {
            background: rgba(255,255,255,.07);
            border-color: rgba(255,255,255,.12);
        }

        /* Footer */
        .page-footer {
            text-align: center; margin-top: 36px;
            font-size: .66rem; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,.15);
        }
        .page-footer span { color: var(--gold); opacity: .5; }

        /* Responsive */
        @media (max-width: 768px) {
            .page-wrap  { padding: 14px 12px 36px; }
            .glass-card { padding: 22px 16px; }
            .topbar     { padding: 12px 14px; }
            .logo-img   { width: 52px; height: 52px; }
            .page-pill  { display: none; }
            .rol-selector { flex-direction: column; }
            .tv-table thead { display: none; }
            .tv-table tbody td {
                display: block; text-align: right;
                padding: 10px 16px;
                border-bottom: 1px dashed rgba(255,255,255,.05);
            }
            .tv-table tbody td::before {
                content: attr(data-label);
                float: left; font-weight: 700;
                font-size: .65rem; letter-spacing: 1px;
                text-transform: uppercase; color: var(--gold);
            }
            .tv-table tbody tr {
                display: block; border: 1px solid rgba(255,255,255,.08);
                border-radius: 16px; margin-bottom: 14px;
                background: rgba(255,255,255,.03);
            }
            .action-btns { justify-content: flex-end; }
        }
    </style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="bg-grid"></div>

<div class="page-wrap">

    <!-- ── TOPBAR ── -->
    <div class="topbar anim anim-1">
        <a href="menu_principal.php" class="brand-row" style="text-decoration:none;">
            <img src="imagenes/logoe.jpeg" alt="TecnoViral" class="logo-img"
                 onerror="this.src='https://placehold.co/68x68/0052cc/fff?text=TV'">
            <div>
                <div class="brand-name">TECNO<span>VIRAL</span></div>
                <div class="brand-sub">Punto de Venta · Sistema Táctil</div>
            </div>
        </a>

        <div class="page-pill">
            <i class="fas fa-users-gear"></i>
            <span>Gestión de Usuarios</span>
        </div>

        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?php echo strtoupper(substr($user_nombre,0,1)); ?></div>
                <div>
                    <div class="u-name"><?php echo htmlspecialchars($user_nombre); ?></div>
                    <div class="u-role">★ Administrador</div>
                </div>
            </div>
            <a href="menu_principal.php" class="btn-back" title="Regresar al menú">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- ── ALERTAS ── -->
    <?php if ($mensaje): ?>
    <div class="tv-alert success anim"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="tv-alert danger anim"><i class="fas fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ── FORMULARIO CREAR USUARIO ── -->
    <div class="glass-card anim anim-2">
        <div class="card-header-tv">
            <div class="ch-ico"><i class="fas fa-user-plus"></i></div>
            <div>
                <div class="ch-title">Nuevo Usuario</div>
                <div class="ch-sub">Completa los datos para crear una cuenta</div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="field-label">Nombre</label>
                    <div class="field-wrap">
                        <input type="text" name="nombre" required placeholder="Nombre">
                        <i class="fas fa-user f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Apellido Paterno</label>
                    <div class="field-wrap">
                        <input type="text" name="apellido_paterno" required placeholder="Apellido paterno">
                        <i class="fas fa-user f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Apellido Materno</label>
                    <div class="field-wrap">
                        <input type="text" name="apellido_materno" placeholder="Apellido materno">
                        <i class="fas fa-user f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Teléfono</label>
                    <div class="field-wrap">
                        <input type="tel" name="telefono" placeholder="10 dígitos">
                        <i class="fas fa-phone f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Email</label>
                    <div class="field-wrap">
                        <input type="email" name="email" placeholder="correo@ejemplo.com">
                        <i class="fas fa-envelope f-ico"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="field-label">Dirección</label>
                    <div class="field-wrap">
                        <input type="text" name="direccion" placeholder="Dirección del usuario">
                        <i class="fas fa-map-pin f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Nombre de Usuario</label>
                    <div class="field-wrap">
                        <input type="text" name="nombre_usuario" required placeholder="usuario123">
                        <i class="fas fa-at f-ico"></i>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="field-label">Contraseña</label>
                    <div class="field-wrap">
                        <input type="password" name="contrasena" required placeholder="Mín. 6 caracteres">
                        <i class="fas fa-lock f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Confirmar Contraseña</label>
                    <div class="field-wrap">
                        <input type="password" name="confirmar_contrasena" required placeholder="Repetir contraseña">
                        <i class="fas fa-lock f-ico"></i>
                    </div>
                </div>

                <!-- Selector de Rol -->
                <div class="col-12">
                    <label class="field-label">Rol del Usuario</label>
                    <div class="rol-selector">
                        <div class="rol-option">
                            <input type="radio" name="rol" id="rol_vendedor" value="vendedor" checked>
                            <label for="rol_vendedor" class="vendedor-lbl">
                                <i class="fas fa-user-tie"></i>
                                <span>Vendedor</span>
                                <small>Ventas, productos e inventario · Desc. máx. 10%</small>
                            </label>
                        </div>
                        <div class="rol-option">
                            <input type="radio" name="rol" id="rol_supervisor" value="supervisor">
                            <label for="rol_supervisor" class="supervisor-lbl">
                                <i class="fas fa-user-shield"></i>
                                <span>Supervisor</span>
                                <small>Reportes, cierres y todos los gastos · Desc. máx. 20%</small>
                            </label>
                        </div>
                        <div class="rol-option">
                            <input type="radio" name="rol" id="rol_admin" value="administrador">
                            <label for="rol_admin" class="admin-lbl">
                                <i class="fas fa-crown"></i>
                                <span>Administrador</span>
                                <small>Acceso total incluyendo usuarios y configuración</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-tv btn-save">
                    <i class="fas fa-user-plus"></i> Crear Usuario
                </button>
            </div>
        </form>
    </div>

    <!-- ── LISTA DE USUARIOS ── -->
    <div class="section-head anim anim-3">
        <div class="s-label">Usuarios registrados (<?php echo $total_usuarios; ?>)</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-3" style="padding: 24px 28px;">
        <div class="table-wrap">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre completo</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($u = mysqli_fetch_assoc($result_usuarios)): ?>
                    <tr>
                        <td data-label="Usuario">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="tbl-avatar <?php echo $u['rol']; ?>">
                                    <?php echo strtoupper(substr($u['nombre'],0,1)); ?>
                                </div>
                                <div>
                                    <div class="user-name">
                                        <?php echo htmlspecialchars($u['nombre_usuario']); ?>
                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="yo-badge">Tú</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Nombre">
                            <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido_paterno'] . ' ' . $u['apellido_materno']); ?>
                        </td>
                        <td data-label="Teléfono">
                            <?php echo $u['telefono'] ?: '—'; ?>
                        </td>
                        <td data-label="Rol">
                            <?php if ($u['rol'] == 'administrador'): ?>
                                <span class="rol-badge rol-admin"><i class="fas fa-crown"></i> Admin</span>
                            <?php elseif ($u['rol'] == 'supervisor'): ?>
                                <span class="rol-badge rol-supervisor"><i class="fas fa-user-shield"></i> Supervisor</span>
                            <?php else: ?>
                                <span class="rol-badge rol-vendedor"><i class="fas fa-user-tie"></i> Vendedor</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Estado">
                            <?php if ($u['activo']): ?>
                                <span class="estado-badge estado-on"><i class="fas fa-circle"></i> Activo</span>
                            <?php else: ?>
                                <span class="estado-badge estado-off"><i class="fas fa-circle"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Registro" style="font-size:.78rem;color:rgba(255,255,255,.4);">
                            <?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?>
                        </td>
                        <td data-label="Acciones">
                            <div class="action-btns">

                                <!-- Cambiar Rol -->
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="cambiar_rol">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="nuevo_rol" value="<?php echo $u['rol'] == 'administrador' ? 'vendedor' : ($u['rol'] == 'supervisor' ? 'administrador' : 'supervisor'); ?>">
                                    <button type="submit" class="btn-act btn-rol"
                                        title="<?php echo $u['rol'] == 'administrador' ? 'Cambiar a Vendedor' : ($u['rol'] == 'supervisor' ? 'Cambiar a Admin' : 'Cambiar a Supervisor'); ?>"
                                        onclick="return confirm('¿Cambiar rol de <?php echo htmlspecialchars($u['nombre_usuario']); ?>?')">
                                        <i class="fas fa-<?php echo $u['rol'] == 'administrador' ? 'user-tie' : 'crown'; ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- Cambiar contraseña -->
                                <button class="btn-act btn-pass" title="Cambiar contraseña"
                                    onclick="abrirModalPass(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre_usuario']); ?>')">
                                    <i class="fas fa-key"></i>
                                </button>

                                <!-- Activar / Desactivar -->
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="toggle_activo">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="activo_actual" value="<?php echo $u['activo']; ?>">
                                    <button type="submit" class="btn-act btn-toggle <?php echo $u['activo'] ? '' : 'off'; ?>"
                                        title="<?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>"
                                        onclick="return confirm('¿<?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?> a <?php echo htmlspecialchars($u['nombre_usuario']); ?>?')">
                                        <i class="fas fa-<?php echo $u['activo'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                                    </button>
                                </form>

                                <!-- Eliminar -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn-act btn-del" title="Eliminar usuario"
                                        onclick="return confirm('¿Eliminar permanentemente a <?php echo htmlspecialchars($u['nombre_usuario']); ?>?')">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-footer">
        <span>◆</span> &nbsp;TecnoViral POS v1.0 &nbsp;<span>◆</span>
    </div>

</div>

<!-- ── MODAL CAMBIAR CONTRASEÑA ── -->
<div class="modal fade modal-dark" id="modalPass" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2" style="color:var(--accent);"></i> Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="reset_password">
                <input type="hidden" name="id" id="modalPassId">
                <div class="modal-body p-4">
                    <p style="color:rgba(255,255,255,.5);font-size:.85rem;margin-bottom:20px;">
                        Cambiando contraseña de: <strong id="modalPassUser" style="color:var(--accent);"></strong>
                    </p>
                    <label class="field-label">Nueva Contraseña</label>
                    <div class="field-wrap mb-3">
                        <input type="password" name="nueva_password" required placeholder="Mínimo 6 caracteres">
                        <i class="fas fa-lock f-ico"></i>
                    </div>
                    <label class="field-label">Confirmar Contraseña</label>
                    <div class="field-wrap">
                        <input type="password" name="confirmar_password" required placeholder="Repetir contraseña">
                        <i class="fas fa-lock f-ico"></i>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-tv" data-bs-dismiss="modal"
                        style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.6);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-tv btn-save">
                        <i class="fas fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function abrirModalPass(id, usuario) {
        document.getElementById('modalPassId').value   = id;
        document.getElementById('modalPassUser').textContent = usuario;
        new bootstrap.Modal(document.getElementById('modalPass')).show();
    }
</script>
</body>
</html>