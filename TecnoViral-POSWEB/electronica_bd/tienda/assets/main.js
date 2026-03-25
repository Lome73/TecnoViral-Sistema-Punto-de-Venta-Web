/* ============================================================
   TecnoViral Store — main.js
   ============================================================ */

// ── TOAST CARRITO ──
function mostrarToast(nombre) {
    let toast = document.getElementById('toastCarrito');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toastCarrito';
        toast.className = 'toast-carrito';
        toast.innerHTML = `
            <div class="toast-ico"><i class="fas fa-bag-shopping"></i></div>
            <div>
                <div class="toast-txt" id="toastNombre"></div>
                <div class="toast-sub">Agregado al carrito</div>
            </div>
            <button class="toast-close" onclick="ocultarToast()"><i class="fas fa-xmark"></i></button>
        `;
        document.body.appendChild(toast);
    }
    document.getElementById('toastNombre').textContent = nombre;
    toast.classList.add('show');
    setTimeout(ocultarToast, 3000);
}

function ocultarToast() {
    const toast = document.getElementById('toastCarrito');
    if (toast) toast.classList.remove('show');
}

// ── AGREGAR AL CARRITO ──
function agregarCarrito(idProducto, nombre, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    }
    fetch('carrito_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `accion=agregar&id_producto=${idProducto}&cantidad=1`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            mostrarToast(nombre);
            // Actualizar badge
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.textContent = data.total_items;
                badge.classList.remove('hidden');
            }
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bag-shopping"></i> Agregar';
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bag-shopping"></i> Agregar';
        }
    });
}

// ── FAQ ACORDEÓN ──
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', function() {
            const item = this.closest('.faq-item');
            const wasOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            if (!wasOpen) item.classList.add('open');
        });
    });

    // ── ANIMACIÓN SCROLL ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.prod-card, .cat-card').forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });
});