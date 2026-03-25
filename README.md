# 🖥️ TecnoViral — Sistema Punto de Venta

Sistema web de punto de venta diseñado para tiendas de electrónica. Permite gestionar ventas, inventario, productos, usuarios y generar reportes, todo desde una interfaz táctil moderna.

En el login ingresa con rol administrador:
user: itslome
password: ray0205

<img width="1362" height="695" alt="image" src="https://github.com/user-attachments/assets/836194bd-d52e-4022-bc29-3b383bfdf9f5" />



<img width="1349" height="704" alt="image" src="https://github.com/user-attachments/assets/572bd5b6-3f89-4e5a-a51c-a26459e7de7d" />

---

## ¿Qué hace?

- **Punto de venta** — Cobra con efectivo, débito, crédito o PayPal. Genera ticket PDF automáticamente con folio, productos y cambio.
- **Control de inventario** — Monitorea el stock en tiempo real. Alerta cuando un producto está por agotarse.
- **Gestión de productos** — Alta, modificación y baja de productos con categorías, precios y ubicación.
- **Historial de ventas** — Consulta todas las ventas por fecha, método de pago o vendedor.
- **Corte de caja** — Resumen de ingresos, gastos y diferencias por turno.
- **Gestión de usuarios** — Roles de administrador, supervisor y vendedor con permisos diferenciados.
- **Reportes** — Visualiza ventas y movimientos en gráficas por período.

---

## Requisitos

- PHP 8.0 o superior
- MySQL / MariaDB
- Servidor local: XAMPP, WAMP o similar
- Navegador moderno (Chrome, Edge, Firefox)

---

## Instalación

**1. Clona el repositorio**
```bash
git clone https://github.com/TU_USUARIO/tecnoviral-pos.git
```

**2. Copia la carpeta dentro de tu servidor**
- En XAMPP: pega la carpeta en `C:/xampp/htdocs/`
- En WAMP: pega la carpeta en `C:/wamp64/www/`

**3. Importa la base de datos**
- Abre phpMyAdmin (`http://localhost/phpmyadmin`)
- Crea una base de datos llamada `electronica_bd`
- Selecciónala y ve a la pestaña **Importar**
- Sube el archivo `database/electronica_bd.sql`
- Clic en **Continuar**

**4. Configura la conexión**
Abre el archivo `conexion.php` y ajusta tus credenciales:
```php
$host     = "localhost";
$usuario  = "root";       // tu usuario de MySQL
$password = "";           // tu contraseña
$base     = "electronica_bd";
```

**5. Accede al sistema**
Abre tu navegador y entra a:
```
http://localhost/electronica_bd/login.php
```

---

## Credenciales por defecto

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin   | admin123   | Administrador |

> Se recomienda cambiar la contraseña después del primer inicio de sesión.

---

## Estructura del proyecto

```
electronica_bd/
├── conexion.php          # Configuración de base de datos
├── login.php             # Inicio de sesión
├── menu_principal.php    # Menú principal
├── punto_venta.php       # Módulo de ventas
├── productos.php         # Gestión de productos
├── inventario.php        # Control de inventario
├── historial_ventas.php  # Historial y filtros
├── reportes.php          # Gráficas y reportes
├── usuarios.php          # Gestión de usuarios
├── corte_caja.php        # Corte de caja
├── gastos.php            # Registro de gastos
├── imagenes/             # Imágenes del sistema
└── database/
    └── electronica_bd.sql
```

---

## Tecnologías usadas

- PHP + MySQL
- Bootstrap 5
- Font Awesome 6
- jsPDF (generación de tickets)
- Chart.js (reportes)

---

> Desarrollado para gestión interna de tiendas de electrónica.
