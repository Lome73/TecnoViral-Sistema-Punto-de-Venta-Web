<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registro'])) {
    $nombre               = mysqli_real_escape_string($conn, trim($_POST['nombre']));
    $apellido_paterno     = mysqli_real_escape_string($conn, trim($_POST['apellido_paterno']));
    $apellido_materno     = mysqli_real_escape_string($conn, trim($_POST['apellido_materno']));
    $direccion            = mysqli_real_escape_string($conn, trim($_POST['direccion']));
    $telefono             = mysqli_real_escape_string($conn, trim($_POST['telefono']));
    $nombre_usuario       = mysqli_real_escape_string($conn, trim($_POST['nombre_usuario']));
    $contrasena           = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    if (empty($nombre) || empty($apellido_paterno) || empty($nombre_usuario)) {
        $error = "Nombre, apellido paterno y usuario son obligatorios.";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE nombre_usuario = '$nombre_usuario'");
        if (mysqli_num_rows($check) > 0) {
            $error = "El nombre de usuario '$nombre_usuario' ya está registrado.";
        } else {
            $hash  = hash('sha256', $contrasena);
            $query = "INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, direccion, telefono, nombre_usuario, contrasena, rol)
                      VALUES ('$nombre','$apellido_paterno','$apellido_materno','$direccion','$telefono','$nombre_usuario','$hash','vendedor')";
            if (mysqli_query($conn, $query)) {
                $mensaje = "ok";
            } else {
                $error = "Error al registrar: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoViral — Registro de Usuario</title>

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
            --purple: #a569bd;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--navy);
            color: var(--white);
            display: flex;
            overflow-x: hidden;
        }

        /* ── FONDO ── */
        .bg-canvas {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(165,105,189,.1) 0%, transparent 50%),
                radial-gradient(ellipse at 85% 75%, rgba(0,82,204,.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%);
        }
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        /* ── PANEL IZQUIERDO ── */
        .brand-panel {
            width: 420px;
            min-height: 100vh;
            background: rgba(13,35,71,.5);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }

        .logo-frame {
            width: 130px; height: 130px;
            border-radius: 32px;
            overflow: hidden;
            border: 2px solid rgba(165,105,189,.4);
            box-shadow: 0 0 0 6px rgba(165,105,189,.08), 0 0 0 14px rgba(165,105,189,.04), 0 24px 60px rgba(0,0,0,.5);
            margin-bottom: 28px;
            transition: transform .4s cubic-bezier(.34,1.56,.64,1);
        }
        .logo-frame:hover { transform: scale(1.06) rotate(-2deg); }
        .logo-frame img { width: 100%; height: 100%; object-fit: cover; }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem; font-weight: 900;
            letter-spacing: 4px; color: var(--white);
            text-align: center; margin-bottom: 6px;
        }
        .brand-name span { color: var(--accent); }
        .brand-tagline {
            font-size: .7rem; letter-spacing: 4px;
            text-transform: uppercase; color: rgba(255,255,255,.35);
            text-align: center; margin-bottom: 40px;
        }

        .info-list { list-style: none; width: 100%; }
        .info-list li {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,.05);
            font-size: .85rem; color: rgba(255,255,255,.6);
        }
        .info-list li:last-child { border-bottom: none; }
        .info-ico {
            width: 34px; height: 34px; border-radius: 10px;
            background: rgba(165,105,189,.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .info-ico i { color: var(--purple); font-size: .85rem; }

        .panel-footer {
            margin-top: auto;
            padding-top: 32px;
            font-size: .65rem;
            color: rgba(255,255,255,.2);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
        }

        /* ── PANEL DERECHO ── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 50px;
            position: relative;
            z-index: 1;
            overflow-y: auto;
        }

        .form-inner {
            width: 100%;
            max-width: 700px;
            animation: fadeUp .6s ease both;
        }

        @keyframes fadeUp { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }

        /* ── ÉXITO ── */
        .success-card {
            background: linear-gradient(135deg, rgba(0,135,90,.12), rgba(0,82,204,.08));
            border: 1px solid rgba(0,214,143,.2);
            border-radius: 28px; padding: 56px 48px;
            text-align: center;
        }
        .success-ico {
            width: 90px; height: 90px; border-radius: 24px;
            background: rgba(0,214,143,.15); border: 2px solid rgba(0,214,143,.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; font-size: 2.5rem; color: var(--success);
            animation: pulseOk 2.5s ease infinite;
        }
        @keyframes pulseOk { 0%,100%{box-shadow:0 0 0 0 rgba(0,214,143,.2);}50%{box-shadow:0 0 0 16px rgba(0,214,143,0);} }
        .success-title { font-family:'Playfair Display',serif; font-size:1.9rem; font-weight:700; color:var(--success); margin-bottom:10px; }
        .success-sub { font-size:.9rem; color:rgba(255,255,255,.5); margin-bottom:32px; line-height:1.7; }

        /* ── FORM CARD ── */
        .form-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 28px; padding: 40px 44px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 40px rgba(0,0,0,.3);
        }

        .form-header { margin-bottom: 32px; }
        .form-header .greeting {
            font-size: .68rem; font-weight: 700;
            letter-spacing: 4px; text-transform: uppercase;
            color: var(--purple); margin-bottom: 10px;
            display: flex; align-items: center; gap: 10px;
        }
        .form-header .greeting::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(to right, rgba(165,105,189,.4), transparent);
        }
        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem; font-weight: 700;
            color: var(--white); margin-bottom: 6px;
        }
        .form-header p { font-size: .85rem; color: rgba(255,255,255,.4); }

        /* Divisor */
        .header-line { display: flex; gap: 6px; margin-bottom: 28px; }
        .header-line span { height: 3px; border-radius: 4px; }
        .header-line span:nth-child(1) { width: 40px; background: var(--purple); }
        .header-line span:nth-child(2) { width: 20px; background: rgba(165,105,189,.5); }
        .header-line span:nth-child(3) { width: 10px; background: rgba(165,105,189,.2); }

        /* Sección */
        .form-section {
            font-size: .65rem; font-weight: 700;
            letter-spacing: 3px; text-transform: uppercase;
            color: rgba(255,255,255,.3); margin-bottom: 14px;
            display: flex; align-items: center; gap: 10px;
        }
        .form-section::after {
            content: ''; flex: 1; height: 1px;
            background: rgba(255,255,255,.06);
        }

        /* Campos */
        .field-label {
            font-size: .67rem; font-weight: 700;
            letter-spacing: 2.5px; text-transform: uppercase;
            color: rgba(255,255,255,.45); margin-bottom: 8px; display: block;
        }
        .field-wrap { position: relative; margin-bottom: 0; }
        .field-wrap .f-ico {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: .88rem;
            pointer-events: none; transition: color .25s; z-index: 1;
        }
        .field-wrap input {
            width: 100%; padding: 13px 16px 13px 44px;
            border: 1px solid rgba(255,255,255,.1); border-radius: 14px;
            font-size: .92rem; font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.05); color: var(--white);
            transition: border-color .25s, box-shadow .25s, background .25s;
            outline: none;
        }
        .field-wrap input::placeholder { color: rgba(255,255,255,.2); }
        .field-wrap input:focus {
            border-color: var(--purple);
            background: rgba(165,105,189,.05);
            box-shadow: 0 0 0 4px rgba(165,105,189,.12);
        }
        .field-wrap input:focus ~ .f-ico,
        .field-wrap input:focus + .f-ico { color: var(--purple); }

        /* Toggle ojo */
        .toggle-eye {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.25); cursor: pointer;
            font-size: .88rem; transition: color .2s; z-index: 1;
        }
        .toggle-eye:hover { color: var(--purple); }

        /* Rol selector */
        .rol-selector { display: flex; gap: 12px; }
        .rol-opt { flex: 1; }
        .rol-opt input[type="radio"] { display: none; }
        .rol-opt label {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 8px; padding: 16px 12px;
            border: 1.5px solid rgba(255,255,255,.1); border-radius: 16px;
            cursor: pointer; transition: all .25s; text-align: center;
            background: rgba(255,255,255,.03);
        }
        .rol-opt label i { font-size: 1.4rem; color: rgba(255,255,255,.3); transition: color .25s; }
        .rol-opt label .rol-name { font-size: .82rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.5); }
        .rol-opt label .rol-desc { font-size: .7rem; color: rgba(255,255,255,.3); }
        .rol-opt input:checked + label.lbl-vendedor { border-color: var(--accent); background: rgba(0,194,255,.08); }
        .rol-opt input:checked + label.lbl-vendedor i { color: var(--accent); }
        .rol-opt input:checked + label.lbl-vendedor .rol-name { color: var(--accent); }
        .rol-opt input:checked + label.lbl-admin { border-color: var(--gold); background: rgba(245,197,24,.08); }
        .rol-opt input:checked + label.lbl-admin i { color: var(--gold); }
        .rol-opt input:checked + label.lbl-admin .rol-name { color: var(--gold); }

        /* Alertas */
        .tv-alert {
            display: flex; align-items: center; gap: 12px;
            border-radius: 14px; padding: 13px 18px;
            font-size: .87rem; font-weight: 500; margin-bottom: 24px;
            animation: fadeUp .4s ease both;
        }
        .tv-alert.danger  { background: rgba(255,77,77,.1);  border: 1px solid rgba(255,77,77,.25);  color: #ff8585; }

        /* Botón */
        .btn-register {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #6c3483, var(--purple));
            color: white; border: none; border-radius: 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem; font-weight: 700;
            letter-spacing: 2px; text-transform: uppercase;
            cursor: pointer; transition: all .3s;
            box-shadow: 0 8px 24px rgba(165,105,189,.25);
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(165,105,189,.35); }
        .btn-register:active { transform: scale(.98); }

        .btn-login {
            display: inline-flex; align-items: center; gap: 10px;
            background: linear-gradient(135deg, var(--blue), var(--accent));
            color: white; border: none; border-radius: 14px;
            padding: 14px 32px; font-family: 'DM Sans', sans-serif;
            font-size: .9rem; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; text-decoration: none;
            cursor: pointer; transition: all .3s;
            box-shadow: 0 8px 24px rgba(0,82,204,.3);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,82,204,.4); color: white; }

        .link-login {
            display: flex; justify-content: center; align-items: center;
            gap: 8px; margin-top: 20px;
            font-size: .82rem; color: rgba(255,255,255,.35);
        }
        .link-login a {
            color: var(--purple); font-weight: 600; text-decoration: none;
            transition: color .2s;
        }
        .link-login a:hover { color: #d2a4e6; }

        /* Responsive */
        @media (max-width: 900px) {
            body { flex-direction: column; }
            .brand-panel { width: 100%; min-height: auto; padding: 40px 24px 32px; }
            .info-list { display: none; }
            .form-panel { padding: 32px 20px; }
            .form-card { padding: 28px 22px; }
            .rol-selector { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="bg-grid"></div>

<!-- ── PANEL IZQUIERDO ── -->
<div class="brand-panel">
    <div class="logo-frame">
        <img src="imagenes/logoe.jpeg" alt="TecnoViral"
             onerror="this.src='https://placehold.co/130x130/0052cc/fff?text=TV'">
    </div>
    <div class="brand-name">TECNO<span>VIRAL</span></div>
    <div class="brand-tagline">Sistema de Punto de Venta</div>

    <ul class="info-list">
        <li>
            <div class="info-ico"><i class="fas fa-user-shield"></i></div>
            Las cuentas nuevas se crean como <strong style="color:var(--accent);">Vendedor</strong> por defecto
        </li>
        <li>
            <div class="info-ico"><i class="fas fa-crown"></i></div>
            Solo un administrador puede cambiar roles desde el módulo de Usuarios
        </li>
        <li>
            <div class="info-ico"><i class="fas fa-lock"></i></div>
            Las contraseñas se almacenan con hash SHA-256
        </li>
        <li>
            <div class="info-ico"><i class="fas fa-check-circle"></i></div>
            Necesitas al menos 6 caracteres en tu contraseña
        </li>
    </ul>

    <div class="panel-footer">© <?php echo date('Y'); ?> TecnoViral · Todos los derechos reservados</div>
</div>

<!-- ── PANEL DERECHO ── -->
<div class="form-panel">
    <div class="form-inner">

        <?php if ($mensaje == 'ok'): ?>
        <!-- ── ÉXITO ── -->
        <div class="success-card">
            <div class="success-ico"><i class="fas fa-user-check"></i></div>
            <div class="success-title">¡Cuenta Creada!</div>
            <div class="success-sub">
                Tu cuenta ha sido registrada correctamente como <strong style="color:var(--accent);">Vendedor</strong>.<br>
                Ya puedes iniciar sesión con tus credenciales.
            </div>
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
        </div>

        <?php else: ?>
        <!-- ── FORMULARIO ── -->
        <div class="form-card">

            <div class="form-header">
                <div class="greeting">Nuevo usuario</div>
                <h2>Crear una cuenta</h2>
                <p>Completa los datos para registrarte en el sistema.</p>
            </div>

            <div class="header-line">
                <span></span><span></span><span></span>
            </div>

            <?php if ($error): ?>
            <div class="tv-alert danger">
                <i class="fas fa-circle-xmark"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">

                <!-- Datos personales -->
                <div class="form-section">Datos personales</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="field-label">Nombre *</label>
                        <div class="field-wrap">
                            <i class="fas fa-user f-ico"></i>
                            <input type="text" name="nombre" required placeholder="Nombre"
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Apellido Paterno *</label>
                        <div class="field-wrap">
                            <i class="fas fa-user f-ico"></i>
                            <input type="text" name="apellido_paterno" required placeholder="Apellido paterno"
                                   value="<?php echo isset($_POST['apellido_paterno']) ? htmlspecialchars($_POST['apellido_paterno']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Apellido Materno</label>
                        <div class="field-wrap">
                            <i class="fas fa-user f-ico"></i>
                            <input type="text" name="apellido_materno" placeholder="Apellido materno"
                                   value="<?php echo isset($_POST['apellido_materno']) ? htmlspecialchars($_POST['apellido_materno']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Teléfono</label>
                        <div class="field-wrap">
                            <i class="fas fa-phone f-ico"></i>
                            <input type="tel" name="telefono" placeholder="10 dígitos"
                                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="field-label">Dirección</label>
                        <div class="field-wrap">
                            <i class="fas fa-map-pin f-ico"></i>
                            <input type="text" name="direccion" placeholder="Dirección completa"
                                   value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Credenciales -->
                <div class="form-section" style="margin-top:8px;">Credenciales de acceso</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="field-label">Nombre de Usuario *</label>
                        <div class="field-wrap">
                            <i class="fas fa-at f-ico"></i>
                            <input type="text" name="nombre_usuario" required placeholder="usuario123"
                                   value="<?php echo isset($_POST['nombre_usuario']) ? htmlspecialchars($_POST['nombre_usuario']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Contraseña *</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock f-ico"></i>
                            <input type="password" name="contrasena" id="pass1" required placeholder="Mín. 6 caracteres">
                            <i class="fas fa-eye toggle-eye" onclick="togglePass('pass1', this)"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Confirmar Contraseña *</label>
                        <div class="field-wrap">
                            <i class="fas fa-lock f-ico"></i>
                            <input type="password" name="confirmar_contrasena" id="pass2" required placeholder="Repetir contraseña">
                            <i class="fas fa-eye toggle-eye" onclick="togglePass('pass2', this)"></i>
                        </div>
                    </div>
                </div>

                <!-- Botón -->
                <button type="submit" name="registro" class="btn-register">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>

            </form>

            <div class="link-login">
                <i class="fas fa-arrow-left" style="font-size:.75rem;"></i>
                <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
            </div>

        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePass(id, icon) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>