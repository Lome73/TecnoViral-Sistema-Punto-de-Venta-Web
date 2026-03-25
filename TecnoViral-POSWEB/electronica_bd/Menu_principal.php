<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id     = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$user_rol    = $_SESSION['user_rol'];

$query_ventas_hoy = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_dia FROM ventas WHERE DATE(fecha_venta) = CURDATE()";
$result_ventas_hoy = mysqli_query($conn, $query_ventas_hoy);
$ventas_hoy = mysqli_fetch_assoc($result_ventas_hoy);

$query_productos_bajos = "SELECT COUNT(*) as total FROM productos WHERE stock <= stock_minimo AND activo = 1";
$result_productos_bajos = mysqli_query($conn, $query_productos_bajos);
$productos_bajos = mysqli_fetch_assoc($result_productos_bajos);

$query_caja_abierta = "SELECT * FROM cortes_caja WHERE fecha_cierre IS NULL ORDER BY fecha_apertura DESC LIMIT 1";
$result_caja_abierta = mysqli_query($conn, $query_caja_abierta);
$caja_abierta = mysqli_fetch_assoc($result_caja_abierta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Panel Principal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:    #07172e;
            --navy2:   #0d2347;
            --blue:    #0052cc;
            --accent:  #00c2ff;
            --gold:    #f5c518;
            --white:   #ffffff;
            --light:   #f0f4fb;
            --muted:   #7a8ba0;
            --border:  rgba(255,255,255,.1);
            --danger:  #ff4d4d;
            --success: #00d68f;
            --card-bg: rgba(255,255,255,.05);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
        }

        /* ── Fondo animado ── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse at 15% 20%, rgba(0,194,255,.12) 0%, transparent 50%),
                radial-gradient(ellipse at 85% 75%, rgba(0,82,204,.18) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%);
        }
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .page-wrap {
            position: relative;
            z-index: 1;
            padding: 24px 28px 40px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* ════════════════════════════
           TOPBAR
        ════════════════════════════ */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: rgba(13,35,71,.7);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 14px 22px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
            flex-wrap: wrap;
        }

        /* Logo + nombre */
        .brand-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .logo-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .logo-img {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid rgba(0,194,255,.3);
            box-shadow: 0 0 0 4px rgba(0,194,255,.08), 0 8px 20px rgba(0,0,0,.5);
            transition: transform .4s cubic-bezier(.34,1.56,.64,1);
        }
        .logo-img:hover { transform: scale(1.08) rotate(-2deg); }

        .logo-online {
            position: absolute;
            bottom: 4px; right: 4px;
            width: 12px; height: 12px;
            background: var(--success);
            border-radius: 50%;
            border: 2px solid var(--navy2);
            box-shadow: 0 0 6px var(--success);
            animation: blink 2s ease infinite;
        }
        @keyframes blink {
            0%,100% { opacity: 1; } 50% { opacity: .4; }
        }

        .brand-text .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 900;
            letter-spacing: 3px;
            line-height: 1;
            color: var(--white);
        }
        .brand-text .brand-name span { color: var(--accent); }
        .brand-text .brand-sub {
            font-size: .7rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
            margin-top: 3px;
        }

        /* Hora en vivo */
        .live-time {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(0,194,255,.07);
            border: 1px solid rgba(0,194,255,.15);
            border-radius: 14px;
            padding: 8px 18px;
        }
        .live-time .time-val {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 2px;
        }
        .live-time .time-date {
            font-size: .65rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
        }

        /* Usuario */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 8px 18px 8px 8px;
        }
        .user-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--blue), var(--accent));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .user-chip .u-name {
            font-size: .88rem;
            font-weight: 600;
            color: var(--white);
            line-height: 1.2;
        }
        .user-chip .u-role {
            font-size: .68rem;
            color: var(--accent);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-logout {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(255,77,77,.12);
            border: 1px solid rgba(255,77,77,.25);
            color: #ff8585;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all .25s;
            font-size: .9rem;
        }
        .btn-logout:hover {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
            transform: rotate(90deg);
        }
        .btn-registro-top {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(165,105,189,.12);
            border: 1px solid rgba(165,105,189,.25);
            color: #d2a4e6;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all .25s;
            font-size: .88rem;
        }
        .btn-registro-top:hover {
            background: #6c3483;
            color: white;
            border-color: #a569bd;
            transform: scale(1.12);
        }

        /* ════════════════════════════
           STATS ROW
        ════════════════════════════ */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
            transition: transform .3s, box-shadow .3s;
            animation: fadeUp .5s ease both;
        }
        .stat-card:nth-child(1) { animation-delay: .05s; }
        .stat-card:nth-child(2) { animation-delay: .10s; }
        .stat-card:nth-child(3) { animation-delay: .15s; }
        .stat-card:nth-child(4) { animation-delay: .20s; }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 4px 4px 0 0;
        }
        .stat-card.s-blue::before  { background: linear-gradient(90deg, var(--blue), var(--accent)); }
        .stat-card.s-green::before { background: linear-gradient(90deg, #00b37e, #00d68f); }
        .stat-card.s-amber::before { background: linear-gradient(90deg, #e07b00, var(--gold)); }
        .stat-card.s-red::before   { background: linear-gradient(90deg, #c0392b, var(--danger)); }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0,0,0,.35);
        }

        .stat-ico {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .s-blue  .stat-ico { background: rgba(0,82,204,.2);  color: var(--accent); }
        .s-green .stat-ico { background: rgba(0,214,143,.15); color: #00d68f; }
        .s-amber .stat-ico { background: rgba(245,197,24,.15); color: var(--gold); }
        .s-red   .stat-ico { background: rgba(255,77,77,.15); color: var(--danger); }

        .stat-body { flex: 1; min-width: 0; }
        .stat-val {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stat-lbl {
            font-size: .72rem;
            color: rgba(255,255,255,.45);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 4px;
        }
        .pill-urgent {
            display: inline-block;
            background: rgba(255,77,77,.2);
            color: var(--danger);
            border: 1px solid rgba(255,77,77,.3);
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .pill-open {
            display: inline-block;
            background: rgba(0,214,143,.15);
            color: #00d68f;
            border: 1px solid rgba(0,214,143,.25);
            font-size: .6rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 4px;
            letter-spacing: 1px;
        }
        .pill-closed {
            display: inline-block;
            background: rgba(255,77,77,.15);
            color: var(--danger);
            border: 1px solid rgba(255,77,77,.2);
            font-size: .6rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 4px;
            letter-spacing: 1px;
        }

        /* ════════════════════════════
           SECCIÓN MÓDULOS
        ════════════════════════════ */
        .section-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .section-head .s-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, rgba(0,194,255,.3), transparent);
        }
        .section-head .s-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--accent);
        }

        /* ════════════════════════════
           MÓDULOS GRID (táctil)
        ════════════════════════════ */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .mod-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            cursor: pointer;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px 24px;
            text-align: center;
            text-decoration: none;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            backdrop-filter: blur(8px);
            animation: fadeUp .5s ease both;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        /* stagger animation */
        .mod-card:nth-child(1) { animation-delay: .08s; }
        .mod-card:nth-child(2) { animation-delay: .13s; }
        .mod-card:nth-child(3) { animation-delay: .18s; }
        .mod-card:nth-child(4) { animation-delay: .23s; }
        .mod-card:nth-child(5) { animation-delay: .28s; }
        .mod-card:nth-child(6) { animation-delay: .33s; }
        .mod-card:nth-child(7) { animation-delay: .38s; }
        .mod-card:nth-child(8) { animation-delay: .43s; }

        /* Barra de color superior */
        .mod-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--mod-color, linear-gradient(90deg, var(--blue), var(--accent)));
            border-radius: 0;
            transition: height .3s;
        }

        /* Glow de fondo al hover */
        .mod-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--mod-glow, rgba(0,194,255,.06));
            opacity: 0;
            transition: opacity .3s;
            pointer-events: none;
        }

        .mod-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0,0,0,.45);
            border-color: rgba(0,194,255,.25);
        }
        .mod-card:hover::after { opacity: 1; }
        .mod-card:hover::before { height: 6px; }
        .mod-card:active { transform: scale(.97) !important; }

        /* Ícono del módulo */
        .mod-ico-wrap {
            width: 90px; height: 90px;
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
            background: var(--mod-ico-bg, rgba(0,82,204,.2));
            color: var(--mod-ico-color, var(--accent));
            transition: transform .3s cubic-bezier(.34,1.56,.64,1);
            box-shadow: 0 8px 20px rgba(0,0,0,.3);
        }
        .mod-card:hover .mod-ico-wrap {
            transform: scale(1.1) rotate(-4deg);
        }

        .mod-name {
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--white);
            margin-bottom: 6px;
            position: relative; z-index: 1;
        }
        .mod-desc {
            font-size: .75rem;
            color: rgba(255,255,255,.45);
            max-width: 160px;
            line-height: 1.5;
            position: relative; z-index: 1;
        }

        /* Badge "NUEVO" */
        .mod-badge {
            position: absolute;
            top: 14px; right: 14px;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            z-index: 2;
        }
        .badge-nuevo {
            background: linear-gradient(135deg, #ff6b35, #ff4444);
            color: white;
            box-shadow: 0 4px 12px rgba(255,77,77,.4);
            animation: pulseBadge 2s ease infinite;
        }
        .badge-admin {
            background: linear-gradient(135deg, var(--gold), #e6a800);
            color: #1a0a00;
        }
        @keyframes pulseBadge {
            0%,100% { box-shadow: 0 4px 12px rgba(255,77,77,.4); }
            50%      { box-shadow: 0 4px 20px rgba(255,77,77,.7); }
        }

        /* Colores por módulo */
        .mod-venta   { --mod-color: linear-gradient(90deg,#0052cc,#00c2ff); --mod-glow: rgba(0,194,255,.07); --mod-ico-bg: rgba(0,82,204,.22); --mod-ico-color: #00c2ff; }
        .mod-producto{ --mod-color: linear-gradient(90deg,#00875a,#00d68f); --mod-glow: rgba(0,214,143,.07); --mod-ico-bg: rgba(0,175,90,.18); --mod-ico-color: #00d68f; }
        .mod-historial{ --mod-color: linear-gradient(90deg,#5b3cc4,#9b59b6); --mod-glow: rgba(155,89,182,.07); --mod-ico-bg: rgba(91,60,196,.2); --mod-ico-color: #b07fec; }
        .mod-gastos  { --mod-color: linear-gradient(90deg,#e07b00,#f5c518); --mod-glow: rgba(245,197,24,.07); --mod-ico-bg: rgba(224,123,0,.18); --mod-ico-color: #f5c518; }
        .mod-caja    { --mod-color: linear-gradient(90deg,#c0392b,#ff4d4d); --mod-glow: rgba(255,77,77,.07); --mod-ico-bg: rgba(192,57,43,.2); --mod-ico-color: #ff8585; }
        .mod-reporte { --mod-color: linear-gradient(90deg,#0066aa,#00c2ff); --mod-glow: rgba(0,194,255,.07); --mod-ico-bg: rgba(0,102,170,.22); --mod-ico-color: #66d9ff; }
        .mod-usuario { --mod-color: linear-gradient(90deg,#f5c518,#e6a800); --mod-glow: rgba(245,197,24,.07); --mod-ico-bg: rgba(245,197,24,.15); --mod-ico-color: #f5c518; }
        .mod-inventario{ --mod-color: linear-gradient(90deg,#1abc9c,#00d68f); --mod-glow: rgba(26,188,156,.07); --mod-ico-bg: rgba(26,188,156,.18); --mod-ico-color: #1abc9c; }
        .mod-registro{ --mod-color: linear-gradient(90deg,#6c3483,#a569bd); --mod-glow: rgba(165,105,189,.07); --mod-ico-bg: rgba(108,52,131,.22); --mod-ico-color: #d2a4e6; }

        /* ════════════════════════════
           ACCESOS RÁPIDOS
        ════════════════════════════ */
        .quick-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }
        .quick-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 16px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.75);
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .5px;
            text-decoration: none;
            transition: all .25s;
            -webkit-tap-highlight-color: transparent;
        }
        .quick-btn i { font-size: 1rem; color: var(--accent); }
        .quick-btn:hover {
            background: rgba(0,194,255,.1);
            border-color: rgba(0,194,255,.3);
            color: white;
            transform: translateY(-3px);
        }
        .quick-btn:active { transform: scale(.97); }

        /* ════════════════════════════
           FOOTER
        ════════════════════════════ */
        .page-footer {
            text-align: center;
            font-size: .68rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255,255,255,.18);
            padding-top: 10px;
        }
        .page-footer span { color: var(--accent); opacity: .5; }

        /* ════════════════════════════
           ANIMACIONES
        ════════════════════════════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ════════════════════════════
           RESPONSIVE
        ════════════════════════════ */
        @media (max-width: 1200px) {
            .modules-grid { grid-template-columns: repeat(3, 1fr); }
            .stats-row    { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .page-wrap    { padding: 16px 14px 30px; }
            .modules-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stats-row    { grid-template-columns: repeat(2, 1fr); }
            .topbar       { padding: 12px 16px; }
            .logo-img     { width: 56px; height: 56px; }
            .live-time    { display: none; }
            .mod-card     { min-height: 170px; padding: 22px 14px 18px; }
            .mod-ico-wrap { width: 72px; height: 72px; font-size: 1.8rem; }
            .mod-name     { font-size: .88rem; }
        }
        @media (max-width: 480px) {
            .modules-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stats-row    { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-val     { font-size: 1.3rem; }
            .brand-text .brand-name { font-size: 1.1rem; }
        }
    </style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="bg-grid"></div>

<div class="page-wrap">

    <!-- ══ TOPBAR ══ -->
    <div class="topbar" style="animation: fadeUp .5s ease both;">
        <!-- Logo + Marca -->
        <a href="menu_principal.php" class="brand-row" style="text-decoration:none;">
            <div class="logo-wrap">
                <img src="imagenes/logoe.jpeg" alt="TecnoViral" class="logo-img"
                     onerror="this.src='https://placehold.co/72x72/0052cc/fff?text=TV'">
                <div class="logo-online"></div>
            </div>
            <div class="brand-text">
                <div class="brand-name">TECNO<span>VIRAL</span></div>
                <div class="brand-sub">Punto de Venta · Sistema Táctil</div>
            </div>
        </a>

        <!-- Hora en vivo -->
        <div class="live-time">
            <div class="time-val" id="liveClock">--:--:--</div>
            <div class="time-date" id="liveDate">cargando...</div>
        </div>

        <!-- Usuario + logout -->
        <div style="display:flex;align-items:center;gap:8px;">
            <?php if ($user_rol == 'administrador'): ?>
            <a href="registro.php" class="btn-registro-top" title="Registrar nuevo usuario">
                <i class="fas fa-user-plus"></i>
            </a>
            <?php endif; ?>
            <?php if ($user_rol == 'supervisor'): ?>
            <div style="display:flex;align-items:center;gap:6px;background:rgba(41,128,185,.1);border:1px solid rgba(41,128,185,.25);border-radius:50px;padding:5px 14px 5px 8px;">
                <div style="width:28px;height:28px;background:linear-gradient(135deg,#2980b9,#3498db);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;">S</div>
                <span style="font-size:.65rem;color:#3498db;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Supervisor</span>
            </div>
            <?php endif; ?>
            <div class="user-chip">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_nombre, 0, 1)); ?>
                </div>
                <div>
                    <div class="u-name"><?php echo htmlspecialchars($user_nombre); ?></div>
                    <div class="u-role"><?php echo $user_rol == 'administrador' ? '★ Administrador' : ($user_rol == 'supervisor' ? '◆ Supervisor' : 'Vendedor'); ?></div>
                </div>
                <a href="logout.php" class="btn-logout ms-2" title="Cerrar sesión">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ══ STATS ══ -->
    <div class="stats-row">
        <div class="stat-card s-blue">
            <div class="stat-ico"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-body">
                <div class="stat-val"><?php echo $ventas_hoy['total_ventas'] ?? 0; ?></div>
                <div class="stat-lbl">Ventas hoy</div>
            </div>
        </div>
        <div class="stat-card s-green">
            <div class="stat-ico"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-body">
                <div class="stat-val">$<?php echo number_format($ventas_hoy['total_dia'] ?? 0, 0); ?></div>
                <div class="stat-lbl">Total del día</div>
            </div>
        </div>
        <div class="stat-card s-amber">
            <div class="stat-ico"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stat-body">
                <div class="stat-val"><?php echo $productos_bajos['total'] ?? 0; ?></div>
                <div class="stat-lbl">Stock bajo</div>
                <?php if (($productos_bajos['total'] ?? 0) > 0): ?>
                <div class="pill-urgent">¡Urgente!</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card s-<?php echo $caja_abierta ? 'green' : 'red'; ?>">
            <div class="stat-ico"><i class="fas fa-cash-register"></i></div>
            <div class="stat-body">
                <div class="stat-val"><?php echo $caja_abierta ? 'Abierta' : 'Cerrada'; ?></div>
                <div class="stat-lbl">Estado de caja</div>
                <?php if ($caja_abierta): ?>
                <div class="pill-open">● En operación</div>
                <?php else: ?>
                <div class="pill-closed">● Sin abrir</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ MÓDULOS ══ -->
    <div class="section-head">
        <div class="s-label">Módulos del sistema</div>
        <div class="s-line"></div>
    </div>

    <div class="modules-grid">

        <!-- Punto de Venta -->
        <a href="punto_venta.php" class="mod-card mod-venta">
            <div class="mod-badge badge-nuevo">Nuevo</div>
            <div class="mod-ico-wrap"><i class="fas fa-cash-register"></i></div>
            <div class="mod-name">Punto de Venta</div>
            <div class="mod-desc">Realiza ventas rápidas e intuitivas</div>
        </a>

        <!-- Productos -->
        <a href="productos.php" class="mod-card mod-producto">
            <div class="mod-ico-wrap"><i class="fas fa-box-open"></i></div>
            <div class="mod-name">Productos</div>
            <div class="mod-desc">Catálogo y gestión de inventario</div>
        </a>

        <!-- Historial -->
        <a href="historial_ventas.php" class="mod-card mod-historial">
            <div class="mod-ico-wrap"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="mod-name">Historial</div>
            <div class="mod-desc">Consulta de ventas realizadas</div>
        </a>

        <!-- Gastos -->
        <a href="gastos.php" class="mod-card mod-gastos">
            <div class="mod-ico-wrap"><i class="fas fa-wallet"></i></div>
            <div class="mod-name">Gastos</div>
            <div class="mod-desc">Registro de gastos del día</div>
        </a>

        <!-- Corte de Caja -->
        <a href="corte_caja.php" class="mod-card mod-caja">
            <div class="mod-ico-wrap"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="mod-name">Corte de Caja</div>
            <div class="mod-desc">Cierre y corte de caja diario</div>
        </a>

        <!-- Reportes (admin y supervisor) -->
        <?php if (in_array($user_rol, ['administrador','supervisor'])): ?>
        <a href="reportes.php" class="mod-card mod-reporte">
            <div class="mod-badge badge-admin" style="<?php echo $user_rol=='supervisor'?'background:linear-gradient(135deg,#2980b9,#3498db);':''; ?>">
                <i class="fas fa-<?php echo $user_rol=='supervisor'?'user-tie':'crown'; ?> me-1"></i>
                <?php echo $user_rol=='supervisor'?'Super':'Admin'; ?>
            </div>
            <div class="mod-ico-wrap"><i class="fas fa-chart-line"></i></div>
            <div class="mod-name">Reportes</div>
            <div class="mod-desc">Estadísticas y gráficas de ventas</div>
        </a>
        <?php else: ?>
        <div class="mod-card mod-reporte" style="opacity:.35;cursor:not-allowed;pointer-events:none;" title="Solo administradores y supervisores">
            <div class="mod-badge" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.3);font-size:.6rem;padding:4px 10px;border-radius:20px;position:absolute;top:14px;right:14px;z-index:2;"><i class="fas fa-lock me-1"></i>Restringido</div>
            <div class="mod-ico-wrap"><i class="fas fa-chart-line"></i></div>
            <div class="mod-name">Reportes</div>
            <div class="mod-desc">Sin acceso</div>
        </div>
        <?php endif; ?>

        <!-- Usuarios (solo admin) -->
        <?php if ($user_rol == 'administrador'): ?>
        <a href="usuarios.php" class="mod-card mod-usuario">
            <div class="mod-badge badge-admin"><i class="fas fa-crown me-1"></i>Admin</div>
            <div class="mod-ico-wrap"><i class="fas fa-users-gear"></i></div>
            <div class="mod-name">Usuarios</div>
            <div class="mod-desc">Administración de cuentas del sistema</div>
        </a>
        <?php endif; ?>

        <!-- Inventario -->
        <a href="inventario.php" class="mod-card mod-inventario">
            <div class="mod-ico-wrap"><i class="fas fa-clipboard-list"></i></div>
            <div class="mod-name">Inventario</div>
            <div class="mod-desc">Control de existencias y movimientos</div>
        </a>


    </div>

    <!-- ══ FOOTER ══ -->
    <div class="page-footer">
        <span>◆</span> &nbsp;TecnoViral POS v1.0 &nbsp;<span>◆</span>
    </div>

</div><!-- /page-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /* ── Reloj en vivo ── */
    function updateClock() {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2,'0');
        const mm = String(now.getMinutes()).padStart(2,'0');
        const ss = String(now.getSeconds()).padStart(2,'0');
        document.getElementById('liveClock').textContent = `${hh}:${mm}:${ss}`;

        const dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        document.getElementById('liveDate').textContent =
            `${dias[now.getDay()]} ${now.getDate()} ${meses[now.getMonth()]} ${now.getFullYear()}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── Ripple táctil en módulos ── */
    document.querySelectorAll('.mod-card, .quick-btn').forEach(el => {
        el.addEventListener('pointerdown', function(e) {
            const r = document.createElement('span');
            const d = Math.max(this.clientWidth, this.clientHeight) * 2;
            const rect = this.getBoundingClientRect();
            r.style.cssText = `
                position:absolute;
                border-radius:50%;
                width:${d}px;height:${d}px;
                left:${e.clientX-rect.left-d/2}px;
                top:${e.clientY-rect.top-d/2}px;
                background:rgba(255,255,255,.12);
                transform:scale(0);
                animation:ripple .6s linear forwards;
                pointer-events:none;
                z-index:10;
            `;
            this.appendChild(r);
            setTimeout(() => r.remove(), 700);
        });
    });

    /* ── Keyframe ripple dinámico ── */
    const st = document.createElement('style');
    st.textContent = `@keyframes ripple { to { transform:scale(1); opacity:0; } }`;
    document.head.appendChild(st);
</script>
</body>
</html>