<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_nombre = $_SESSION['user_nombre'];
$user_rol    = $_SESSION['user_rol'];
$mensaje = '';
$error   = '';

// ── CATEGORÍAS ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_cat'])) {
    $accion_cat = $_POST['accion_cat'];
    if ($accion_cat == 'crear_cat') {
        $nom_cat  = mysqli_real_escape_string($conn, trim($_POST['nombre_cat']));
        $desc_cat = mysqli_real_escape_string($conn, trim($_POST['descripcion_cat']));
        if ($nom_cat == '') {
            $error = "El nombre de la categoría es obligatorio.";
        } else {
            $chk = mysqli_query($conn, "SELECT id FROM categorias WHERE nombre = '$nom_cat'");
            if (mysqli_num_rows($chk) > 0) {
                $error = "Ya existe una categoría con ese nombre.";
            } else {
                mysqli_query($conn, "INSERT INTO categorias (nombre, descripcion) VALUES ('$nom_cat','$desc_cat')");
                $mensaje = "Categoría '$nom_cat' creada correctamente.";
            }
        }
    } elseif ($accion_cat == 'eliminar_cat') {
        $id_cat = intval($_POST['id_cat']);
        $chk2   = mysqli_query($conn, "SELECT COUNT(*) as total FROM productos WHERE id_categoria = $id_cat AND activo = 1");
        $en_uso = mysqli_fetch_assoc($chk2);
        if ($en_uso['total'] > 0) {
            $error = "No puedes eliminar esta categoría porque tiene productos asignados.";
        } else {
            mysqli_query($conn, "DELETE FROM categorias WHERE id = $id_cat");
            $mensaje = "Categoría eliminada correctamente.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {
        $accion       = $_POST['accion'];
        $nombre       = mysqli_real_escape_string($conn, $_POST['nombre']);
        $descripcion  = mysqli_real_escape_string($conn, $_POST['descripcion']);
        $categoria    = mysqli_real_escape_string($conn, $_POST['categoria']);
        $marca        = mysqli_real_escape_string($conn, $_POST['marca']);
        $precio       = floatval($_POST['precio']);
        $stock        = max(0, intval($_POST['stock']));
        $stock_minimo = max(0, intval($_POST['stock_minimo']));
        $stock_maximo = max(0, intval($_POST['stock_maximo']));
        $ubicacion    = mysqli_real_escape_string($conn, $_POST['ubicacion']);

        // Validaciones de negocio
        if ($precio < 0) $error = "El precio no puede ser negativo.";
        elseif ($stock_minimo > $stock_maximo) $error = "El stock mínimo no puede ser mayor al máximo.";
        elseif (strlen($nombre) < 2) $error = "El nombre del producto es muy corto.";
        elseif ($accion == 'alta') {
            $query = "INSERT INTO productos (nombre, descripcion, id_categoria, marca, precio, stock, stock_minimo, stock_maximo, ubicacion)
                      VALUES ('$nombre','$descripcion','$categoria','$marca',$precio,$stock,$stock_minimo,$stock_maximo,'$ubicacion')";
            if (mysqli_query($conn, $query)) $mensaje = "Producto dado de alta correctamente.";
            else $error = "Error al dar de alta: " . mysqli_error($conn);
        } elseif ($accion == 'modificar' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $query = "UPDATE productos SET nombre='$nombre', descripcion='$descripcion', id_categoria='$categoria',
                      marca='$marca', precio=$precio, stock=$stock, stock_minimo=$stock_minimo,
                      stock_maximo=$stock_maximo, ubicacion='$ubicacion' WHERE id=$id";
            if (mysqli_query($conn, $query)) $mensaje = "Producto modificado correctamente.";
            else $error = "Error al modificar: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id    = intval($_GET['eliminar']);
    $check = mysqli_query($conn, "SELECT COUNT(*) as total FROM detalles_venta WHERE id_producto = $id");
    $ventas = mysqli_fetch_assoc($check);
    if ($ventas['total'] > 0) {
        $query   = "UPDATE productos SET activo = 0 WHERE id = $id";
        $mensaje = "Producto desactivado (tiene ventas registradas).";
    } else {
        $query   = "DELETE FROM productos WHERE id = $id";
        $mensaje = "Producto eliminado permanentemente.";
    }
    if (!mysqli_query($conn, $query)) $error = "Error al eliminar: " . mysqli_error($conn);
}

$query_productos  = "SELECT p.*, c.nombre as categoria_nombre FROM productos p
                     LEFT JOIN categorias c ON p.id_categoria = c.id
                     WHERE p.activo = 1 ORDER BY p.id DESC";
$result_productos = mysqli_query($conn, $query_productos);

$query_categorias  = "SELECT c.*, COUNT(p.id) as total_productos FROM categorias c LEFT JOIN productos p ON c.id = p.id_categoria AND p.activo = 1 GROUP BY c.id ORDER BY c.nombre";
$result_categorias = mysqli_query($conn, $query_categorias);

$producto_editar = null;
if (isset($_GET['editar'])) {
    $id_editar       = intval($_GET['editar']);
    $result_editar   = mysqli_query($conn, "SELECT * FROM productos WHERE id = $id_editar");
    $producto_editar = mysqli_fetch_assoc($result_editar);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Productos</title>

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
            --card:   rgba(255,255,255,.04);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--navy);
            color: var(--white);
            overflow-x: hidden;
        }

        /* Fondo */
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

        /* ── Animación entrada ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim { animation: fadeUp .5s ease both; }
        .anim-1 { animation-delay: .05s; }
        .anim-2 { animation-delay: .12s; }
        .anim-3 { animation-delay: .20s; }

        /* ══════════════════════════
           TOPBAR
        ══════════════════════════ */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: rgba(13,35,71,.7);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 14px 22px;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,.4);
            flex-wrap: wrap;
        }
        .brand-row { display: flex; align-items: center; gap: 14px; }
        .logo-wrap  { position: relative; flex-shrink: 0; }
        .logo-img {
            width: 68px; height: 68px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid rgba(0,194,255,.3);
            box-shadow: 0 0 0 4px rgba(0,194,255,.08), 0 8px 20px rgba(0,0,0,.5);
            transition: transform .4s cubic-bezier(.34,1.56,.64,1);
        }
        .logo-img:hover { transform: scale(1.08) rotate(-2deg); }

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem; font-weight: 900;
            letter-spacing: 3px; color: var(--white);
        }
        .brand-name span { color: var(--accent); }
        .brand-sub {
            font-size: .68rem; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,.35);
            margin-top: 3px;
        }

        /* Page title pill */
        .page-pill {
            display: flex; align-items: center; gap: 10px;
            background: rgba(0,194,255,.08);
            border: 1px solid rgba(0,194,255,.2);
            border-radius: 40px;
            padding: 8px 20px;
        }
        .page-pill i   { color: var(--accent); font-size: .9rem; }
        .page-pill span{ font-size: .78rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.75); }

        /* User + back */
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .user-chip {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 16px 6px 6px;
        }
        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--blue), var(--accent));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .9rem;
        }
        .u-name { font-size: .82rem; font-weight: 600; color: var(--white); }
        .u-role { font-size: .62rem; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; }

        .btn-back {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.7);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: all .25s;
        }
        .btn-back:hover { background: var(--blue); color: white; border-color: var(--blue); transform: translateX(-3px); }

        /* ══════════════════════════
           ALERTAS
        ══════════════════════════ */
        .tv-alert {
            display: flex; align-items: center; gap: 12px;
            border-radius: 16px;
            padding: 14px 20px;
            font-size: .88rem; font-weight: 500;
            margin-bottom: 22px;
            animation: fadeUp .4s ease both;
        }
        .tv-alert.success {
            background: rgba(0,214,143,.1);
            border: 1px solid rgba(0,214,143,.25);
            color: #00d68f;
        }
        .tv-alert.danger {
            background: rgba(255,77,77,.1);
            border: 1px solid rgba(255,77,77,.25);
            color: #ff8585;
        }
        .tv-alert i { font-size: 1.1rem; }

        /* ══════════════════════════
           FORMULARIO CARD
        ══════════════════════════ */
        .glass-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 24px;
            padding: 32px 36px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 40px rgba(0,0,0,.3);
            margin-bottom: 24px;
        }

        .card-header-tv {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .card-header-tv .ch-ico {
            width: 46px; height: 46px;
            border-radius: 14px;
            background: rgba(0,82,204,.22);
            display: flex; align-items: center; justify-content: center;
            color: var(--accent); font-size: 1.1rem;
        }
        .card-header-tv .ch-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem; font-weight: 700;
            color: var(--white);
        }
        .card-header-tv .ch-sub {
            font-size: .72rem; color: rgba(255,255,255,.4);
            text-transform: uppercase; letter-spacing: 2px;
        }

        /* Campos del formulario */
        .field-label {
            font-size: .68rem; font-weight: 700;
            letter-spacing: 2.5px; text-transform: uppercase;
            color: rgba(255,255,255,.5);
            margin-bottom: 8px; display: block;
        }
        .field-wrap {
            position: relative; margin-bottom: 20px;
        }
        .field-wrap .f-ico {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: .88rem;
            pointer-events: none; transition: color .25s;
            z-index: 1;
        }
        .field-wrap.textarea-wrap .f-ico { top: 18px; transform: none; }

        .field-wrap input,
        .field-wrap select,
        .field-wrap textarea {
            width: 100%;
            padding: 14px 16px 14px 44px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            font-size: .92rem;
            font-family: 'DM Sans', sans-serif;
            background: rgba(255,255,255,.05);
            color: var(--white);
            transition: border-color .25s, box-shadow .25s, background .25s;
            outline: none;
            -webkit-appearance: none;
        }
        .field-wrap select option { background: var(--navy2); color: white; }
        .field-wrap input::placeholder,
        .field-wrap textarea::placeholder { color: rgba(255,255,255,.2); }
        .field-wrap textarea { min-height: 90px; resize: vertical; padding-top: 14px; }

        .field-wrap input:focus,
        .field-wrap select:focus,
        .field-wrap textarea:focus {
            border-color: var(--accent);
            background: rgba(0,194,255,.05);
            box-shadow: 0 0 0 4px rgba(0,194,255,.1);
        }
        .field-wrap input:focus + .f-ico,
        .field-wrap select:focus + .f-ico { color: var(--accent); }

        /* Botones formulario */
        .btn-tv {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 13px 28px;
            border-radius: 14px; border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            cursor: pointer; text-decoration: none;
            transition: all .25s;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-save {
            background: linear-gradient(135deg, var(--blue), var(--accent));
            color: white;
            box-shadow: 0 6px 20px rgba(0,82,204,.35);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,82,204,.45); color: white; }
        .btn-cancel {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: rgba(255,255,255,.65);
        }
        .btn-cancel:hover { background: rgba(255,255,255,.12); color: white; }

        /* Divisor sección */
        .section-head {
            display: flex; align-items: center; gap: 12px; margin-bottom: 18px;
        }
        .section-head .s-line { flex: 1; height: 1px; background: linear-gradient(to right, rgba(0,194,255,.3), transparent); }
        .section-head .s-label { font-size: .68rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: var(--accent); }

        /* ══════════════════════════
           SEARCH BAR
        ══════════════════════════ */
        .search-bar {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .search-wrap {
            position: relative; flex: 1; min-width: 220px;
        }
        .search-wrap i {
            position: absolute; left: 16px; top: 50%;
            transform: translateY(-50%); color: var(--muted); font-size: .9rem;
        }
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            background: rgba(255,255,255,.05);
            color: var(--white); font-size: .9rem;
            font-family: 'DM Sans', sans-serif;
            outline: none;
            transition: border-color .25s, box-shadow .25s;
        }
        .search-input::placeholder { color: rgba(255,255,255,.25); }
        .search-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(0,194,255,.1);
        }
        .count-pill {
            background: rgba(0,194,255,.1);
            border: 1px solid rgba(0,194,255,.2);
            border-radius: 30px;
            padding: 8px 18px;
            font-size: .75rem;
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        /* ══════════════════════════
           TABLA DE PRODUCTOS
        ══════════════════════════ */
        .table-wrap { overflow-x: auto; border-radius: 16px; }

        table.tv-table {
            width: 100%; border-collapse: separate; border-spacing: 0;
        }
        .tv-table thead tr th {
            background: rgba(0,82,204,.2);
            color: rgba(255,255,255,.6);
            font-size: .65rem; font-weight: 700;
            letter-spacing: 2.5px; text-transform: uppercase;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            white-space: nowrap;
        }
        .tv-table thead tr th:first-child { border-radius: 16px 0 0 0; }
        .tv-table thead tr th:last-child  { border-radius: 0 16px 0 0; }

        .tv-table tbody tr {
            transition: background .2s;
            animation: fadeUp .4s ease both;
        }
        .tv-table tbody tr:hover { background: rgba(0,194,255,.05); }

        .tv-table tbody td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255,255,255,.05);
            vertical-align: middle;
            font-size: .88rem;
            color: rgba(255,255,255,.8);
        }

        /* Nombre del producto */
        .prod-name { font-weight: 600; color: var(--white); font-size: .92rem; }
        .prod-desc { font-size: .75rem; color: rgba(255,255,255,.35); margin-top: 2px; }

        /* Badge stock */
        .stock-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px;
            font-size: .75rem; font-weight: 700; letter-spacing: .5px;
        }
        .stock-alto  { background: rgba(0,214,143,.15); color: #00d68f; border: 1px solid rgba(0,214,143,.25); }
        .stock-medio { background: rgba(245,197,24,.15); color: var(--gold); border: 1px solid rgba(245,197,24,.25); }
        .stock-bajo  { background: rgba(255,77,77,.15);  color: var(--danger); border: 1px solid rgba(255,77,77,.25); }

        /* Precio */
        .price-val { font-family: 'Playfair Display', serif; font-size: 1rem; color: var(--accent); }

        /* Categoría pill */
        .cat-pill {
            display: inline-block;
            background: rgba(0,82,204,.18);
            border: 1px solid rgba(0,82,204,.3);
            color: rgba(255,255,255,.7);
            padding: 4px 12px; border-radius: 20px; font-size: .72rem;
        }

        /* Ubicación */
        .loc-val { display: flex; align-items: center; gap: 6px; color: rgba(255,255,255,.5); font-size: .8rem; }
        .loc-val i { color: var(--accent); font-size: .75rem; }

        /* Botones acción */
        .action-btns { display: flex; align-items: center; gap: 8px; }
        .btn-act {
            width: 38px; height: 38px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: .88rem;
            border: none; cursor: pointer;
            transition: all .25s; text-decoration: none;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-edit {
            background: rgba(0,82,204,.2);
            border: 1px solid rgba(0,82,204,.3);
            color: var(--accent);
        }
        .btn-edit:hover { background: var(--blue); color: white; transform: scale(1.1); }
        .btn-del {
            background: rgba(255,77,77,.15);
            border: 1px solid rgba(255,77,77,.25);
            color: var(--danger);
        }
        .btn-del:hover { background: var(--danger); color: white; transform: scale(1.1); }

        /* Fila expandible */
        .prod-row:hover { background: rgba(0,194,255,.06) !important; }
        .desc-row td { background: rgba(0,194,255,.03); }
        .desc-arrow.open { transform: rotate(180deg); color: var(--accent) !important; }

        /* Sin productos */
        .empty-state {
            text-align: center; padding: 60px 20px;
            color: rgba(255,255,255,.3);
        }
        .empty-state i { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: .4; }
        .empty-state h4 { font-size: 1.1rem; margin-bottom: 6px; color: rgba(255,255,255,.5); }
        .empty-state p  { font-size: .85rem; }

        /* Footer */
        .page-footer {
            text-align: center; margin-top: 36px;
            font-size: .66rem; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,.15);
        }
        .page-footer span { color: var(--accent); opacity: .5; }

        /* ══════════════════════════
           RESPONSIVE
        ══════════════════════════ */
        @media (max-width: 768px) {
            .page-wrap   { padding: 14px 12px 36px; }
            .glass-card  { padding: 22px 16px; }
            .topbar      { padding: 12px 14px; }
            .logo-img    { width: 52px; height: 52px; }
            .page-pill   { display: none; }
            .tv-table thead { display: none; }
            .tv-table tbody td {
                display: block;
                text-align: right;
                padding: 10px 16px;
                border-bottom: 1px dashed rgba(255,255,255,.05);
                font-size: .82rem;
            }
            .tv-table tbody td::before {
                content: attr(data-label);
                float: left;
                font-weight: 700;
                font-size: .65rem;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: var(--accent);
            }
            .tv-table tbody tr {
                display: block;
                border: 1px solid rgba(255,255,255,.08);
                border-radius: 16px;
                margin-bottom: 14px;
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

    <!-- ══ TOPBAR ══ -->
    <div class="topbar anim anim-1">
        <a href="menu_principal.php" class="brand-row" style="text-decoration:none;">
            <div class="logo-wrap">
                <img src="imagenes/logoe.jpeg" alt="TecnoViral" class="logo-img"
                     onerror="this.src='https://placehold.co/68x68/0052cc/fff?text=TV'">
            </div>
            <div>
                <div class="brand-name">TECNO<span>VIRAL</span></div>
                <div class="brand-sub">Punto de Venta · Sistema Táctil</div>
            </div>
        </a>

        <div class="page-pill">
            <i class="fas fa-box-open"></i>
            <span>Administración de Productos</span>
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

    <!-- ══ ALERTAS ══ -->
    <?php if ($mensaje): ?>
    <div class="tv-alert success anim">
        <i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="tv-alert danger anim">
        <i class="fas fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- ══ FORMULARIO ══ -->
    <div class="glass-card anim anim-2">
        <div class="card-header-tv">
            <div class="ch-ico">
                <i class="fas fa-<?php echo $producto_editar ? 'pen-to-square' : 'plus'; ?>"></i>
            </div>
            <div>
                <div class="ch-title"><?php echo $producto_editar ? 'Modificar Producto' : 'Nuevo Producto'; ?></div>
                <div class="ch-sub"><?php echo $producto_editar ? 'Edita los datos del producto seleccionado' : 'Completa los datos para registrar un producto'; ?></div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="<?php echo $producto_editar ? 'modificar' : 'alta'; ?>">
            <?php if ($producto_editar): ?>
                <input type="hidden" name="id" value="<?php echo $producto_editar['id']; ?>">
            <?php endif; ?>

            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-md-5">
                    <label class="field-label">Nombre del Producto</label>
                    <div class="field-wrap">
                        <input type="text" name="nombre" required placeholder="Ej. Laptop HP Pavilion"
                               value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['nombre']) : ''; ?>">
                        <i class="fas fa-box f-ico"></i>
                    </div>
                </div>
                <!-- Marca -->
                <div class="col-md-2">
                    <label class="field-label">Marca</label>
                    <div class="field-wrap">
                        <input type="text" name="marca" required placeholder="Ej. HP, Samsung"
                               value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['marca']) : ''; ?>">
                        <i class="fas fa-tag f-ico"></i>
                    </div>
                </div>
                <!-- Precio -->
                <div class="col-md-2">
                    <label class="field-label">Precio</label>
                    <div class="field-wrap">
                        <input type="number" step="0.01" name="precio" required placeholder="0.00"
                               value="<?php echo $producto_editar ? $producto_editar['precio'] : ''; ?>">
                        <i class="fas fa-dollar-sign f-ico"></i>
                    </div>
                </div>
                <!-- Categoría -->
                <div class="col-md-3">
                    <label class="field-label">Categoría</label>
                    <div class="field-wrap">
                        <select name="categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php
                            mysqli_data_seek($result_categorias, 0);
                            while ($cat = mysqli_fetch_assoc($result_categorias)):
                            ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo ($producto_editar && $producto_editar['id_categoria'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <i class="fas fa-folder f-ico"></i>
                    </div>
                </div>

                <!-- Stock -->
                <div class="col-md-2">
                    <label class="field-label">Stock actual</label>
                    <div class="field-wrap">
                        <input type="number" name="stock" required placeholder="0" min="0"
                               value="<?php echo $producto_editar ? $producto_editar['stock'] : '0'; ?>">
                        <i class="fas fa-cubes f-ico"></i>
                    </div>
                </div>
                <!-- Mínimo -->
                <div class="col-md-2">
                    <label class="field-label">Stock mínimo</label>
                    <div class="field-wrap">
                        <input type="number" name="stock_minimo" required placeholder="5" min="0"
                               value="<?php echo $producto_editar ? $producto_editar['stock_minimo'] : '5'; ?>">
                        <i class="fas fa-arrow-down f-ico"></i>
                    </div>
                </div>
                <!-- Máximo -->
                <div class="col-md-2">
                    <label class="field-label">Stock máximo</label>
                    <div class="field-wrap">
                        <input type="number" name="stock_maximo" required placeholder="100" min="0"
                               value="<?php echo $producto_editar ? $producto_editar['stock_maximo'] : '100'; ?>">
                        <i class="fas fa-arrow-up f-ico"></i>
                    </div>
                </div>
                <!-- Ubicación -->
                <div class="col-md-2">
                    <label class="field-label">Ubicación</label>
                    <div class="field-wrap">
                        <input type="text" name="ubicacion" placeholder="Ej. A1, B2"
                               value="<?php echo $producto_editar ? htmlspecialchars($producto_editar['ubicacion']) : ''; ?>">
                        <i class="fas fa-map-pin f-ico"></i>
                    </div>
                </div>
                <!-- Descripción -->
                <div class="col-md-4">
                    <label class="field-label">Descripción</label>
                    <div class="field-wrap textarea-wrap">
                        <textarea name="descripcion" placeholder="Descripción detallada del producto..."><?php echo $producto_editar ? htmlspecialchars($producto_editar['descripcion']) : ''; ?></textarea>
                        <i class="fas fa-align-left f-ico"></i>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3 mt-3 flex-wrap">
                <button type="submit" class="btn-tv btn-save">
                    <i class="fas fa-floppy-disk"></i>
                    <?php echo $producto_editar ? 'Guardar Cambios' : 'Guardar Producto'; ?>
                </button>
                <?php if ($producto_editar): ?>
                <a href="productos.php" class="btn-tv btn-cancel">
                    <i class="fas fa-xmark"></i> Cancelar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>


    <!-- ══ CATEGORÍAS ══ -->
    <div class="section-head anim anim-3">
        <div class="s-label">Categorías</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-3" style="padding: 24px 28px; margin-bottom: 28px;">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <form method="POST" action="" class="d-flex gap-3 flex-wrap align-items-end">
                    <input type="hidden" name="accion_cat" value="crear_cat">
                    <div style="flex:1; min-width:200px;">
                        <label class="field-label">Nueva Categoría</label>
                        <div class="field-wrap" style="margin-bottom:0;">
                            <input type="text" name="nombre_cat" required placeholder="Ej. Celulares, Accesorios...">
                            <i class="fas fa-folder f-ico"></i>
                        </div>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label class="field-label">Descripción (opcional)</label>
                        <div class="field-wrap" style="margin-bottom:0;">
                            <input type="text" name="descripcion_cat" placeholder="Descripción breve">
                            <i class="fas fa-align-left f-ico"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-tv btn-save" style="white-space:nowrap;">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </form>
            </div>
            <div class="col-md-7">
                <label class="field-label">Categorías existentes</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:4px;">
                    <?php
                    mysqli_data_seek($result_categorias, 0);
                    while ($cat = mysqli_fetch_assoc($result_categorias)):
                    ?>
                    <div style="display:flex; align-items:center; gap:8px;
                                background:rgba(0,82,204,.15); border:1px solid rgba(0,82,204,.25);
                                border-radius:20px; padding:6px 14px;">
                        <i class="fas fa-folder" style="color:var(--accent); font-size:.8rem;"></i>
                        <span style="font-size:.85rem; font-weight:600; color:var(--white);">
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </span>
                        <span style="font-size:.72rem; color:rgba(255,255,255,.4);">
                            (<?php echo $cat['total_productos']; ?>)
                        </span>
                        <?php if ($cat['total_productos'] == 0): ?>
                        <form method="POST" style="display:inline; margin:0;">
                            <input type="hidden" name="accion_cat" value="eliminar_cat">
                            <input type="hidden" name="id_cat" value="<?php echo $cat['id']; ?>">
                            <button type="submit" title="Eliminar categoría"
                                onclick="return confirm('¿Eliminar categoría '<?php echo htmlspecialchars($cat['nombre']); ?>'?')"
                                style="background:none; border:none; cursor:pointer; color:rgba(255,77,77,.6);
                                       padding:0; margin-left:4px; font-size:.8rem; transition:color .2s;"
                                onmouseover="this.style.color='var(--danger)'"
                                onmouseout="this.style.color='rgba(255,77,77,.6)'">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ TABLA ══ -->
    <div class="section-head anim anim-3">
        <div class="s-label">Productos registrados</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-3" style="padding: 24px 28px;">
        <!-- Search bar -->
        <div class="search-bar">
            <div class="search-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" class="search-input" id="buscador"
                       placeholder="Buscar por nombre, marca, categoría..." onkeyup="buscarProductos()">
            </div>
            <div class="count-pill">
                <i class="fas fa-layer-group me-1"></i>
                <span id="mostrando">0</span> productos
            </div>
        </div>

        <div class="table-wrap">
            <table class="tv-table" id="tablaProductos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Ubicación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result_productos) > 0):
                    mysqli_data_seek($result_productos, 0);
                    while ($p = mysqli_fetch_assoc($result_productos)):
                        $sc = 'stock-alto';
                        if ($p['stock'] <= $p['stock_minimo']) $sc = 'stock-bajo';
                        elseif ($p['stock'] <= ($p['stock_minimo'] + max(2, intval($p['stock_minimo'] * 0.5)))) $sc = 'stock-medio';
                        $sc_ico = $sc == 'stock-alto' ? 'circle-check' : ($sc == 'stock-medio' ? 'circle-exclamation' : 'circle-xmark');
                ?>
                    <tr class="prod-row" onclick="toggleDesc(<?php echo $p['id']; ?>)" style="cursor:pointer;">
                        <td data-label="ID">
                            <span style="font-size:.75rem; color:rgba(255,255,255,.35); font-weight:600;">#<?php echo $p['id']; ?></span>
                        </td>
                        <td data-label="Producto">
                            <div class="prod-name" style="display:flex;align-items:center;gap:8px;">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                                <?php if ($p['descripcion']): ?>
                                <i class="fas fa-chevron-down desc-arrow" id="arrow-<?php echo $p['id']; ?>"
                                   style="font-size:.65rem;color:rgba(255,255,255,.3);transition:transform .3s;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="prod-desc"><?php echo mb_substr(htmlspecialchars($p['descripcion']), 0, 55) . (mb_strlen($p['descripcion']) > 55 ? '…' : ''); ?></div>
                        </td>
                        <td data-label="Marca"><?php echo htmlspecialchars($p['marca']); ?></td>
                        <td data-label="Categoría">
                            <span class="cat-pill"><?php echo htmlspecialchars($p['categoria_nombre'] ?? '—'); ?></span>
                        </td>
                        <td data-label="Precio">
                            <span class="price-val">$<?php echo number_format($p['precio'], 2); ?></span>
                        </td>
                        <td data-label="Stock">
                            <span class="stock-badge <?php echo $sc; ?>">
                                <i class="fas fa-<?php echo $sc_ico; ?>"></i>
                                <?php echo $p['stock']; ?> pz
                            </span>
                        </td>
                        <td data-label="Ubicación">
                            <div class="loc-val">
                                <i class="fas fa-location-dot"></i>
                                <?php echo $p['ubicacion'] ?: '—'; ?>
                            </div>
                        </td>
                        <td data-label="Acciones">
                            <div class="action-btns">
                                <a href="?editar=<?php echo $p['id']; ?>" class="btn-act btn-edit" title="Editar">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <button onclick="confirmarEliminar(<?php echo $p['id']; ?>,'<?php echo htmlspecialchars($p['nombre']); ?>')"
                                        class="btn-act btn-del" title="Eliminar">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php if ($p['descripcion']): ?>
                    <tr class="desc-row" id="desc-<?php echo $p['id']; ?>" style="display:none;">
                        <td colspan="8" style="padding:0;">
                            <div style="padding:14px 22px 18px 60px;
                                        background:rgba(0,194,255,.04);
                                        border-top:1px dashed rgba(0,194,255,.1);
                                        border-bottom:1px solid rgba(255,255,255,.05);">
                                <div style="font-size:.7rem;font-weight:700;letter-spacing:2px;
                                            text-transform:uppercase;color:var(--accent);margin-bottom:8px;">
                                    <i class="fas fa-align-left me-2"></i>Descripción completa
                                </div>
                                <div style="font-size:.88rem;color:rgba(255,255,255,.7);line-height:1.7;">
                                    <?php echo nl2br(htmlspecialchars($p['descripcion'])); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <h4>Sin productos registrados</h4>
                                <p>Usa el formulario de arriba para agregar tu primer producto.</p>
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
<script>
    /* ── Expandir descripción ── */
    function toggleDesc(id) {
        const row   = document.getElementById('desc-' + id);
        const arrow = document.getElementById('arrow-' + id);
        if (!row) return;
        const open = row.style.display === 'table-row';
        row.style.display = open ? 'none' : 'table-row';
        if (arrow) arrow.classList.toggle('open', !open);
    }

    /* ── Buscador ── */
    function buscarProductos() {
        const filter = document.getElementById('buscador').value.toUpperCase();
        const rows   = document.querySelectorAll('#tablaProductos tbody tr.prod-row');
        let count    = 0;
        rows.forEach(tr => {
            const txt = tr.textContent.toUpperCase();
            const show = txt.includes(filter);
            tr.style.display = show ? '' : 'none';
            // Ocultar también la fila de descripción si el producto se oculta
            const id = tr.getAttribute('onclick').match(/\d+/)[0];
            const descRow = document.getElementById('desc-' + id);
            if (descRow) descRow.style.display = 'none';
            if (show) count++;
        });
        document.getElementById('mostrando').textContent = count;
    }

    window.addEventListener('load', () => {
        const rows = document.querySelectorAll('#tablaProductos tbody tr.prod-row');
        document.getElementById('mostrando').textContent = rows.length;
    });

    /* ── Confirmar eliminación ── */
    function confirmarEliminar(id, nombre) {
        if (confirm(`¿Eliminar el producto "${nombre}"?\n\nSi tiene ventas, solo se desactivará.`)) {
            window.location.href = '?eliminar=' + id;
        }
    }

    /* ── Ripple táctil ── */
    document.querySelectorAll('.btn-tv, .btn-act').forEach(el => {
        el.addEventListener('pointerdown', function(e) {
            const r = document.createElement('span');
            const d = Math.max(this.clientWidth, this.clientHeight) * 2;
            const rect = this.getBoundingClientRect();
            r.style.cssText = `position:absolute;border-radius:50%;width:${d}px;height:${d}px;
                left:${e.clientX-rect.left-d/2}px;top:${e.clientY-rect.top-d/2}px;
                background:rgba(255,255,255,.15);transform:scale(0);
                animation:rpl .5s linear forwards;pointer-events:none;z-index:10;`;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(r);
            setTimeout(() => r.remove(), 600);
        });
    });
    const s = document.createElement('style');
    s.textContent = `@keyframes rpl { to { transform:scale(1); opacity:0; } }`;
    document.head.appendChild(s);
</script>
</body>
</html>