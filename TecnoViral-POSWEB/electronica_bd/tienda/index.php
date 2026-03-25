<?php
require_once __DIR__ . '/includes/conexion.php';
$page_title = 'Inicio';

// Productos destacados (los 8 con más stock o más recientes)
$q_destacados = mysqli_query($conn,
    "SELECT p.*, c.nombre as categoria_nombre
     FROM productos p
     LEFT JOIN categorias c ON p.id_categoria = c.id
     WHERE p.activo = 1 AND p.stock > 0
     ORDER BY p.id DESC
     LIMIT 8"
);

// Categorías con conteo
$q_categorias = mysqli_query($conn,
    "SELECT c.*, COUNT(p.id) as total
     FROM categorias c
     LEFT JOIN productos p ON p.id_categoria = c.id AND p.activo = 1 AND p.stock > 0
     GROUP BY c.id
     HAVING total > 0
     ORDER BY total DESC
     LIMIT 6"
);

// Total productos
$q_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM productos WHERE activo=1 AND stock>0"));
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-content">

    <!-- ── HERO ── -->
    <div class="hero anim">
        <div class="hero-content">
            <div class="hero-tag">
                <i class="fas fa-bolt"></i> Nueva colección 2026
            </div>
            <h1 class="hero-title">
                Tecnología que<br><span>transforma</span> tu vida
            </h1>
            <p class="hero-desc">
                Los mejores productos de electrónica y accesorios al mejor precio.
                Envío rápido a todo México con garantía incluida.
            </p>
            <div class="hero-btns">
                <a href="productos.php" class="btn-hero-primary">
                    <i class="fas fa-bag-shopping"></i> Ver productos
                </a>
                <a href="rastreo.php" class="btn-hero-secondary">
                    <i class="fas fa-truck"></i> Rastrear pedido
                </a>
            </div>
            <div class="hero-stats">
                <div>
                    <div class="hero-stat-val"><?php echo $q_total['t']; ?>+</div>
                    <div class="hero-stat-lbl">Productos</div>
                </div>
                <div>
                    <div class="hero-stat-val">24h</div>
                    <div class="hero-stat-lbl">Envío express</div>
                </div>
                <div>
                    <div class="hero-stat-val">100%</div>
                    <div class="hero-stat-lbl">Pago seguro</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CATEGORÍAS ── -->
    <?php if (mysqli_num_rows($q_categorias) > 0): ?>
    <div class="anim anim-1">
        <div class="section-head">
            <div>
                <div class="section-line"><span></span><span></span><span></span></div>
                <h2 class="section-title">Categorías</h2>
            </div>
            <a href="productos.php" class="ver-todos">Ver todo <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php
        $cat_colores = [
            'linear-gradient(90deg,#0052cc,#00c2ff)',
            'linear-gradient(90deg,#00875a,#00d68f)',
            'linear-gradient(90deg,#5b3cc4,#9b59b6)',
            'linear-gradient(90deg,#e07b00,#f5c518)',
            'linear-gradient(90deg,#c0392b,#ff4d4d)',
            'linear-gradient(90deg,#1abc9c,#00d68f)',
        ];
        $cat_iconos = [
            'fa-mobile-screen', 'fa-headphones', 'fa-laptop',
            'fa-gamepad', 'fa-cable-car', 'fa-box'
        ];
        ?>
        <div class="cat-grid">
            <?php $ci = 0; while ($cat = mysqli_fetch_assoc($q_categorias)): ?>
            <a href="productos.php?categoria=<?php echo $cat['id']; ?>" class="cat-card"
               style="--cat-color:<?php echo $cat_colores[$ci % count($cat_colores)]; ?>">
                <div class="cat-ico"><i class="fas <?php echo $cat_iconos[$ci % count($cat_iconos)]; ?>" style="color:<?php echo ['#00c2ff','#00d68f','#b07fec','#f5c518','#ff8585','#1abc9c'][$ci % 6]; ?>"></i></div>
                <div class="cat-name"><?php echo htmlspecialchars($cat['nombre']); ?></div>
                <div class="cat-count"><?php echo $cat['total']; ?> productos</div>
            </a>
            <?php $ci++; endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── PRODUCTOS DESTACADOS ── -->
    <div class="anim anim-2">
        <div class="section-head">
            <div>
                <div class="section-line"><span></span><span></span><span></span></div>
                <h2 class="section-title">Productos destacados</h2>
                <p class="section-sub">Los más recientes en nuestra tienda</p>
            </div>
            <a href="productos.php" class="ver-todos">Ver todos <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="prod-grid">
            <?php
            $delay = 0;
            while ($p = mysqli_fetch_assoc($q_destacados)):
                $delay += 0.06;
            $tiene_img = file_exists(__DIR__ . '/../imagenes/productos/' . $p['id'] . '.jpg');    
        ?>
            ?>
            <div class="prod-card" style="animation-delay:<?php echo $delay; ?>s;">
                <!-- Imagen -->
                <a href="producto.php?id=<?php echo $p['id']; ?>" class="prod-img-wrap" style="display:block;">
                    <?php if ($tiene_img): ?>
                    <img src="../imagenes/productos/<?php echo $p['id']; ?>.jpg" alt="<?php echo htmlspecialchars($p['nombre']); ?>"
                         onerror="this.src='assets/img/placeholder.png'">
                    <?php else: ?>
                    <!-- Placeholder con inicial -->
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(0,82,204,.15),rgba(0,194,255,.08));min-height:200px;">
                        <div style="text-align:center;">
                            <i class="fas fa-microchip" style="font-size:3rem;color:rgba(0,194,255,.3);display:block;margin-bottom:10px;"></i>
                            <span style="font-size:.7rem;color:rgba(255,255,255,.2);letter-spacing:2px;text-transform:uppercase;"><?php echo htmlspecialchars($p['categoria_nombre'] ?? 'Producto'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($p['stock'] <= 3): ?>
                    <span class="prod-badge badge-oferta">Últimas unidades</span>
                    <?php endif; ?>

                    <div class="prod-actions">
                        <a href="producto.php?id=<?php echo $p['id']; ?>" class="prod-action-btn" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </a>

                <!-- Info -->
                <div class="prod-info">
                    <div class="prod-cat"><?php echo htmlspecialchars($p['categoria_nombre'] ?? ''); ?></div>
                    <a href="producto.php?id=<?php echo $p['id']; ?>" style="text-decoration:none;">
                        <div class="prod-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    </a>
                    <div class="prod-precios">
                        <span class="prod-precio">$<?php echo number_format($p['precio'], 2); ?></span>
                        <span style="font-size:.72rem;color:rgba(255,255,255,.3);">
                            <i class="fas fa-cubes" style="font-size:.65rem;"></i> <?php echo $p['stock']; ?> disp.
                        </span>
                    </div>
                </div>

                <!-- Botón agregar -->
                <button class="btn-carrito"
                    onclick="agregarCarrito(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nombre']); ?>', this)"
                    <?php echo $p['stock'] == 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-bag-shopping"></i>
                    <?php echo $p['stock'] > 0 ? 'Agregar' : 'Agotado'; ?>
                </button>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ── BANNER MEDIO ── -->
    <div class="anim anim-3" style="margin: 56px 0;">
        <div style="background:linear-gradient(135deg,rgba(0,82,204,.2),rgba(0,194,255,.08));border:1px solid rgba(0,194,255,.15);border-radius:22px;padding:36px 40px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:24px;">
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="width:56px;height:56px;background:rgba(0,194,255,.12);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--accent);flex-shrink:0;">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <div>
                    <div style="font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;margin-bottom:4px;">Envío gratis en pedidos +$999</div>
                    <div style="font-size:.85rem;color:rgba(255,255,255,.45);">Entrega en 2–5 días hábiles a todo México · Rastreo en tiempo real</div>
                </div>
            </div>
            <a href="productos.php" class="btn-tv btn-primary">
                <i class="fas fa-bag-shopping"></i> Aprovechar
            </a>
        </div>
    </div>

    <!-- ── TRUST BADGES ── -->
    <div class="anim anim-3" style="margin-bottom:56px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
            <?php
            $badges = [
                ['fas fa-shield-check','#00d68f','Compra Segura','Pago 100% protegido'],
                ['fas fa-rotate-left','#00c2ff','30 días','Devolución sin preguntas'],
                ['fas fa-headset','#f5c518','Soporte 24/7','Siempre disponibles'],
                ['fas fa-award','#b07fec','Garantía','Productos originales'],
            ];
            foreach ($badges as $b):
            ?>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px;">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:<?php echo $b[1]; ?>;flex-shrink:0;">
                    <i class="<?php echo $b[0]; ?>"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.88rem;"><?php echo $b[2]; ?></div>
                    <div style="font-size:.75rem;color:rgba(255,255,255,.4);"><?php echo $b[3]; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── FAQ ── -->
    <div class="faq-section anim anim-4">
        <div class="section-head">
            <div>
                <div class="section-line"><span></span><span></span><span></span></div>
                <h2 class="section-title">Preguntas frecuentes</h2>
            </div>
        </div>
        <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:20px;overflow:hidden;">
            <?php
            $faqs = [
                ['¿Mi compra es segura?', 'Sí, todas las transacciones están protegidas con encriptación SSL. Aceptamos tarjetas de crédito, débito y PayPal de forma segura.'],
                ['¿Cuánto tarda el envío?', 'Los envíos estándar tardan de 3 a 5 días hábiles. El envío express llega en 24-48 horas. Recibirás un número de rastreo al confirmar tu pedido.'],
                ['¿Puedo devolver un producto?', 'Sí, tienes 30 días para devolver cualquier producto en su estado original. La devolución es gratuita si el producto tiene defecto de fábrica.'],
                ['¿Los productos tienen garantía?', 'Todos nuestros productos son originales y cuentan con garantía del fabricante. En caso de defecto, lo reparamos o reemplazamos sin costo.'],
                ['¿Cómo rastrea mi pedido?', 'Una vez confirmado tu pago recibirás un número de rastreo por correo. También puedes consultarlo en la sección "Rastrear Pedido" de nuestra tienda.'],
            ];
            foreach ($faqs as $faq):
            ?>
            <div class="faq-item">
                <div class="faq-question">
                    <?php echo $faq[0]; ?>
                    <div class="faq-icon"><i class="fas fa-plus"></i></div>
                </div>
                <div class="faq-answer"><?php echo $faq[1]; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>