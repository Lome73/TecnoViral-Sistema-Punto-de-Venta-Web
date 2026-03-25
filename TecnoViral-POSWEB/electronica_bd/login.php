<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['intentos'])) {
    $_SESSION['intentos'] = 0;
    $_SESSION['bloqueo_hasta'] = 0;
}

$bloqueado = false;
$tiempo_restante = 0;
if ($_SESSION['bloqueo_hasta'] > time()) {
    $bloqueado = true;
    $tiempo_restante = $_SESSION['bloqueo_hasta'] - time();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && !$bloqueado) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM usuarios WHERE nombre_usuario = '$username' AND activo = 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (hash('sha256', $password) == $user['contrasena']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'] . ' ' . $user['apellido_paterno'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['intentos'] = 0;
            $_SESSION['bloqueo_hasta'] = 0;
            header('Location: menu_principal.php');
            exit();
        } else {
            $_SESSION['intentos']++;
            $error = "Contraseña incorrecta. Intento " . $_SESSION['intentos'] . " de 3.";
        }
    } else {
        $_SESSION['intentos']++;
        $error = "Usuario no encontrado. Intento " . $_SESSION['intentos'] . " de 3.";
    }
    
    if ($_SESSION['intentos'] >= 3) {
        $_SESSION['bloqueo_hasta'] = time() + 30;
        $bloqueado = true;
        $tiempo_restante = 30;
        $error = "Demasiados intentos fallidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoViral — Iniciar Sesión</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Playfair Display + DM Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:   #07172e;
            --navy2:  #0d2347;
            --blue:   #0052cc;
            --accent: #00c2ff;
            --gold:   #f5c518;
            --light:  #f0f4fb;
            --card:   #ffffff;
            --muted:  #7a8ba0;
            --border: #dde4ef;
            --danger: #e03e3e;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--light);
            display: flex;
            overflow: hidden;
        }

        /* ═══════════════════════════════════════
           PANEL IZQUIERDO — Marca / Logo
        ═══════════════════════════════════════ */
        .brand-panel {
            width: 48%;
            min-height: 100vh;
            background: var(--navy);
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 45px;
            overflow: hidden;
            flex-shrink: 0;
        }

        /* Fondo con gradiente de malla */
        .brand-panel .mesh-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 10%, rgba(0,168,255,.22) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 80%, rgba(0,82,204,.28) 0%, transparent 55%),
                radial-gradient(ellipse at 50% 50%, rgba(7,23,46,1) 0%, rgba(13,35,71,1) 100%);
            pointer-events: none;
        }

        /* Grid punteado */
        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.06) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
            z-index: 1;
        }

        /* Círculos orbitales animados */
        .orbit-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(0,194,255,.12);
            pointer-events: none;
            z-index: 1;
        }
        .orbit-ring-1 { width: 520px; height: 520px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation: spinRing 30s linear infinite; }
        .orbit-ring-2 { width: 680px; height: 680px; top: 50%; left: 50%; transform: translate(-50%,-50%); border-color: rgba(0,82,204,.08); animation: spinRing 45s linear infinite reverse; }
        .orbit-ring-3 { width: 860px; height: 860px; top: 50%; left: 50%; transform: translate(-50%,-50%); border-color: rgba(0,168,255,.05); animation: spinRing 60s linear infinite; }

        /* Punto en la órbita */
        .orbit-ring-1::before {
            content: '';
            position: absolute;
            width: 8px; height: 8px;
            background: var(--accent);
            border-radius: 50%;
            top: -4px; left: 50%;
            box-shadow: 0 0 12px var(--accent), 0 0 24px var(--accent);
        }
        .orbit-ring-2::before {
            content: '';
            position: absolute;
            width: 6px; height: 6px;
            background: var(--blue);
            border-radius: 50%;
            bottom: -3px; right: 20%;
            box-shadow: 0 0 10px var(--blue);
        }

        @keyframes spinRing {
            from { transform: translate(-50%,-50%) rotate(0deg); }
            to   { transform: translate(-50%,-50%) rotate(360deg); }
        }

        /* Línea diagonal derecha */
        .diag-line {
            position: absolute;
            top: 0; right: 0;
            width: 5px; height: 100%;
            background: linear-gradient(to bottom, transparent, var(--accent), var(--blue), transparent);
            opacity: .6;
            z-index: 3;
        }

        /* Barras decorativas horizontales */
        .deco-bars {
            position: absolute;
            bottom: 60px; left: 40px;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .deco-bar { height: 3px; border-radius: 4px; background: var(--accent); opacity: .25; }
        .deco-bar:nth-child(1) { width: 55px; }
        .deco-bar:nth-child(2) { width: 35px; opacity: .15; }
        .deco-bar:nth-child(3) { width: 20px; opacity: .08; }

        /* Esquina decorativa */
        .corner-deco {
            position: absolute;
            top: 36px; left: 36px;
            z-index: 2;
            width: 40px; height: 40px;
            border-top: 3px solid var(--accent);
            border-left: 3px solid var(--accent);
            border-radius: 4px 0 0 0;
            opacity: .4;
        }
        .corner-deco-br {
            position: absolute;
            bottom: 36px; right: 45px;
            z-index: 2;
            width: 28px; height: 28px;
            border-bottom: 2px solid rgba(0,194,255,.3);
            border-right: 2px solid rgba(0,194,255,.3);
            border-radius: 0 0 4px 0;
        }

        /* Contenido del panel */
        .brand-content {
            position: relative;
            z-index: 4;
            text-align: center;
            width: 100%;
        }

        /* LOGO grande con capas de brillo */
        .logo-frame {
            width: 260px;
            height: 260px;
            border-radius: 48px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
            overflow: hidden;
            box-shadow:
                0 0 0 6px rgba(0,194,255,.15),
                0 0 0 14px rgba(0,194,255,.07),
                0 0 0 24px rgba(0,194,255,.03),
                0 40px 80px rgba(0,0,0,.6),
                inset 0 1px 0 rgba(255,255,255,.8);
            transition: transform .5s cubic-bezier(.34,1.56,.64,1), box-shadow .5s ease;
        }
        .logo-frame:hover {
            transform: scale(1.05) translateY(-6px);
            box-shadow:
                0 0 0 8px rgba(0,194,255,.22),
                0 0 0 18px rgba(0,194,255,.10),
                0 0 0 30px rgba(0,194,255,.04),
                0 55px 90px rgba(0,0,0,.7),
                inset 0 1px 0 rgba(255,255,255,.9);
        }
        .logo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Shine sweep sobre el logo */
        .logo-frame::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(130deg, rgba(255,255,255,.35) 0%, transparent 60%);
            border-radius: inherit;
            pointer-events: none;
        }

        /* Badge flotante sobre el logo */
        .logo-badge {
            position: absolute;
            top: -12px; right: -12px;
            background: linear-gradient(135deg, var(--gold), #e6a800);
            color: #1a0a00;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 10px;
            border-radius: 20px;
            box-shadow: 0 4px 14px rgba(245,197,24,.5);
            z-index: 5;
            white-space: nowrap;
        }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 3.2rem;
            letter-spacing: 5px;
            color: #ffffff;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 4px 30px rgba(0,194,255,.3);
        }
        .brand-name span { color: var(--accent); }

        .brand-tagline {
            font-size: .72rem;
            font-weight: 500;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: rgba(255,255,255,.38);
            margin-bottom: 38px;
        }

        /* Estadísticas en píldoras */
        .stats-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .stat-pill {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 40px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(8px);
        }
        .stat-pill i { color: var(--accent); font-size: .8rem; }
        .stat-pill-num {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            color: white;
        }
        .stat-pill-lbl {
            font-size: .68rem;
            color: rgba(255,255,255,.45);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .brand-features {
            list-style: none;
            text-align: left;
            display: inline-block;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px;
            padding: 22px 28px;
            backdrop-filter: blur(6px);
            width: 100%;
            max-width: 340px;
        }
        .brand-features li {
            color: rgba(255,255,255,.72);
            font-size: .85rem;
            font-weight: 400;
            margin-bottom: 13px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-features li:last-child { margin-bottom: 0; }
        .brand-features li .feat-icon {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: rgba(0,194,255,.15);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .brand-features li .feat-icon i { color: var(--accent); font-size: .75rem; }

        .brand-footer {
            position: absolute;
            bottom: 22px;
            font-size: .68rem;
            color: rgba(255,255,255,.2);
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 4;
        }

        /* ═══════════════════════════════════════
           PANEL DERECHO — Formulario
        ═══════════════════════════════════════ */
        .form-panel {
            flex: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 55px;
            background: #f5f8ff;
            position: relative;
            overflow-y: auto;
        }

        /* Fondo sutil del panel derecho */
        .form-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 90% 10%, rgba(0,194,255,.08) 0%, transparent 50%),
                radial-gradient(ellipse at 10% 90%, rgba(0,82,204,.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Subtle grid lines */
        .form-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,82,204,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,82,204,.04) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        .form-inner {
            width: 100%;
            max-width: 430px;
            position: relative;
            z-index: 2;
            animation: fadeSlide .7s ease both;
            margin: auto;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Tarjeta de cristal que envuelve el form */
        .glass-card {
            background: #ffffff;
            border: 1px solid rgba(0,82,204,.12);
            border-radius: 28px;
            padding: 44px 42px;
            box-shadow:
                0 8px 40px rgba(0,52,130,.1),
                0 2px 8px rgba(0,82,204,.06);
        }

        /* Header */
        .form-header {
            margin-bottom: 34px;
        }
        .form-header .greeting {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-header .greeting::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, rgba(0,194,255,.4), transparent);
        }
        .form-header h2 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 2rem;
            color: var(--navy);
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .form-header p {
            font-size: .87rem;
            color: var(--muted);
            font-weight: 400;
        }

        /* Separador decorativo bajo el header */
        .header-line {
            display: flex;
            gap: 6px;
            margin-bottom: 30px;
        }
        .header-line span {
            height: 3px;
            border-radius: 4px;
        }
        .header-line span:nth-child(1) { width: 40px; background: var(--accent); }
        .header-line span:nth-child(2) { width: 20px; background: var(--blue); opacity: .6; }
        .header-line span:nth-child(3) { width: 10px; background: rgba(0,194,255,.25); }

        /* Labels */
        .field-label {
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--navy);
            margin-bottom: 9px;
            display: block;
            opacity: .7;
        }

        /* Inputs */
        .input-shell {
            position: relative;
            margin-bottom: 22px;
        }
        .input-shell .field-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .9rem;
            transition: color .25s;
            pointer-events: none;
            z-index: 1;
        }
        .input-shell input {
            width: 100%;
            padding: 15px 50px 15px 48px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            font-size: .93rem;
            font-family: 'DM Sans', sans-serif;
            background: #f8faff;
            color: var(--navy);
            transition: border-color .25s, box-shadow .25s, background .25s;
            outline: none;
        }
        .input-shell input::placeholder { color: #b0bccd; }
        .input-shell input:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0,194,255,.1);
        }
        .input-shell input:focus + .field-icon { color: var(--accent); }
        .input-shell input:disabled {
            background: #eef1f7;
            cursor: not-allowed;
            opacity: .6;
        }

        .toggle-eye {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            cursor: pointer;
            transition: color .2s;
            font-size: .9rem;
        }
        .toggle-eye:hover { color: var(--blue); }

        /* Botón principal */
        .btn-primary-tv {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--blue) 0%, var(--accent) 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform .25s, box-shadow .25s;
            box-shadow: 0 8px 28px rgba(0,194,255,.25);
            position: relative;
            overflow: hidden;
            margin-top: 8px;
        }
        .btn-primary-tv:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 14px 36px rgba(0,194,255,.35);
        }
        .btn-primary-tv:active:not(:disabled) { transform: translateY(0); }
        .btn-primary-tv:disabled {
            background: rgba(255,255,255,.1);
            cursor: not-allowed;
            box-shadow: none;
            color: rgba(255,255,255,.3);
        }
        .btn-primary-tv .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: rippleAnim .6s linear;
            background: rgba(255,255,255,.2);
            pointer-events: none;
        }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }

        /* Alerta error */
        .tv-alert {
            background: #fff5f5;
            border: 1px solid #fcc;
            border-left: 4px solid var(--danger);
            border-radius: 12px;
            padding: 13px 16px;
            font-size: .85rem;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            animation: shake .4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }

        /* Timer bloqueo */
        .tv-timer {
            background: #edf3ff;
            border: 1px solid #c5d6f5;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: .85rem;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            font-weight: 500;
        }
        .tv-timer i { color: var(--blue); }
        .tv-timer strong { color: var(--blue); }

        /* Links */
        .form-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 22px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .link-muted {
            font-size: .8rem;
            color: var(--muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color .2s;
        }
        .link-muted:hover { color: var(--blue); }

        .link-register {
            font-size: .8rem;
            font-weight: 600;
            color: var(--blue);
            text-decoration: none;
            border: 1.5px solid var(--blue);
            border-radius: 30px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all .25s;
        }
        .link-register:hover {
            background: var(--blue);
            color: white;
        }

        /* Divisor */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 28px 0 20px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .divider span {
            font-size: .7rem;
            color: var(--muted);
            white-space: nowrap;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Botones sociales */
        .social-row { display: flex; gap: 10px; }
        .btn-social {
            flex: 1;
            padding: 11px 10px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            transition: all .25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: var(--muted);
            font-size: .8rem;
            font-weight: 500;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-social:hover {
            border-color: var(--blue);
            color: var(--navy);
            box-shadow: 0 4px 12px rgba(0,82,204,.1);
            transform: translateY(-2px);
        }
        .btn-social .fab { font-size: .95rem; }
        .btn-social.google .fab { color: #DB4437; }
        .btn-social.fb    .fab { color: #4267B2; }
        .btn-social.gh    .fab { color: #333; }

        /* Firma inferior */
        .form-footer {
            text-align: center;
            margin-top: 28px;
            font-size: .68rem;
            color: rgba(0,20,60,.3);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width: 900px) {
            body { flex-direction: column; overflow: auto; }
            .brand-panel { width: 100%; min-height: auto; padding: 50px 30px 40px; }
            .brand-panel .diag-line { display: none; }
            .logo-frame { width: 130px; height: 130px; border-radius: 26px; }
            .brand-name { font-size: 2.2rem; }
            .brand-features { display: none; }
            .form-panel { padding: 40px 20px; }
            .glass-card { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<!-- ══════════════ PANEL IZQUIERDO ══════════════ -->
<div class="brand-panel">
    <div class="mesh-bg"></div>
    <div class="dot-grid"></div>
    <div class="orbit-ring orbit-ring-1"></div>
    <div class="orbit-ring orbit-ring-2"></div>
    <div class="orbit-ring orbit-ring-3"></div>
    <div class="diag-line"></div>
    <div class="corner-deco"></div>
    <div class="corner-deco-br"></div>
    <div class="deco-bars">
        <div class="deco-bar"></div>
        <div class="deco-bar"></div>
        <div class="deco-bar"></div>
    </div>

    <div class="brand-content">
        <!-- Logo grande -->
        <div style="position:relative; display:inline-block; margin-bottom:4px;">
            <div class="logo-frame">
                <img src="imagenes/logoe.jpeg" alt="TecnoViral Logo"
                     onerror="this.src='https://placehold.co/260x260/0052cc/fff?text=TV'">
            </div>
            <div class="logo-badge"><i class="fas fa-star me-1" style="font-size:.55rem;"></i> Sistema Pro</div>
        </div>

        <div class="brand-name">TECNO<span>VIRAL</span></div>
        <div class="brand-tagline">Sistema de Punto de Venta</div>

        <!-- Estadísticas -->
        <div class="stats-row">
            <div class="stat-pill">
                <i class="fas fa-bolt"></i>
                <div>
                    <div class="stat-pill-num">99.9%</div>
                    <div class="stat-pill-lbl">Uptime</div>
                </div>
            </div>
            <div class="stat-pill">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <div class="stat-pill-num">256-bit</div>
                    <div class="stat-pill-lbl">Seguridad</div>
                </div>
            </div>
            <div class="stat-pill">
                <i class="fas fa-users"></i>
                <div>
                    <div class="stat-pill-num">Multi</div>
                    <div class="stat-pill-lbl">Usuario</div>
                </div>
            </div>
        </div>

        <!-- Features card -->
        <ul class="brand-features">
            <li>
                <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
                Reportes y estadísticas en tiempo real
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-boxes"></i></div>
                Control de inventario inteligente
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-receipt"></i></div>
                Facturación y tickets automáticos
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-user-shield"></i></div>
                Roles y permisos por usuario
            </li>
        </ul>
    </div>

    <div class="brand-footer">© <?= date('Y') ?> TecnoViral · Todos los derechos reservados</div>
</div>

<!-- ══════════════ PANEL DERECHO ══════════════ -->
<div class="form-panel">
    <div class="form-inner">
      <div class="glass-card">

        <div class="form-header">
            <div class="greeting">Bienvenido de nuevo</div>
            <h2>Inicia tu sesión</h2>
            <p>Ingresa tus credenciales para acceder al sistema.</p>
        </div>

        <div class="header-line">
            <span></span><span></span><span></span>
        </div>

        <!-- Alerta de error -->
        <?php if (isset($error)): ?>
        <div class="tv-alert">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Timer de bloqueo -->
        <?php if ($bloqueado): ?>
        <div class="tv-timer" id="timerBox">
            <i class="fas fa-lock"></i>
            Cuenta bloqueada. Espera <strong id="countdown"><?= $tiempo_restante ?></strong> segundos.
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="" id="loginForm">

            <!-- Usuario -->
            <div>
                <label class="field-label">Usuario</label>
                <div class="input-shell">
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" id="username" name="username"
                           placeholder="Nombre de usuario"
                           required autocomplete="username"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                           <?= $bloqueado ? 'disabled' : '' ?>>
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <label class="field-label">Contraseña</label>
                <div class="input-shell">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password"
                           <?= $bloqueado ? 'disabled' : '' ?>>
                    <i class="fas fa-eye toggle-eye" id="eyeIcon" onclick="togglePassword()"></i>
                </div>
            </div>

            <!-- Botón -->
            <button type="submit" name="login" class="btn-primary-tv"
                    id="btnLogin" <?= $bloqueado ? 'disabled' : '' ?>>
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
            </button>
        </form>

        <!-- Links -->
        <div class="form-links">
            <a href="#" class="link-muted">
                <i class="fas fa-question-circle"></i> ¿Olvidaste tu contraseña?
            </a>

      </div><!-- /glass-card -->
      <div class="form-footer">© <?= date('Y') ?> TecnoViral · Acceso seguro</div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* ── Toggle contraseña ── */
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    /* ── Ripple en el botón ── */
    document.getElementById('btnLogin')?.addEventListener('click', function(e) {
        const btn = this;
        const circle = document.createElement('span');
        const d = Math.max(btn.clientWidth, btn.clientHeight);
        const rect = btn.getBoundingClientRect();
        circle.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX-rect.left-d/2}px;top:${e.clientY-rect.top-d/2}px`;
        circle.classList.add('ripple');
        btn.appendChild(circle);
        setTimeout(() => circle.remove(), 700);
    });

    /* ── Countdown de bloqueo ── */
    <?php if ($bloqueado): ?>
    let t = <?= $tiempo_restante ?>;
    const cd  = document.getElementById('countdown');
    const box = document.getElementById('timerBox');
    const interval = setInterval(() => {
        t--;
        if (t > 0) {
            cd.textContent = t;
        } else {
            clearInterval(interval);
            box.style.display = 'none';
            ['username','password'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
            const btn = document.getElementById('btnLogin');
            if (btn) btn.disabled = false;
        }
    }, 1000);
    <?php endif; ?>
</script>
</body>
</html>