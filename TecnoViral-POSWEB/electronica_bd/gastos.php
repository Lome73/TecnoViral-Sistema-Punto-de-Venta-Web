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

// ── REGISTRAR GASTO ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] == 'registrar') {
        $concepto      = mysqli_real_escape_string($conn, trim($_POST['concepto']));
        $monto         = floatval($_POST['monto']);
        $tipo          = mysqli_real_escape_string($conn, $_POST['tipo']);
        $observaciones = mysqli_real_escape_string($conn, trim($_POST['observaciones']));

        if ($concepto == '') {
            $error = "El concepto es obligatorio.";
        } elseif ($monto <= 0) {
            $error = "El monto debe ser mayor a $0.";
        } else {
            $q = "INSERT INTO gastos_dia (id_usuario, concepto, monto, tipo, observaciones)
                  VALUES ($user_id, '$concepto', $monto, '$tipo', '$observaciones')";
            if (mysqli_query($conn, $q)) {
                $mensaje = "Gasto registrado correctamente.";
            } else {
                $error = "Error al registrar: " . mysqli_error($conn);
            }
        }
    }

    // ── ELIMINAR GASTO (solo admin) ──
    if ($_POST['accion'] == 'eliminar' && in_array($user_rol, ['administrador','supervisor'])) {
        $id = intval($_POST['id']);
        mysqli_query($conn, "DELETE FROM gastos_dia WHERE id = $id");
        $mensaje = "Gasto eliminado.";
    }
}

// ── FILTROS ──
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : 'hoy';
$filtro_tipo  = isset($_GET['tipo'])  ? $_GET['tipo']  : '';

switch ($filtro_fecha) {
    case 'hoy':    $cond_fecha = "DATE(fecha_gasto) = CURDATE()"; break;
    case 'semana': $cond_fecha = "fecha_gasto >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; break;
    case 'mes':    $cond_fecha = "MONTH(fecha_gasto) = MONTH(CURDATE()) AND YEAR(fecha_gasto) = YEAR(CURDATE())"; break;
    default:       $cond_fecha = "DATE(fecha_gasto) = CURDATE()";
}

$cond_tipo = $filtro_tipo != '' ? " AND tipo = '" . mysqli_real_escape_string($conn, $filtro_tipo) . "'" : '';

// Solo admin ve todos los gastos, vendedor solo los suyos
$cond_usuario = !in_array($user_rol, ['administrador','supervisor']) ? " AND id_usuario = $user_id" : '';

$query_gastos = "SELECT g.*, u.nombre as usuario_nombre, u.apellido_paterno as usuario_ap
                 FROM gastos_dia g
                 LEFT JOIN usuarios u ON g.id_usuario = u.id
                 WHERE $cond_fecha $cond_tipo $cond_usuario
                 ORDER BY g.fecha_gasto DESC";
$result_gastos = mysqli_query($conn, $query_gastos);
$total_gastos  = mysqli_num_rows($result_gastos);

// Resumen por tipo
$query_resumen = "SELECT tipo, COALESCE(SUM(monto),0) as total, COUNT(*) as cantidad
                  FROM gastos_dia
                  WHERE $cond_fecha $cond_usuario
                  GROUP BY tipo";
$result_resumen = mysqli_query($conn, $query_resumen);

// Total general
$query_total = "SELECT COALESCE(SUM(monto),0) as total FROM gastos_dia WHERE $cond_fecha $cond_usuario";
$total_general = mysqli_fetch_assoc(mysqli_query($conn, $query_total))['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TecnoViral — Gastos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:#07172e; --navy2:#0d2347; --blue:#0052cc; --accent:#00c2ff;
            --gold:#f5c518; --white:#ffffff; --muted:#7a8ba0;
            --border:rgba(255,255,255,.1); --danger:#ff4d4d; --success:#00d68f;
            --amber:#f5a623;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html,body{font-family:'DM Sans',sans-serif;min-height:100vh;background:var(--navy);color:var(--white);overflow-x:hidden;}

        .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;background:radial-gradient(ellipse at 10% 15%,rgba(0,194,255,.1) 0%,transparent 50%),radial-gradient(ellipse at 88% 80%,rgba(0,82,204,.15) 0%,transparent 50%),radial-gradient(ellipse at 50% 50%,var(--navy) 0%,#050e1e 100%);}
        .bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.022) 1px,transparent 1px);background-size:48px 48px;}
        .page-wrap{position:relative;z-index:1;padding:24px 28px 50px;max-width:1400px;margin:0 auto;}

        @keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
        .anim{animation:fadeUp .5s ease both;}
        .anim-1{animation-delay:.05s;} .anim-2{animation-delay:.12s;} .anim-3{animation-delay:.20s;}

        /* TOPBAR */
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:rgba(13,35,71,.7);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:22px;padding:14px 22px;margin-bottom:28px;box-shadow:0 8px 32px rgba(0,0,0,.4);}
        .brand-row{display:flex;align-items:center;gap:14px;}
        .logo-img{width:68px;height:68px;border-radius:18px;object-fit:cover;border:2px solid rgba(0,194,255,.3);box-shadow:0 0 0 4px rgba(0,194,255,.08),0 8px 20px rgba(0,0,0,.5);transition:transform .4s cubic-bezier(.34,1.56,.64,1);}
        .logo-img:hover{transform:scale(1.08) rotate(-2deg);}
        .brand-name{font-family:'Playfair Display',serif;font-size:1.35rem;font-weight:900;letter-spacing:3px;}
        .brand-name span{color:var(--accent);}
        .brand-sub{font-size:.68rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.35);margin-top:3px;}
        .page-pill{display:flex;align-items:center;gap:10px;background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.25);border-radius:40px;padding:8px 20px;}
        .page-pill i{color:var(--amber);font-size:.9rem;}
        .page-pill span{font-size:.78rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.75);}
        .user-chip{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:50px;padding:6px 16px 6px 6px;}
        .user-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--blue),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;}
        .u-name{font-size:.82rem;font-weight:600;}
        .u-role{font-size:.62rem;color:var(--accent);text-transform:uppercase;letter-spacing:1px;}
        .btn-back{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .25s;}
        .btn-back:hover{background:var(--blue);color:white;transform:translateX(-3px);}

        /* ALERTAS */
        .tv-alert{display:flex;align-items:center;gap:12px;border-radius:16px;padding:14px 20px;font-size:.88rem;font-weight:500;margin-bottom:22px;animation:fadeUp .4s ease both;}
        .tv-alert.success{background:rgba(0,214,143,.1);border:1px solid rgba(0,214,143,.25);color:#00d68f;}
        .tv-alert.danger{background:rgba(255,77,77,.1);border:1px solid rgba(255,77,77,.25);color:#ff8585;}

        /* STATS POR TIPO */
        .tipos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:24px;}
        .tipo-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;}
        .tipo-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:4px 4px 0 0;}
        .tipo-card.t-mercancia::before{background:linear-gradient(90deg,#00875a,var(--success));}
        .tipo-card.t-renta::before{background:linear-gradient(90deg,var(--blue),var(--accent));}
        .tipo-card.t-servicios::before{background:linear-gradient(90deg,#5b3cc4,#9b59b6);}
        .tipo-card.t-robo::before{background:linear-gradient(90deg,#c0392b,var(--danger));}
        .tipo-card.t-otros::before{background:linear-gradient(90deg,#e07b00,var(--gold));}
        .tipo-ico{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
        .t-mercancia .tipo-ico{background:rgba(0,214,143,.15);color:var(--success);}
        .t-renta     .tipo-ico{background:rgba(0,82,204,.2);color:var(--accent);}
        .t-servicios .tipo-ico{background:rgba(155,89,182,.2);color:#b07fec;}
        .t-robo      .tipo-ico{background:rgba(255,77,77,.15);color:var(--danger);}
        .t-otros     .tipo-ico{background:rgba(245,197,24,.15);color:var(--gold);}
        .tipo-val{font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:var(--white);}
        .tipo-lbl{font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1.5px;margin-top:2px;}

        /* TOTAL CARD */
        .total-card{background:linear-gradient(135deg,rgba(255,77,77,.12),rgba(192,57,43,.08));border:1px solid rgba(255,77,77,.25);border-radius:18px;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
        .total-card .t-ico{width:52px;height:52px;border-radius:14px;background:rgba(255,77,77,.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--danger);}
        .total-card .t-val{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;color:var(--danger);}
        .total-card .t-lbl{font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:1.5px;margin-top:2px;}

        /* GLASS CARD */
        .glass-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:28px 32px;backdrop-filter:blur(10px);box-shadow:0 8px 40px rgba(0,0,0,.3);margin-bottom:24px;}

        /* FORMULARIO */
        .card-header-tv{display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,.07);}
        .ch-ico{width:46px;height:46px;border-radius:14px;background:rgba(245,166,35,.15);display:flex;align-items:center;justify-content:center;color:var(--amber);font-size:1.1rem;}
        .ch-title{font-family:'Playfair Display',serif;font-size:1.15rem;font-weight:700;}
        .ch-sub{font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:2px;}

        .field-label{font-size:.68rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.45);margin-bottom:8px;display:block;}
        .field-wrap{position:relative;margin-bottom:0;}
        .field-wrap .f-ico{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.88rem;pointer-events:none;z-index:1;}
        .field-wrap.ta-wrap .f-ico{top:16px;transform:none;}
        .field-wrap input,.field-wrap select,.field-wrap textarea{width:100%;padding:13px 16px 13px 44px;border:1px solid rgba(255,255,255,.1);border-radius:14px;font-size:.92rem;font-family:'DM Sans',sans-serif;background:rgba(255,255,255,.05);color:var(--white);transition:border-color .25s,box-shadow .25s;outline:none;-webkit-appearance:none;}
        .field-wrap select option{background:var(--navy2);}
        .field-wrap input::placeholder,.field-wrap textarea::placeholder{color:rgba(255,255,255,.2);}
        .field-wrap textarea{min-height:80px;resize:vertical;padding-top:13px;}
        .field-wrap input:focus,.field-wrap select:focus,.field-wrap textarea:focus{border-color:var(--amber);background:rgba(245,166,35,.04);box-shadow:0 0 0 4px rgba(245,166,35,.1);}

        .btn-tv{display:inline-flex;align-items:center;gap:9px;padding:13px 28px;border-radius:14px;border:none;font-family:'DM Sans',sans-serif;font-size:.85rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all .25s;}
        .btn-save{background:linear-gradient(135deg,#e07b00,var(--amber));color:white;box-shadow:0 6px 20px rgba(245,166,35,.3);}
        .btn-save:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(245,166,35,.4);color:white;}

        /* FILTROS */
        .filtros-wrap{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;}
        .date-tabs{display:flex;gap:6px;}
        .date-tab{padding:8px 16px;border-radius:12px;font-size:.8rem;font-weight:600;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:rgba(255,255,255,.55);cursor:pointer;text-decoration:none;transition:all .2s;}
        .date-tab:hover,.date-tab.active{background:rgba(245,166,35,.15);border-color:var(--amber);color:var(--amber);}
        .select-wrap{position:relative;}
        .select-wrap .f-ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.82rem;pointer-events:none;z-index:1;}
        .select-wrap select{padding:10px 14px 10px 36px;border:1px solid rgba(255,255,255,.1);border-radius:12px;font-size:.88rem;font-family:'DM Sans',sans-serif;background:rgba(255,255,255,.05);color:var(--white);outline:none;-webkit-appearance:none;min-width:160px;}
        .select-wrap select option{background:var(--navy2);}
        .select-wrap select:focus{border-color:var(--amber);}

        /* SECTION HEAD */
        .section-head{display:flex;align-items:center;gap:12px;margin-bottom:18px;}
        .section-head .s-line{flex:1;height:1px;background:linear-gradient(to right,rgba(245,166,35,.3),transparent);}
        .section-head .s-label{font-size:.68rem;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:var(--amber);}

        /* TABLA */
        .table-wrap{overflow-x:auto;border-radius:16px;}
        table.tv-table{width:100%;border-collapse:separate;border-spacing:0;}
        .tv-table thead tr th{background:rgba(245,166,35,.1);color:rgba(255,255,255,.55);font-size:.65rem;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;padding:13px 16px;border-bottom:1px solid rgba(255,255,255,.07);white-space:nowrap;}
        .tv-table thead tr th:first-child{border-radius:16px 0 0 0;}
        .tv-table thead tr th:last-child{border-radius:0 16px 0 0;}
        .tv-table tbody tr{transition:background .2s;}
        .tv-table tbody tr:hover{background:rgba(245,166,35,.04);}
        .tv-table tbody td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle;font-size:.86rem;color:rgba(255,255,255,.75);}

        /* Badges tipo */
        .tipo-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;}
        .tb-mercancia{background:rgba(0,214,143,.12);color:var(--success);border:1px solid rgba(0,214,143,.25);}
        .tb-renta    {background:rgba(0,194,255,.12);color:var(--accent);border:1px solid rgba(0,194,255,.25);}
        .tb-servicios{background:rgba(155,89,182,.12);color:#b07fec;border:1px solid rgba(155,89,182,.25);}
        .tb-robo     {background:rgba(255,77,77,.12);color:var(--danger);border:1px solid rgba(255,77,77,.25);}
        .tb-otros    {background:rgba(245,197,24,.12);color:var(--gold);border:1px solid rgba(245,197,24,.25);}

        .monto-val{font-family:'Playfair Display',serif;font-size:.95rem;color:var(--danger);}

        /* Empty */
        .empty-state{text-align:center;padding:50px 20px;color:rgba(255,255,255,.3);}
        .empty-state i{font-size:3rem;margin-bottom:14px;display:block;opacity:.35;}
        .empty-state h5{color:rgba(255,255,255,.45);margin-bottom:6px;}

        /* Footer */
        .page-footer{text-align:center;margin-top:36px;font-size:.66rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.15);}
        .page-footer span{color:var(--amber);opacity:.5;}

        @media(max-width:768px){
            .page-wrap{padding:14px 12px 36px;}
            .glass-card{padding:18px 16px;}
            .page-pill{display:none;}
            .tipos-grid{grid-template-columns:1fr 1fr;}
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
            <i class="fas fa-wallet"></i>
            <span>Gastos del Día</span>
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

    <!-- ALERTAS -->
    <?php if ($mensaje): ?>
    <div class="tv-alert success anim"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="tv-alert danger anim"><i class="fas fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- TOTAL GENERAL -->
    <div class="total-card anim anim-1">
        <div style="display:flex;align-items:center;gap:16px;">
            <div class="t-ico"><i class="fas fa-arrow-trend-down"></i></div>
            <div>
                <div class="t-val">$<?php echo number_format($total_general, 2); ?></div>
                <div class="t-lbl">Total en gastos del período</div>
            </div>
        </div>
        <div style="font-size:.82rem;color:rgba(255,255,255,.35);">
            <i class="fas fa-receipt me-1"></i> <?php echo $total_gastos; ?> gasto(s) registrado(s)
        </div>
    </div>

    <!-- RESUMEN POR TIPO -->
    <div class="tipos-grid anim anim-1">
        <?php
        $tipos_info = [
            'mercancia' => ['ico' => 'fa-boxes-stacked', 'label' => 'Mercancía'],
            'renta'     => ['ico' => 'fa-building',      'label' => 'Renta'],
            'servicios' => ['ico' => 'fa-bolt',          'label' => 'Servicios'],
            'robo'      => ['ico' => 'fa-user-secret',   'label' => 'Robo hormiga'],
            'otros'     => ['ico' => 'fa-ellipsis',      'label' => 'Otros'],
        ];
        $resumen_data = [];
        while ($r = mysqli_fetch_assoc($result_resumen)) {
            $resumen_data[$r['tipo']] = $r;
        }
        foreach ($tipos_info as $tipo => $info):
            $monto = isset($resumen_data[$tipo]) ? $resumen_data[$tipo]['total'] : 0;
        ?>
        <div class="tipo-card t-<?php echo $tipo; ?>">
            <div class="tipo-ico"><i class="fas <?php echo $info['ico']; ?>"></i></div>
            <div>
                <div class="tipo-val">$<?php echo number_format($monto, 2); ?></div>
                <div class="tipo-lbl"><?php echo $info['label']; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FORMULARIO -->
    <div class="glass-card anim anim-2">
        <div class="card-header-tv">
            <div class="ch-ico"><i class="fas fa-plus"></i></div>
            <div>
                <div class="ch-title">Registrar Gasto</div>
                <div class="ch-sub">Ingresa los datos del gasto</div>
            </div>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="registrar">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="field-label">Concepto</label>
                    <div class="field-wrap">
                        <input type="text" name="concepto" required placeholder="Ej. Pago de renta mensual">
                        <i class="fas fa-file-invoice f-ico"></i>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="field-label">Monto</label>
                    <div class="field-wrap">
                        <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00">
                        <i class="fas fa-dollar-sign f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Tipo</label>
                    <div class="field-wrap">
                        <select name="tipo" required>
                            <option value="mercancia">🛒 Compra de Mercancía</option>
                            <option value="renta">🏢 Renta</option>
                            <option value="servicios">⚡ Servicios (luz, agua, internet)</option>
                            <option value="robo">🕵️ Robo Hormiga</option>
                            <option value="otros">📦 Otros</option>
                        </select>
                        <i class="fas fa-tag f-ico"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="field-label">Observaciones (opcional)</label>
                    <div class="field-wrap">
                        <input type="text" name="observaciones" placeholder="Detalles adicionales...">
                        <i class="fas fa-comment f-ico"></i>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn-tv btn-save">
                    <i class="fas fa-floppy-disk"></i> Registrar Gasto
                </button>
            </div>
        </form>
    </div>

    <!-- FILTROS -->
    <div class="glass-card anim anim-2" style="padding:20px 24px;">
        <div class="filtros-wrap">
            <div>
                <div style="font-size:.65rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:8px;">Período</div>
                <div class="date-tabs">
                    <a href="?fecha=hoy"    class="date-tab <?php echo $filtro_fecha=='hoy'    ? 'active':''; ?>"><i class="fas fa-sun me-1"></i>Hoy</a>
                    <a href="?fecha=semana" class="date-tab <?php echo $filtro_fecha=='semana' ? 'active':''; ?>"><i class="fas fa-calendar-week me-1"></i>7 días</a>
                    <a href="?fecha=mes"    class="date-tab <?php echo $filtro_fecha=='mes'    ? 'active':''; ?>"><i class="fas fa-calendar me-1"></i>Este mes</a>
                </div>
            </div>
            <div>
                <div style="font-size:.65rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:8px;">Tipo</div>
                <div class="select-wrap">
                    <i class="fas fa-tag f-ico"></i>
                    <select onchange="window.location='?fecha=<?php echo $filtro_fecha; ?>&tipo='+this.value">
                        <option value="">Todos los tipos</option>
                        <option value="mercancia" <?php echo $filtro_tipo=='mercancia' ? 'selected':''; ?>>Mercancía</option>
                        <option value="renta"     <?php echo $filtro_tipo=='renta'     ? 'selected':''; ?>>Renta</option>
                        <option value="servicios" <?php echo $filtro_tipo=='servicios' ? 'selected':''; ?>>Servicios</option>
                        <option value="robo"      <?php echo $filtro_tipo=='robo'      ? 'selected':''; ?>>Robo hormiga</option>
                        <option value="otros"     <?php echo $filtro_tipo=='otros'     ? 'selected':''; ?>>Otros</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div class="section-head anim anim-3">
        <div class="s-label">Gastos registrados (<?php echo $total_gastos; ?>)</div>
        <div class="s-line"></div>
    </div>

    <div class="glass-card anim anim-3" style="padding:24px 28px;">
        <div class="table-wrap">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Observaciones</th>
                        <?php if (in_array($user_rol, ['administrador','supervisor'])): ?>
                        <th>Registrado por</th>
                        <th style="text-align:center;">Eliminar</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total_gastos > 0):
                    mysqli_data_seek($result_gastos, 0);
                    while ($g = mysqli_fetch_assoc($result_gastos)):
                        $tipos_labels = [
                            'mercancia' => ['label'=>'Mercancía',  'ico'=>'fa-boxes-stacked', 'cls'=>'tb-mercancia'],
                            'renta'     => ['label'=>'Renta',      'ico'=>'fa-building',      'cls'=>'tb-renta'],
                            'servicios' => ['label'=>'Servicios',  'ico'=>'fa-bolt',          'cls'=>'tb-servicios'],
                            'robo'      => ['label'=>'Robo hormiga','ico'=>'fa-user-secret',  'cls'=>'tb-robo'],
                            'otros'     => ['label'=>'Otros',      'ico'=>'fa-ellipsis',      'cls'=>'tb-otros'],
                        ];
                        $tinfo = $tipos_labels[$g['tipo']] ?? $tipos_labels['otros'];
                ?>
                <tr>
                    <td style="font-size:.78rem;color:rgba(255,255,255,.45);">
                        <?php echo date('d/m/Y', strtotime($g['fecha_gasto'])); ?><br>
                        <span style="font-size:.7rem;"><?php echo date('H:i', strtotime($g['fecha_gasto'])); ?></span>
                    </td>
                    <td><strong style="color:var(--white);"><?php echo htmlspecialchars($g['concepto']); ?></strong></td>
                    <td>
                        <span class="tipo-badge <?php echo $tinfo['cls']; ?>">
                            <i class="fas <?php echo $tinfo['ico']; ?>"></i>
                            <?php echo $tinfo['label']; ?>
                        </span>
                    </td>
                    <td><span class="monto-val">$<?php echo number_format($g['monto'], 2); ?></span></td>
                    <td style="color:rgba(255,255,255,.45);font-size:.82rem;">
                        <?php echo $g['observaciones'] ? htmlspecialchars($g['observaciones']) : '—'; ?>
                    </td>
                    <?php if (in_array($user_rol, ['administrador','supervisor'])): ?>
                    <td>
                        <span style="display:flex;align-items:center;gap:6px;font-size:.82rem;">
                            <i class="fas fa-user-circle" style="color:var(--accent);"></i>
                            <?php echo htmlspecialchars($g['usuario_nombre'] . ' ' . $g['usuario_ap']); ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                            <button type="submit"
                                onclick="return confirm('¿Eliminar este gasto?')"
                                style="width:34px;height:34px;border-radius:10px;background:rgba(255,77,77,.12);border:1px solid rgba(255,77,77,.25);color:var(--danger);cursor:pointer;transition:all .25s;display:inline-flex;align-items:center;justify-content:center;"
                                onmouseover="this.style.background='var(--danger)';this.style.color='white';"
                                onmouseout="this.style.background='rgba(255,77,77,.12)';this.style.color='var(--danger)';">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="<?php echo in_array($user_rol,['administrador','supervisor']) ? '7' : '5'; ?>">
                        <div class="empty-state">
                            <i class="fas fa-wallet"></i>
                            <h5>No hay gastos en este período</h5>
                            <p>Usa el formulario de arriba para registrar un gasto.</p>
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
</body>
</html>