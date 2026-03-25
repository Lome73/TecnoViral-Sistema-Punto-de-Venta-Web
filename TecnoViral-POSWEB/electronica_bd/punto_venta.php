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

// ── PROCESAR VENTA ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'procesar_venta') {
    $productos_json = $_POST['productos_json'];
    $metodo_pago    = mysqli_real_escape_string($conn, $_POST['metodo_pago']);
    $descuento_raw  = floatval($_POST['descuento']);
    // Límite de descuento según rol
    $max_descuento  = $_SESSION['user_rol'] == 'administrador' ? 100 : ($_SESSION['user_rol'] == 'supervisor' ? 20 : 10);
    $descuento      = min($descuento_raw, $max_descuento);
    $total          = floatval($_POST['total']);
    $productos      = json_decode($productos_json, true);

    if (empty($productos)) {
        echo json_encode(['success' => false, 'msg' => 'No hay productos en la venta.']);
        exit();
    }

    // Generar folio único
    $folio = 'TV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Insertar venta
    $q_venta = "INSERT INTO ventas (folio, id_usuario, total, metodo_pago, estado)
                VALUES ('$folio', $user_id, $total, '$metodo_pago', 'completada')";

    if (!mysqli_query($conn, $q_venta)) {
        echo json_encode(['success' => false, 'msg' => 'Error al registrar venta: ' . mysqli_error($conn)]);
        exit();
    }

    $id_venta = mysqli_insert_id($conn);

    // ── VALIDAR STOCK EN SERVIDOR antes de descontar ──
    foreach ($productos as $p) {
        $id_prod  = intval($p['id']);
        $cantidad = intval($p['cantidad']);
        $r_stk = mysqli_query($conn, "SELECT nombre, stock FROM productos WHERE id = $id_prod AND activo = 1");
        $row_stk = mysqli_fetch_assoc($r_stk);
        if (!$row_stk || $row_stk['stock'] < $cantidad) {
            // Revertir la venta insertada
            mysqli_query($conn, "DELETE FROM ventas WHERE id = $id_venta");
            $disp = $row_stk ? $row_stk['stock'] : 0;
            $nombre_err = $row_stk ? mysqli_real_escape_string($conn, $row_stk['nombre']) : "ID $id_prod";
            echo json_encode(['success' => false, 'msg' => "Stock insuficiente para \"$nombre_err\". Disponible: $disp, solicitado: $cantidad."]);
            exit();
        }
    }

    // Insertar detalles y descontar stock
    foreach ($productos as $p) {
        $id_prod   = intval($p['id']);
        $cantidad  = intval($p['cantidad']);
        $precio    = floatval($p['precio']);
        $subtotal  = $precio * $cantidad;

        mysqli_query($conn, "INSERT INTO detalles_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                             VALUES ($id_venta, $id_prod, $cantidad, $precio, $subtotal)");

        // Descontar stock con protección extra: nunca bajar de 0
        mysqli_query($conn, "UPDATE productos SET stock = stock - $cantidad WHERE id = $id_prod AND stock >= $cantidad");

        // Registrar movimiento
        $nom = mysqli_real_escape_string($conn, $p['nombre']);
        mysqli_query($conn, "INSERT INTO movimientos_inventario (id_producto, id_usuario, tipo_movimiento, cantidad, motivo)
                             VALUES ($id_prod, $user_id, 'salida', $cantidad, 'Venta folio $folio')");
    }

    echo json_encode(['success' => true, 'folio' => $folio, 'id_venta' => $id_venta]);
    exit();
}

// ── OBTENER STOCK ACTUAL (AJAX para refrescar catálogo) ──
if (isset($_GET['get_stock'])) {
    $r = mysqli_query($conn, "SELECT id, stock FROM productos WHERE activo = 1");
    $stocks = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $stocks[(int)$row['id']] = (int)$row['stock'];
    }
    echo json_encode($stocks);
    exit();
}

// ── BUSCAR PRODUCTOS (AJAX) ──
if (isset($_GET['buscar'])) {
    $term = mysqli_real_escape_string($conn, $_GET['buscar']);
    $q = "SELECT p.id, p.nombre, p.marca, p.precio, p.stock, c.nombre as categoria
          FROM productos p
          LEFT JOIN categorias c ON p.id_categoria = c.id
          WHERE p.activo = 1 AND p.stock > 0
          AND (p.nombre LIKE '%$term%' OR p.marca LIKE '%$term%' OR p.id LIKE '%$term%')
          LIMIT 8";
    $r = mysqli_query($conn, $q);
    $res = [];
    while ($row = mysqli_fetch_assoc($r)) $res[] = $row;
    echo json_encode($res);
    exit();
}

// Obtener todos los productos para el catálogo
$result_productos = mysqli_query($conn, "SELECT p.*, c.nombre as categoria FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id WHERE p.activo = 1 AND p.stock > 0 ORDER BY p.nombre ASC");
$result_categorias = mysqli_query($conn, "SELECT * FROM categorias ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Punto de Venta</title>

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
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--navy); color: var(--white); overflow-x: hidden; }

        .bg-canvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; background: radial-gradient(ellipse at 10% 15%, rgba(0,194,255,.1) 0%, transparent 50%), radial-gradient(ellipse at 88% 80%, rgba(0,82,204,.15) 0%, transparent 50%), radial-gradient(ellipse at 50% 50%, var(--navy) 0%, #050e1e 100%); }
        .bg-grid   { position: fixed; inset: 0; z-index: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.022) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.022) 1px, transparent 1px); background-size: 48px 48px; }

        .page-wrap { position: relative; z-index: 1; padding: 20px 24px 40px; max-width: 1800px; margin: 0 auto; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .anim { animation: fadeUp .5s ease both; }

        /* TOPBAR */
        .topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; background:rgba(13,35,71,.7); backdrop-filter:blur(16px); border:1px solid var(--border); border-radius:20px; padding:12px 20px; margin-bottom:20px; box-shadow:0 8px 32px rgba(0,0,0,.4); }
        .brand-row { display:flex; align-items:center; gap:12px; }
        .logo-img  { width:58px; height:58px; border-radius:15px; object-fit:cover; border:2px solid rgba(0,194,255,.3); }
        .brand-name { font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:900; letter-spacing:3px; }
        .brand-name span { color:var(--accent); }
        .brand-sub { font-size:.62rem; letter-spacing:3px; text-transform:uppercase; color:rgba(255,255,255,.35); margin-top:2px; }
        .page-pill { display:flex; align-items:center; gap:8px; background:rgba(0,82,204,.1); border:1px solid rgba(0,82,204,.25); border-radius:40px; padding:7px 18px; }
        .page-pill i { color:var(--accent); font-size:.85rem; }
        .page-pill span { font-size:.75rem; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.75); }
        .user-chip { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:50px; padding:5px 14px 5px 5px; }
        .user-avatar { width:34px; height:34px; background:linear-gradient(135deg,var(--blue),var(--accent)); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; }
        .u-name { font-size:.8rem; font-weight:600; }
        .u-role { font-size:.6rem; color:var(--accent); text-transform:uppercase; letter-spacing:1px; }
        .btn-back { width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); color:rgba(255,255,255,.7); display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all .25s; }
        .btn-back:hover { background:var(--blue); color:white; transform:translateX(-3px); }

        /* LAYOUT PRINCIPAL */
        .pos-layout { display:grid; grid-template-columns: 1fr 380px; gap:20px; align-items:start; }

        /* PANEL IZQUIERDO — Productos */
        .panel-productos { display:flex; flex-direction:column; gap:16px; }

        /* Buscador */
        .search-box { position:relative; }
        .search-box input {
            width:100%; padding:14px 16px 14px 48px;
            border:1px solid rgba(255,255,255,.12); border-radius:16px;
            font-size:.95rem; font-family:'DM Sans',sans-serif;
            background:rgba(255,255,255,.06); color:var(--white);
            outline:none; transition:all .25s;
        }
        .search-box input::placeholder { color:rgba(255,255,255,.25); }
        .search-box input:focus { border-color:var(--accent); background:rgba(0,194,255,.05); box-shadow:0 0 0 4px rgba(0,194,255,.1); }
        .search-box .search-ico { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:1rem; pointer-events:none; }
        .search-box .search-clear { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,.3); cursor:pointer; font-size:.9rem; display:none; }
        .search-box .search-clear:hover { color:var(--danger); }

        /* Resultados búsqueda */
        .search-results {
            position:absolute; top:calc(100% + 6px); left:0; right:0;
            background:var(--navy2); border:1px solid rgba(255,255,255,.12);
            border-radius:16px; z-index:100; overflow:hidden;
            box-shadow:0 16px 40px rgba(0,0,0,.5);
            display:none;
        }
        .search-result-item { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; cursor:pointer; transition:background .2s; border-bottom:1px solid rgba(255,255,255,.05); }
        .search-result-item:last-child { border-bottom:none; }
        .search-result-item:hover { background:rgba(0,194,255,.08); }
        .sri-nombre { font-weight:600; font-size:.9rem; }
        .sri-marca  { font-size:.72rem; color:rgba(255,255,255,.4); margin-top:2px; }
        .sri-precio { font-family:'Playfair Display',serif; color:var(--accent); font-size:.95rem; }
        .sri-stock  { font-size:.72rem; color:rgba(255,255,255,.35); }

        /* Filtro categorías */
        .cat-filter { display:flex; gap:8px; flex-wrap:wrap; }
        .cat-btn { padding:7px 16px; border-radius:20px; font-size:.78rem; font-weight:600; border:1px solid rgba(255,255,255,.1); background:rgba(255,255,255,.04); color:rgba(255,255,255,.55); cursor:pointer; transition:all .2s; }
        .cat-btn:hover, .cat-btn.active { background:rgba(0,194,255,.15); border-color:var(--accent); color:var(--accent); }

        /* Grid productos */
        .productos-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:12px; max-height:calc(100vh - 380px); overflow-y:auto; padding-right:4px; }
        .productos-grid::-webkit-scrollbar { width:4px; }
        .productos-grid::-webkit-scrollbar-track { background:transparent; }
        .productos-grid::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }

        .prod-btn {
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
            border-radius:18px; padding:16px; cursor:pointer;
            transition:all .25s; text-align:left; color:var(--white);
            position:relative; overflow:visible;
        }
        .prod-btn:hover { background:rgba(0,194,255,.08); border-color:rgba(0,194,255,.25); transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.3); }
        .prod-btn:active { transform:scale(.97); }
        .prod-btn.sin-stock { opacity:.4; cursor:not-allowed; }
        .prod-btn::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--blue),var(--accent)); border-radius:4px 4px 0 0; }
        .prod-qty-badge {
            position:absolute; top:8px; left:8px;
            background:var(--accent); color:var(--navy);
            font-size:.7rem; font-weight:700;
            width:22px; height:22px; border-radius:50%;
            align-items:center; justify-content:center;
            box-shadow:0 2px 8px rgba(0,194,255,.4);
            display:none;
        }

        .pb-nombre  { font-weight:700; font-size:.88rem; margin-bottom:4px; line-height:1.3; }
        .pb-marca   { font-size:.7rem; color:rgba(255,255,255,.4); margin-bottom:10px; }
        .pb-precio  { font-family:'Playfair Display',serif; font-size:1.1rem; color:var(--accent); }
        .pb-stock   { font-size:.7rem; color:rgba(255,255,255,.35); margin-top:4px; }
        .pb-cat     { position:absolute; top:10px; right:10px; font-size:.62rem; background:rgba(0,82,204,.2); border:1px solid rgba(0,82,204,.3); color:rgba(255,255,255,.5); padding:2px 8px; border-radius:10px; }

        /* PANEL DERECHO — Carrito */
        .panel-carrito {
            background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.09);
            border-radius:24px; padding:20px;
            position:sticky; top:20px;
            backdrop-filter:blur(10px);
            box-shadow:0 8px 40px rgba(0,0,0,.3);
        }

        .carrito-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid rgba(255,255,255,.07); }
        .carrito-title  { font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:8px; }
        .carrito-title i { color:var(--accent); }
        .carrito-count  { background:var(--accent); color:var(--navy); font-size:.7rem; font-weight:700; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; }

        .btn-limpiar { background:rgba(255,77,77,.12); border:1px solid rgba(255,77,77,.2); color:var(--danger); border-radius:10px; padding:6px 12px; font-size:.75rem; font-weight:600; cursor:pointer; transition:all .2s; }
        .btn-limpiar:hover { background:var(--danger); color:white; }

        /* Items carrito */
        .carrito-items { max-height:320px; overflow-y:auto; margin-bottom:14px; padding-right:2px; }
        .carrito-items::-webkit-scrollbar { width:3px; }
        .carrito-items::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1); border-radius:4px; }

        .carrito-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.05); }
        .carrito-item:last-child { border-bottom:none; }
        .ci-nombre { flex:1; font-size:.83rem; font-weight:600; line-height:1.3; }
        .ci-precio { font-size:.75rem; color:rgba(255,255,255,.4); }
        .ci-subtotal { font-family:'Playfair Display',serif; font-size:.9rem; color:var(--success); min-width:60px; text-align:right; }

        .qty-ctrl { display:flex; align-items:center; gap:6px; }
        .qty-btn  { width:26px; height:26px; border-radius:8px; border:none; cursor:pointer; font-size:.85rem; font-weight:700; display:flex; align-items:center; justify-content:center; transition:all .2s; }
        .qty-minus { background:rgba(255,77,77,.15); color:var(--danger); }
        .qty-minus:hover { background:var(--danger); color:white; }
        .qty-plus  { background:rgba(0,214,143,.15); color:var(--success); }
        .qty-plus:hover  { background:var(--success); color:white; }
        .qty-num   { font-weight:700; font-size:.88rem; min-width:24px; text-align:center; }

        /* Vacío */
        .carrito-empty { text-align:center; padding:40px 20px; color:rgba(255,255,255,.25); }
        .carrito-empty i { font-size:2.5rem; margin-bottom:10px; display:block; opacity:.3; }
        .carrito-empty p { font-size:.82rem; }

        /* Totales */
        .totales-box { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:16px; padding:14px 16px; margin-bottom:14px; }
        .total-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; font-size:.85rem; color:rgba(255,255,255,.6); }
        .total-row:last-child { margin-bottom:0; }
        .total-row.grande { font-size:1.1rem; color:var(--white); font-weight:700; padding-top:8px; border-top:1px solid rgba(255,255,255,.08); margin-top:4px; }
        .total-row.grande .val { font-family:'Playfair Display',serif; font-size:1.4rem; color:var(--success); }

        /* Descuento */
        .descuento-wrap { display:flex; align-items:center; gap:8px; margin-bottom:14px; }
        .descuento-wrap label { font-size:.72rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.4); white-space:nowrap; }
        .descuento-input { flex:1; position:relative; }
        .descuento-input input { width:100%; padding:10px 32px 10px 14px; border:1px solid rgba(255,255,255,.1); border-radius:12px; background:rgba(255,255,255,.05); color:var(--white); font-size:.9rem; font-family:'DM Sans',sans-serif; outline:none; transition:all .25s; }
        .descuento-input input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(245,197,24,.1); }
        .descuento-input span { position:absolute; right:10px; top:50%; transform:translateY(-50%); color:var(--gold); font-size:.85rem; font-weight:700; }

        /* Método pago */
        .metodo-selector { display:flex; gap:8px; margin-bottom:14px; }
        .metodo-opt { flex:1; }
        .metodo-opt input[type="radio"] { display:none; }
        .metodo-opt label { display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 6px; border:1px solid rgba(255,255,255,.1); border-radius:14px; cursor:pointer; transition:all .2s; background:rgba(255,255,255,.03); font-size:.7rem; font-weight:600; color:rgba(255,255,255,.5); text-align:center; }
        .metodo-opt label i { font-size:1.1rem; }
        .metodo-opt input[type="radio"]:checked + label.lbl-efectivo { border-color:var(--success); background:rgba(0,214,143,.12); color:var(--success); }
        .metodo-opt input[type="radio"]:checked + label.lbl-debito  { border-color:var(--accent); background:rgba(0,194,255,.12); color:var(--accent); }
        .metodo-opt input[type="radio"]:checked + label.lbl-credito { border-color:var(--gold);   background:rgba(245,197,24,.1);  color:var(--gold); }
        .metodo-opt input[type="radio"]:checked + label.lbl-paypal  { border-color:#4db3ff;       background:rgba(0,112,243,.12);  color:#4db3ff; }

        /* Efectivo / cambio */
        .efectivo-box { background:rgba(0,214,143,.05); border:1px solid rgba(0,214,143,.15); border-radius:14px; padding:12px 14px; margin-bottom:14px; display:none; }
        .efectivo-box label { font-size:.68rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:6px; display:block; }
        .efectivo-box input { width:100%; padding:10px 14px; border:1px solid rgba(0,214,143,.2); border-radius:10px; background:rgba(0,214,143,.06); color:var(--white); font-size:1rem; font-family:'Playfair Display',serif; outline:none; }
        .efectivo-box input:focus { border-color:var(--success); }
        .cambio-row { display:flex; justify-content:space-between; align-items:center; margin-top:10px; padding-top:10px; border-top:1px solid rgba(0,214,143,.15); }
        .cambio-lbl { font-size:.72rem; color:rgba(255,255,255,.45); text-transform:uppercase; letter-spacing:1px; }
        .cambio-val { font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; color:var(--success); }

        /* Botón cobrar */
        .btn-cobrar { width:100%; padding:16px; background:linear-gradient(135deg,#00875a,var(--success)); color:white; border:none; border-radius:16px; font-family:'DM Sans',sans-serif; font-size:.95rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; cursor:pointer; transition:all .3s; box-shadow:0 8px 24px rgba(0,214,143,.25); display:flex; align-items:center; justify-content:center; gap:10px; }
        .btn-cobrar:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 12px 32px rgba(0,214,143,.35); }
        .btn-cobrar:active:not(:disabled) { transform:scale(.98); }
        .btn-cobrar:disabled { background:rgba(255,255,255,.08); color:rgba(255,255,255,.3); cursor:not-allowed; box-shadow:none; }

        /* MODAL TICKET */
        .modal-dark .modal-content { background:var(--navy2); border:1px solid rgba(255,255,255,.1); border-radius:20px; color:white; }
        .modal-dark .modal-header  { border-bottom:1px solid rgba(255,255,255,.08); padding:18px 22px; }
        .modal-dark .modal-footer  { border-top:1px solid rgba(255,255,255,.08); padding:14px 22px; }
        .modal-dark .modal-title   { font-family:'Playfair Display',serif; font-size:1.1rem; }
        .modal-dark .btn-close     { filter:invert(1); opacity:.5; }

        /* Ticket imprimible */
        .ticket {
            background:white; color:#000; padding:20px;
            font-family:'Courier New', monospace; font-size:12px;
            max-width:300px; margin:0 auto; border-radius:8px;
        }
        .ticket-header { text-align:center; margin-bottom:12px; border-bottom:1px dashed #ccc; padding-bottom:10px; }
        .ticket-logo   { font-size:18px; font-weight:700; letter-spacing:2px; }
        .ticket-sub    { font-size:11px; color:#666; }
        .ticket-folio  { font-size:13px; font-weight:700; margin-top:6px; }
        .ticket-table  { width:100%; margin:10px 0; border-collapse:collapse; }
        .ticket-table th { font-size:10px; border-bottom:1px dashed #ccc; padding:4px 2px; text-align:left; }
        .ticket-table td { font-size:11px; padding:4px 2px; vertical-align:top; }
        .ticket-totales { border-top:1px dashed #ccc; margin-top:8px; padding-top:8px; }
        .ticket-totales .t-row { display:flex; justify-content:space-between; margin-bottom:4px; font-size:12px; }
        .ticket-totales .t-row.grande { font-weight:700; font-size:14px; margin-top:4px; border-top:1px dashed #ccc; padding-top:4px; }
        .ticket-footer { text-align:center; margin-top:12px; border-top:1px dashed #ccc; padding-top:10px; font-size:10px; color:#666; }

        /* Responsive */
        @media (max-width: 1100px) { .pos-layout { grid-template-columns:1fr; } .panel-carrito { position:static; } }
        @media (max-width: 768px)  { .page-wrap { padding:12px 10px 30px; } .page-pill { display:none; } .productos-grid { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<div class="bg-canvas"></div>
<div class="bg-grid"></div>

<div class="page-wrap">

    <!-- TOPBAR -->
    <div class="topbar anim">
        <a href="menu_principal.php" class="brand-row" style="text-decoration:none;">
            <img src="imagenes/logoe.jpeg" alt="TecnoViral" class="logo-img"
                 onerror="this.src='https://placehold.co/58x58/0052cc/fff?text=TV'">
            <div>
                <div class="brand-name">TECNO<span>VIRAL</span></div>
                <div class="brand-sub">Punto de Venta · Sistema Táctil</div>
            </div>
        </a>
        <div class="page-pill">
            <i class="fas fa-cash-register"></i>
            <span>Punto de Venta</span>
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

    <!-- POS LAYOUT -->
    <div class="pos-layout anim">

        <!-- ══ PANEL IZQUIERDO ══ -->
        <div class="panel-productos">

            <!-- Buscador -->
            <div class="search-box" id="searchBox">
                <i class="fas fa-magnifying-glass search-ico"></i>
                <input type="text" id="searchInput" placeholder="Buscar producto por nombre, marca o código..." autocomplete="off">
                <i class="fas fa-xmark search-clear" id="searchClear" onclick="limpiarBusqueda()"></i>
                <div class="search-results" id="searchResults"></div>
            </div>

            <!-- Filtro categorías -->
            <div class="cat-filter">
                <button class="cat-btn active" onclick="filtrarCat('todas', this)">
                    <i class="fas fa-th me-1"></i> Todos
                </button>
                <?php
                mysqli_data_seek($result_categorias, 0);
                while ($cat = mysqli_fetch_assoc($result_categorias)):
                ?>
                <button class="cat-btn" onclick="filtrarCat('<?php echo $cat['id']; ?>', this)">
                    <?php echo htmlspecialchars($cat['nombre']); ?>
                </button>
                <?php endwhile; ?>
            </div>

            <!-- Grid de productos -->
            <div class="productos-grid" id="productosGrid">
                <?php
                mysqli_data_seek($result_productos, 0);
                while ($p = mysqli_fetch_assoc($result_productos)):
                ?>
                <button class="prod-btn" data-id="<?php echo $p['id']; ?>"
                        data-cat="<?php echo $p['id_categoria']; ?>"
                        data-nombre="<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-stock="<?php echo $p['stock']; ?>"
                        onclick="agregarDesdeBtn(this)">
                    <span class="pb-cat"><?php echo htmlspecialchars($p['categoria'] ?? ''); ?></span>
                    <div class="pb-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="pb-marca"><i class="fas fa-tag" style="font-size:.6rem;"></i> <?php echo htmlspecialchars($p['marca']); ?></div>
                    <div class="pb-precio">$<?php echo number_format($p['precio'], 2); ?></div>
                    <div class="pb-stock"><i class="fas fa-cubes" style="font-size:.65rem;"></i> <?php echo $p['stock']; ?> disponibles</div>
                </button>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- ══ PANEL DERECHO — CARRITO ══ -->
        <div class="panel-carrito">
            <div class="carrito-header">
                <div class="carrito-title">
                    <i class="fas fa-shopping-cart"></i>
                    Venta actual
                    <span class="carrito-count" id="carritoCount">0</span>
                </div>
                <button class="btn-limpiar" onclick="limpiarCarrito()">
                    <i class="fas fa-trash-can me-1"></i> Limpiar
                </button>
            </div>

            <!-- Items -->
            <div class="carrito-items" id="carritoItems">
                <div class="carrito-empty" id="carritoEmpty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Selecciona productos del catálogo</p>
                </div>
            </div>

            <!-- Descuento -->
            <div class="descuento-wrap">
                <label>Descuento</label>
                <div class="descuento-input">
                    <input type="number" id="descuento" min="0" max="<?php echo $user_rol == 'administrador' ? '100' : ($user_rol == 'supervisor' ? '20' : '10'); ?>"
                           value="0" placeholder="0" oninput="calcularTotales()">
                    <?php if ($user_rol == 'vendedor'): ?>
                    <div style="font-size:.65rem;color:rgba(255,165,0,.7);margin-top:4px;letter-spacing:1px;">
                        <i class="fas fa-lock" style="font-size:.6rem;"></i> Máx. 10% para vendedor
                    </div>
                    <?php elseif ($user_rol == 'supervisor'): ?>
                    <div style="font-size:.65rem;color:rgba(52,152,219,.7);margin-top:4px;letter-spacing:1px;">
                        <i class="fas fa-lock" style="font-size:.6rem;"></i> Máx. 20% para supervisor
                    </div>
                    <?php endif; ?>
                    <span>%</span>
                </div>
            </div>

            <!-- Totales -->
            <div class="totales-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span id="subtotalVal">$0.00</span>
                </div>
                <div class="total-row">
                    <span>Descuento</span>
                    <span id="descuentoVal" style="color:var(--danger);">-$0.00</span>
                </div>
                <div class="total-row grande">
                    <span>TOTAL</span>
                    <span class="val" id="totalVal">$0.00</span>
                </div>
            </div>

            <!-- Método de pago -->
            <div class="metodo-selector">
                <div class="metodo-opt">
                    <input type="radio" name="metodo" id="m-efectivo" value="efectivo" checked>
                    <label for="m-efectivo" class="lbl-efectivo" onclick="seleccionarMetodo('efectivo')">
                        <i class="fas fa-money-bill-wave"></i>
                        Efectivo
                    </label>
                </div>
                <div class="metodo-opt">
                    <input type="radio" name="metodo" id="m-debito" value="debito">
                    <label for="m-debito" class="lbl-debito" onclick="seleccionarMetodo('debito')">
                        <i class="fas fa-credit-card"></i>
                        Débito
                    </label>
                </div>
                <div class="metodo-opt">
                    <input type="radio" name="metodo" id="m-credito" value="credito">
                    <label for="m-credito" class="lbl-credito" onclick="seleccionarMetodo('credito')">
                        <i class="fas fa-credit-card"></i>
                        Crédito
                    </label>
                </div>
                <div class="metodo-opt">
                    <input type="radio" name="metodo" id="m-paypal" value="paypal">
                    <label for="m-paypal" class="lbl-paypal" onclick="seleccionarMetodo('paypal')">
                        <i class="fab fa-paypal"></i>
                        PayPal
                    </label>
                </div>
            </div>

            <!-- Caja efectivo / cambio -->
            <div class="efectivo-box" id="efectivoBox">
                <label>Pago con</label>
                <input type="number" id="pagoCon" placeholder="$0.00" oninput="calcularCambio()" min="0">
                <div class="cambio-row">
                    <span class="cambio-lbl"><i class="fas fa-coins me-1"></i> Cambio</span>
                    <span class="cambio-val" id="cambioVal">$0.00</span>
                </div>
            </div>

            <!-- Botón cobrar -->
            <button class="btn-cobrar" id="btnCobrar" onclick="procesarVenta()" disabled>
                <i class="fas fa-check-circle"></i>
                COBRAR
            </button>
        </div>
    </div>
</div>

<!-- MODAL TICKET -->
<div class="modal fade modal-dark" id="modalTicket" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2" style="color:var(--success);"></i> Venta Completada</h5>
            </div>
            <div class="modal-body p-3">
                <div id="ticketContent"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cobrar" style="flex:1;padding:12px;" onclick="imprimirTicket()">
                    <i class="fas fa-print"></i> Imprimir Ticket
                </button>
                <button type="button" style="flex:1;padding:12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.7);border-radius:16px;cursor:pointer;font-family:'DM Sans',sans-serif;font-weight:600;"
                    onclick="nuevaVenta()">
                    <i class="fas fa-plus me-2"></i>Nueva Venta
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // ── Estado del carrito ──
    let carrito = [];
    let totalFinal = 0;
    let metodoPagoSeleccionado = 'efectivo'; // Variable global confiable para el método de pago

    // ── SELECCIONAR MÉTODO DE PAGO ──
    function seleccionarMetodo(metodo) {
        metodoPagoSeleccionado = metodo;
        // Forzar el check del radio correspondiente
        const radio = document.getElementById('m-' + metodo);
        if (radio) radio.checked = true;
        mostrarEfectivo();
    }

    // ── AGREGAR DESDE BOTÓN (usa data attributes para evitar problemas con caracteres especiales) ──
    function agregarDesdeBtn(btn) {
        const id     = parseInt(btn.dataset.id);
        const nombre = btn.dataset.nombre;
        const precio = parseFloat(btn.dataset.precio);
        const stock  = parseInt(btn.dataset.stock);
        agregarAlCarrito(id, nombre, precio, stock);
    }

    // ── AGREGAR AL CARRITO ──
    function agregarAlCarrito(id, nombre, precio, stock) {
        id     = parseInt(id);
        precio = parseFloat(precio);
        stock  = parseInt(stock);

        if (isNaN(id) || isNaN(precio) || id <= 0) return;

        const idx = carrito.findIndex(i => i.id === id);
        if (idx >= 0) {
            if (carrito[idx].cantidad >= stock) {
                mostrarToastError(`Solo hay ${stock} unidades disponibles de "${nombre}"`);
                return;
            }
            carrito[idx].cantidad++;
        } else {
            carrito.push({ id, nombre, precio, stock, cantidad: 1 });
        }
        renderCarrito();
        // Feedback visual en el botón
        document.querySelectorAll('.prod-btn').forEach(btn => {
            if (parseInt(btn.dataset.id) === id) {
                btn.style.transform = 'scale(0.96)';
                setTimeout(() => btn.style.transform = '', 150);
            }
        });
    }

    // ── RENDER CARRITO ──
    function renderCarrito() {
        const container = document.getElementById('carritoItems');
        const count     = document.getElementById('carritoCount');
        if (!container) return;

        if (carrito.length === 0) {
            container.innerHTML = `
                <div class="carrito-empty" id="carritoEmpty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Selecciona productos del catálogo</p>
                </div>`;
            document.getElementById('btnCobrar').disabled = true;
            if (count) count.textContent = '0';
            actualizarBadges();
            calcularTotales();
            return;
        }

        let html = '';
        let totalItems = 0;
        carrito.forEach((item, idx) => {
            totalItems += item.cantidad;
            const nombre = item.nombre.replace(/'/g, "\'").replace(/"/g, '&quot;');
            html += `
            <div class="carrito-item" data-idx="${idx}">
                <div style="flex:1;min-width:0;">
                    <div class="ci-nombre" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${item.nombre}</div>
                    <div class="ci-precio">$${parseFloat(item.precio).toFixed(2)} c/u</div>
                </div>
                <div class="qty-ctrl">
                    <button class="qty-btn qty-minus" onclick="cambiarCantidad(${idx}, -1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="qty-num">${item.cantidad}</span>
                    <button class="qty-btn qty-plus" onclick="cambiarCantidad(${idx}, +1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="ci-subtotal">$${(item.precio * item.cantidad).toFixed(2)}</div>
                <button onclick="quitarItem(${idx})"
                    style="background:none;border:none;color:rgba(255,77,77,.5);cursor:pointer;padding:0 0 0 6px;font-size:.85rem;flex-shrink:0;"
                    onmouseover="this.style.color='var(--danger)'"
                    onmouseout="this.style.color='rgba(255,77,77,.5)'">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>`;
        });

        container.innerHTML = html;
        if (count) count.textContent = totalItems;
        document.getElementById('btnCobrar').disabled = false;

        actualizarBadges();
        calcularTotales();
    }

    // ── ACTUALIZAR BADGES DEL CATÁLOGO ──
    function actualizarBadges() {
        document.querySelectorAll('.prod-btn').forEach(btn => {
            const id   = parseInt(btn.dataset.id);
            const item = carrito.find(i => i.id === id);
            let badge  = btn.querySelector('.prod-qty-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'prod-qty-badge';
                btn.appendChild(badge);
            }
            if (item && item.cantidad > 0) {
                badge.textContent = item.cantidad;
                badge.style.display = 'flex';
                btn.style.borderColor = 'rgba(0,194,255,.4)';
                btn.style.background  = 'rgba(0,194,255,.1)';
            } else {
                badge.style.display = 'none';
                btn.style.borderColor = '';
                btn.style.background  = '';
            }
        });
    }

    // ── TOAST ERROR ──
    function mostrarToastError(msg) {
        let t = document.getElementById('toastError');
        if (!t) {
            t = document.createElement('div');
            t.id = 'toastError';
            t.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:rgba(255,77,77,.95);color:white;border-radius:12px;padding:12px 20px;font-size:.85rem;font-weight:600;z-index:9999;opacity:0;transition:all .3s;white-space:nowrap;box-shadow:0 8px 24px rgba(255,77,77,.3);';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.style.opacity = '1';
        t.style.transform = 'translateX(-50%) translateY(0)';
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transform = 'translateX(-50%) translateY(80px)';
        }, 2500);
    }

    function cambiarCantidad(idx, delta) {
        idx = parseInt(idx);
        const item = carrito[idx];
        if (!item) return;
        const nueva = item.cantidad + delta;
        if (nueva <= 0) { quitarItem(idx); return; }
        if (nueva > item.stock) { alert(`Solo hay ${item.stock} unidades disponibles.`); return; }
        carrito[idx].cantidad = nueva;
        renderCarrito();
    }

    function quitarItem(idx) {
        carrito.splice(idx, 1);
        renderCarrito();
    }

    function limpiarCarrito() {
        if (carrito.length === 0) return;
        if (confirm('¿Limpiar todos los productos del carrito?')) {
            carrito = [];
            document.querySelectorAll('.prod-qty-badge').forEach(b => b.style.display = 'none');
            document.querySelectorAll('.prod-btn').forEach(b => { b.style.borderColor = ''; b.style.background = ''; });
            renderCarrito();
        }
    }

    // ── CALCULAR TOTALES ──
    function calcularTotales() {
        const subtotal   = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
        const maxDesc    = <?php echo $user_rol == 'administrador' ? '100' : ($user_rol == 'supervisor' ? '20' : '10'); ?>;
        let descPct      = parseFloat(document.getElementById('descuento').value) || 0;
        if (descPct > maxDesc) {
            descPct = maxDesc;
            document.getElementById('descuento').value = maxDesc;
        }
        const descMonto  = subtotal * (descPct / 100);
        totalFinal       = subtotal - descMonto;

        document.getElementById('subtotalVal').textContent  = `$${subtotal.toFixed(2)}`;
        document.getElementById('descuentoVal').textContent = `-$${descMonto.toFixed(2)}`;
        document.getElementById('totalVal').textContent     = `$${totalFinal.toFixed(2)}`;
        calcularCambio();
    }

    // ── MOSTRAR/OCULTAR EFECTIVO ──
    function mostrarEfectivo() {
        const metodo = metodoPagoSeleccionado;
        const box    = document.getElementById('efectivoBox');
        if (box) box.style.display = metodo === 'efectivo' ? 'block' : 'none';
        if (metodo === 'efectivo') {
            document.getElementById('pagoCon').focus();
        }
        calcularCambio();
    }

    function calcularCambio() {
        const pagado = parseFloat(document.getElementById('pagoCon')?.value) || 0;
        const cambio = pagado - totalFinal;
        const el = document.getElementById('cambioVal');
        if (el) {
            el.textContent = `$${Math.max(0, cambio).toFixed(2)}`;
            el.style.color = cambio >= 0 ? 'var(--success)' : 'var(--danger)';
        }
    }

    // Mostrar efectivo por defecto al cargar
    window.addEventListener('load', () => mostrarEfectivo());

    // ── FILTRAR CATEGORÍAS ──
    function filtrarCat(catId, btn) {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.prod-btn').forEach(p => {
            p.style.display = catId === 'todas' || p.dataset.cat == catId ? '' : 'none';
        });
    }

    // ── BÚSQUEDA ──
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        const val = this.value.trim();
        document.getElementById('searchClear').style.display = val ? 'block' : 'none';
        clearTimeout(searchTimeout);
        if (val.length < 2) { document.getElementById('searchResults').style.display = 'none'; return; }
        searchTimeout = setTimeout(() => buscarProductos(val), 300);
    });

    function buscarProductos(term) {
        fetch(`punto_venta.php?buscar=${encodeURIComponent(term)}`)
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('searchResults');
                if (data.length === 0) { box.style.display = 'none'; return; }
                box.innerHTML = data.map(p => `
                    <div class="search-result-item"
                         data-id="${p.id}" data-nombre="${p.nombre.replace(/"/g,'&quot;')}"
                         data-precio="${p.precio}" data-stock="${p.stock}"
                         onclick="agregarAlCarrito(parseInt(this.dataset.id), this.dataset.nombre, parseFloat(this.dataset.precio), parseInt(this.dataset.stock)); limpiarBusqueda();">
                        <div>
                            <div class="sri-nombre">${p.nombre}</div>
                            <div class="sri-marca">${p.marca} · ${p.categoria ?? ''}</div>
                        </div>
                        <div style="text-align:right;">
                            <div class="sri-precio">$${parseFloat(p.precio).toFixed(2)}</div>
                            <div class="sri-stock">${p.stock} disp.</div>
                        </div>
                    </div>`).join('');
                box.style.display = 'block';
            });
    }

    function limpiarBusqueda() {
        document.getElementById('searchInput').value = '';
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('searchClear').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (!document.getElementById('searchBox').contains(e.target)) {
            document.getElementById('searchResults').style.display = 'none';
        }
    });

    // ── PROCESAR VENTA ──
    function procesarVenta() {
        if (carrito.length === 0) return;
        // Usar la variable global que se actualiza al hacer click en el label
        const metodo = metodoPagoSeleccionado;
        const descuento = parseFloat(document.getElementById('descuento').value) || 0;

        const formData = new FormData();
        formData.append('accion', 'procesar_venta');
        formData.append('productos_json', JSON.stringify(carrito));
        formData.append('metodo_pago', metodo);
        formData.append('descuento', descuento);
        formData.append('total', totalFinal.toFixed(2));

        document.getElementById('btnCobrar').disabled = true;
        document.getElementById('btnCobrar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        fetch('punto_venta.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Refrescar stock del catálogo en tiempo real
                    refrescarStockCatalogo();
                    mostrarTicket(data.folio, metodo, descuento);
                } else {
                    alert('Error: ' + data.msg);
                    // Si el error es por stock, también refrescar para mostrar estado real
                    refrescarStockCatalogo();
                    document.getElementById('btnCobrar').disabled = false;
                    document.getElementById('btnCobrar').innerHTML = '<i class="fas fa-check-circle"></i> COBRAR';
                }
            });
    }

    // ── REFRESCAR STOCK DEL CATÁLOGO ──
    function refrescarStockCatalogo() {
        fetch('punto_venta.php?get_stock=1')
            .then(r => r.json())
            .then(stocks => {
                document.querySelectorAll('.prod-btn').forEach(btn => {
                    const id = parseInt(btn.dataset.id);
                    const nuevoStock = stocks[id];
                    if (nuevoStock === undefined || nuevoStock <= 0) {
                        // Sin stock: ocultar del catálogo y quitar del carrito
                        btn.style.display = 'none';
                        const idx = carrito.findIndex(i => i.id === id);
                        if (idx >= 0) {
                            carrito.splice(idx, 1);
                            renderCarrito();
                        }
                    } else {
                        btn.dataset.stock = nuevoStock;
                        btn.style.display = '';
                        const stockEl = btn.querySelector('.pb-stock');
                        if (stockEl) stockEl.innerHTML = `<i class="fas fa-cubes" style="font-size:.65rem;"></i> ${nuevoStock} disponibles`;
                        // Actualizar cantidad en carrito si excede nuevo stock
                        const idx = carrito.findIndex(i => i.id === id);
                        if (idx >= 0 && carrito[idx].cantidad > nuevoStock) {
                            carrito[idx].cantidad = nuevoStock;
                            renderCarrito();
                        }
                    }
                });
            })
            .catch(() => {}); // silencioso si falla
    }

    // Variables globales del ticket actual
    let ticketMetodo = '';
    let ticketDescPct = 0;
    let ticketFolio = '';

    // ── MOSTRAR TICKET ──
    function mostrarTicket(folio, metodo, descPct) {
        ticketMetodo  = metodo;
        ticketDescPct = descPct;
        ticketFolio   = folio;
        const subtotal  = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
        const descMonto = subtotal * (descPct / 100);
        const total     = subtotal - descMonto;
        const ahora     = new Date();
        const fecha     = ahora.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' });
        const hora      = ahora.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
        const metodoNom = { efectivo:'Efectivo', debito:'Tarjeta Débito', credito:'Tarjeta Crédito', paypal:'PayPal' }[metodo] || metodo;

        let filas = carrito.map(i =>
            `<tr><td>${i.nombre}</td><td style="text-align:center;">${i.cantidad}</td><td style="text-align:right;">$${(i.precio*i.cantidad).toFixed(2)}</td></tr>`
        ).join('');

        document.getElementById('ticketContent').innerHTML = `
        <div class="ticket" id="ticketImprimir">
            <div class="ticket-header">
                <div class="ticket-logo">TECNOVIRAL</div>
                <div class="ticket-sub">Sistema Punto de Venta</div>
                <div class="ticket-folio">Folio: ${folio}</div>
                <div style="font-size:10px;color:#666;">${fecha} ${hora}</div>
            </div>
            <table class="ticket-table">
                <thead><tr><th>Producto</th><th style="text-align:center;">Cant</th><th style="text-align:right;">Total</th></tr></thead>
                <tbody>${filas}</tbody>
            </table>
            <div class="ticket-totales">
                <div class="t-row"><span>Subtotal:</span><span>$${subtotal.toFixed(2)}</span></div>
                ${descPct > 0 ? `<div class="t-row"><span>Descuento (${descPct}%):</span><span>-$${descMonto.toFixed(2)}</span></div>` : ''}
                <div class="t-row grande"><span>TOTAL:</span><span>$${total.toFixed(2)}</span></div>
                <div class="t-row"><span>Método:</span><span>${metodoNom}</span></div>
                ${metodo === 'efectivo' ? `<div class="t-row"><span>Pago con:</span><span>$${parseFloat(document.getElementById('pagoCon').value||0).toFixed(2)}</span></div><div class="t-row grande"><span>CAMBIO:</span><span>$${Math.max(0, parseFloat(document.getElementById('pagoCon').value||0) - total).toFixed(2)}</span></div>` : ''}
            </div>
            <div class="ticket-footer">¡Gracias por su compra!<br>TecnoViral</div>
        </div>`;

        new bootstrap.Modal(document.getElementById('modalTicket')).show();
    }

    function imprimirTicket() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'mm', format: [80, 200] });

        const ahora     = new Date();
        const fecha     = ahora.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' });
        const hora      = ahora.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
        const folioTxt  = 'Folio: ' + ticketFolio;
        const metodo    = ticketMetodo;
        const descPct   = ticketDescPct;
        const metodoNom = { efectivo:'Efectivo', debito:'Tarjeta Débito', credito:'Tarjeta Crédito', paypal:'PayPal' }[metodo] || metodo;
        const pagoCon   = parseFloat(document.getElementById('pagoCon')?.value || 0);

        const subtotal  = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
        const descMonto = subtotal * (descPct / 100);
        const total     = subtotal - descMonto;

        let y = 8;
        const cx = 40; // centro

        // Header
        doc.setFontSize(14); doc.setFont('helvetica','bold');
        doc.text('TECNOVIRAL', cx, y, { align:'center' }); y += 5;
        doc.setFontSize(8); doc.setFont('helvetica','normal');
        doc.text('Sistema Punto de Venta', cx, y, { align:'center' }); y += 4;
        doc.setFontSize(9); doc.setFont('helvetica','bold');
        doc.text(folioTxt, cx, y, { align:'center' }); y += 4;
        doc.setFontSize(7); doc.setFont('helvetica','normal');
        doc.text(`${fecha}  ${hora}`, cx, y, { align:'center' }); y += 5;

        // Línea
        doc.setDrawColor(180); doc.setLineDash([1,1]);
        doc.line(5, y, 75, y); y += 4;

        // Encabezados tabla
        doc.setFontSize(7); doc.setFont('helvetica','bold');
        doc.text('PRODUCTO', 5, y);
        doc.text('CANT', 52, y, { align:'center' });
        doc.text('TOTAL', 75, y, { align:'right' }); y += 3;
        doc.setLineDash([1,1]);
        doc.line(5, y, 75, y); y += 3;

        // Productos
        doc.setFont('helvetica','normal'); doc.setFontSize(8);
        carrito.forEach(item => {
            const nombre = item.nombre.length > 28 ? item.nombre.substring(0,25) + '...' : item.nombre;
            doc.text(nombre, 5, y);
            doc.text(String(item.cantidad), 52, y, { align:'center' });
            doc.text('$' + (item.precio * item.cantidad).toFixed(2), 75, y, { align:'right' });
            y += 5;
        });

        // Totales
        doc.setLineDash([1,1]);
        doc.line(5, y, 75, y); y += 4;
        doc.setFontSize(8);
        doc.text('Subtotal:', 5, y); doc.text('$' + subtotal.toFixed(2), 75, y, { align:'right' }); y += 4;
        if (descPct > 0) {
            doc.text(`Descuento (${descPct}%):`, 5, y); doc.text('-$' + descMonto.toFixed(2), 75, y, { align:'right' }); y += 4;
        }
        doc.setLineDash([1,1]);
        doc.line(5, y, 75, y); y += 4;
        doc.setFontSize(10); doc.setFont('helvetica','bold');
        doc.text('TOTAL:', 5, y); doc.text('$' + total.toFixed(2), 75, y, { align:'right' }); y += 5;
        doc.setFontSize(8); doc.setFont('helvetica','normal');
        doc.text('Método: ' + metodoNom, 5, y); y += 5;
        if (metodo === 'efectivo') {
            doc.text('Pago con: $' + pagoCon.toFixed(2), 5, y); y += 4;
            doc.setFont('helvetica','bold');
            doc.text('Cambio: $' + Math.max(0, pagoCon - total).toFixed(2), 5, y); y += 5;
            doc.setFont('helvetica','normal');
        }

        // Footer
        doc.setLineDash([1,1]);
        doc.line(5, y, 75, y); y += 4;
        doc.setFontSize(7);
        doc.text('¡Gracias por su compra!', cx, y, { align:'center' }); y += 3;
        doc.text('TecnoViral', cx, y, { align:'center' });

        // Descargar
        const folioNom = folioTxt.replace('Folio: ', '').trim() || 'ticket';
        doc.save(`Ticket_${folioNom}.pdf`);
    }

    function nuevaVenta() {
        // Cerrar modal primero
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalTicket'));
        if (modal) modal.hide();
        
        carrito = [];
        metodoPagoSeleccionado = 'efectivo';
        renderCarrito();
        document.getElementById('descuento').value = 0;
        document.getElementById('m-efectivo').checked = true;
        mostrarEfectivo();
        document.getElementById('btnCobrar').disabled = true;
        document.getElementById('btnCobrar').innerHTML = '<i class="fas fa-check-circle"></i> COBRAR';
        bootstrap.Modal.getInstance(document.getElementById('modalTicket')).hide();
        calcularTotales();
    }
</script>
</body>
</html>