-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-04-2025 a las 16:43:15
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `estilo_personal_hombres`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asesores`
--

CREATE TABLE `asesores` (
  `id_asesor` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `especialidad` varchar(250) NOT NULL,
  `cod_barra` varchar(500) NOT NULL,
  `id_empresa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asesores`
--

INSERT INTO `asesores` (`id_asesor`, `nombre`, `especialidad`, `cod_barra`, `id_empresa`) VALUES
(1, 'Andrés Salazar', 'Perfumes', 'ASESOR-1-20250423', 1),
(2, 'María López', 'Moda', 'ASESOR-2-20250423', 2),
(3, 'mico shomin', 'los perfumes caros de sauvage.', 'ASESOR-3-20250424', 1),
(4, 'cesar esquivel', 'camisas de las mas alta calidad', 'ASESOR-4-20250424', 2),
(5, 'juan escamote', 'camisas rayadas', 'ASESOR-5-20250425', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `documento_usuario` varchar(10) NOT NULL,
  `asesor_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id_cita`, `nombre`, `documento_usuario`, `asesor_id`, `fecha`, `hora`) VALUES
(1, 'Cita de Prueba 1', '1000000001', 1, '2025-04-24', '10:00:00'),
(2, 'Cita de Prueba 2', '2000000002', 2, '2025-04-25', '14:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id_empresa` int(11) NOT NULL,
  `nombre_empresa` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id_empresa`, `nombre_empresa`) VALUES
(1, 'EstiloPersonalSA'),
(2, 'ClienteModaXYZ'),
(3, 'ModaElegante'),
(4, 'ligistica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL,
  `estado` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado`
--

INSERT INTO `estado` (`id_estado`, `estado`) VALUES
(1, 'Vigente'),
(2, 'Vencida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `licencia`
--

CREATE TABLE `licencia` (
  `id_licencia` varchar(20) NOT NULL,
  `fecha_inicial` datetime NOT NULL,
  `fecha_final` datetime NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_tipo_licencia` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `licencia`
--

INSERT INTO `licencia` (`id_licencia`, `fecha_inicial`, `fecha_final`, `id_empresa`, `id_tipo_licencia`, `id_estado`) VALUES
('1109475855F5266BFF23', '2025-04-25 06:56:00', '2027-04-25 06:56:00', 4, 4, 1),
('B48LXM0ZIE7IPO0SK871', '2025-04-20 00:00:00', '2025-04-23 00:00:00', 2, 1, 2),
('HBID33X3EDFSB0RGTEB4', '2025-04-23 00:00:00', '2030-04-23 00:00:00', 1, 4, 1),
('HBID33X3EDFSB0RGTEB5', '2025-04-24 08:00:00', '2026-04-24 08:00:00', 3, 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `roles` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `roles`) VALUES
(1, 'Superadmin'),
(2, 'Admin'),
(3, 'Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_licencia`
--

CREATE TABLE `tipo_licencia` (
  `id_tipo_licencia` int(11) NOT NULL,
  `nombre_tipo_licencia` varchar(50) NOT NULL,
  `costo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_licencia`
--

INSERT INTO `tipo_licencia` (`id_tipo_licencia`, `nombre_tipo_licencia`, `costo`) VALUES
(1, 'Demo (3 días)', 0),
(2, '6 Meses', 50000),
(3, '1 Año', 90000),
(4, '2 Años', 150000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `documento` varchar(11) NOT NULL,
  `nombres` varchar(40) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`documento`, `nombres`, `telefono`, `email`, `clave`, `id_empresa`, `id_rol`) VALUES
('1000000001', 'Superadmin User', '3001234567', 'superadmin@estilo.com', '$2y$10$XP149X1oaIz5FDWAbHXLLOMgI88XOO4Jn7uPVArZgCBw.yxUqJZcC', 1, 1),
('2000000002', 'Admin User', '3109876543', 'admin@modaxyz.com', '$2y$10$XP149X1oaIz5FDWAbHXLLOMgI88XOO4Jn7uPVArZgCBw.yxUqJZcC', 2, 2),
('5000000005 ', 'Diego Vargas', '3119876543', 'diego@modaelegante.com', '$2y$10$WzQjCaj5NSDlu4Lggh2JHOM2WEQLuxc.iqfQmW1CIhc4ru4aG6hrC', 1, 2),
('8000000008', 'Laura Martínez', '3145678901', 'laura@modaelegante.com', '$2y$10$qQ/.i0JsIvoR0t5m9jkQXumLj3TMEh54ng3LYGsQztR52vO6hwvTO', 3, 2),
('9000000009', 'Juan Pérez', '3123456789', 'juan@modaelegante.com', '$2y$10$hu7WQSEXG/OdRwzX27T37ezDyGGCLKmVyuBdj204MHDHmtXx.S.hq', 3, 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asesores`
--
ALTER TABLE `asesores`
  ADD PRIMARY KEY (`id_asesor`),
  ADD KEY `fk_asesores_empresa` (`id_empresa`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `asesor_id` (`asesor_id`),
  ADD KEY `documento_usuario` (`documento_usuario`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id_empresa`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `licencia`
--
ALTER TABLE `licencia`
  ADD PRIMARY KEY (`id_licencia`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_tipo_licencia` (`id_tipo_licencia`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  ADD PRIMARY KEY (`id_tipo_licencia`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`documento`),
  ADD KEY `id_empresa` (`id_empresa`),
  ADD KEY `fk_usuarios_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asesores`
--
ALTER TABLE `asesores`
  MODIFY `id_asesor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estado`
--
ALTER TABLE `estado`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  MODIFY `id_tipo_licencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asesores`
--
ALTER TABLE `asesores`
  ADD CONSTRAINT `fk_asesores_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`);

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_citas_asesor` FOREIGN KEY (`asesor_id`) REFERENCES `asesores` (`id_asesor`),
  ADD CONSTRAINT `fk_citas_usuario` FOREIGN KEY (`documento_usuario`) REFERENCES `usuarios` (`documento`);

--
-- Filtros para la tabla `licencia`
--
ALTER TABLE `licencia`
  ADD CONSTRAINT `fk_licencia_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`),
  ADD CONSTRAINT `fk_licencia_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `fk_licencia_tipo_licencia` FOREIGN KEY (`id_tipo_licencia`) REFERENCES `tipo_licencia` (`id_tipo_licencia`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`),
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
