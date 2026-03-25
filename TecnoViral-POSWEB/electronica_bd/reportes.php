<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Solo administradores
if (!in_array($_SESSION['user_rol'], ['administrador','supervisor'])) {
    // Mostrar página de acceso denegado con diseño del sistema
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
            .deny-card {
                position: relative; z-index: 1;
                background: rgba(255,255,255,.04);
                border: 1px solid rgba(255,77,77,.2);
                border-radius: 28px;
                padding: 56px 60px;
                text-align: center;
                max-width: 480px;
                width: 90%;
                box-shadow: 0 0 0 1px rgba(255,77,77,.08), 0 32px 80px rgba(0,0,0,.5);
                animation: fadeUp .6s ease both;
            }
            @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
            .deny-icon-wrap {
                width: 100px; height: 100px; border-radius: 28px;
                background: rgba(255,77,77,.12);
                border: 2px solid rgba(255,77,77,.25);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 28px;
                font-size: 2.8rem;
                color: var(--danger);
                position: relative;
                animation: pulseRed 2.5s ease infinite;
            }
            @keyframes pulseRed {
                0%,100% { box-shadow: 0 0 0 0 rgba(255,77,77,.2); }
                50%      { box-shadow: 0 0 0 16px rgba(255,77,77,0); }
            }
            .deny-code {
                font-family: 'Playfair Display', serif;
                font-size: 5rem; font-weight: 900;
                color: rgba(255,77,77,.15);
                line-height: 1;
                position: absolute;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
            }
            .deny-title { font-family: 'Playfair Display', serif; font-size: 1.7rem; font-weight: 700; color: var(--white); margin-bottom: 10px; }
            .deny-sub { font-size: .9rem; color: rgba(255,255,255,.45); line-height: 1.7; margin-bottom: 32px; }
            .deny-user {
                display: inline-flex; align-items: center; gap: 10px;
                background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
                border-radius: 50px; padding: 8px 20px 8px 8px;
                margin-bottom: 32px;
            }
            .deny-avatar {
                width: 34px; height: 34px;
                background: linear-gradient(135deg, var(--blue), var(--accent));
                border-radius: 50%; display: flex; align-items: center; justify-content: center;
                font-weight: 700; font-size: .9rem;
            }
            .deny-uname { font-size: .82rem; font-weight: 600; }
            .deny-urole { font-size: .65rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1px; }
            .btn-back {
                display: inline-flex; align-items: center; gap: 10px;
                background: linear-gradient(135deg, var(--blue), var(--accent));
                color: white; border: none; border-radius: 14px;
                padding: 14px 32px; font-family: 'DM Sans', sans-serif;
                font-size: .9rem; font-weight: 700; letter-spacing: 1.5px;
                text-transform: uppercase; text-decoration: none;
                cursor: pointer; transition: all .3s;
                box-shadow: 0 8px 24px rgba(0,82,204,.3);
            }
            .btn-back:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,82,204,.4); color: white; }
            .countdown-bar {
                width: 100%; height: 3px;
                background: rgba(255,255,255,.06);
                border-radius: 4px; margin-top: 24px; overflow: hidden;
            }
            .countdown-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--blue), var(--accent));
                border-radius: 4px;
                animation: shrink 5s linear forwards;
            }
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
                Tu cuenta no tiene los permisos necesarios para ver los reportes del sistema.
            </div>
            <div class="deny-user">
                <div class="deny-avatar"><?php echo strtoupper(substr($_SESSION['user_nombre'], 0, 1)); ?></div>
                <div>
                    <div class="deny-uname"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></div>
                    <div class="deny-urole">Vendedor · Sin acceso</div>
                </div>
            </div>
            <br>
            <a href="menu_principal.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Regresar al Menú
            </a>
            <div class="countdown-bar"><div class="countdown-fill"></div></div>
            <div class="countdown-txt">Redirigiendo automáticamente en 5 segundos...</div>
        </div>

        <script>
            setTimeout(() => { window.location.href = 'menu_principal.php'; }, 5000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

$user_nombre = $_SESSION['user_nombre'];
$user_rol    = $_SESSION['user_rol'];

// ── FILTROS DE PERÍODO ──
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes';
$desde   = isset($_GET['desde'])   ? $_GET['desde']   : '';
$hasta   = isset($_GET['hasta'])   ? $_GET['hasta']   : '';

switch ($periodo) {
    case 'hoy':
        $cond_fecha = "DATE(v.fecha_venta) = CURDATE()";
        $cond_fecha_g = "DATE(g.fecha_gasto) = CURDATE()";
        $label_periodo = "Hoy";
        break;
    case 'semana':
        $cond_fecha = "v.fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $cond_fecha_g = "g.fecha_gasto >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $label_periodo = "Últimos 7 días";
        break;
    case 'rango':
        $d = mysqli_real_escape_string($conn, $desde);
        $h = mysqli_real_escape_string($conn, $hasta);
        $cond_fecha = "DATE(v.fecha_venta) BETWEEN '$d' AND '$h'";
        $cond_fecha_g = "DATE(g.fecha_gasto) BETWEEN '$d' AND '$h'";
        $label_periodo = "Del $desde al $hasta";
        break;
    case 'mes':
    default:
        $cond_fecha = "MONTH(v.fecha_venta) = MONTH(CURDATE()) AND YEAR(v.fecha_venta) = YEAR(CURDATE())";
        $cond_fecha_g = "MONTH(g.fecha_gasto) = MONTH(CURDATE()) AND YEAR(g.fecha_gasto) = YEAR(CURDATE())";
        $label_periodo = "Este mes";
        break;
}

// ── KPIs PRINCIPALES ──
$r_kpi = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total_ventas,
            COALESCE(SUM(total), 0) as ingresos,
            COALESCE(AVG(total), 0) as ticket_promedio
     FROM ventas v
     WHERE $cond_fecha AND estado != 'cancelada'"
));

$r_gastos = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(monto), 0) as total_gastos
     FROM gastos_dia g
     WHERE $cond_fecha_g"
));

$ingresos      = floatval($r_kpi['ingresos']);
$gastos        = floatval($r_gastos['total_gastos']);
$utilidad      = $ingresos - $gastos;
$total_ventas  = intval($r_kpi['total_ventas']);
$ticket_prom   = floatval($r_kpi['ticket_promedio']);

// ── VENTAS POR DÍA (últimos 30 días para la gráfica) ──
$r_por_dia = mysqli_query($conn,
    "SELECT DATE(fecha_venta) as dia,
            COUNT(*) as num_ventas,
            COALESCE(SUM(total), 0) as monto
     FROM ventas
     WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       AND estado != 'cancelada'
     GROUP BY DATE(fecha_venta)
     ORDER BY dia ASC"
);
$datos_dia = [];
while ($row = mysqli_fetch_assoc($r_por_dia)) {
    $datos_dia[] = $row;
}

// ── VENTAS POR MÉTODO DE PAGO ──
$r_metodo = mysqli_query($conn,
    "SELECT metodo_pago,
            COUNT(*) as cantidad,
            COALESCE(SUM(total), 0) as monto
     FROM ventas v
     WHERE $cond_fecha AND estado != 'cancelada'
     GROUP BY metodo_pago
     ORDER BY monto DESC"
);
$datos_metodo = [];
while ($row = mysqli_fetch_assoc($r_metodo)) {
    $datos_metodo[] = $row;
}

// ── TOP 10 PRODUCTOS MÁS VENDIDOS ──
$r_top_prod = mysqli_query($conn,
    "SELECT p.nombre, p.marca,
            SUM(dv.cantidad) as unidades,
            SUM(dv.subtotal) as total
     FROM detalles_venta dv
     JOIN ventas v ON dv.id_venta = v.id
     JOIN productos p ON dv.id_producto = p.id
     WHERE $cond_fecha AND v.estado != 'cancelada'
     GROUP BY dv.id_producto
     ORDER BY unidades DESC
     LIMIT 10"
);
$top_productos = [];
while ($row = mysqli_fetch_assoc($r_top_prod)) {
    $top_productos[] = $row;
}
$max_unidades = !empty($top_productos) ? $top_productos[0]['unidades'] : 1;

// ── GASTOS POR TIPO ──
$r_gastos_tipo = mysqli_query($conn,
    "SELECT tipo,
            COUNT(*) as cantidad,
            COALESCE(SUM(monto), 0) as total
     FROM gastos_dia g
     WHERE $cond_fecha_g
     GROUP BY tipo
     ORDER BY total DESC"
);
$datos_gastos_tipo = [];
while ($row = mysqli_fetch_assoc($r_gastos_tipo)) {
    $datos_gastos_tipo[] = $row;
}

// ── VENTAS POR VENDEDOR ──
$r_vendedores = mysqli_query($conn,
    "SELECT u.nombre, u.apellido_paterno,
            COUNT(v.id) as ventas,
            COALESCE(SUM(v.total), 0) as monto
     FROM ventas v
     JOIN usuarios u ON v.id_usuario = u.id
     WHERE $cond_fecha AND v.estado != 'cancelada'
     GROUP BY v.id_usuario
     ORDER BY monto DESC"
);
$datos_vendedores = [];
while ($row = mysqli_fetch_assoc($r_vendedores)) {
    $datos_vendedores[] = $row;
}

// ── ÚLTIMAS 10 VENTAS ──
$r_ultimas = mysqli_query($conn,
    "SELECT v.folio, v.total, v.metodo_pago, v.fecha_venta,
            u.nombre as vendedor
     FROM ventas v
     JOIN usuarios u ON v.id_usuario = u.id
     WHERE v.estado != 'cancelada'
     ORDER BY v.fecha_venta DESC
     LIMIT 10"
);

// JSON para gráficas
$json_dias    = json_encode(array_map(fn($d) => date('d/m', strtotime($d['dia'])), $datos_dia));
$json_montos  = json_encode(array_map(fn($d) => round($d['monto'], 2), $datos_dia));
$json_ventas  = json_encode(array_map(fn($d) => intval($d['num_ventas']), $datos_dia));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Reportes</title>

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
            --purple: #9b59b6;
            --teal:   #0066aa;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--navy); color: var(--white); overflow-x: hidden; }

        .bg-canvas {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse at 10% 15%, rgba(0,194,255,.1) 0%, transparent 50%),
                radial-gradient(ellipse at 88% 80%, rgba(0,82,204,.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%);
        }
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image: linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .page-wrap { position: relative; z-index: 1; padding: 24px 28px 50px; max-width: 1600px; margin: 0 auto; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        .anim   { animation: fadeUp .5s ease both; }
        .anim-1 { animation-delay: .05s; }
        .anim-2 { animation-delay: .12s; }
        .anim-3 { animation-delay: .20s; }
        .anim-4 { animation-delay: .28s; }
        .anim-5 { animation-delay: .36s; }

        /* ── TOPBAR ── */
        .topbar {
            display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
            background: rgba(13,35,71,.7); backdrop-filter: blur(16px);
            border: 1px solid var(--border); border-radius: 22px;
            padding: 14px 22px; margin-bottom: 28px; box-shadow: 0 8px 32px rgba(0,0,0,.4);
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .logo-img { width: 68px; height: 68px; border-radius: 18px; object-fit: cover; border: 2px solid rgba(0,194,255,.3); box-shadow: 0 0 0 4px rgba(0,194,255,.08), 0 8px 20px rgba(0,0,0,.5); transition: transform .4s cubic-bezier(.34,1.56,.64,1); }
        .logo-img:hover { transform: scale(1.08) rotate(-2deg); }
        .brand-name { font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 900; letter-spacing: 3px; }
        .brand-name span { color: var(--accent); }
        .brand-sub { font-size: .68rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.35); margin-top: 3px; }

        .page-pill { display: flex; align-items: center; gap: 10px; background: rgba(0,102,170,.1); border: 1px solid rgba(0,102,170,.25); border-radius: 40px; padding: 8px 20px; }
        .page-pill i   { color: #66d9ff; font-size: .9rem; }
        .page-pill span{ font-size: .78rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.75); }

        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .user-chip { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,.05); border: 1px solid var(--border); border-radius: 50px; padding: 6px 16px 6px 6px; }
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; }
        .u-name { font-size: .82rem; font-weight: 600; }
        .u-role { font-size: .62rem; color: var(--gold); text-transform: uppercase; letter-spacing: 1px; }
        .btn-back { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12); color: rgba(255,255,255,.7); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all .25s; }
        .btn-back:hover { background: var(--blue); color: white; border-color: var(--blue); transform: translateX(-3px); }

        /* ── FILTROS ── */
        .filtros-card {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px; padding: 18px 24px;
            display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .date-tabs { display: flex; gap: 6px; }
        .date-tab { padding: 9px 18px; border-radius: 12px; font-size: .8rem; font-weight: 600; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04); color: rgba(255,255,255,.55); cursor: pointer; text-decoration: none; transition: all .2s; }
        .date-tab:hover, .date-tab.active { background: rgba(0,102,170,.18); border-color: #66d9ff; color: #66d9ff; }

        .field-label { font-size: .65rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,.45); margin-bottom: 7px; display: block; }
        .field-wrap { position: relative; }
        .field-wrap .f-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: .82rem; pointer-events: none; z-index: 1; }
        .field-wrap input { padding: 11px 14px 11px 38px; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; font-size: .88rem; font-family: 'DM Sans', sans-serif; background: rgba(255,255,255,.05); color: var(--white); outline: none; transition: border-color .25s; min-width: 150px; }
        .field-wrap input:focus { border-color: #66d9ff; box-shadow: 0 0 0 3px rgba(0,194,255,.1); }

        .btn-tv { display: inline-flex; align-items: center; gap: 8px; padding: 11px 24px; border-radius: 12px; border: none; font-family: 'DM Sans', sans-serif; font-size: .85rem; font-weight: 700; letter-spacing: 1px; cursor: pointer; text-decoration: none; transition: all .25s; }
        .btn-save { background: linear-gradient(135deg, var(--teal), var(--accent)); color: white; box-shadow: 0 6px 20px rgba(0,82,204,.3); }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,82,204,.4); color: white; }

        /* ── KPI CARDS ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }

        .kpi-card {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 20px; padding: 20px 22px;
            display: flex; align-items: center; gap: 16px;
            position: relative; overflow: hidden;
            transition: transform .3s, box-shadow .3s;
        }
        .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 4px 4px 0 0; }
        .kpi-card.k-blue::before   { background: linear-gradient(90deg, var(--blue), var(--accent)); }
        .kpi-card.k-green::before  { background: linear-gradient(90deg, #00875a, var(--success)); }
        .kpi-card.k-gold::before   { background: linear-gradient(90deg, #e07b00, var(--gold)); }
        .kpi-card.k-red::before    { background: linear-gradient(90deg, #c0392b, var(--danger)); }
        .kpi-card.k-purple::before { background: linear-gradient(90deg, #5b3cc4, var(--purple)); }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,.35); }

        .kpi-ico { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .k-blue   .kpi-ico { background: rgba(0,82,204,.2);   color: var(--accent); }
        .k-green  .kpi-ico { background: rgba(0,214,143,.15); color: var(--success); }
        .k-gold   .kpi-ico { background: rgba(245,197,24,.15); color: var(--gold); }
        .k-red    .kpi-ico { background: rgba(255,77,77,.15);  color: var(--danger); }
        .k-purple .kpi-ico { background: rgba(155,89,182,.2);  color: var(--purple); }

        .kpi-val { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: var(--white); line-height: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kpi-lbl { font-size: .7rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 4px; }

        /* ── SECTION HEAD ── */
        .section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-head .s-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(0,102,170,.4), transparent); }
        .section-head .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: #66d9ff; }

        /* ── GLASS CARD ── */
        .glass-card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 24px; padding: 24px 28px; backdrop-filter: blur(10px); box-shadow: 0 8px 40px rgba(0,0,0,.3); }

        .card-title { font-family: 'Playfair Display', serif; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
        .card-title i { color: #66d9ff; }

        /* ── GRÁFICA LÍNEA ── */
        .chart-wrap { position: relative; height: 260px; }

        /* ── MÉTODOS PAGO ── */
        .metodo-list { display: flex; flex-direction: column; gap: 12px; }
        .metodo-row { display: flex; align-items: center; gap: 12px; }
        .metodo-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
        .m-efectivo .metodo-icon { background: rgba(0,214,143,.15); color: var(--success); }
        .m-debito   .metodo-icon { background: rgba(0,194,255,.15); color: var(--accent); }
        .m-credito  .metodo-icon { background: rgba(245,197,24,.15); color: var(--gold); }
        .m-paypal   .metodo-icon { background: rgba(0,112,243,.15);  color: #4db3ff; }
        .metodo-nombre { font-size: .85rem; font-weight: 600; flex: 1; }
        .metodo-monto  { font-family: 'Playfair Display', serif; font-size: .95rem; color: var(--success); white-space: nowrap; }
        .metodo-cant   { font-size: .72rem; color: rgba(255,255,255,.35); margin-top: 1px; }
        .metodo-bar-wrap { flex: 2; }
        .metodo-bar-bg { height: 6px; background: rgba(255,255,255,.06); border-radius: 4px; overflow: hidden; }
        .metodo-bar-fill { height: 100%; border-radius: 4px; transition: width 1s ease; }
        .m-efectivo .metodo-bar-fill { background: linear-gradient(90deg, #00875a, var(--success)); }
        .m-debito   .metodo-bar-fill { background: linear-gradient(90deg, var(--blue), var(--accent)); }
        .m-credito  .metodo-bar-fill { background: linear-gradient(90deg, #e07b00, var(--gold)); }
        .m-paypal   .metodo-bar-fill { background: linear-gradient(90deg, #0052cc, #4db3ff); }

        /* ── TOP PRODUCTOS ── */
        .prod-rank { display: flex; flex-direction: column; gap: 10px; }
        .prod-rank-item { display: flex; align-items: center; gap: 12px; }
        .rank-num { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: rgba(255,255,255,.2); width: 24px; text-align: center; flex-shrink: 0; }
        .rank-num.top3 { color: var(--gold); }
        .rank-info { flex: 1; min-width: 0; }
        .rank-nombre { font-size: .85rem; font-weight: 600; color: var(--white); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rank-marca  { font-size: .7rem; color: rgba(255,255,255,.35); }
        .rank-bar-wrap { flex: 1.5; }
        .rank-bar-bg   { height: 5px; background: rgba(255,255,255,.06); border-radius: 4px; overflow: hidden; }
        .rank-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--blue), var(--accent)); transition: width 1.2s ease; }
        .rank-uds  { font-size: .82rem; font-weight: 700; color: var(--accent); white-space: nowrap; min-width: 50px; text-align: right; }

        /* ── GASTOS POR TIPO ── */
        .gasto-tipo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .gasto-tipo-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.07); border-radius: 16px; padding: 16px; position: relative; overflow: hidden; }
        .gasto-tipo-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
        .gt-mercancia::before { background: linear-gradient(90deg, #00875a, var(--success)); }
        .gt-renta::before     { background: linear-gradient(90deg, var(--blue), var(--accent)); }
        .gt-servicios::before { background: linear-gradient(90deg, #5b3cc4, var(--purple)); }
        .gt-robo::before      { background: linear-gradient(90deg, #c0392b, var(--danger)); }
        .gt-otros::before     { background: linear-gradient(90deg, #e07b00, var(--gold)); }
        .gasto-tipo-ico { font-size: 1.3rem; margin-bottom: 10px; }
        .gt-mercancia .gasto-tipo-ico { color: var(--success); }
        .gt-renta     .gasto-tipo-ico { color: var(--accent); }
        .gt-servicios .gasto-tipo-ico { color: var(--purple); }
        .gt-robo      .gasto-tipo-ico { color: var(--danger); }
        .gt-otros     .gasto-tipo-ico { color: var(--gold); }
        .gasto-tipo-monto { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: var(--white); }
        .gasto-tipo-lbl   { font-size: .68rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 3px; }
        .gasto-tipo-cant  { font-size: .72rem; color: rgba(255,255,255,.3); margin-top: 2px; }

        /* ── VENDEDORES ── */
        .vendedor-list { display: flex; flex-direction: column; gap: 12px; }
        .vendedor-row  { display: flex; align-items: center; gap: 12px; }
        .vend-avatar { width: 38px; height: 38px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .95rem; flex-shrink: 0; }
        .vend-nombre { font-size: .85rem; font-weight: 600; }
        .vend-ventas { font-size: .72rem; color: rgba(255,255,255,.35); margin-top: 2px; }
        .vend-monto  { font-family: 'Playfair Display', serif; font-size: .95rem; color: var(--success); margin-left: auto; white-space: nowrap; }

        /* ── TABLA ÚLTIMAS VENTAS ── */
        .table-wrap { overflow-x: auto; border-radius: 16px; }
        table.tv-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .tv-table thead tr th { background: rgba(0,102,170,.12); color: rgba(255,255,255,.55); font-size: .65rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; padding: 13px 16px; border-bottom: 1px solid rgba(255,255,255,.07); white-space: nowrap; }
        .tv-table thead tr th:first-child { border-radius: 16px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius: 0 16px 0 0; }
        .tv-table tbody tr { transition: background .2s; }
        .tv-table tbody tr:hover { background: rgba(0,102,170,.06); }
        .tv-table tbody td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; font-size: .86rem; color: rgba(255,255,255,.75); }

        .folio-val { font-family: 'Playfair Display', serif; font-size: .92rem; color: #66d9ff; font-weight: 700; }
        .price-val { font-family: 'Playfair Display', serif; font-size: .95rem; color: var(--success); }

        .metodo-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .mb-efectivo { background: rgba(0,214,143,.12); color: var(--success); border: 1px solid rgba(0,214,143,.25); }
        .mb-debito   { background: rgba(0,194,255,.12); color: var(--accent);  border: 1px solid rgba(0,194,255,.25); }
        .mb-credito  { background: rgba(245,197,24,.12); color: var(--gold);   border: 1px solid rgba(245,197,24,.25); }
        .mb-paypal   { background: rgba(0,112,243,.15);  color: #4db3ff;       border: 1px solid rgba(0,112,243,.3); }

        /* ── UTILIDAD CARD ── */
        .utilidad-card {
            background: <?php echo $utilidad >= 0 ? 'linear-gradient(135deg, rgba(0,135,90,.12), rgba(0,214,143,.06))' : 'linear-gradient(135deg, rgba(192,57,43,.12), rgba(255,77,77,.06))'; ?>;
            border: 1px solid <?php echo $utilidad >= 0 ? 'rgba(0,214,143,.2)' : 'rgba(255,77,77,.2)'; ?>;
            border-radius: 20px; padding: 22px 28px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 24px;
        }
        .util-label { font-size: .7rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 6px; }
        .util-val   { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: <?php echo $utilidad >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; }
        .util-period{ font-size: .75rem; color: rgba(255,255,255,.35); margin-top: 4px; }
        .util-breakdown { display: flex; gap: 28px; flex-wrap: wrap; }
        .util-item { text-align: center; }
        .util-item-val { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; }
        .util-item-lbl { font-size: .65rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 2px; }
        .util-item-val.ingreso { color: var(--success); }
        .util-item-val.gasto   { color: var(--danger); }

        /* Empty */
        .empty-state { text-align: center; padding: 40px 20px; color: rgba(255,255,255,.3); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: .3; }
        .empty-state p { font-size: .83rem; }

        /* Footer */
        .page-footer { text-align: center; margin-top: 36px; font-size: .66rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.15); }
        .page-footer span { color: #66d9ff; opacity: .5; }

        /* Responsive */
        @media (max-width: 1200px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) {
            .page-wrap { padding: 14px 12px 36px; }
            .kpi-grid  { grid-template-columns: 1fr 1fr; }
            .glass-card { padding: 18px 16px; }
            .page-pill  { display: none; }
        }
        @media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr; } }
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
            <i class="fas fa-chart-line"></i>
            <span>Reportes y Estadísticas</span>
        </div>
        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?php echo strtoupper(substr($user_nombre,0,1)); ?></div>
                <div>
                    <div class="u-name"><?php echo htmlspecialchars($user_nombre); ?></div>
                    <div class="u-role"><?php echo $user_rol == 'supervisor' ? '◆ Supervisor' : '★ Administrador'; ?></div>
                </div>
            </div>
            <a href="menu_principal.php" class="btn-back" title="Regresar al menú">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- ── FILTROS ── -->
    <div class="filtros-card anim anim-1">
        <div>
            <label class="field-label">Período</label>
            <div class="date-tabs">
                <a href="?periodo=hoy"    class="date-tab <?php echo $periodo=='hoy'    ? 'active':''; ?>"><i class="fas fa-sun me-1"></i>Hoy</a>
                <a href="?periodo=semana" class="date-tab <?php echo $periodo=='semana' ? 'active':''; ?>"><i class="fas fa-calendar-week me-1"></i>7 días</a>
                <a href="?periodo=mes"    class="date-tab <?php echo $periodo=='mes'    ? 'active':''; ?>"><i class="fas fa-calendar me-1"></i>Este mes</a>
                <a href="?periodo=rango"  class="date-tab <?php echo $periodo=='rango'  ? 'active':''; ?>"><i class="fas fa-calendar-range me-1"></i>Rango</a>
            </div>
        </div>
        <?php if ($periodo == 'rango'): ?>
        <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="periodo" value="rango">
            <div>
                <label class="field-label">Desde</label>
                <div class="field-wrap">
                    <i class="fas fa-calendar f-ico"></i>
                    <input type="date" name="desde" value="<?php echo $desde; ?>">
                </div>
            </div>
            <div>
                <label class="field-label">Hasta</label>
                <div class="field-wrap">
                    <i class="fas fa-calendar f-ico"></i>
                    <input type="date" name="hasta" value="<?php echo $hasta; ?>">
                </div>
            </div>
            <button type="submit" class="btn-tv btn-save">
                <i class="fas fa-magnifying-glass"></i> Buscar
            </button>
        </form>
        <?php endif; ?>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px;font-size:.78rem;color:rgba(255,255,255,.4);">
            <i class="fas fa-clock-rotate-left"></i>
            <span><?php echo $label_periodo; ?></span>
        </div>
    </div>

    <!-- ── KPIs ── -->
    <div class="kpi-grid anim anim-2">
        <div class="kpi-card k-blue">
            <div class="kpi-ico"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="kpi-val"><?php echo number_format($total_ventas); ?></div>
                <div class="kpi-lbl">Ventas totales</div>
            </div>
        </div>
        <div class="kpi-card k-green">
            <div class="kpi-ico"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="kpi-val">$<?php echo number_format($ingresos, 0); ?></div>
                <div class="kpi-lbl">Ingresos</div>
            </div>
        </div>
        <div class="kpi-card k-red">
            <div class="kpi-ico"><i class="fas fa-arrow-trend-down"></i></div>
            <div>
                <div class="kpi-val">$<?php echo number_format($gastos, 0); ?></div>
                <div class="kpi-lbl">Gastos</div>
            </div>
        </div>
        <div class="kpi-card <?php echo $utilidad >= 0 ? 'k-green' : 'k-red'; ?>">
            <div class="kpi-ico"><i class="fas fa-scale-balanced"></i></div>
            <div>
                <div class="kpi-val">$<?php echo number_format(abs($utilidad), 0); ?></div>
                <div class="kpi-lbl"><?php echo $utilidad >= 0 ? 'Utilidad' : 'Pérdida'; ?></div>
            </div>
        </div>
        <div class="kpi-card k-purple">
            <div class="kpi-ico"><i class="fas fa-chart-simple"></i></div>
            <div>
                <div class="kpi-val">$<?php echo number_format($ticket_prom, 0); ?></div>
                <div class="kpi-lbl">Ticket promedio</div>
            </div>
        </div>
    </div>

    <!-- ── UTILIDAD RESUMEN ── -->
    <div class="utilidad-card anim anim-2">
        <div>
            <div class="util-label">Resultado del período</div>
            <div class="util-val"><?php echo $utilidad >= 0 ? '+' : ''; ?>$<?php echo number_format($utilidad, 2); ?></div>
            <div class="util-period"><i class="fas fa-calendar-check me-1"></i><?php echo $label_periodo; ?></div>
        </div>
        <div class="util-breakdown">
            <div class="util-item">
                <div class="util-item-val ingreso">$<?php echo number_format($ingresos, 2); ?></div>
                <div class="util-item-lbl"><i class="fas fa-arrow-up me-1"></i>Ingresos</div>
            </div>
            <div class="util-item">
                <div class="util-item-val gasto">$<?php echo number_format($gastos, 2); ?></div>
                <div class="util-item-lbl"><i class="fas fa-arrow-down me-1"></i>Gastos</div>
            </div>
            <div class="util-item">
                <div class="util-item-val" style="color:<?php echo $utilidad>=0?'var(--success)':'var(--danger)';?>">
                    <?php echo $ingresos > 0 ? number_format(($utilidad / $ingresos) * 100, 1) : '0'; ?>%
                </div>
                <div class="util-item-lbl"><i class="fas fa-percent me-1"></i>Margen</div>
            </div>
        </div>
    </div>

    <!-- ── FILA: GRÁFICA + MÉTODOS ── -->
    <div class="section-head anim anim-3">
        <div class="s-label">Tendencia de ventas</div>
        <div class="s-line"></div>
    </div>

    <div class="row g-4 mb-4 anim anim-3">
        <!-- Gráfica ventas por día -->
        <div class="col-lg-8">
            <div class="glass-card" style="height:100%;">
                <div class="card-title">
                    <i class="fas fa-chart-line"></i> Ventas diarias — últimos 30 días
                </div>
                <?php if (empty($datos_dia)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No hay datos de ventas en este período</p>
                </div>
                <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="chartVentas"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Métodos de pago -->
        <div class="col-lg-4">
            <div class="glass-card" style="height:100%;">
                <div class="card-title">
                    <i class="fas fa-credit-card"></i> Métodos de pago
                </div>
                <?php if (empty($datos_metodo)): ?>
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>Sin datos</p>
                </div>
                <?php else: ?>
                <?php
                $max_metodo = !empty($datos_metodo) ? $datos_metodo[0]['monto'] : 1;
                $total_monto_metodos = array_sum(array_column($datos_metodo, 'monto'));
                $metodo_info = [
                    'efectivo' => ['ico' => 'fa-money-bill-wave', 'clase' => 'm-efectivo', 'nom' => 'Efectivo'],
                    'debito'   => ['ico' => 'fa-credit-card',     'clase' => 'm-debito',   'nom' => 'Débito'],
                    'credito'  => ['ico' => 'fa-credit-card',     'clase' => 'm-credito',  'nom' => 'Crédito'],
                    'paypal'   => ['ico' => 'fab fa-paypal',      'clase' => 'm-paypal',   'nom' => 'PayPal'],
                ];
                ?>
                <div class="metodo-list">
                    <?php foreach ($datos_metodo as $m):
                        $info = $metodo_info[$m['metodo_pago']] ?? ['ico'=>'fa-money-bill','clase'=>'m-efectivo','nom'=>ucfirst($m['metodo_pago'])];
                        $pct  = $max_metodo > 0 ? ($m['monto'] / $max_metodo) * 100 : 0;
                        $pct_total = $total_monto_metodos > 0 ? round(($m['monto'] / $total_monto_metodos) * 100, 1) : 0;
                    ?>
                    <div class="metodo-row <?php echo $info['clase']; ?>">
                        <div class="metodo-icon">
                            <i class="<?php echo strpos($info['ico'],'fab')===false ? 'fas ' : ''; ?><?php echo $info['ico']; ?>"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="metodo-nombre"><?php echo $info['nom']; ?></div>
                            <div class="metodo-cant"><?php echo $m['cantidad']; ?> ventas · <?php echo $pct_total; ?>%</div>
                            <div class="metodo-bar-wrap mt-1">
                                <div class="metodo-bar-bg">
                                    <div class="metodo-bar-fill" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="metodo-monto">$<?php echo number_format($m['monto'], 0); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Donut chart -->
                <div style="height:160px;margin-top:20px;position:relative;">
                    <canvas id="chartMetodos"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── FILA: TOP PRODUCTOS + VENDEDORES ── -->
    <div class="section-head anim anim-4">
        <div class="s-label">Rendimiento</div>
        <div class="s-line"></div>
    </div>

    <div class="row g-4 mb-4 anim anim-4">
        <!-- Top productos -->
        <div class="col-lg-7">
            <div class="glass-card">
                <div class="card-title">
                    <i class="fas fa-trophy"></i> Top 10 productos más vendidos
                </div>
                <?php if (empty($top_productos)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No hay ventas en este período</p>
                </div>
                <?php else: ?>
                <div class="prod-rank">
                    <?php foreach ($top_productos as $i => $p):
                        $pct = $max_unidades > 0 ? ($p['unidades'] / $max_unidades) * 100 : 0;
                    ?>
                    <div class="prod-rank-item">
                        <div class="rank-num <?php echo $i < 3 ? 'top3' : ''; ?>"><?php echo $i+1; ?></div>
                        <div class="rank-info">
                            <div class="rank-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                            <div class="rank-marca"><?php echo htmlspecialchars($p['marca']); ?></div>
                        </div>
                        <div class="rank-bar-wrap">
                            <div class="rank-bar-bg">
                                <div class="rank-bar-fill" style="width:<?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                        <div class="rank-uds"><?php echo $p['unidades']; ?> uds</div>
                        <div style="min-width:70px;text-align:right;font-family:'Playfair Display',serif;font-size:.85rem;color:var(--success);">
                            $<?php echo number_format($p['total'], 0); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vendedores -->
        <div class="col-lg-5">
            <div class="glass-card" style="height:100%;">
                <div class="card-title">
                    <i class="fas fa-users"></i> Ventas por vendedor
                </div>
                <?php if (empty($datos_vendedores)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>Sin datos de vendedores</p>
                </div>
                <?php else: ?>
                <div class="vendedor-list">
                    <?php foreach ($datos_vendedores as $vend): ?>
                    <div class="vendedor-row">
                        <div class="vend-avatar"><?php echo strtoupper(substr($vend['nombre'],0,1)); ?></div>
                        <div>
                            <div class="vend-nombre"><?php echo htmlspecialchars($vend['nombre'] . ' ' . $vend['apellido_paterno']); ?></div>
                            <div class="vend-ventas"><?php echo $vend['ventas']; ?> venta(s) realizadas</div>
                        </div>
                        <div class="vend-monto">$<?php echo number_format($vend['monto'], 0); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Gráfica vendedores -->
                <?php if (count($datos_vendedores) > 1): ?>
                <div style="height:160px;margin-top:20px;position:relative;">
                    <canvas id="chartVendedores"></canvas>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── GASTOS POR TIPO ── -->
    <?php if (!empty($datos_gastos_tipo)): ?>
    <div class="section-head anim anim-4">
        <div class="s-label">Desglose de gastos</div>
        <div class="s-line"></div>
    </div>
    <div class="glass-card mb-4 anim anim-4">
        <div class="card-title">
            <i class="fas fa-wallet"></i> Gastos por tipo — <?php echo $label_periodo; ?>
        </div>
        <div class="gasto-tipo-grid">
            <?php
            $gt_info = [
                'mercancia' => ['ico' => 'fa-boxes-stacked', 'cls' => 'gt-mercancia', 'nom' => 'Mercancía'],
                'renta'     => ['ico' => 'fa-building',      'cls' => 'gt-renta',     'nom' => 'Renta'],
                'servicios' => ['ico' => 'fa-bolt',          'cls' => 'gt-servicios', 'nom' => 'Servicios'],
                'robo'      => ['ico' => 'fa-user-secret',   'cls' => 'gt-robo',      'nom' => 'Robo hormiga'],
                'otros'     => ['ico' => 'fa-ellipsis',      'cls' => 'gt-otros',     'nom' => 'Otros'],
            ];
            foreach ($datos_gastos_tipo as $gt):
                $gi = $gt_info[$gt['tipo']] ?? $gt_info['otros'];
            ?>
            <div class="gasto-tipo-card <?php echo $gi['cls']; ?>">
                <div class="gasto-tipo-ico"><i class="fas <?php echo $gi['ico']; ?>"></i></div>
                <div class="gasto-tipo-monto">$<?php echo number_format($gt['total'], 2); ?></div>
                <div class="gasto-tipo-lbl"><?php echo $gi['nom']; ?></div>
                <div class="gasto-tipo-cant"><?php echo $gt['cantidad']; ?> registro(s)</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── ÚLTIMAS VENTAS ── -->
    <div class="section-head anim anim-5">
        <div class="s-label">Últimas ventas registradas</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-5">
        <div class="card-title">
            <i class="fas fa-clock-rotate-left"></i> Historial reciente
            <a href="historial_ventas.php" style="margin-left:auto;font-size:.75rem;font-weight:600;color:rgba(255,255,255,.4);text-decoration:none;display:flex;align-items:center;gap:6px;" onmouseover="this.style.color='#66d9ff'" onmouseout="this.style.color='rgba(255,255,255,.4)'">
                Ver todo <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-wrap">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha y Hora</th>
                        <th>Vendedor</th>
                        <th>Método</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $metodo_badges = [
                    'efectivo' => ['cls'=>'mb-efectivo','ico'=>'fa-money-bill-wave','nom'=>'Efectivo'],
                    'debito'   => ['cls'=>'mb-debito',  'ico'=>'fa-credit-card',    'nom'=>'Débito'],
                    'credito'  => ['cls'=>'mb-credito', 'ico'=>'fa-credit-card',    'nom'=>'Crédito'],
                    'paypal'   => ['cls'=>'mb-paypal',  'ico'=>'fa-paypal',         'nom'=>'PayPal'],
                ];
                $count = 0;
                while ($v = mysqli_fetch_assoc($r_ultimas)):
                    $mb = $metodo_badges[$v['metodo_pago']] ?? $metodo_badges['efectivo'];
                    $count++;
                ?>
                <tr>
                    <td><span class="folio-val"><?php echo htmlspecialchars($v['folio']); ?></span></td>
                    <td style="font-size:.82rem;">
                        <div><?php echo date('d/m/Y', strtotime($v['fecha_venta'])); ?></div>
                        <div style="color:rgba(255,255,255,.35);font-size:.72rem;"><?php echo date('H:i', strtotime($v['fecha_venta'])); ?></div>
                    </td>
                    <td>
                        <span style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user-circle" style="color:#66d9ff;font-size:.85rem;"></i>
                            <?php echo htmlspecialchars($v['vendedor']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="metodo-badge <?php echo $mb['cls']; ?>">
                            <i class="fas <?php echo $mb['ico']; ?>"></i>
                            <?php echo $mb['nom']; ?>
                        </span>
                    </td>
                    <td><span class="price-val">$<?php echo number_format($v['total'], 2); ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>No hay ventas registradas</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-footer">
        <span>◆</span> &nbsp;TecnoViral POS v1.0 &nbsp;<span>◆</span>
    </div>

</div><!-- /page-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Datos desde PHP ──
const diasLabels  = <?php echo $json_dias; ?>;
const diasMontos  = <?php echo $json_montos; ?>;
const diasVentas  = <?php echo $json_ventas; ?>;

// Defaults globales Chart.js
Chart.defaults.color = 'rgba(255,255,255,.45)';
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.font.size = 11;

// ── Gráfica de línea ventas diarias ──
<?php if (!empty($datos_dia)): ?>
const ctxVentas = document.getElementById('chartVentas').getContext('2d');

const gradientLine = ctxVentas.createLinearGradient(0, 0, 0, 260);
gradientLine.addColorStop(0, 'rgba(0,194,255,.35)');
gradientLine.addColorStop(1, 'rgba(0,194,255,.01)');

new Chart(ctxVentas, {
    type: 'line',
    data: {
        labels: diasLabels,
        datasets: [
            {
                label: 'Ingresos ($)',
                data: diasMontos,
                borderColor: '#00c2ff',
                backgroundColor: gradientLine,
                borderWidth: 2.5,
                pointRadius: diasLabels.length > 15 ? 2 : 4,
                pointBackgroundColor: '#00c2ff',
                pointBorderColor: '#07172e',
                pointBorderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y',
            },
            {
                label: 'Ventas (cant)',
                data: diasVentas,
                borderColor: '#00d68f',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: diasLabels.length > 15 ? 2 : 4,
                pointBackgroundColor: '#00d68f',
                pointBorderColor: '#07172e',
                pointBorderWidth: 2,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1',
                borderDash: [5, 3],
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 12, padding: 16, color: 'rgba(255,255,255,.55)' }
            },
            tooltip: {
                backgroundColor: 'rgba(13,35,71,.95)',
                borderColor: 'rgba(0,194,255,.3)',
                borderWidth: 1,
                titleColor: '#fff',
                bodyColor: 'rgba(255,255,255,.7)',
                padding: 12,
                callbacks: {
                    label: function(ctx) {
                        if (ctx.datasetIndex === 0) return ' $' + ctx.parsed.y.toLocaleString('es-MX', {minimumFractionDigits:2});
                        return ' ' + ctx.parsed.y + ' ventas';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,.04)' },
                ticks: { maxTicksLimit: 10, color: 'rgba(255,255,255,.35)' }
            },
            y: {
                position: 'left',
                grid: { color: 'rgba(255,255,255,.06)' },
                ticks: {
                    color: '#00c2ff',
                    callback: v => '$' + v.toLocaleString('es-MX')
                }
            },
            y1: {
                position: 'right',
                grid: { drawOnChartArea: false },
                ticks: { color: '#00d68f', stepSize: 1 }
            }
        }
    }
});
<?php endif; ?>

// ── Gráfica donut métodos de pago ──
<?php if (!empty($datos_metodo)): ?>
const ctxMetodos = document.getElementById('chartMetodos').getContext('2d');
const metodoLabels = <?php echo json_encode(array_map(function($m) {
    $labels = ['efectivo'=>'Efectivo','debito'=>'Débito','credito'=>'Crédito','paypal'=>'PayPal'];
    return $labels[$m['metodo_pago']] ?? ucfirst($m['metodo_pago']);
}, $datos_metodo)); ?>;
const metodoData = <?php echo json_encode(array_map(fn($m) => round($m['monto'], 2), $datos_metodo)); ?>;

new Chart(ctxMetodos, {
    type: 'doughnut',
    data: {
        labels: metodoLabels,
        datasets: [{
            data: metodoData,
            backgroundColor: ['rgba(0,214,143,.75)','rgba(0,194,255,.75)','rgba(245,197,24,.75)','rgba(77,179,255,.75)'],
            borderColor: ['#00d68f','#00c2ff','#f5c518','#4db3ff'],
            borderWidth: 2,
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, color: 'rgba(255,255,255,.5)' } },
            tooltip: {
                backgroundColor: 'rgba(13,35,71,.95)',
                borderColor: 'rgba(0,194,255,.3)',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ' $' + ctx.parsed.toLocaleString('es-MX', {minimumFractionDigits:2})
                }
            }
        }
    }
});
<?php endif; ?>

// ── Gráfica barras vendedores ──
<?php if (count($datos_vendedores) > 1): ?>
const ctxVend = document.getElementById('chartVendedores').getContext('2d');
new Chart(ctxVend, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(fn($v) => $v['nombre'], $datos_vendedores)); ?>,
        datasets: [{
            label: 'Ingresos',
            data: <?php echo json_encode(array_map(fn($v) => round($v['monto'], 2), $datos_vendedores)); ?>,
            backgroundColor: 'rgba(0,194,255,.25)',
            borderColor: '#00c2ff',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(13,35,71,.95)',
                borderColor: 'rgba(0,194,255,.3)',
                borderWidth: 1,
                callbacks: { label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-MX') }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,.45)' } },
            y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: 'rgba(255,255,255,.35)', callback: v => '$' + v.toLocaleString() } }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>