<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$carrito_count = isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0;
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoViral Store <?php echo isset($page_title) ? '— '.$page_title : ''; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/electronica_bd/tienda/assets/css/style.css" rel="stylesheet">

    <style>
        /* ── ANNOUNCEMENT BAR ── */
        .announce-bar {
            background: linear-gradient(90deg, #0052cc, #00c2ff, #0052cc);
            background-size: 200% auto;
            animation: shimmer 4s linear infinite;
            color: white;
            text-align: center;
            padding: 9px 20px;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            position: relative;
            overflow: hidden;
        }
        @keyframes shimmer { 0%{background-position:0% center} 100%{background-position:200% center} }
        .announce-bar .announce-ico { margin-right: 8px; animation: pulse 2s ease infinite; display: inline-block; }
        @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.2)} }
        .announce-close {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; color: rgba(255,255,255,.6);
            cursor: pointer; font-size: .8rem; transition: color .2s;
        }
        .announce-close:hover { color: white; }

        /* ── NAVBAR ── */
        .tv-navbar {
            background: rgba(7,23,46,.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,.08);
            padding: 0 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 24px rgba(0,0,0,.3);
        }
        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            padding: 14px 28px;
            gap: 20px;
        }

        /* Logo */
        .nav-logo {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; flex-shrink: 0;
        }
        .nav-logo img {
            width: 44px; height: 44px; border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(0,194,255,.3);
            box-shadow: 0 0 0 3px rgba(0,194,255,.08);
        }
        .nav-logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem; font-weight: 900;
            letter-spacing: 2px; color: white;
        }
        .nav-logo-text span { color: #00c2ff; }
        .nav-logo-sub {
            font-size: .58rem; letter-spacing: 3px;
            text-transform: uppercase; color: rgba(255,255,255,.3);
            display: block; margin-top: 1px;
        }

        /* Links centro */
        .nav-links {
            display: flex; align-items: center; gap: 6px;
            list-style: none; margin: 0; padding: 0;
        }
        .nav-links a {
            color: rgba(255,255,255,.6);
            text-decoration: none;
            font-size: .85rem; font-weight: 500;
            padding: 8px 14px; border-radius: 10px;
            transition: all .2s; letter-spacing: .3px;
        }
        .nav-links a:hover, .nav-links a.active {
            color: white;
            background: rgba(255,255,255,.07);
        }
        .nav-links a.active { color: #00c2ff; }

        /* Iconos derecha */
        .nav-actions { display: flex; align-items: center; gap: 8px; }
        .nav-icon-btn {
            width: 40px; height: 40px; border-radius: 12px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.7);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: .95rem;
            transition: all .25s; position: relative;
        }
        .nav-icon-btn:hover { background: rgba(0,194,255,.12); border-color: rgba(0,194,255,.3); color: #00c2ff; }
        .cart-badge {
            position: absolute; top: -6px; right: -6px;
            background: #00c2ff; color: #07172e;
            font-size: .6rem; font-weight: 800;
            width: 18px; height: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #07172e;
        }
        .cart-badge.hidden { display: none; }

        /* Búsqueda */
        .nav-search {
            display: flex; align-items: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px; padding: 8px 14px;
            gap: 8px; flex: 1; max-width: 260px;
            transition: all .25s;
        }
        .nav-search:focus-within { border-color: rgba(0,194,255,.4); background: rgba(0,194,255,.05); box-shadow: 0 0 0 3px rgba(0,194,255,.08); }
        .nav-search i { color: rgba(255,255,255,.3); font-size: .85rem; flex-shrink: 0; }
        .nav-search input {
            background: none; border: none; outline: none;
            color: white; font-size: .88rem;
            font-family: 'DM Sans', sans-serif; width: 100%;
        }
        .nav-search input::placeholder { color: rgba(255,255,255,.25); }

        /* Mobile hamburger */
        .nav-hamburger {
            display: none; flex-direction: column; gap: 5px;
            background: none; border: none; cursor: pointer; padding: 4px;
        }
        .nav-hamburger span {
            display: block; width: 22px; height: 2px;
            background: rgba(255,255,255,.7); border-radius: 2px;
            transition: all .3s;
        }

        /* Mobile menu */
        .nav-mobile {
            display: none;
            background: rgba(7,23,46,.98);
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 16px 20px 20px;
        }
        .nav-mobile.open { display: block; }
        .nav-mobile a {
            display: block; padding: 12px 16px; color: rgba(255,255,255,.6);
            text-decoration: none; font-size: .9rem; border-radius: 10px;
            transition: all .2s;
        }
        .nav-mobile a:hover { background: rgba(255,255,255,.06); color: white; }

        @media (max-width: 768px) {
            .nav-links, .nav-search { display: none; }
            .nav-hamburger { display: flex; }
        }
    </style>
</head>
<body>

<!-- ── ANNOUNCE BAR ── -->
<div class="announce-bar" id="announceBar">
    <span class="announce-ico">⚡</span>
    Envío gratis en compras mayores a $999 · Pago seguro garantizado
    <button class="announce-close" onclick="document.getElementById('announceBar').style.display='none'">
        <i class="fas fa-xmark"></i>
    </button>
</div>

<!-- ── NAVBAR ── -->
<nav class="tv-navbar">
    <div class="nav-inner">
        <!-- Logo -->
        <a href="index.php" class="nav-logo">
            <img src="/electronica_bd/imagenes/logoe.jpeg" alt="TecnoViral"
                 onerror="this.src='https://placehold.co/44x44/0052cc/fff?text=TV'">
            <div>
                <div class="nav-logo-text">TECNO<span>VIRAL</span></div>
                <span class="nav-logo-sub">Store</span>
            </div>
        </a>

        <!-- Links -->
        <ul class="nav-links">
            <li><a href="index.php" class="<?php echo $pagina_actual=='index.php'?'active':''; ?>">Inicio</a></li>
            <li><a href="productos.php" class="<?php echo $pagina_actual=='productos.php'?'active':''; ?>">Productos</a></li>
            <li><a href="rastreo.php" class="<?php echo $pagina_actual=='rastreo.php'?'active':''; ?>">Rastrear Pedido</a></li>
            <li><a href="contacto.php" class="<?php echo $pagina_actual=='contacto.php'?'active':''; ?>">Contacto</a></li>
        </ul>

        <!-- Búsqueda -->
        <div class="nav-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar productos..." id="navSearch"
                   onkeydown="if(event.key==='Enter') window.location='productos.php?q='+this.value">
        </div>

        <!-- Acciones -->
        <div class="nav-actions">
            <a href="mi_cuenta.php" class="nav-icon-btn" title="Mi cuenta">
                <i class="fas fa-user"></i>
            </a>
            <a href="carrito.php" class="nav-icon-btn" title="Carrito">
                <i class="fas fa-bag-shopping"></i>
                <span class="cart-badge <?php echo $carrito_count == 0 ? 'hidden' : ''; ?>" id="cartBadge">
                    <?php echo $carrito_count; ?>
                </span>
            </a>
        </div>

        <!-- Hamburger mobile -->
        <button class="nav-hamburger" onclick="toggleMobile()" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="nav-mobile" id="navMobile">
        <a href="index.php"><i class="fas fa-home me-2"></i>Inicio</a>
        <a href="productos.php"><i class="fas fa-box me-2"></i>Productos</a>
        <a href="rastreo.php"><i class="fas fa-truck me-2"></i>Rastrear Pedido</a>
        <a href="contacto.php"><i class="fas fa-envelope me-2"></i>Contacto</a>
        <a href="carrito.php"><i class="fas fa-bag-shopping me-2"></i>Carrito
            <?php if ($carrito_count > 0): ?>
            <span style="background:#00c2ff;color:#07172e;font-size:.65rem;font-weight:800;padding:2px 7px;border-radius:20px;margin-left:6px;">
                <?php echo $carrito_count; ?>
            </span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<script>
function toggleMobile() {
    document.getElementById('navMobile').classList.toggle('open');
}
</script>