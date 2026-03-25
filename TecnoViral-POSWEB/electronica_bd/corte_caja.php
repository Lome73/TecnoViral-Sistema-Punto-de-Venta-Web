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

$mensaje = '';
$error   = '';

// ── DETECTAR COLUMNAS REALES DE cortes_caja ──
$cols_result = mysqli_query($conn, "SHOW COLUMNS FROM cortes_caja");
$cols_exist  = [];
while ($col = mysqli_fetch_assoc($cols_result)) $cols_exist[] = $col['Field'];

// Mapeo flexible: si no existe la columna, usar alternativa o crearla
$col_user_ap  = in_array('id_usuario_apertura', $cols_exist) ? 'id_usuario_apertura' : (in_array('id_usuario', $cols_exist) ? 'id_usuario' : 'id_usuario_apertura');
$col_user_cl  = in_array('id_usuario_cierre',   $cols_exist) ? 'id_usuario_cierre'   : 'id_usuario_apertura';
$col_fondo    = in_array('fondo_inicial',        $cols_exist) ? 'fondo_inicial'        : (in_array('monto_inicial', $cols_exist) ? 'monto_inicial' : 'fondo_inicial');
$col_tv       = in_array('total_ventas',         $cols_exist) ? 'total_ventas'         : (in_array('total_ingresos', $cols_exist) ? 'total_ingresos' : 'total_ventas');
$col_tg       = in_array('total_gastos',         $cols_exist) ? 'total_gastos'         : 'total_gastos';
$col_ereal    = in_array('efectivo_real',        $cols_exist) ? 'efectivo_real'        : (in_array('monto_real', $cols_exist) ? 'monto_real' : 'efectivo_real');
$col_eesper   = in_array('efectivo_esperado',    $cols_exist) ? 'efectivo_esperado'    : (in_array('monto_esperado', $cols_exist) ? 'monto_esperado' : 'efectivo_esperado');
$col_dif      = in_array('diferencia',           $cols_exist) ? 'diferencia'           : 'diferencia';
$col_obs_ap   = in_array('observaciones_apertura',$cols_exist)? 'observaciones_apertura': (in_array('observaciones', $cols_exist) ? 'observaciones' : null);
$col_obs_cl   = in_array('observaciones_cierre', $cols_exist) ? 'observaciones_cierre'  : (in_array('observaciones', $cols_exist) ? 'observaciones' : null);
$col_fap      = in_array('fecha_apertura',       $cols_exist) ? 'fecha_apertura'       : (in_array('fecha_inicio', $cols_exist) ? 'fecha_inicio' : 'fecha_apertura');
$col_fcl      = in_array('fecha_cierre',         $cols_exist) ? 'fecha_cierre'         : (in_array('fecha_fin', $cols_exist) ? 'fecha_fin' : 'fecha_cierre');

// Si faltan columnas críticas, agregarlas automáticamente
$alter_queries = [];
if (!in_array('id_usuario_apertura', $cols_exist) && !in_array('id_usuario', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN id_usuario_apertura INT(11) NOT NULL DEFAULT 1";
if (!in_array('id_usuario_cierre', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN id_usuario_cierre INT(11) DEFAULT NULL";
if (!in_array('fondo_inicial', $cols_exist) && !in_array('monto_inicial', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN fondo_inicial DECIMAL(10,2) NOT NULL DEFAULT 0.00";
if (!in_array('total_ventas', $cols_exist) && !in_array('total_ingresos', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN total_ventas DECIMAL(10,2) DEFAULT 0.00";
if (!in_array('total_gastos', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN total_gastos DECIMAL(10,2) DEFAULT 0.00";
if (!in_array('efectivo_real', $cols_exist) && !in_array('monto_real', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN efectivo_real DECIMAL(10,2) DEFAULT 0.00";
if (!in_array('efectivo_esperado', $cols_exist) && !in_array('monto_esperado', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN efectivo_esperado DECIMAL(10,2) DEFAULT 0.00";
if (!in_array('diferencia', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN diferencia DECIMAL(10,2) DEFAULT 0.00";
if (!in_array('observaciones_apertura', $cols_exist) && !in_array('observaciones', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN observaciones_apertura TEXT DEFAULT NULL";
if (!in_array('observaciones_cierre', $cols_exist))
    $alter_queries[] = "ALTER TABLE cortes_caja ADD COLUMN observaciones_cierre TEXT DEFAULT NULL";
foreach ($alter_queries as $aq) {
    mysqli_query($conn, $aq);
}
// Re-leer columnas tras posibles ALTER
$cols_result = mysqli_query($conn, "SHOW COLUMNS FROM cortes_caja");
$cols_exist  = [];
while ($col = mysqli_fetch_assoc($cols_result)) $cols_exist[] = $col['Field'];
// Re-mapear tras ALTER
$col_user_ap  = in_array('id_usuario_apertura', $cols_exist) ? 'id_usuario_apertura' : 'id_usuario';
$col_user_cl  = in_array('id_usuario_cierre',   $cols_exist) ? 'id_usuario_cierre'   : $col_user_ap;
$col_fondo    = in_array('fondo_inicial',        $cols_exist) ? 'fondo_inicial'        : 'monto_inicial';
$col_tv       = in_array('total_ventas',         $cols_exist) ? 'total_ventas'         : 'total_ingresos';
$col_ereal    = in_array('efectivo_real',        $cols_exist) ? 'efectivo_real'        : 'monto_real';
$col_eesper   = in_array('efectivo_esperado',    $cols_exist) ? 'efectivo_esperado'    : 'monto_esperado';
$col_obs_ap   = in_array('observaciones_apertura',$cols_exist)? 'observaciones_apertura': 'observaciones';
$col_obs_cl   = in_array('observaciones_cierre', $cols_exist) ? 'observaciones_cierre'  : 'observaciones';
$col_fap      = in_array('fecha_apertura',       $cols_exist) ? 'fecha_apertura'       : 'fecha_inicio';
$col_fcl      = in_array('fecha_cierre',         $cols_exist) ? 'fecha_cierre'         : 'fecha_fin';

// ── VERIFICAR CAJA ABIERTA ──
$q_caja = mysqli_query($conn, "SELECT * FROM cortes_caja WHERE $col_fcl IS NULL ORDER BY $col_fap DESC LIMIT 1");
$caja   = mysqli_fetch_assoc($q_caja);
// Normalizar claves para el resto del código
if ($caja) {
    $caja['fecha_apertura']    = $caja[$col_fap]    ?? null;
    $caja['fecha_cierre']      = $caja[$col_fcl]    ?? null;
    $caja['fondo_inicial']     = $caja[$col_fondo]  ?? 0;
}

// ── ABRIR CAJA ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'abrir') {
    if ($caja) {
        $error = "Ya hay una caja abierta. Ciérrala antes de abrir una nueva.";
    } else {
        $fondo    = floatval($_POST['fondo_inicial']);
        $obs_open = mysqli_real_escape_string($conn, trim($_POST['observaciones_apertura'] ?? ''));
        $ins_cols = "$col_user_ap, $col_fondo";
        $ins_vals = "$user_id, $fondo";
        if ($col_obs_ap) { $ins_cols .= ", $col_obs_ap"; $ins_vals .= ", '$obs_open'"; }
        $q = "INSERT INTO cortes_caja ($ins_cols, $col_fap) VALUES ($ins_vals, NOW())";
        if (mysqli_query($conn, $q)) {
            $mensaje = "✅ Caja abierta correctamente con fondo de $" . number_format($fondo, 2);
            $q_caja = mysqli_query($conn, "SELECT * FROM cortes_caja WHERE $col_fcl IS NULL ORDER BY $col_fap DESC LIMIT 1");
            $caja   = mysqli_fetch_assoc($q_caja);
            if ($caja) {
                $caja['fecha_apertura'] = $caja[$col_fap]   ?? null;
                $caja['fondo_inicial']  = $caja[$col_fondo] ?? 0;
            }
        } else {
            $error = "Error al abrir caja: " . mysqli_error($conn);
        }
    }
}

// ── CERRAR CAJA ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'cerrar') {
    if (!$caja) {
        $error = "No hay ninguna caja abierta para cerrar.";
    } elseif (!in_array($user_rol, ['administrador','supervisor'])) {
        $error = "Solo un administrador puede realizar el corte de caja.";
    } else {
        $id_corte        = intval($caja['id']);
        $efectivo_real   = floatval($_POST['efectivo_real']);
        $obs_cierre      = mysqli_real_escape_string($conn, trim($_POST['observaciones_cierre'] ?? ''));
        $fecha_apertura  = $caja['fecha_apertura'];

        // Calcular totales del período
        $q_ventas = mysqli_query($conn,
            "SELECT metodo_pago, COUNT(*) as cantidad, COALESCE(SUM(total),0) as monto
             FROM ventas
             WHERE fecha_venta >= '$fecha_apertura' AND fecha_venta <= NOW() AND estado != 'cancelada'
             GROUP BY metodo_pago"
        );
        $total_ingresos = 0;
        $total_efectivo_ventas = 0;
        while ($r = mysqli_fetch_assoc($q_ventas)) {
            $total_ingresos += $r['monto'];
            if ($r['metodo_pago'] == 'efectivo') $total_efectivo_ventas += $r['monto'];
        }

        $q_gastos = mysqli_query($conn,
            "SELECT COALESCE(SUM(monto),0) as total FROM gastos_dia
             WHERE fecha_gasto >= '$fecha_apertura' AND fecha_gasto <= NOW()"
        );
        $total_gastos = mysqli_fetch_assoc($q_gastos)['total'];

        $fondo_inicial   = floatval($caja['fondo_inicial']);
        $efectivo_esperado = $fondo_inicial + $total_efectivo_ventas - $total_gastos;
        $diferencia      = $efectivo_real - $efectivo_esperado;
        $total_neto      = $total_ingresos - $total_gastos;

        $set_parts = [
            "$col_user_cl = $user_id",
            "$col_fcl = NOW()",
            "$col_tv = $total_ingresos",
            "total_gastos = $total_gastos",
            "$col_ereal = $efectivo_real",
            "$col_eesper = $efectivo_esperado",
            "diferencia = $diferencia",
        ];
        if ($col_obs_cl) $set_parts[] = "$col_obs_cl = '$obs_cierre'";
        $q_close = "UPDATE cortes_caja SET " . implode(", ", $set_parts) . " WHERE id = $id_corte";

        if (mysqli_query($conn, $q_close)) {
            $mensaje  = "corte_realizado";
            $id_corte_final = $id_corte;
            $caja = null;
            // Recargar corte para mostrar resumen
            $q_caja = mysqli_query($conn, "SELECT * FROM cortes_caja WHERE id = $id_corte_final");
            $corte_final = mysqli_fetch_assoc($q_caja);
        } else {
            $error = "Error al cerrar caja: " . mysqli_error($conn);
        }
    }
}

// ── DATOS DEL TURNO ACTUAL (si hay caja abierta) ──
$ventas_turno = [];
$gastos_turno = [];
$resumen_turno = ['ventas' => 0, 'ingresos' => 0, 'gastos' => 0, 'neto' => 0];
$ventas_por_metodo = [];

if ($caja) {
    $fa = $caja['fecha_apertura'] ?? $caja[$col_fap];

    // Ventas del turno por método
    $q_vm = mysqli_query($conn,
        "SELECT metodo_pago, COUNT(*) as cantidad, COALESCE(SUM(total),0) as monto
         FROM ventas
         WHERE fecha_venta >= '$fa' AND estado != 'cancelada'
         GROUP BY metodo_pago ORDER BY monto DESC"
    );
    while ($r = mysqli_fetch_assoc($q_vm)) {
        $ventas_por_metodo[] = $r;
        $resumen_turno['ingresos'] += $r['monto'];
        $resumen_turno['ventas']   += $r['cantidad'];
    }

    // Gastos del turno
    $q_gt = mysqli_query($conn,
        "SELECT COALESCE(SUM(monto),0) as total FROM gastos_dia WHERE fecha_gasto >= '$fa'"
    );
    $resumen_turno['gastos'] = floatval(mysqli_fetch_assoc($q_gt)['total']);
    $resumen_turno['neto']   = $resumen_turno['ingresos'] - $resumen_turno['gastos'];

    // Últimas ventas del turno
    $q_vt = mysqli_query($conn,
        "SELECT v.folio, v.total, v.metodo_pago, v.fecha_venta, u.nombre as vendedor
         FROM ventas v JOIN usuarios u ON v.id_usuario = u.id
         WHERE v.fecha_venta >= '$fa' AND v.estado != 'cancelada'
         ORDER BY v.fecha_venta DESC LIMIT 15"
    );
    while ($r = mysqli_fetch_assoc($q_vt)) $ventas_turno[] = $r;

    // Gastos del turno detalle
    $q_gd = mysqli_query($conn,
        "SELECT g.concepto, g.monto, g.tipo, g.fecha_gasto, u.nombre as usuario
         FROM gastos_dia g JOIN usuarios u ON g.id_usuario = u.id
         WHERE g.fecha_gasto >= '$fa'
         ORDER BY g.fecha_gasto DESC LIMIT 10"
    );
    while ($r = mysqli_fetch_assoc($q_gd)) $gastos_turno[] = $r;
}

// ── HISTORIAL DE CORTES ──
$q_historial = mysqli_query($conn,
    "SELECT c.*,
            ua.nombre as nombre_apertura, ua.apellido_paterno as ap_apertura,
            uc.nombre as nombre_cierre,   uc.apellido_paterno as ap_cierre
     FROM cortes_caja c
     LEFT JOIN usuarios ua ON c.$col_user_ap = ua.id
     LEFT JOIN usuarios uc ON c.$col_user_cl = uc.id
     WHERE c.$col_fcl IS NOT NULL
     ORDER BY c.$col_fcl DESC
     LIMIT 10"
);
$historial = [];
while ($r = mysqli_fetch_assoc($q_historial)) {
    // Normalizar columnas para la vista
    $r['fecha_apertura']     = $r[$col_fap]    ?? $r['fecha_apertura']    ?? null;
    $r['fecha_cierre']       = $r[$col_fcl]    ?? $r['fecha_cierre']      ?? null;
    $r['fondo_inicial']      = $r[$col_fondo]  ?? $r['fondo_inicial']     ?? 0;
    $r['total_ventas']       = $r[$col_tv]     ?? $r['total_ventas']      ?? 0;
    $r['total_gastos']       = $r['total_gastos'] ?? 0;
    $r['efectivo_real']      = $r[$col_ereal]  ?? $r['efectivo_real']     ?? 0;
    $r['efectivo_esperado']  = $r[$col_eesper] ?? $r['efectivo_esperado'] ?? 0;
    $r['diferencia']         = $r['diferencia'] ?? 0;
    $historial[] = $r;
}

// Total efectivo esperado para el turno actual
$efectivo_esperado_turno = 0;
$efectivo_ventas_turno   = 0;
if ($caja) {
    foreach ($ventas_por_metodo as $vm) {
        if ($vm['metodo_pago'] == 'efectivo') $efectivo_ventas_turno = $vm['monto'];
    }
    $efectivo_esperado_turno = floatval($caja['fondo_inicial'] ?? $caja[$col_fondo] ?? 0) + $efectivo_ventas_turno - $resumen_turno['gastos'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Corte de Caja</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:#07172e; --navy2:#0d2347; --blue:#0052cc; --accent:#00c2ff;
            --gold:#f5c518; --white:#ffffff; --muted:#7a8ba0;
            --border:rgba(255,255,255,.1); --danger:#ff4d4d; --success:#00d68f;
            --caja-color: #ff6b35;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--navy); color: var(--white); overflow-x: hidden; }

        .bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; background: radial-gradient(ellipse at 10% 15%, rgba(0,194,255,.09) 0%, transparent 50%), radial-gradient(ellipse at 88% 80%, rgba(255,107,53,.07) 0%, transparent 50%), radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%); }
        .bg-grid   { position: fixed; inset: 0; z-index: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px); background-size: 48px 48px; }

        .page-wrap { position: relative; z-index: 1; padding: 24px 28px 50px; max-width: 1500px; margin: 0 auto; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
        .anim   { animation: fadeUp .5s ease both; }
        .anim-1 { animation-delay:.05s; }
        .anim-2 { animation-delay:.12s; }
        .anim-3 { animation-delay:.20s; }
        .anim-4 { animation-delay:.28s; }

        /* ── TOPBAR ── */
        .topbar { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; background:rgba(13,35,71,.7); backdrop-filter:blur(16px); border:1px solid var(--border); border-radius:22px; padding:14px 22px; margin-bottom:28px; box-shadow:0 8px 32px rgba(0,0,0,.4); }
        .brand-row { display:flex; align-items:center; gap:14px; }
        .logo-img  { width:68px; height:68px; border-radius:18px; object-fit:cover; border:2px solid rgba(0,194,255,.3); box-shadow:0 0 0 4px rgba(0,194,255,.08),0 8px 20px rgba(0,0,0,.5); transition:transform .4s cubic-bezier(.34,1.56,.64,1); }
        .logo-img:hover { transform:scale(1.08) rotate(-2deg); }
        .brand-name { font-family:'Playfair Display',serif; font-size:1.35rem; font-weight:900; letter-spacing:3px; }
        .brand-name span { color:var(--accent); }
        .brand-sub { font-size:.68rem; letter-spacing:3px; text-transform:uppercase; color:rgba(255,255,255,.35); margin-top:3px; }
        .page-pill { display:flex; align-items:center; gap:10px; background:rgba(255,107,53,.08); border:1px solid rgba(255,107,53,.22); border-radius:40px; padding:8px 20px; }
        .page-pill i   { color:var(--caja-color); font-size:.9rem; }
        .page-pill span{ font-size:.78rem; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.75); }
        .user-chip { display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:50px; padding:6px 16px 6px 6px; }
        .user-avatar { width:36px; height:36px; background:linear-gradient(135deg,var(--blue),var(--accent)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.9rem; }
        .u-name { font-size:.82rem; font-weight:600; }
        .u-role { font-size:.62rem; color:var(--accent); text-transform:uppercase; letter-spacing:1px; }
        .btn-back { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.7); display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all .25s; }
        .btn-back:hover { background:var(--blue); color:white; border-color:var(--blue); transform:translateX(-3px); }

        /* ── ALERTAS ── */
        .tv-alert { display:flex; align-items:center; gap:12px; border-radius:16px; padding:14px 20px; font-size:.88rem; font-weight:500; margin-bottom:22px; animation:fadeUp .4s ease both; }
        .tv-alert.success { background:rgba(0,214,143,.1); border:1px solid rgba(0,214,143,.25); color:#00d68f; }
        .tv-alert.danger  { background:rgba(255,77,77,.1);  border:1px solid rgba(255,77,77,.25);  color:#ff8585; }

        /* ── ESTADO CAJA BANNER ── */
        .estado-banner {
            border-radius:22px; padding:24px 32px;
            display:flex; align-items:center; justify-content:space-between;
            gap:20px; flex-wrap:wrap; margin-bottom:24px;
            position:relative; overflow:hidden;
        }
        .estado-banner.abierta {
            background:linear-gradient(135deg,rgba(0,135,90,.15),rgba(0,214,143,.06));
            border:1px solid rgba(0,214,143,.25);
        }
        .estado-banner.cerrada {
            background:linear-gradient(135deg,rgba(255,107,53,.12),rgba(192,57,43,.06));
            border:1px solid rgba(255,107,53,.25);
        }
        .estado-banner::before {
            content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:4px 4px 0 0;
        }
        .estado-banner.abierta::before { background:linear-gradient(90deg,#00875a,var(--success)); }
        .estado-banner.cerrada::before { background:linear-gradient(90deg,#c0392b,var(--caja-color)); }

        .estado-ico { width:64px; height:64px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; flex-shrink:0; }
        .abierta .estado-ico { background:rgba(0,214,143,.15); color:var(--success); }
        .cerrada .estado-ico { background:rgba(255,107,53,.15); color:var(--caja-color); }

        .estado-title { font-family:'Playfair Display',serif; font-size:1.6rem; font-weight:700; }
        .abierta .estado-title { color:var(--success); }
        .cerrada .estado-title { color:var(--caja-color); }
        .estado-sub { font-size:.82rem; color:rgba(255,255,255,.45); margin-top:4px; }

        .estado-meta { display:flex; gap:24px; flex-wrap:wrap; }
        .meta-item { text-align:center; }
        .meta-val  { font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; color:var(--white); }
        .meta-lbl  { font-size:.65rem; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1.5px; margin-top:2px; }

        /* ── GRID STATS TURNO ── */
        .stats-turno { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
        .stat-t {
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
            border-radius:18px; padding:18px 20px;
            display:flex; align-items:center; gap:14px;
            position:relative; overflow:hidden;
        }
        .stat-t::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:4px 4px 0 0; }
        .st-blue::before   { background:linear-gradient(90deg,var(--blue),var(--accent)); }
        .st-green::before  { background:linear-gradient(90deg,#00875a,var(--success)); }
        .st-red::before    { background:linear-gradient(90deg,#c0392b,var(--danger)); }
        .st-orange::before { background:linear-gradient(90deg,#c0392b,var(--caja-color)); }
        .st-ico { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .st-blue   .st-ico { background:rgba(0,82,204,.2);   color:var(--accent); }
        .st-green  .st-ico { background:rgba(0,214,143,.15); color:var(--success); }
        .st-red    .st-ico { background:rgba(255,77,77,.15); color:var(--danger); }
        .st-orange .st-ico { background:rgba(255,107,53,.15);color:var(--caja-color); }
        .st-val { font-family:'Playfair Display',serif; font-size:1.4rem; font-weight:700; color:var(--white); line-height:1; }
        .st-lbl { font-size:.68rem; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1.5px; margin-top:4px; }

        /* ── GLASS CARD ── */
        .glass-card { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:24px; padding:26px 30px; backdrop-filter:blur(10px); box-shadow:0 8px 40px rgba(0,0,0,.3); margin-bottom:22px; }
        .card-title { font-family:'Playfair Display',serif; font-size:1rem; font-weight:700; display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid rgba(255,255,255,.06); }
        .card-title i { color:var(--caja-color); }

        /* ── FORMULARIO ── */
        .field-label { font-size:.67rem; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:rgba(255,255,255,.45); margin-bottom:8px; display:block; }
        .field-wrap  { position:relative; }
        .field-wrap .f-ico { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:.88rem; pointer-events:none; z-index:1; }
        .field-wrap input,
        .field-wrap textarea,
        .field-wrap select { width:100%; padding:13px 16px 13px 44px; border:1px solid rgba(255,255,255,.1); border-radius:14px; font-size:.92rem; font-family:'DM Sans',sans-serif; background:rgba(255,255,255,.05); color:var(--white); transition:border-color .25s,box-shadow .25s; outline:none; -webkit-appearance:none; }
        .field-wrap select option { background:var(--navy2); }
        .field-wrap input::placeholder, .field-wrap textarea::placeholder { color:rgba(255,255,255,.2); }
        .field-wrap textarea { min-height:70px; resize:vertical; padding-top:13px; }
        .field-wrap input:focus, .field-wrap textarea:focus { border-color:var(--caja-color); background:rgba(255,107,53,.04); box-shadow:0 0 0 4px rgba(255,107,53,.1); }

        .btn-tv { display:inline-flex; align-items:center; gap:9px; padding:13px 28px; border-radius:14px; border:none; font-family:'DM Sans',sans-serif; font-size:.85rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; cursor:pointer; text-decoration:none; transition:all .25s; }
        .btn-open  { background:linear-gradient(135deg,#00875a,var(--success)); color:white; box-shadow:0 6px 20px rgba(0,214,143,.25); }
        .btn-open:hover  { transform:translateY(-2px); box-shadow:0 10px 28px rgba(0,214,143,.35); color:white; }
        .btn-close { background:linear-gradient(135deg,#c0392b,var(--danger)); color:white; box-shadow:0 6px 20px rgba(255,77,77,.25); }
        .btn-close:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(255,77,77,.35); color:white; }
        .btn-cancel{ background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.65); }
        .btn-cancel:hover { background:rgba(255,255,255,.12); color:white; }

        /* ── SECTION HEAD ── */
        .section-head { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
        .section-head .s-line  { flex:1; height:1px; background:linear-gradient(to right,rgba(255,107,53,.3),transparent); }
        .section-head .s-label { font-size:.68rem; font-weight:700; letter-spacing:4px; text-transform:uppercase; color:var(--caja-color); }

        /* ── MÉTODOS PAGO RESUMEN ── */
        .metodo-resumen { display:flex; flex-direction:column; gap:10px; }
        .mr-row { display:flex; align-items:center; gap:12px; padding:10px 14px; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:14px; }
        .mr-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
        .mr-efectivo .mr-ico { background:rgba(0,214,143,.15); color:var(--success); }
        .mr-debito   .mr-ico { background:rgba(0,194,255,.15); color:var(--accent); }
        .mr-credito  .mr-ico { background:rgba(245,197,24,.15); color:var(--gold); }
        .mr-paypal   .mr-ico { background:rgba(0,112,243,.15);  color:#4db3ff; }
        .mr-nombre { flex:1; font-size:.85rem; font-weight:600; }
        .mr-cant   { font-size:.72rem; color:rgba(255,255,255,.35); }
        .mr-monto  { font-family:'Playfair Display',serif; font-size:.95rem; color:var(--success); }

        /* ── CAJA CIERRE PREVIEW ── */
        .cierre-preview {
            background:rgba(255,107,53,.05); border:1px solid rgba(255,107,53,.15);
            border-radius:18px; padding:20px 24px; margin-top:16px;
        }
        .cp-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:.88rem; border-bottom:1px solid rgba(255,255,255,.05); color:rgba(255,255,255,.65); }
        .cp-row:last-child { border-bottom:none; }
        .cp-row.grande { font-size:1rem; font-weight:700; color:var(--white); padding-top:12px; margin-top:4px; border-top:1px solid rgba(255,107,53,.2); border-bottom:none; }
        .cp-row .val-pos { font-family:'Playfair Display',serif; color:var(--success); }
        .cp-row .val-neg { font-family:'Playfair Display',serif; color:var(--danger); }
        .cp-row .val-neu { font-family:'Playfair Display',serif; color:var(--accent); }
        .diferencia-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:700; margin-top:8px; }
        .dif-ok  { background:rgba(0,214,143,.15); color:var(--success); border:1px solid rgba(0,214,143,.25); }
        .dif-mal { background:rgba(255,77,77,.15); color:var(--danger); border:1px solid rgba(255,77,77,.25); }

        /* ── TABLA ── */
        .table-wrap { overflow-x:auto; border-radius:14px; }
        table.tv-table { width:100%; border-collapse:separate; border-spacing:0; }
        .tv-table thead tr th { background:rgba(255,107,53,.08); color:rgba(255,255,255,.55); font-size:.64rem; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,.07); white-space:nowrap; }
        .tv-table thead tr th:first-child { border-radius:14px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius:0 14px 0 0; }
        .tv-table tbody tr { transition:background .2s; }
        .tv-table tbody tr:hover { background:rgba(255,107,53,.04); }
        .tv-table tbody td { padding:13px 16px; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; font-size:.85rem; color:rgba(255,255,255,.75); }

        .folio-val  { font-family:'Playfair Display',serif; color:var(--accent); font-weight:700; font-size:.9rem; }
        .price-val  { font-family:'Playfair Display',serif; color:var(--success); }
        .price-neg  { font-family:'Playfair Display',serif; color:var(--danger); }

        .metodo-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:20px; font-size:.72rem; font-weight:700; }
        .mb-efectivo { background:rgba(0,214,143,.12); color:var(--success); border:1px solid rgba(0,214,143,.25); }
        .mb-debito   { background:rgba(0,194,255,.12); color:var(--accent);  border:1px solid rgba(0,194,255,.25); }
        .mb-credito  { background:rgba(245,197,24,.12);color:var(--gold);   border:1px solid rgba(245,197,24,.25); }
        .mb-paypal   { background:rgba(0,112,243,.15); color:#4db3ff;       border:1px solid rgba(0,112,243,.3); }

        /* ── CORTE FINAL CARD ── */
        .corte-final {
            background:linear-gradient(135deg,rgba(0,135,90,.12),rgba(0,82,204,.08));
            border:1px solid rgba(0,214,143,.2);
            border-radius:24px; padding:36px 40px;
            text-align:center; animation:fadeUp .6s ease both;
        }
        .cf-ico { width:80px; height:80px; border-radius:22px; background:rgba(0,214,143,.15); border:2px solid rgba(0,214,143,.25); display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:2.2rem; color:var(--success); animation:pulsOk 2.5s ease infinite; }
        @keyframes pulsOk { 0%,100%{box-shadow:0 0 0 0 rgba(0,214,143,.2);}50%{box-shadow:0 0 0 14px rgba(0,214,143,0);} }
        .cf-title { font-family:'Playfair Display',serif; font-size:1.8rem; font-weight:700; color:var(--success); margin-bottom:6px; }
        .cf-sub   { font-size:.85rem; color:rgba(255,255,255,.45); margin-bottom:28px; }
        .cf-grid  { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; margin:0 auto; max-width:700px; }
        .cf-item  { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); border-radius:16px; padding:16px; }
        .cf-item-val { font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; }
        .cf-item-lbl { font-size:.68rem; color:rgba(255,255,255,.4); text-transform:uppercase; letter-spacing:1.5px; margin-top:4px; }
        .cf-dif-ok  { color:var(--success); }
        .cf-dif-mal { color:var(--danger); }

        /* ── HISTORIAL CORTES ── */
        .hist-item { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:16px 20px; margin-bottom:10px; display:flex; align-items:center; gap:16px; flex-wrap:wrap; cursor:pointer; transition:background .2s; }
        .hist-item:hover { background:rgba(255,107,53,.05); border-color:rgba(255,107,53,.15); }
        .hist-fecha { font-size:.78rem; color:rgba(255,255,255,.4); min-width:90px; }
        .hist-rango { font-size:.82rem; color:rgba(255,255,255,.6); flex:1; }
        .hist-vals  { display:flex; gap:16px; flex-wrap:wrap; }
        .hv-item    { text-align:right; }
        .hv-val     { font-family:'Playfair Display',serif; font-size:.95rem; font-weight:700; }
        .hv-lbl     { font-size:.65rem; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:1px; }
        .hist-dif   { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:700; }

        /* Empty */
        .empty-state { text-align:center; padding:40px 20px; color:rgba(255,255,255,.3); }
        .empty-state i { font-size:2.5rem; margin-bottom:12px; display:block; opacity:.3; }
        .empty-state p { font-size:.83rem; }

        /* Footer */
        .page-footer { text-align:center; margin-top:36px; font-size:.66rem; letter-spacing:3px; text-transform:uppercase; color:rgba(255,255,255,.15); }
        .page-footer span { color:var(--caja-color); opacity:.5; }

        /* Responsive */
        @media(max-width:992px) { .stats-turno { grid-template-columns:1fr 1fr; } }
        @media(max-width:768px) {
            .page-wrap   { padding:14px 12px 36px; }
            .glass-card  { padding:18px 16px; }
            .page-pill   { display:none; }
            .stats-turno { grid-template-columns:1fr 1fr; }
            .estado-banner { padding:18px 20px; }
        }
        @media(max-width:480px) { .stats-turno { grid-template-columns:1fr; } }
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
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Corte de Caja</span>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
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

    <!-- ── ALERTAS ── -->
    <?php if ($mensaje && $mensaje != 'corte_realizado'): ?>
    <div class="tv-alert success anim"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="tv-alert danger anim"><i class="fas fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- ── CORTE REALIZADO ── -->
    <?php if ($mensaje == 'corte_realizado' && isset($corte_final)): ?>
    <div class="corte-final anim">
        <div class="cf-ico"><i class="fas fa-check-circle"></i></div>
        <div class="cf-title">¡Corte Realizado!</div>
        <div class="cf-sub">
            El turno ha sido cerrado correctamente el <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($corte_final['fecha_cierre'])); ?>
        </div>
        <div class="cf-grid">
            <div class="cf-item">
                <div class="cf-item-val" style="color:var(--success);">$<?php echo number_format($corte_final['total_ventas'], 2); ?></div>
                <div class="cf-item-lbl">Ingresos del turno</div>
            </div>
            <div class="cf-item">
                <div class="cf-item-val" style="color:var(--danger);">$<?php echo number_format($corte_final['total_gastos'], 2); ?></div>
                <div class="cf-item-lbl">Gastos del turno</div>
            </div>
            <div class="cf-item">
                <div class="cf-item-val" style="color:var(--accent);">$<?php echo number_format($corte_final['efectivo_esperado'], 2); ?></div>
                <div class="cf-item-lbl">Efectivo esperado</div>
            </div>
            <div class="cf-item">
                <div class="cf-item-val" style="color:var(--white);">$<?php echo number_format($corte_final['efectivo_real'], 2); ?></div>
                <div class="cf-item-lbl">Efectivo contado</div>
            </div>
            <div class="cf-item">
                <?php $dif = floatval($corte_final['diferencia']); ?>
                <div class="cf-item-val <?php echo $dif >= 0 ? 'cf-dif-ok' : 'cf-dif-mal'; ?>">
                    <?php echo $dif >= 0 ? '+' : ''; ?>$<?php echo number_format($dif, 2); ?>
                </div>
                <div class="cf-item-lbl">Diferencia</div>
            </div>
        </div>
        <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <button onclick="window.print()" class="btn-tv btn-open" style="background:linear-gradient(135deg,var(--blue),var(--accent));">
                <i class="fas fa-print"></i> Imprimir Resumen
            </button>
            <a href="corte_caja.php" class="btn-tv btn-cancel">
                <i class="fas fa-rotate-right"></i> Nueva Caja
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── ESTADO BANNER ── -->
    <div class="estado-banner <?php echo $caja ? 'abierta' : 'cerrada'; ?> anim anim-1">
        <div style="display:flex;align-items:center;gap:18px;">
            <div class="estado-ico">
                <i class="fas fa-<?php echo $caja ? 'lock-open' : 'lock'; ?>"></i>
            </div>
            <div>
                <div class="estado-title"><?php echo $caja ? 'Caja Abierta' : 'Caja Cerrada'; ?></div>
                <div class="estado-sub">
                    <?php if ($caja): ?>
                        Turno iniciado el <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($caja['fecha_apertura'])); ?>
                    <?php else: ?>
                        No hay ningún turno activo · Abre la caja para comenzar a operar
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($caja): ?>
        <div class="estado-meta">
            <div class="meta-item">
                <div class="meta-val">$<?php echo number_format($caja['fondo_inicial'], 2); ?></div>
                <div class="meta-lbl">Fondo inicial</div>
            </div>
            <div class="meta-item">
                <div class="meta-val" style="color:var(--success);"><?php echo $resumen_turno['ventas']; ?></div>
                <div class="meta-lbl">Ventas del turno</div>
            </div>
            <div class="meta-item">
                <div class="meta-val" style="color:var(--success);">$<?php echo number_format($resumen_turno['ingresos'], 2); ?></div>
                <div class="meta-lbl">Ingresos</div>
            </div>
            <div class="meta-item">
                <div class="meta-val" style="color:var(--accent);">$<?php echo number_format($efectivo_esperado_turno, 2); ?></div>
                <div class="meta-lbl">Efectivo en caja</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── STATS DEL TURNO ── -->
    <?php if ($caja): ?>
    <div class="stats-turno anim anim-2">
        <div class="stat-t st-blue">
            <div class="st-ico"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="st-val"><?php echo $resumen_turno['ventas']; ?></div>
                <div class="st-lbl">Ventas del turno</div>
            </div>
        </div>
        <div class="stat-t st-green">
            <div class="st-ico"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="st-val">$<?php echo number_format($resumen_turno['ingresos'], 0); ?></div>
                <div class="st-lbl">Ingresos totales</div>
            </div>
        </div>
        <div class="stat-t st-red">
            <div class="st-ico"><i class="fas fa-arrow-trend-down"></i></div>
            <div>
                <div class="st-val">$<?php echo number_format($resumen_turno['gastos'], 0); ?></div>
                <div class="st-lbl">Gastos del turno</div>
            </div>
        </div>
        <div class="stat-t <?php echo $resumen_turno['neto'] >= 0 ? 'st-green' : 'st-red'; ?>">
            <div class="st-ico"><i class="fas fa-scale-balanced"></i></div>
            <div>
                <div class="st-val">$<?php echo number_format(abs($resumen_turno['neto']), 0); ?></div>
                <div class="st-lbl"><?php echo $resumen_turno['neto'] >= 0 ? 'Utilidad neta' : 'Pérdida neta'; ?></div>
            </div>
        </div>
    </div>

    <!-- ── FILA: MÉTODOS + VENTAS DEL TURNO ── -->
    <div class="row g-4 anim anim-3">
        <div class="col-lg-4">
            <div class="glass-card" style="height:100%;">
                <div class="card-title"><i class="fas fa-credit-card"></i> Ingresos por método</div>
                <?php if (empty($ventas_por_metodo)): ?>
                <div class="empty-state"><i class="fas fa-credit-card"></i><p>Sin ventas en este turno</p></div>
                <?php else: ?>
                <?php
                $metodo_info = [
                    'efectivo' => ['ico'=>'fa-money-bill-wave','cls'=>'mr-efectivo','nom'=>'Efectivo'],
                    'debito'   => ['ico'=>'fa-credit-card',    'cls'=>'mr-debito',  'nom'=>'Débito'],
                    'credito'  => ['ico'=>'fa-credit-card',    'cls'=>'mr-credito', 'nom'=>'Crédito'],
                    'paypal'   => ['ico'=>'fab fa-paypal',     'cls'=>'mr-paypal',  'nom'=>'PayPal'],
                ];
                ?>
                <div class="metodo-resumen">
                    <?php foreach ($ventas_por_metodo as $vm):
                        $mi = $metodo_info[$vm['metodo_pago']] ?? ['ico'=>'fa-money-bill','cls'=>'mr-efectivo','nom'=>ucfirst($vm['metodo_pago'])];
                    ?>
                    <div class="mr-row <?php echo $mi['cls']; ?>">
                        <div class="mr-ico"><i class="<?php echo strpos($mi['ico'],'fab')===false?'fas ':''; ?><?php echo $mi['ico']; ?>"></i></div>
                        <div style="flex:1;">
                            <div class="mr-nombre"><?php echo $mi['nom']; ?></div>
                            <div class="mr-cant"><?php echo $vm['cantidad']; ?> venta(s)</div>
                        </div>
                        <div class="mr-monto">$<?php echo number_format($vm['monto'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Preview del efectivo en caja -->
                <div class="cierre-preview" style="margin-top:16px;">
                    <div class="cp-row">
                        <span><i class="fas fa-wallet me-2" style="color:var(--caja-color);"></i>Fondo inicial</span>
                        <span class="val-neu">$<?php echo number_format($caja['fondo_inicial'], 2); ?></span>
                    </div>
                    <div class="cp-row">
                        <span><i class="fas fa-plus me-2" style="color:var(--success);"></i>Ventas efectivo</span>
                        <span class="val-pos">+$<?php echo number_format($efectivo_ventas_turno, 2); ?></span>
                    </div>
                    <div class="cp-row">
                        <span><i class="fas fa-minus me-2" style="color:var(--danger);"></i>Gastos</span>
                        <span class="val-neg">-$<?php echo number_format($resumen_turno['gastos'], 2); ?></span>
                    </div>
                    <div class="cp-row grande">
                        <span>Efectivo esperado en caja</span>
                        <span class="val-neu">$<?php echo number_format($efectivo_esperado_turno, 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card">
                <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Ventas del turno</div>
                <?php if (empty($ventas_turno)): ?>
                <div class="empty-state"><i class="fas fa-receipt"></i><p>No hay ventas registradas en este turno</p></div>
                <?php else: ?>
                <div class="table-wrap">
                    <table class="tv-table">
                        <thead><tr>
                            <th>Folio</th><th>Hora</th><th>Vendedor</th><th>Método</th><th>Total</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($ventas_turno as $vt):
                            $mb_cls = ['efectivo'=>'mb-efectivo','debito'=>'mb-debito','credito'=>'mb-credito','paypal'=>'mb-paypal'];
                            $mb_nom = ['efectivo'=>'Efectivo','debito'=>'Débito','credito'=>'Crédito','paypal'=>'PayPal'];
                            $mb_ico = ['efectivo'=>'fa-money-bill-wave','debito'=>'fa-credit-card','credito'=>'fa-credit-card','paypal'=>'fa-paypal'];
                            $cls = $mb_cls[$vt['metodo_pago']] ?? 'mb-efectivo';
                            $nom = $mb_nom[$vt['metodo_pago']] ?? ucfirst($vt['metodo_pago']);
                            $ico = $mb_ico[$vt['metodo_pago']] ?? 'fa-money-bill';
                        ?>
                        <tr>
                            <td><span class="folio-val"><?php echo htmlspecialchars($vt['folio']); ?></span></td>
                            <td style="font-size:.8rem;color:rgba(255,255,255,.45);"><?php echo date('H:i', strtotime($vt['fecha_venta'])); ?></td>
                            <td>
                                <span style="display:flex;align-items:center;gap:6px;">
                                    <i class="fas fa-user-circle" style="color:var(--accent);font-size:.85rem;"></i>
                                    <?php echo htmlspecialchars($vt['vendedor']); ?>
                                </span>
                            </td>
                            <td><span class="metodo-badge <?php echo $cls; ?>"><i class="fas <?php echo $ico; ?>"></i> <?php echo $nom; ?></span></td>
                            <td><span class="price-val">$<?php echo number_format($vt['total'], 2); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($gastos_turno)): ?>
                <div class="mt-4">
                    <div style="font-size:.7rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:10px;">
                        <i class="fas fa-wallet me-1" style="color:var(--danger);"></i> Gastos del turno
                    </div>
                    <div class="table-wrap">
                        <table class="tv-table">
                            <thead><tr><th>Concepto</th><th>Tipo</th><th>Hora</th><th>Monto</th></tr></thead>
                            <tbody>
                            <?php foreach ($gastos_turno as $gt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gt['concepto']); ?></td>
                                <td><span style="font-size:.72rem;color:rgba(255,255,255,.4);"><?php echo ucfirst($gt['tipo']); ?></span></td>
                                <td style="font-size:.78rem;color:rgba(255,255,255,.4);"><?php echo date('H:i', strtotime($gt['fecha_gasto'])); ?></td>
                                <td><span class="price-neg">-$<?php echo number_format($gt['monto'], 2); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; // fin if ($caja) — stats y tablas del turno ?>

    <!-- ── FORMULARIO ABRIR / CERRAR ── -->
    <?php if (!$caja): ?>
    <!-- ABRIR CAJA -->
    <div class="section-head anim anim-3" style="margin-top:28px;">
        <div class="s-label">Apertura de caja</div>
        <div class="s-line"></div>
    </div>
    <div class="glass-card anim anim-3">
        <div class="card-title"><i class="fas fa-lock-open"></i> Abrir nuevo turno</div>
        <form method="POST" action="">
            <input type="hidden" name="accion" value="abrir">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="field-label">Fondo inicial de caja</label>
                    <div class="field-wrap">
                        <i class="fas fa-dollar-sign f-ico"></i>
                        <input type="number" name="fondo_inicial" step="0.01" min="0" required placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="field-label">Observaciones de apertura (opcional)</label>
                    <div class="field-wrap">
                        <i class="fas fa-comment f-ico"></i>
                        <input type="text" name="observaciones_apertura" placeholder="Notas del inicio de turno...">
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn-tv btn-open">
                    <i class="fas fa-lock-open"></i> Abrir Caja
                </button>
            </div>
        </form>
    </div>

    <?php elseif (in_array($user_rol, ['administrador','supervisor'])): ?>
    <!-- CERRAR CAJA (solo admin) -->
    <div class="section-head anim anim-3" style="margin-top:28px;">
        <div class="s-label">Realizar corte de caja</div>
        <div class="s-line"></div>
    </div>
    <div class="glass-card anim anim-3">
        <div class="card-title"><i class="fas fa-file-invoice-dollar"></i> Cerrar turno y hacer corte</div>
        <form method="POST" action="" id="formCierre">
            <input type="hidden" name="accion" value="cerrar">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="field-label">Efectivo contado en caja</label>
                    <div class="field-wrap">
                        <i class="fas fa-coins f-ico"></i>
                        <input type="number" name="efectivo_real" id="efectivoReal" step="0.01" min="0" required placeholder="0.00" oninput="calcularDiferencia()">
                    </div>
                </div>
                <div class="col-md-8">
                    <label class="field-label">Observaciones del cierre (opcional)</label>
                    <div class="field-wrap">
                        <i class="fas fa-comment f-ico"></i>
                        <input type="text" name="observaciones_cierre" placeholder="Notas del cierre de turno...">
                    </div>
                </div>
            </div>

            <!-- Preview diferencia en tiempo real -->
            <div class="cierre-preview" id="cierrePreview">
                <div class="cp-row">
                    <span><i class="fas fa-coins me-2" style="color:var(--caja-color);"></i>Efectivo esperado</span>
                    <span class="val-neu">$<?php echo number_format($efectivo_esperado_turno, 2); ?></span>
                </div>
                <div class="cp-row">
                    <span><i class="fas fa-hand-holding-dollar me-2" style="color:var(--accent);"></i>Efectivo contado</span>
                    <span id="efectivoMostrado" class="val-neu">$0.00</span>
                </div>
                <div class="cp-row grande">
                    <span>Diferencia</span>
                    <span id="diferenciaMostrada" class="val-neu">—</span>
                </div>
                <div id="difBadge"></div>
            </div>

            <div class="mt-3 d-flex gap-3 flex-wrap">
                <button type="submit" class="btn-tv btn-close" id="btnCerrar"
                    onclick="return confirm('¿Estás seguro de cerrar la caja y realizar el corte?\n\nEsta acción registrará el corte del turno actual.')">
                    <i class="fas fa-file-invoice-dollar"></i> Realizar Corte de Caja
                </button>
                <a href="menu_principal.php" class="btn-tv btn-cancel">
                    <i class="fas fa-xmark"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <!-- Vendedor: ver estado pero sin poder cerrar -->
    <div class="glass-card anim anim-3" style="margin-top:28px;text-align:center;padding:32px;">
        <div style="width:60px;height:60px;background:rgba(245,197,24,.12);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;color:var(--gold);">
            <i class="fas fa-crown"></i>
        </div>
        <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;margin-bottom:8px;">Solo administradores y supervisores pueden realizar el corte</div>
        <div style="font-size:.85rem;color:rgba(255,255,255,.4);">Puedes ver el estado del turno, pero el cierre requiere permisos de administrador o supervisor.</div>
    </div>

    <?php endif; // fin if caja ?>

    <!-- ── HISTORIAL DE CORTES ── -->
    <?php if (!empty($historial)): ?>
    <div class="section-head anim anim-4" style="margin-top:28px;">
        <div class="s-label">Historial de cortes</div>
        <div class="s-line"></div>
    </div>
    <div class="glass-card anim anim-4">
        <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Últimos 10 cortes realizados</div>
        <?php foreach ($historial as $h):
            $dif_h = floatval($h['diferencia']);
            $neto_h = floatval($h['total_ventas']) - floatval($h['total_gastos']);
        ?>
        <div class="hist-item">
            <div>
                <div class="hist-fecha"><?php echo date('d/m/Y', strtotime($h['fecha_cierre'])); ?></div>
                <div style="font-size:.7rem;color:rgba(255,255,255,.3);"><?php echo date('H:i', strtotime($h['fecha_apertura'])); ?> – <?php echo date('H:i', strtotime($h['fecha_cierre'])); ?></div>
            </div>
            <div class="hist-rango">
                <div style="font-size:.8rem;color:rgba(255,255,255,.5);">
                    <i class="fas fa-user-circle me-1" style="color:var(--caja-color);"></i>
                    Cerrado por <?php echo htmlspecialchars($h['nombre_cierre'] . ' ' . $h['ap_cierre']); ?>
                </div>
            </div>
            <div class="hist-vals">
                <div class="hv-item">
                    <div class="hv-val" style="color:var(--success);">$<?php echo number_format($h['total_ventas'], 0); ?></div>
                    <div class="hv-lbl">Ingresos</div>
                </div>
                <div class="hv-item">
                    <div class="hv-val" style="color:var(--danger);">$<?php echo number_format($h['total_gastos'], 0); ?></div>
                    <div class="hv-lbl">Gastos</div>
                </div>
                <div class="hv-item">
                    <div class="hv-val" style="color:<?php echo $neto_h>=0?'var(--success)':'var(--danger)';?>;">$<?php echo number_format(abs($neto_h), 0); ?></div>
                    <div class="hv-lbl">Neto</div>
                </div>
                <div class="hv-item">
                    <span class="hist-dif <?php echo $dif_h == 0 ? 'dif-ok' : ($dif_h > 0 ? 'dif-ok' : 'dif-mal'); ?>"
                          style="<?php echo $dif_h==0?'background:rgba(0,214,143,.12);color:var(--success);border:1px solid rgba(0,214,143,.25);':($dif_h>0?'background:rgba(0,194,255,.12);color:var(--accent);border:1px solid rgba(0,194,255,.25);':'background:rgba(255,77,77,.12);color:var(--danger);border:1px solid rgba(255,77,77,.25);'); ?>">
                        <?php echo $dif_h > 0 ? '+' : ''; ?>$<?php echo number_format($dif_h, 2); ?>
                    </span>
                    <div class="hv-lbl" style="text-align:center;">Diferencia</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="page-footer">
        <span>◆</span> &nbsp;TecnoViral POS v1.0 &nbsp;<span>◆</span>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const efectivoEsperado = <?php echo $efectivo_esperado_turno; ?>;

    function calcularDiferencia() {
        const real     = parseFloat(document.getElementById('efectivoReal')?.value) || 0;
        const dif      = real - efectivoEsperado;
        const elMost   = document.getElementById('efectivoMostrado');
        const elDif    = document.getElementById('diferenciaMostrada');
        const elBadge  = document.getElementById('difBadge');
        if (!elMost) return;

        elMost.textContent = '$' + real.toFixed(2);
        elMost.className   = real >= 0 ? 'val-neu' : 'val-neg';

        if (real === 0) {
            elDif.textContent = '—';
            elDif.className   = 'val-neu';
            elBadge.innerHTML = '';
            return;
        }

        elDif.textContent = (dif >= 0 ? '+' : '') + '$' + dif.toFixed(2);
        elDif.className   = dif === 0 ? 'val-neu' : (dif > 0 ? 'val-pos' : 'val-neg');

        if (dif === 0) {
            elBadge.innerHTML = '<span class="diferencia-badge dif-ok"><i class="fas fa-check-circle me-1"></i>Cuadra exacto</span>';
        } else if (dif > 0) {
            elBadge.innerHTML = `<span class="diferencia-badge dif-ok"><i class="fas fa-circle-up me-1"></i>Sobrante de $${dif.toFixed(2)}</span>`;
        } else {
            elBadge.innerHTML = `<span class="diferencia-badge dif-mal"><i class="fas fa-circle-down me-1"></i>Faltante de $${Math.abs(dif).toFixed(2)}</span>`;
        }
    }
</script>
</body>
</html>