<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_nombre = $_SESSION['user_nombre'];
$user_rol    = $_SESSION['user_rol'];

$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$query_alertas = "SELECT p.*, c.nombre as categoria_nombre
                  FROM productos p
                  LEFT JOIN categorias c ON p.id_categoria = c.id
                  WHERE p.activo = 1 AND p.stock <= p.stock_minimo
                  ORDER BY p.stock ASC";
$result_alertas = mysqli_query($conn, $query_alertas);
$total_alertas  = mysqli_num_rows($result_alertas);

if ($categoria_filtro > 0) {
    $query_productos = "SELECT p.*, c.nombre as categoria_nombre
                        FROM productos p
                        LEFT JOIN categorias c ON p.id_categoria = c.id
                        WHERE p.activo = 1 AND p.id_categoria = $categoria_filtro
                        ORDER BY p.stock ASC";
} else {
    $query_productos = "SELECT p.*, c.nombre as categoria_nombre
                        FROM productos p
                        LEFT JOIN categorias c ON p.id_categoria = c.id
                        WHERE p.activo = 1
                        ORDER BY CASE WHEN p.stock <= p.stock_minimo THEN 0 ELSE 1 END, p.stock ASC";
}
$result_productos = mysqli_query($conn, $query_productos);

$query_categorias  = "SELECT * FROM categorias ORDER BY nombre";
$result_categorias = mysqli_query($conn, $query_categorias);

// ── PAGINACIÓN MOVIMIENTOS ──
$pag         = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
$por_pagina  = 20;
$offset      = ($pag - 1) * $por_pagina;
$busq_mov    = isset($_GET['busq_mov']) ? mysqli_real_escape_string($conn, trim($_GET['busq_mov'])) : '';

$where_mov = $busq_mov ? "WHERE (p.nombre LIKE '%$busq_mov%' OR m.motivo LIKE '%$busq_mov%' OR u.nombre LIKE '%$busq_mov%')" : "";

$total_movimientos = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM movimientos_inventario m
     JOIN productos p ON m.id_producto = p.id
     JOIN usuarios u ON m.id_usuario = u.id
     $where_mov"
))['t'];

$total_paginas = ceil($total_movimientos / $por_pagina);

$query_movimientos = "SELECT m.*, p.nombre as producto_nombre, u.nombre as usuario_nombre
                      FROM movimientos_inventario m
                      JOIN productos p ON m.id_producto = p.id
                      JOIN usuarios u ON m.id_usuario = u.id
                      $where_mov
                      ORDER BY m.fecha_movimiento DESC
                      LIMIT $por_pagina OFFSET $offset";
$result_movimientos = mysqli_query($conn, $query_movimientos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Inventario</title>

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
            max-width: 1600px;
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
        .anim-4 { animation-delay: .28s; }

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
        .brand-name { font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 900; letter-spacing: 3px; }
        .brand-name span { color: var(--accent); }
        .brand-sub { font-size: .68rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.35); margin-top: 3px; }

        .page-pill {
            display: flex; align-items: center; gap: 10px;
            background: rgba(26,188,156,.08); border: 1px solid rgba(26,188,156,.2);
            border-radius: 40px; padding: 8px 20px;
        }
        .page-pill i   { color: #1abc9c; font-size: .9rem; }
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
        .u-role { font-size: .62rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; }

        .btn-back {
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.7);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all .25s;
        }
        .btn-back:hover { background: var(--blue); color: white; border-color: var(--blue); transform: translateX(-3px); }

        /* ── ALERTA STOCK BAJO ── */
        .alerta-card {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
            background: linear-gradient(135deg, rgba(255,77,77,.15), rgba(192,57,43,.1));
            border: 1px solid rgba(255,77,77,.3);
            border-radius: 20px; padding: 20px 28px;
            margin-bottom: 24px;
            animation: fadeUp .4s ease both, pulseAlert 2.5s ease infinite;
        }
        @keyframes pulseAlert {
            0%,100% { box-shadow: 0 0 0 0 rgba(255,77,77,.2); }
            50%      { box-shadow: 0 0 0 8px rgba(255,77,77,0); }
        }
        .alerta-left { display: flex; align-items: center; gap: 16px; }
        .alerta-ico  { font-size: 2rem; color: var(--danger); }
        .alerta-title{ font-family: 'Playfair Display', serif; font-size: 1.2rem; font-weight: 700; color: var(--danger); }
        .alerta-sub  { font-size: .82rem; color: rgba(255,255,255,.5); margin-top: 2px; }
        .btn-ver-alertas {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,77,77,.2); border: 1px solid rgba(255,77,77,.4);
            color: #ff8585; border-radius: 12px; padding: 10px 22px;
            font-size: .82rem; font-weight: 600; text-decoration: none;
            transition: all .25s;
        }
        .btn-ver-alertas:hover { background: var(--danger); color: white; }

        /* ── FILTRO ── */
        .filtro-card {
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 18px; padding: 16px 22px;
            margin-bottom: 24px;
        }
        .filtro-card select {
            padding: 12px 18px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px; font-size: .9rem;
            font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.06); color: var(--white);
            outline: none; min-width: 240px;
            transition: border-color .25s;
            -webkit-appearance: none;
        }
        .filtro-card select option { background: var(--navy2); }
        .filtro-card select:focus { border-color: var(--accent); }
        .btn-filtrar {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--blue), var(--accent));
            color: white; border: none; border-radius: 14px;
            padding: 12px 26px; font-family: 'DM Sans', sans-serif;
            font-size: .85rem; font-weight: 700; letter-spacing: 1px;
            cursor: pointer; transition: all .25s;
            box-shadow: 0 6px 20px rgba(0,82,204,.3);
        }
        .btn-filtrar:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,82,204,.4); }

        /* ── SECTION HEAD ── */
        .section-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-head .s-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(26,188,156,.3), transparent); }
        .section-head .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: #1abc9c; }

        /* ── GRID INVENTARIO ── */
        .inventario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .prod-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 22px; padding: 22px;
            border-left: 5px solid var(--success);
            transition: transform .3s, box-shadow .3s;
            animation: fadeUp .5s ease both;
            position: relative; overflow: hidden;
        }
        .prod-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.35); }
        .prod-card.stock-bajo  { border-left-color: var(--danger); animation: fadeUp .5s ease both, parpadeo 2s ease infinite; }
        .prod-card.stock-medio { border-left-color: var(--gold); }

        @keyframes parpadeo {
            0%,100% { background: rgba(255,77,77,.04); }
            50%      { background: rgba(255,77,77,.09); }
        }

        .prod-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .prod-card-nombre { font-weight: 700; font-size: .95rem; color: var(--white); line-height: 1.3; }
        .prod-card-marca  { font-size: .75rem; color: rgba(255,255,255,.4); margin-top: 3px; }
        .cat-pill {
            display: inline-block;
            background: rgba(0,82,204,.2); border: 1px solid rgba(0,82,204,.3);
            color: var(--accent); padding: 4px 12px; border-radius: 20px;
            font-size: .7rem; font-weight: 600; white-space: nowrap;
        }

        /* Círculo de stock */
        .stock-center { text-align: center; margin: 16px 0; }
        .stock-circle {
            width: 90px; height: 90px; border-radius: 50%;
            background: linear-gradient(135deg, #00875a, var(--success));
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            margin: 0 auto; color: white;
            box-shadow: 0 8px 20px rgba(0,214,143,.25);
        }
        .stock-bajo  .stock-circle { background: linear-gradient(135deg, #c0392b, var(--danger)); box-shadow: 0 8px 20px rgba(255,77,77,.25); }
        .stock-medio .stock-circle { background: linear-gradient(135deg, #e07b00, var(--gold));   box-shadow: 0 8px 20px rgba(245,197,24,.2); }
        .stock-num  { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .stock-txt  { font-size: .65rem; opacity: .85; letter-spacing: 1px; }

        .stock-minmax {
            display: flex; justify-content: space-between;
            margin-top: 10px; font-size: .75rem; color: rgba(255,255,255,.4);
        }
        .stock-minmax span { display: flex; align-items: center; gap: 4px; }
        .stock-minmax .fa-arrow-down { color: var(--danger); }
        .stock-minmax .fa-arrow-up   { color: var(--success); }

        .prod-card-footer {
            display: flex; justify-content: space-between;
            margin-top: 14px; padding-top: 14px;
            border-top: 1px dashed rgba(255,255,255,.07);
            font-size: .78rem; color: rgba(255,255,255,.4);
        }
        .prod-card-footer span { display: flex; align-items: center; gap: 5px; }
        .prod-card-footer i { color: var(--accent); }

        /* ── TABLA MOVIMIENTOS ── */
        .glass-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 24px; padding: 28px 32px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 40px rgba(0,0,0,.3);
        }
        .mov-header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 12px; margin-bottom: 22px;
            padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .mov-title { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .mov-title i { color: var(--accent); }
        .count-pill {
            background: rgba(0,194,255,.1); border: 1px solid rgba(0,194,255,.2);
            border-radius: 30px; padding: 6px 16px;
            font-size: .72rem; color: var(--accent); font-weight: 600; letter-spacing: 1px;
        }

        .table-wrap { overflow-x: auto; border-radius: 14px; }
        table.tv-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .tv-table thead tr th {
            background: rgba(26,188,156,.1); color: rgba(255,255,255,.55);
            font-size: .65rem; font-weight: 700; letter-spacing: 2.5px;
            text-transform: uppercase; padding: 13px 16px;
            border-bottom: 1px solid rgba(255,255,255,.07); white-space: nowrap;
        }
        .tv-table thead tr th:first-child { border-radius: 14px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius: 0 14px 0 0; }
        .tv-table tbody tr { transition: background .2s; }
        .tv-table tbody tr:hover { background: rgba(26,188,156,.04); }
        .tv-table tbody td {
            padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04);
            vertical-align: middle; font-size: .86rem; color: rgba(255,255,255,.75);
        }

        /* Badges tipo movimiento */
        .badge-mov {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px;
            font-size: .72rem; font-weight: 700;
        }
        .badge-entrada { background: rgba(0,214,143,.15); color: var(--success); border: 1px solid rgba(0,214,143,.25); }
        .badge-salida  { background: rgba(255,77,77,.15);  color: var(--danger);  border: 1px solid rgba(255,77,77,.25); }
        .badge-ajuste  { background: rgba(245,197,24,.15); color: var(--gold);    border: 1px solid rgba(245,197,24,.25); }

        /* Empty state */
        .empty-state { text-align: center; padding: 50px 20px; color: rgba(255,255,255,.3); }
        .empty-state i { font-size: 3rem; margin-bottom: 14px; display: block; opacity: .35; }
        .empty-state h5 { color: rgba(255,255,255,.45); margin-bottom: 6px; }
        .empty-state p  { font-size: .83rem; }

        /* Footer */
        .page-footer { text-align: center; margin-top: 36px; font-size: .66rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,.15); }
        .page-footer span { color: #1abc9c; opacity: .5; }

        /* Responsive */
        @media (max-width: 768px) {
            .page-wrap { padding: 14px 12px 36px; }
            .inventario-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .page-pill { display: none; }
            .topbar { padding: 12px 14px; }
            .logo-img { width: 52px; height: 52px; }
        }
        @media (max-width: 480px) {
            .inventario-grid { grid-template-columns: 1fr; }
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
            <i class="fas fa-clipboard-list"></i>
            <span>Control de Inventario</span>
        </div>

        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?php echo strtoupper(substr($user_nombre,0,1)); ?></div>
                <div>
                    <div class="u-name"><?php echo htmlspecialchars($user_nombre); ?></div>
                    <div class="u-role"><?php echo $user_rol == 'administrador' ? '★ Admin' : 'Vendedor'; ?></div>
                </div>
            </div>
            <a href="menu_principal.php" class="btn-back" title="Regresar al menú">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- ── ALERTA STOCK BAJO ── -->
    <?php if ($total_alertas > 0): ?>
    <div class="alerta-card anim">
        <div class="alerta-left">
            <i class="fas fa-triangle-exclamation alerta-ico"></i>
            <div>
                <div class="alerta-title"><?php echo $total_alertas; ?> producto(s) con stock bajo</div>
                <div class="alerta-sub">Se recomienda realizar un pedido a la brevedad</div>
            </div>
        </div>
        <a href="#productos-bajos" class="btn-ver-alertas">
            <i class="fas fa-eye"></i> Ver productos
        </a>
    </div>
    <?php endif; ?>

    <!-- ── FILTRO ── -->
    <div class="filtro-card anim anim-2">
        <i class="fas fa-filter" style="color:var(--accent);font-size:1.1rem;"></i>
        <form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <select name="categoria">
                <option value="0">Todas las categorías</option>
                <?php while ($cat = mysqli_fetch_assoc($result_categorias)): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_filtro == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-filtrar">
                <i class="fas fa-magnifying-glass"></i> Filtrar
            </button>
            <?php if ($categoria_filtro > 0): ?>
            <a href="inventario.php" style="color:rgba(255,255,255,.4);font-size:.82rem;text-decoration:none;">
                <i class="fas fa-xmark me-1"></i>Quitar filtro
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ── GRID DE PRODUCTOS ── -->
    <div class="section-head anim anim-2">
        <div class="s-label">Stock actual</div>
        <div class="s-line"></div>
    </div>

    <div class="inventario-grid" id="productos-bajos">
        <?php if (mysqli_num_rows($result_productos) > 0):
            $delay = 0;
            while ($p = mysqli_fetch_assoc($result_productos)):
                $sc = '';
                if ($p['stock'] <= $p['stock_minimo']) $sc = 'stock-bajo';
                elseif ($p['stock'] <= ($p['stock_minimo'] + max(2, intval($p['stock_minimo'] * 0.5)))) $sc = 'stock-medio';
                $delay += 0.05;
        ?>
        <div class="prod-card <?php echo $sc; ?>" style="animation-delay:<?php echo $delay; ?>s;">
            <div class="prod-card-header">
                <div>
                    <div class="prod-card-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="prod-card-marca"><i class="fas fa-tag" style="font-size:.65rem;"></i> <?php echo htmlspecialchars($p['marca']); ?></div>
                </div>
                <span class="cat-pill"><?php echo htmlspecialchars($p['categoria_nombre'] ?? '—'); ?></span>
            </div>

            <div class="stock-center">
                <div class="stock-circle">
                    <span class="stock-num"><?php echo $p['stock']; ?></span>
                    <span class="stock-txt">existencias</span>
                </div>
                <div class="stock-minmax">
                    <span><i class="fas fa-arrow-down"></i> Mín: <?php echo $p['stock_minimo']; ?></span>
                    <span><i class="fas fa-arrow-up"></i> Máx: <?php echo $p['stock_maximo']; ?></span>
                </div>
            </div>

            <div class="prod-card-footer">
                <span><i class="fas fa-location-dot"></i> <?php echo $p['ubicacion'] ?: 'Sin ubicación'; ?></span>
                <span><i class="fas fa-dollar-sign"></i> $<?php echo number_format($p['precio'], 2); ?></span>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div style="grid-column:1/-1;">
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h5>No hay productos en esta categoría</h5>
                <p>Agrega productos desde el módulo de Productos.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── HISTORIAL DE MOVIMIENTOS ── -->
    <div class="section-head anim anim-3">
        <div class="s-label">Historial de movimientos</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-4">
        <div class="mov-header">
            <div class="mov-title">
                <i class="fas fa-clock-rotate-left"></i> Historial de movimientos
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="count-pill">
                    <i class="fas fa-list me-1"></i>
                    <?php echo $total_movimientos; ?> registros totales
                </div>
                <!-- Búsqueda -->
                <form method="GET" action="" style="display:flex;gap:8px;align-items:center;">
                    <?php if ($categoria_filtro): ?><input type="hidden" name="categoria" value="<?php echo $categoria_filtro; ?>"><?php endif; ?>
                    <div style="position:relative;">
                        <i class="fas fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.8rem;pointer-events:none;"></i>
                        <input type="text" name="busq_mov" value="<?php echo htmlspecialchars($busq_mov); ?>"
                               placeholder="Buscar producto, motivo..."
                               style="padding:8px 12px 8px 34px;border:1px solid rgba(255,255,255,.1);border-radius:12px;background:rgba(255,255,255,.05);color:var(--white);font-size:.82rem;font-family:'DM Sans',sans-serif;outline:none;width:220px;"
                               onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
                    </div>
                    <button type="submit" style="padding:8px 16px;border-radius:12px;background:linear-gradient(135deg,var(--blue),var(--accent));border:none;color:white;font-size:.8rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;">
                        Buscar
                    </button>
                    <?php if ($busq_mov): ?>
                    <a href="inventario.php" style="font-size:.78rem;color:rgba(255,255,255,.4);text-decoration:none;" title="Limpiar búsqueda">
                        <i class="fas fa-xmark"></i> Limpiar
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Motivo</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result_movimientos) > 0):
                    while ($mov = mysqli_fetch_assoc($result_movimientos)): ?>
                    <tr>
                        <td style="font-size:.78rem;color:rgba(255,255,255,.45);">
                            <?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?>
                        </td>
                        <td><strong style="color:var(--white);"><?php echo htmlspecialchars($mov['producto_nombre']); ?></strong></td>
                        <td>
                            <?php if ($mov['tipo_movimiento'] == 'entrada'): ?>
                                <span class="badge-mov badge-entrada"><i class="fas fa-arrow-up"></i> ENTRADA</span>
                            <?php elseif ($mov['tipo_movimiento'] == 'salida'): ?>
                                <span class="badge-mov badge-salida"><i class="fas fa-arrow-down"></i> SALIDA</span>
                            <?php else: ?>
                                <span class="badge-mov badge-ajuste"><i class="fas fa-sliders"></i> AJUSTE</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $mov['cantidad']; ?> pz</strong></td>
                        <td style="color:rgba(255,255,255,.55);"><?php echo htmlspecialchars($mov['motivo']); ?></td>
                        <td>
                            <span style="display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-user-circle" style="color:var(--accent);"></i>
                                <?php echo htmlspecialchars($mov['usuario_nombre']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-exchange-alt"></i>
                                <h5>No hay movimientos registrados</h5>
                                <p>Los movimientos se generan automáticamente al realizar ventas o dar de alta productos.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ── PAGINACIÓN ── -->
        <?php if ($total_paginas > 1): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.06);">
            <div style="font-size:.78rem;color:rgba(255,255,255,.35);">
                Mostrando <?php echo min($offset + $por_pagina, $total_movimientos); ?> de <?php echo $total_movimientos; ?> registros
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if ($pag > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pag' => $pag - 1])); ?>"
                   style="padding:7px 14px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;transition:all .2s;"
                   onmouseover="this.style.background='rgba(0,194,255,.12)';this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.borderColor='rgba(255,255,255,.1)';this.style.color='rgba(255,255,255,.6)'">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $rango_ini = max(1, $pag - 2);
                $rango_fin = min($total_paginas, $pag + 2);
                if ($rango_ini > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pag' => 1])); ?>"
                   style="padding:7px 14px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;">1</a>
                <?php if ($rango_ini > 2): ?><span style="color:rgba(255,255,255,.3);padding:0 4px;">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $rango_ini; $i <= $rango_fin; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pag' => $i])); ?>"
                   style="padding:7px 14px;border-radius:10px;text-decoration:none;font-size:.82rem;font-weight:<?php echo $i==$pag?'700':'400'; ?>;
                          background:<?php echo $i==$pag?'linear-gradient(135deg,var(--blue),var(--accent))':'rgba(255,255,255,.06)'; ?>;
                          border:1px solid <?php echo $i==$pag?'transparent':'rgba(255,255,255,.1)'; ?>;
                          color:<?php echo $i==$pag?'white':'rgba(255,255,255,.6)'; ?>;">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <?php if ($rango_fin < $total_paginas): ?>
                <?php if ($rango_fin < $total_paginas - 1): ?><span style="color:rgba(255,255,255,.3);padding:0 4px;">…</span><?php endif; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pag' => $total_paginas])); ?>"
                   style="padding:7px 14px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;"><?php echo $total_paginas; ?></a>
                <?php endif; ?>

                <?php if ($pag < $total_paginas): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pag' => $pag + 1])); ?>"
                   style="padding:7px 14px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);text-decoration:none;font-size:.82rem;transition:all .2s;"
                   onmouseover="this.style.background='rgba(0,194,255,.12)';this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.borderColor='rgba(255,255,255,.1)';this.style.color='rgba(255,255,255,.6)'">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div class="page-footer">
        <span>◆</span> &nbsp;TecnoViral POS v1.0 &nbsp;<span>◆</span>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('.btn-ver-alertas')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector('#productos-bajos').scrollIntoView({ behavior: 'smooth' });
    });
</script>
</body>
</html>