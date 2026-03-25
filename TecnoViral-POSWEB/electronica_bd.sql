CREATE DATABASE IF NOT EXISTS electronica_bd;
USE electronica_bd;

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-03-2026 a las 20:12:41
-- Versión del servidor: 10.4.27-MariaDB
-- Versión de PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
...
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `electronica_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 'Protectores', 'fundas para celular y tablets ', '2026-03-05 04:48:40'),
(2, 'Audifonos', 'Audífonos y auriculares con y sin cable', '2026-03-19 13:49:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cortes_caja`
--

CREATE TABLE `cortes_caja` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) DEFAULT NULL,
  `total_ventas` decimal(10,2) DEFAULT 0.00,
  `total_gastos` decimal(10,2) DEFAULT 0.00,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario_cierre` int(11) DEFAULT NULL,
  `efectivo_real` decimal(10,2) DEFAULT 0.00,
  `efectivo_esperado` decimal(10,2) DEFAULT 0.00,
  `observaciones_cierre` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cortes_caja`
--

INSERT INTO `cortes_caja` (`id`, `id_usuario`, `fecha_apertura`, `fecha_cierre`, `monto_inicial`, `monto_final`, `total_ventas`, `total_gastos`, `diferencia`, `observaciones`, `id_usuario_cierre`, `efectivo_real`, `efectivo_esperado`, `observaciones_cierre`) VALUES
(1, 5, '2026-03-19 10:19:37', '2026-03-25 12:08:51', '1000.00', NULL, '6762.00', '0.00', '3650.00', 'ray', 8, '5000.00', '1350.00', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_venta`
--

CREATE TABLE `detalles_venta` (
  `id` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_venta`
--

INSERT INTO `detalles_venta` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 3, 1, '370.00', '370.00'),
(2, 1, 7, 3, '150.00', '450.00'),
(3, 2, 7, 1, '150.00', '150.00'),
(6, 4, 9, 2, '300.00', '600.00'),
(7, 4, 8, 1, '350.00', '350.00'),
(8, 5, 8, 2, '350.00', '700.00'),
(9, 5, 7, 1, '150.00', '150.00'),
(10, 6, 3, 1, '370.00', '370.00'),
(11, 6, 7, 1, '150.00', '150.00'),
(12, 6, 8, 2, '350.00', '700.00'),
(13, 7, 3, 1, '370.00', '370.00'),
(14, 7, 7, 1, '150.00', '150.00'),
(15, 7, 8, 2, '350.00', '700.00'),
(16, 7, 9, 2, '300.00', '600.00'),
(17, 7, 6, 1, '300.00', '300.00'),
(18, 8, 3, 1, '370.00', '370.00'),
(19, 9, 3, 1, '370.00', '370.00'),
(20, 10, 8, 1, '350.00', '350.00'),
(21, 11, 3, 3, '370.00', '1110.00'),
(22, 11, 6, 1, '300.00', '300.00'),
(23, 12, 7, 1, '150.00', '150.00'),
(24, 13, 8, 1, '350.00', '350.00'),
(25, 14, 3, 1, '370.00', '370.00'),
(26, 15, 3, 1, '370.00', '370.00'),
(27, 16, 9, 1, '300.00', '300.00'),
(28, 17, 7, 1, '150.00', '150.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos_dia`
--

CREATE TABLE `gastos_dia` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipo` enum('mercancia','renta','servicios','robo','otros') NOT NULL DEFAULT 'otros',
  `fecha_gasto` timestamp NOT NULL DEFAULT current_timestamp(),
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gastos_dia`
--

INSERT INTO `gastos_dia` (`id`, `id_usuario`, `concepto`, `monto`, `tipo`, `fecha_gasto`, `observaciones`) VALUES
(1, 5, 'Mantenimiento', '200.00', 'otros', '2026-03-19 15:25:24', 'Se hizo una fumigación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste','venta') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `fecha_movimiento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `id_producto`, `id_usuario`, `tipo_movimiento`, `cantidad`, `motivo`, `fecha_movimiento`) VALUES
(1, 3, 3, 'salida', 1, 'Venta folio TV-20260319-7629', '2026-03-19 14:52:35'),
(2, 7, 3, 'salida', 3, 'Venta folio TV-20260319-7629', '2026-03-19 14:52:35'),
(3, 7, 3, 'salida', 1, 'Venta folio TV-20260319-5910', '2026-03-19 14:54:29'),
(4, 9, 3, 'salida', 2, 'Venta folio TV-20260319-6195', '2026-03-19 14:57:51'),
(5, 8, 3, 'salida', 1, 'Venta folio TV-20260319-6195', '2026-03-19 14:57:51'),
(6, 9, 5, 'salida', 2, 'Venta folio TV-20260319-5228', '2026-03-19 15:16:39'),
(7, 8, 5, 'salida', 1, 'Venta folio TV-20260319-5228', '2026-03-19 15:16:39'),
(8, 8, 3, 'salida', 2, 'Venta folio TV-20260325-5092', '2026-03-25 11:36:34'),
(9, 7, 3, 'salida', 1, 'Venta folio TV-20260325-5092', '2026-03-25 11:36:34'),
(10, 3, 3, 'salida', 1, 'Venta folio TV-20260325-1052', '2026-03-25 11:39:11'),
(11, 7, 3, 'salida', 1, 'Venta folio TV-20260325-1052', '2026-03-25 11:39:11'),
(12, 8, 3, 'salida', 2, 'Venta folio TV-20260325-1052', '2026-03-25 11:39:11'),
(13, 3, 3, 'salida', 1, 'Venta folio TV-20260325-2853', '2026-03-25 11:50:59'),
(14, 7, 3, 'salida', 1, 'Venta folio TV-20260325-2853', '2026-03-25 11:50:59'),
(15, 8, 3, 'salida', 2, 'Venta folio TV-20260325-2853', '2026-03-25 11:50:59'),
(16, 9, 3, 'salida', 2, 'Venta folio TV-20260325-2853', '2026-03-25 11:50:59'),
(17, 6, 3, 'salida', 1, 'Venta folio TV-20260325-2853', '2026-03-25 11:50:59'),
(18, 3, 3, 'salida', 1, 'Venta folio TV-20260325-4986', '2026-03-25 11:51:57'),
(19, 3, 3, 'salida', 1, 'Venta folio TV-20260325-1470', '2026-03-25 11:52:02'),
(20, 8, 3, 'salida', 1, 'Venta folio TV-20260325-1442', '2026-03-25 11:52:05'),
(21, 3, 3, 'salida', 3, 'Venta folio TV-20260325-2064', '2026-03-25 11:58:45'),
(22, 6, 3, 'salida', 1, 'Venta folio TV-20260325-2064', '2026-03-25 11:58:45'),
(23, 7, 3, 'salida', 1, 'Venta folio TV-20260325-6160', '2026-03-25 16:24:23'),
(24, 8, 8, 'salida', 1, 'Venta folio TV-20260325-7930', '2026-03-25 16:27:31'),
(25, 3, 8, 'salida', 1, 'Venta folio TV-20260325-7181', '2026-03-25 16:28:05'),
(26, 3, 8, 'salida', 1, 'Venta folio TV-20260325-2138', '2026-03-25 16:28:20'),
(27, 9, 8, 'salida', 1, 'Venta folio TV-20260325-3005', '2026-03-25 16:28:26'),
(28, 7, 1, 'salida', 1, 'Venta folio TV-20260325-0017', '2026-03-25 18:26:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `marca` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `stock_maximo` int(11) DEFAULT 100,
  `ubicacion` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `id_categoria`, `marca`, `precio`, `stock`, `stock_minimo`, `stock_maximo`, `ubicacion`, `fecha_registro`, `activo`) VALUES
(3, 'Protector de uso rudo ', 'Disponible para samsung A 54, A55 y A56', 1, 'JETech', '370.00', 30, 20, 50, 'A1', '2026-03-05 17:28:52', 1),
(6, 'Audifonos Lenovo Trinkplus Gaming', 'Duración de pila 6 horas o mas✅\r\nTiempo de carga 40 minutos✅\r\nDistancia de transmisión 10 metros✅\r\nSe adapta a la oreja✅\r\nAislamiento de sonido ✅\r\nMicrófono ✅\r\nCancelación de ruido✅\r\nImpermeable ✅\r\nControl de volumen ✅\r\nCertificación CE✅\r\nConector tipo c✅', 2, 'Lenovo', '300.00', 8, 2, 20, 'Guerrero', '2026-03-19 13:57:46', 1),
(7, 'Audifonos X15 Gaming', 'cancelación de ruido ✅\r\nCuenta con pantalla led✅\r\nImpermeable ✅\r\nMicrófono ✅\r\nAislamiento de sonido ✅\r\nBluetooth 5.0✅\r\n6 horas de batería, pero puede llegar a las 24hrs\r\nMateriales: metal, plástico y gel silicona ✅', 2, 'Generico', '150.00', 1, 2, 20, 'Guerrero', '2026-03-19 14:08:27', 1),
(8, 'Audifonos Lenovo Trinkplus LP40 PRO', 'Incluye usb de carga ✅\r\nGomas de repuesto ✅\r\nSellados✅\r\nDura todo el dia y hasta mas✅\r\nBuena calidad de sonido ✅\r\nSoporta el sudor✅\r\nNo se caen de la oreja✅\r\nCómodos ✅\r\nCancelación del ruido exterior ✅\r\nCargan en 35minutos✅', 2, 'Lenovo', '350.00', 25, 2, 20, 'Guerrero', '2026-03-19 14:13:12', 1),
(9, 'Audifonos Lenovo Trinkplus LP40', 'Incluye usb de carga✅\r\nSellados✅\r\nDura todo el dia y hasta mas✅\r\nBuena calidad de sonido ✅\r\nSoporta el sudor✅\r\nNo se caen de la oreja✅\r\nCómodos ✅\r\nCancelación del ruido exterior ✅\r\nCargan en 35minutos✅', 2, 'Lenovo', '300.00', 10, 2, 22, 'Guerrero', '2026-03-19 14:14:37', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `direccion` text NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('vendedor','supervisor','administrador') DEFAULT 'vendedor',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `direccion`, `telefono`, `email`, `nombre_usuario`, `contrasena`, `rol`, `fecha_registro`, `activo`) VALUES
(1, 'Elena ', 'Alvarez ', 'Alvarez', 'col, Adolfo lopez mateos privada de 5 de mayo s/n', '7331446812', '', 'nubecita', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'administrador', '2026-03-04 05:13:08', 1),
(3, 'Raymond', 'Lomelin', '', 'Vicente Guerrero', '2224246948', 'tecnoviral73@gmail.com', 'itslome', 'b26a79d88151845a92a754af98bc8e7b1fa83518d03ae59089750a74d8b53a41', 'administrador', '2026-03-19 12:18:49', 1),
(5, 'Prueba', 'prueba', '', '', '', 'prueba@gmail.com', 'vendedor', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'vendedor', '2026-03-19 15:10:24', 1),
(8, 'Edgar', 'perez', '', '', '', 'edgar@gmail.com', 'supervisor', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'supervisor', '2026-03-25 11:31:50', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `folio` varchar(20) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','debito','credito','paypal') NOT NULL,
  `estado` enum('completada','cancelada','pendiente') DEFAULT 'completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `folio`, `id_usuario`, `fecha_venta`, `total`, `metodo_pago`, `estado`) VALUES
(1, 'TV-20260319-7629', 3, '2026-03-19 14:52:35', '370.00', '', 'completada'),
(2, 'TV-20260319-5910', 3, '2026-03-19 14:54:29', '150.00', '', 'completada'),
(4, 'TV-20260319-5228', 5, '2026-03-19 15:16:39', '585.00', 'paypal', 'completada'),
(5, 'TV-20260325-5092', 3, '2026-03-25 11:36:34', '425.00', 'credito', 'completada'),
(6, 'TV-20260325-1052', 3, '2026-03-25 11:39:11', '1098.00', '', 'completada'),
(7, 'TV-20260325-2853', 3, '2026-03-25 11:50:59', '1696.00', '', 'completada'),
(8, 'TV-20260325-4986', 3, '2026-03-25 11:51:57', '370.00', '', 'completada'),
(9, 'TV-20260325-1470', 3, '2026-03-25 11:52:02', '370.00', 'credito', 'completada'),
(10, 'TV-20260325-1442', 3, '2026-03-25 11:52:05', '350.00', 'paypal', 'completada'),
(11, 'TV-20260325-2064', 3, '2026-03-25 11:58:45', '987.00', '', 'completada'),
(12, 'TV-20260325-6160', 3, '2026-03-25 16:24:23', '150.00', '', 'completada'),
(13, 'TV-20260325-7930', 8, '2026-03-25 16:27:31', '350.00', 'efectivo', 'completada'),
(14, 'TV-20260325-7181', 8, '2026-03-25 16:28:05', '370.00', 'debito', 'completada'),
(15, 'TV-20260325-2138', 8, '2026-03-25 16:28:20', '296.00', 'credito', 'completada'),
(16, 'TV-20260325-3005', 8, '2026-03-25 16:28:26', '300.00', 'paypal', 'completada'),
(17, 'TV-20260325-0017', 1, '2026-03-25 18:26:10', '142.50', 'efectivo', 'completada');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `gastos_dia`
--
ALTER TABLE `gastos_dia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folio` (`folio`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `gastos_dia`
--
ALTER TABLE `gastos_dia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cortes_caja`
--
ALTER TABLE `cortes_caja`
  ADD CONSTRAINT `cortes_caja_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD CONSTRAINT `detalles_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalles_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `gastos_dia`
--
ALTER TABLE `gastos_dia`
  ADD CONSTRAINT `gastos_dia_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
