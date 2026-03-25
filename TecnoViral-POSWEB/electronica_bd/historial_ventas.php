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

// ── ELIMINAR VENTA (solo admin) ──
if (in_array($user_rol, ['administrador','supervisor']) && isset($_GET['eliminar'])) {
    $id_venta = intval($_GET['eliminar']);
    mysqli_query($conn, "DELETE FROM detalles_venta WHERE id_venta = $id_venta");
    mysqli_query($conn, "DELETE FROM ventas WHERE id = $id_venta");
    header('Location: historial_ventas.php?fecha=' . (isset($_GET['fecha']) ? $_GET['fecha'] : 'hoy'));
    exit();
}

// ── FILTROS ──
$filtro_fecha  = isset($_GET['fecha'])   ? $_GET['fecha']            : 'hoy';
$filtro_metodo = isset($_GET['metodo'])  ? $_GET['metodo']           : '';
$filtro_vendedor = isset($_GET['vendedor']) ? intval($_GET['vendedor']) : 0;
$fecha_desde   = isset($_GET['desde'])   ? $_GET['desde']            : '';
$fecha_hasta   = isset($_GET['hasta'])   ? $_GET['hasta']            : '';

// Construir condición de fecha
switch ($filtro_fecha) {
    case 'hoy':
        $cond_fecha = "DATE(v.fecha_venta) = CURDATE()";
        break;
    case 'semana':
        $cond_fecha = "v.fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'mes':
        $cond_fecha = "MONTH(v.fecha_venta) = MONTH(CURDATE()) AND YEAR(v.fecha_venta) = YEAR(CURDATE())";
        break;
    case 'rango':
        $desde = mysqli_real_escape_string($conn, $fecha_desde);
        $hasta = mysqli_real_escape_string($conn, $fecha_hasta);
        $cond_fecha = "DATE(v.fecha_venta) BETWEEN '$desde' AND '$hasta'";
        break;
    default:
        $cond_fecha = "DATE(v.fecha_venta) = CURDATE()";
}

$cond_extra = "";
if ($filtro_metodo != '') {
    $m = mysqli_real_escape_string($conn, $filtro_metodo);
    $cond_extra .= " AND v.metodo_pago = '$m'";
}
if (!in_array($user_rol, ['administrador','supervisor'])) {
    $cond_extra .= " AND v.id_usuario = $user_id";
} elseif ($filtro_vendedor > 0) {
    $cond_extra .= " AND v.id_usuario = $filtro_vendedor";
}

// Ventas
$query_ventas = "SELECT v.*, u.nombre as vendedor_nombre, u.apellido_paterno as vendedor_ap
                 FROM ventas v
                 LEFT JOIN usuarios u ON v.id_usuario = u.id
                 WHERE $cond_fecha AND v.estado != 'cancelada' $cond_extra
                 ORDER BY v.fecha_venta DESC";
$result_ventas = mysqli_query($conn, $query_ventas);
$total_ventas  = mysqli_num_rows($result_ventas);

// Resumen
$query_resumen = "SELECT COUNT(*) as num_ventas, COALESCE(SUM(total),0) as total_monto,
                  COALESCE(AVG(total),0) as promedio
                  FROM ventas v
                  WHERE $cond_fecha AND v.estado != 'cancelada' $cond_extra";
$resumen = mysqli_fetch_assoc(mysqli_query($conn, $query_resumen));

// Vendedores (solo admin)
$result_vendedores = null;
if (in_array($user_rol, ['administrador','supervisor'])) {
    $result_vendedores = mysqli_query($conn, "SELECT id, nombre, apellido_paterno FROM usuarios WHERE activo = 1 ORDER BY nombre");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Historial de Ventas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:   #07172e; --navy2:  #0d2347;
            --blue:   #0052cc; --accent: #00c2ff;
            --gold:   #f5c518; --white:  #ffffff;
            --muted:  #7a8ba0; --border: rgba(255,255,255,.1);
            --danger: #ff4d4d; --success:#00d68f;
            --purple: #9b59b6;
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

        /* TOPBAR */
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
        .page-pill { display: flex; align-items: center; gap: 10px; background: rgba(155,89,182,.1); border: 1px solid rgba(155,89,182,.25); border-radius: 40px; padding: 8px 20px; }
        .page-pill i   { color: var(--purple); font-size: .9rem; }
        .page-pill span{ font-size: .78rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.75); }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .user-chip { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,.05); border: 1px solid var(--border); border-radius: 50px; padding: 6px 16px 6px 6px; }
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; }
        .u-name { font-size: .82rem; font-weight: 600; }
        .u-role { font-size: .62rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; }
        .btn-back { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12); color: rgba(255,255,255,.7); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all .25s; }
        .btn-back:hover { background: var(--blue); color: white; border-color: var(--blue); transform: translateX(-3px); }

        /* STATS */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: rgba(255,255,255,.04); border: 1px solid var(--border); border-radius: 20px; padding: 20px 24px; display: flex; align-items: center; gap: 16px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 4px 4px 0 0; }
        .stat-card.s-blue::before  { background: linear-gradient(90deg, var(--blue), var(--accent)); }
        .stat-card.s-green::before { background: linear-gradient(90deg, #00875a, var(--success)); }
        .stat-card.s-purple::before{ background: linear-gradient(90deg, #5b3cc4, var(--purple)); }
        .stat-ico { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .s-blue   .stat-ico { background: rgba(0,82,204,.2);   color: var(--accent); }
        .s-green  .stat-ico { background: rgba(0,214,143,.15); color: var(--success); }
        .s-purple .stat-ico { background: rgba(155,89,182,.2); color: var(--purple); }
        .stat-val { font-family: 'Playfair Display', serif; font-size: 1.6rem; font-weight: 700; color: var(--white); line-height: 1; }
        .stat-lbl { font-size: .72rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1.5px; margin-top: 4px; }

        /* FILTROS */
        .glass-card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 24px; padding: 24px 28px; backdrop-filter: blur(10px); box-shadow: 0 8px 40px rgba(0,0,0,.3); margin-bottom: 24px; }

        .filtros-wrap { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }

        .field-label { font-size: .65rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,.45); margin-bottom: 7px; display: block; }
        .field-wrap { position: relative; }
        .field-wrap .f-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: .82rem; pointer-events: none; z-index: 1; }
        .field-wrap input,
        .field-wrap select {
            padding: 11px 14px 11px 38px;
            border: 1px solid rgba(255,255,255,.1); border-radius: 12px;
            font-size: .88rem; font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.05); color: var(--white);
            outline: none; transition: border-color .25s; -webkit-appearance: none; min-width: 160px;
        }
        .field-wrap select option { background: var(--navy2); }
        .field-wrap input::placeholder { color: rgba(255,255,255,.2); }
        .field-wrap input:focus, .field-wrap select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,194,255,.1); }

        /* Tabs de fecha */
        .date-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
        .date-tab {
            padding: 9px 18px; border-radius: 12px; font-size: .8rem; font-weight: 600;
            border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.04);
            color: rgba(255,255,255,.55); cursor: pointer; text-decoration: none;
            transition: all .2s; letter-spacing: .5px;
            font-family: 'DM Sans', sans-serif; outline: none;
        }
        .date-tab:hover { border-color: var(--accent); color: var(--accent); }
        .date-tab.active { background: rgba(0,194,255,.15); border-color: var(--accent); color: var(--accent); }

        .btn-tv { display: inline-flex; align-items: center; gap: 8px; padding: 11px 24px; border-radius: 12px; border: none; font-family: 'DM Sans', sans-serif; font-size: .85rem; font-weight: 700; letter-spacing: 1px; cursor: pointer; text-decoration: none; transition: all .25s; }
        .btn-save { background: linear-gradient(135deg, var(--blue), var(--accent)); color: white; box-shadow: 0 6px 20px rgba(0,82,204,.3); }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,82,204,.4); color: white; }

        /* SECTION HEAD */
        .section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-head .s-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(155,89,182,.3), transparent); }
        .section-head .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: var(--purple); }

        /* TABLA */
        .table-wrap { overflow-x: auto; border-radius: 16px; }
        table.tv-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .tv-table thead tr th { background: rgba(155,89,182,.1); color: rgba(255,255,255,.55); font-size: .65rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; padding: 13px 16px; border-bottom: 1px solid rgba(255,255,255,.07); white-space: nowrap; }
        .tv-table thead tr th:first-child { border-radius: 16px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius: 0 16px 0 0; }
        .tv-table tbody tr.venta-row { cursor: pointer; transition: background .2s; }
        .tv-table tbody tr.venta-row:hover { background: rgba(155,89,182,.06); }
        .tv-table tbody td { padding: 15px 16px; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; font-size: .86rem; color: rgba(255,255,255,.75); }

        .folio-val { font-family: 'Playfair Display', serif; font-size: .95rem; color: var(--accent); font-weight: 700; }
        .price-val { font-family: 'Playfair Display', serif; font-size: 1rem; color: var(--success); }

        /* Método de pago badges */
        .metodo-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
        .m-efectivo { background: rgba(0,214,143,.12); color: var(--success); border: 1px solid rgba(0,214,143,.25); }
        .m-debito   { background: rgba(0,194,255,.12); color: var(--accent); border: 1px solid rgba(0,194,255,.25); }
        .m-credito  { background: rgba(245,197,24,.12); color: var(--gold);   border: 1px solid rgba(245,197,24,.25); }
        .m-paypal   { background: rgba(0,112,243,.15);  color: #4db3ff;       border: 1px solid rgba(0,112,243,.3); }

        /* Fila detalle expandible */
        .detalle-row td { padding: 0; }
        .detalle-inner { padding: 16px 24px 20px 60px; background: rgba(155,89,182,.04); border-top: 1px dashed rgba(155,89,182,.15); border-bottom: 1px solid rgba(255,255,255,.04); }
        .detalle-title { font-size: .68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--purple); margin-bottom: 12px; }
        .detalle-table { width: 100%; border-collapse: collapse; }
        .detalle-table th { font-size: .65rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1.5px; padding: 6px 10px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .detalle-table td { font-size: .84rem; color: rgba(255,255,255,.7); padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,.04); }
        .detalle-table tr:last-child td { border-bottom: none; }

        /* Arrow */
        .det-arrow { font-size: .65rem; color: rgba(255,255,255,.3); transition: transform .3s; margin-left: 6px; }
        .det-arrow.open { transform: rotate(180deg); color: var(--purple); }

        /* Empty */
        .empty-state { text-align: center; padding: 60px 20px; color: rgba(255,255,255,.3); }
        .empty-state i { font-size: 3rem; margin-bottom: 14px; display: block; opacity: .35; }
        .empty-state h5 { color: rgba(255,255,255,.45); margin-bottom: 6px; }

        /* Footer */
        .page-footer { text-align: center; margin-top: 36px; font-size: .66rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.15); }
        .page-footer span { color: var(--purple); opacity: .5; }

        /* Responsive */
        @media (max-width: 768px) {
            .page-wrap  { padding: 14px 12px 36px; }
            .stats-row  { grid-template-columns: 1fr 1fr; }
            .glass-card { padding: 18px 16px; }
            .page-pill  { display: none; }
            .topbar     { padding: 12px 14px; }
            .logo-img   { width: 52px; height: 52px; }
            .tv-table thead { display: none; }
            .tv-table tbody td { display: block; text-align: right; padding: 9px 14px; border-bottom: 1px dashed rgba(255,255,255,.05); font-size: .82rem; }
            .tv-table tbody td::before { content: attr(data-label); float: left; font-weight: 700; font-size: .63rem; letter-spacing: 1px; text-transform: uppercase; color: var(--purple); }
            .tv-table tbody tr.venta-row { display: block; border: 1px solid rgba(255,255,255,.07); border-radius: 16px; margin-bottom: 12px; background: rgba(255,255,255,.03); }
        }
    </style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="bg-grid"></div>

<div class="page-wrap">

    <!-- TOPBAR -->
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
            <i class="fas fa-clock-rotate-left"></i>
            <span>Historial de Ventas</span>
        </div>
        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?php echo strtoupper(substr($user_nombre,0,1)); ?></div>
                <div>
                    <div class="u-name"><?php echo htmlspecialchars($user_nombre); ?></div>
                    <div class="u-role"><?php echo $user_rol == 'administrador' ? '★ Admin' : 'Vendedor'; ?></div>
                </div>
            </div>
            <a href="menu_principal.php" class="btn-back"><i class="fas fa-arrow-left"></i></a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-row anim anim-1">
        <div class="stat-card s-blue">
            <div class="stat-ico"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="stat-val"><?php echo $resumen['num_ventas']; ?></div>
                <div class="stat-lbl">Ventas en período</div>
            </div>
        </div>
        <div class="stat-card s-green">
            <div class="stat-ico"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="stat-val">$<?php echo number_format($resumen['total_monto'], 0); ?></div>
                <div class="stat-lbl">Total recaudado</div>
            </div>
        </div>
        <div class="stat-card s-purple">
            <div class="stat-ico"><i class="fas fa-chart-simple"></i></div>
            <div>
                <div class="stat-val">$<?php echo number_format($resumen['promedio'], 0); ?></div>
                <div class="stat-lbl">Venta promedio</div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="glass-card anim anim-2">
        <form method="GET" action="">
            <div class="filtros-wrap">

                <!-- Tabs fecha -->
                <div>
                    <label class="field-label">Período</label>
                    <div class="date-tabs">
                        <button type="button" onclick="setFecha('hoy')"    class="date-tab <?php echo $filtro_fecha=='hoy'    ? 'active' : ''; ?>"><i class="fas fa-sun me-1"></i>Hoy</button>
                        <button type="button" onclick="setFecha('semana')" class="date-tab <?php echo $filtro_fecha=='semana' ? 'active' : ''; ?>"><i class="fas fa-calendar-week me-1"></i>7 días</button>
                        <button type="button" onclick="setFecha('mes')"    class="date-tab <?php echo $filtro_fecha=='mes'    ? 'active' : ''; ?>"><i class="fas fa-calendar me-1"></i>Este mes</button>
                        <button type="button" onclick="setFecha('rango')"  class="date-tab <?php echo $filtro_fecha=='rango'  ? 'active' : ''; ?>"><i class="fas fa-calendar-range me-1"></i>Rango</button>
                    </div>
                </div>
                <!-- Siempre preservar el período actual -->
                <input type="hidden" name="fecha" id="fechaHidden" value="<?php echo $filtro_fecha; ?>">

                <!-- Rango personalizado -->
                <?php if ($filtro_fecha == 'rango'): ?>
                <div>
                    <label class="field-label">Desde</label>
                    <div class="field-wrap">
                        <input type="date" name="desde" value="<?php echo $fecha_desde; ?>">
                        <i class="fas fa-calendar f-ico"></i>
                    </div>
                </div>
                <div>
                    <label class="field-label">Hasta</label>
                    <div class="field-wrap">
                        <input type="date" name="hasta" value="<?php echo $fecha_hasta; ?>">
                        <i class="fas fa-calendar f-ico"></i>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Método de pago -->
                <div>
                    <label class="field-label">Método de pago</label>
                    <div class="field-wrap">
                        <select name="metodo">
                            <option value="">Todos</option>
                            <option value="efectivo" <?php echo $filtro_metodo=='efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="debito"   <?php echo $filtro_metodo=='debito'   ? 'selected' : ''; ?>>Débito</option>
                            <option value="credito"  <?php echo $filtro_metodo=='credito'  ? 'selected' : ''; ?>>Crédito</option>
                            <option value="paypal"   <?php echo $filtro_metodo=='paypal'   ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                        <i class="fas fa-credit-card f-ico"></i>
                    </div>
                </div>

                <!-- Vendedor (solo admin) -->
                <?php if (in_array($user_rol, ['administrador','supervisor']) && $result_vendedores): ?>
                <div>
                    <label class="field-label">Vendedor</label>
                    <div class="field-wrap">
                        <select name="vendedor">
                            <option value="0">Todos</option>
                            <?php while ($v = mysqli_fetch_assoc($result_vendedores)): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $filtro_vendedor == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['nombre'] . ' ' . $v['apellido_paterno']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <i class="fas fa-user f-ico"></i>
                    </div>
                </div>
                <?php endif; ?>

                <div style="padding-top:22px;">
                    <button type="submit" class="btn-tv btn-save">
                        <i class="fas fa-magnifying-glass"></i> Aplicar
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- TABLA VENTAS -->
    <div class="section-head anim anim-3">
        <div class="s-label">Ventas registradas (<?php echo $total_ventas; ?>)</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-3" style="padding:24px 28px;">
        <div class="table-wrap">
            <table class="tv-table" id="tablaVentas">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha y Hora</th>
                        <th>Vendedor</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th style="text-align:center;">Detalle</th>
                        <?php if (in_array($user_rol, ['administrador','supervisor'])): ?>
                        <th style="text-align:center;">Eliminar</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total_ventas > 0):
                    mysqli_data_seek($result_ventas, 0);
                    while ($v = mysqli_fetch_assoc($result_ventas)):
                        // Obtener detalles de esta venta
                        $qdet = "SELECT dv.*, p.nombre as prod_nombre
                                 FROM detalles_venta dv
                                 JOIN productos p ON dv.id_producto = p.id
                                 WHERE dv.id_venta = {$v['id']}";
                        $rdet = mysqli_query($conn, $qdet);
                ?>
                <tr class="venta-row" onclick="toggleDetalle(<?php echo $v['id']; ?>)">
                    <td data-label="Folio">
                        <span class="folio-val"><?php echo htmlspecialchars($v['folio']); ?></span>
                    </td>
                    <td data-label="Fecha">
                        <div style="font-size:.85rem;"><?php echo date('d/m/Y', strtotime($v['fecha_venta'])); ?></div>
                        <div style="font-size:.72rem;color:rgba(255,255,255,.35);"><?php echo date('H:i', strtotime($v['fecha_venta'])); ?></div>
                    </td>
                    <td data-label="Vendedor">
                        <span style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user-circle" style="color:var(--accent);font-size:.85rem;"></i>
                            <?php echo htmlspecialchars($v['vendedor_nombre'] . ' ' . $v['vendedor_ap']); ?>
                        </span>
                    </td>
                    <td data-label="Método">
                        <?php if ($v['metodo_pago'] == 'efectivo'): ?>
                            <span class="metodo-badge m-efectivo"><i class="fas fa-money-bill-wave"></i> Efectivo</span>
                        <?php elseif ($v['metodo_pago'] == 'debito'): ?>
                            <span class="metodo-badge m-debito"><i class="fas fa-credit-card"></i> Débito</span>
                        <?php elseif ($v['metodo_pago'] == 'credito'): ?>
                            <span class="metodo-badge m-credito"><i class="fas fa-credit-card"></i> Crédito</span>
                        <?php else: ?>
                            <span class="metodo-badge m-paypal"><i class="fab fa-paypal"></i> PayPal</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Total">
                        <span class="price-val">$<?php echo number_format($v['total'], 2); ?></span>
                    </td>
                    <td data-label="Detalle" style="text-align:center;">
                        <i class="fas fa-chevron-down det-arrow" id="arrow-<?php echo $v['id']; ?>"></i>
                    </td>
                    <?php if ($user_rol == 'administrador'): ?>
                    <td data-label="Eliminar" style="text-align:center;">
                        <a href="?eliminar=<?php echo $v['id']; ?>&fecha=<?php echo $filtro_fecha; ?>"
                           onclick="return confirm('¿Eliminar la venta <?php echo $v['folio']; ?>? Esta acción no se puede deshacer.')"
                           style="display:inline-flex;align-items:center;justify-content:center;
                                  width:34px;height:34px;border-radius:10px;
                                  background:rgba(255,77,77,.12);border:1px solid rgba(255,77,77,.25);
                                  color:var(--danger);text-decoration:none;transition:all .25s;"
                           onmouseover="this.style.background='var(--danger)';this.style.color='white';"
                           onmouseout="this.style.background='rgba(255,77,77,.12)';this.style.color='var(--danger)';">
                            <i class="fas fa-trash-can"></i>
                        </a>
                    </td>
                    <?php endif; ?>
                </tr>
                <!-- Fila detalle -->
                <tr class="detalle-row" id="det-<?php echo $v['id']; ?>" style="display:none;">
                    <td colspan="<?php echo $user_rol == 'administrador' ? '7' : '6'; ?>">
                        <div class="detalle-inner">
                            <div class="detalle-title"><i class="fas fa-list me-2"></i>Productos de esta venta</div>
                            <table class="detalle-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio unitario</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (mysqli_num_rows($rdet) > 0):
                                    while ($det = mysqli_fetch_assoc($rdet)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($det['prod_nombre']); ?></td>
                                        <td>$<?php echo number_format($det['precio_unitario'], 2); ?></td>
                                        <td><?php echo $det['cantidad']; ?> pz</td>
                                        <td style="color:var(--success);font-weight:600;">$<?php echo number_format($det['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="4" style="text-align:center;color:rgba(255,255,255,.3);">Sin detalle disponible</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-clock-rotate-left"></i>
                            <h5>No hay ventas en este período</h5>
                            <p>Las ventas aparecerán aquí una vez que se registren desde el Punto de Venta.</p>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleDetalle(id) {
        const row   = document.getElementById('det-' + id);
        const arrow = document.getElementById('arrow-' + id);
        if (!row) return;
        const open = row.style.display === 'table-row';
        row.style.display = open ? 'none' : 'table-row';
        if (arrow) arrow.classList.toggle('open', !open);
    }
    /* ── Filtro fecha ── */
    function setFecha(valor) {
        document.getElementById('fechaHidden').value = valor;
        // Actualizar visual activo
        document.querySelectorAll('.date-tab').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        // Si es rango solo marcar, no hacer submit aún
        if (valor !== 'rango') {
            document.querySelector('form').submit();
        }
    }
</script>
</body>
</html>