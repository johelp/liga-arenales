-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 06-04-2026 a las 06:42:23
-- Versión del servidor: 8.0.45-cll-lve
-- Versión de PHP: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `lvuizwgj_liga`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clubes`
--

CREATE TABLE `clubes` (
  `id_club` int NOT NULL,
  `nombre_corto` varchar(50) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `escudo_url` varchar(255) DEFAULT NULL,
  `fecha_fundacion` date DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clubes`
--

INSERT INTO `clubes` (`id_club`, `nombre_corto`, `nombre_completo`, `escudo_url`, `fecha_fundacion`, `direccion`, `telefono`, `sitio_web`) VALUES
(1, 'Singlar', 'Singlar Club Social y Deportivo', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/singlar.png', NULL, NULL, NULL, NULL),
(2, 'Belgrano', 'Club Atlético Belgrano', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/belgrano.png', NULL, NULL, NULL, NULL),
(3, 'Social', 'Club Social Deportivo Ascension', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/social.jpg', NULL, NULL, NULL, NULL),
(4, 'Huracan', 'Club Atlético Huracán', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/huracan.png', NULL, NULL, NULL, NULL),
(5, 'Agustina', 'Club Deportivo Agustina', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/agustina-fc.png', NULL, NULL, NULL, NULL),
(6, 'ArenalesFC', 'Arenales Fútbol Club', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/arenalesfc.png', NULL, NULL, NULL, NULL),
(7, '12deOctubre', 'Club 12 de Octubre', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/12deOctubre.png', NULL, NULL, NULL, NULL),
(8, 'Colonial', 'Club Colonial', 'https://ascensiondigital.ar/wp-content/uploads/2025/01/colonial.png', NULL, NULL, NULL, NULL),
(10, 'Independiente VC', 'Independiente Futbol Club', 'https://ascensiondigital.ar/wp-content/uploads/2025/04/INDEPENDOENTE.png', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clubes_en_division`
--

CREATE TABLE `clubes_en_division` (
  `id_club_division` int NOT NULL,
  `id_club` int NOT NULL,
  `id_division` int NOT NULL,
  `id_torneo` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clubes_en_division`
--

INSERT INTO `clubes_en_division` (`id_club_division`, `id_club`, `id_division`, `id_torneo`) VALUES
(7, 1, 1, 1),
(15, 1, 2, 1),
(51, 1, 3, 1),
(64, 1, 4, 1),
(72, 1, 5, 1),
(31, 1, 6, 1),
(45, 1, 7, 1),
(56, 1, 8, 1),
(4, 2, 1, 1),
(12, 2, 2, 1),
(48, 2, 3, 1),
(61, 2, 4, 1),
(69, 2, 5, 1),
(28, 2, 6, 1),
(42, 2, 7, 1),
(55, 2, 8, 1),
(8, 3, 1, 1),
(16, 3, 2, 1),
(52, 3, 3, 1),
(65, 3, 4, 1),
(73, 3, 5, 1),
(32, 3, 6, 1),
(46, 3, 7, 1),
(57, 3, 8, 1),
(6, 4, 1, 1),
(14, 4, 2, 1),
(50, 4, 3, 1),
(63, 4, 4, 1),
(71, 4, 5, 1),
(30, 4, 6, 1),
(2, 5, 1, 1),
(10, 5, 2, 1),
(59, 5, 4, 1),
(67, 5, 5, 1),
(26, 5, 6, 1),
(3, 6, 1, 1),
(11, 6, 2, 1),
(47, 6, 3, 1),
(60, 6, 4, 1),
(68, 6, 5, 1),
(27, 6, 6, 1),
(41, 6, 7, 1),
(54, 6, 8, 1),
(1, 7, 1, 1),
(9, 7, 2, 1),
(58, 7, 4, 1),
(66, 7, 5, 1),
(25, 7, 6, 1),
(53, 7, 8, 1),
(5, 8, 1, 1),
(13, 8, 2, 1),
(49, 8, 3, 1),
(62, 8, 4, 1),
(70, 8, 5, 1),
(29, 8, 6, 1),
(43, 8, 7, 1),
(44, 10, 7, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `divisiones`
--

CREATE TABLE `divisiones` (
  `id_division` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `orden` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `divisiones`
--

INSERT INTO `divisiones` (`id_division`, `nombre`, `orden`) VALUES
(1, 'Primera División', 1),
(2, 'División Reserva', 2),
(3, 'Femenino', 3),
(4, 'Octava División', 8),
(5, 'Cuarta División', 4),
(6, 'Sexta División', 6),
(7, 'Femenino Inferiores', 9),
(8, 'Femenino Juveniles', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `goles`
--

CREATE TABLE `goles` (
  `id_gol` int NOT NULL,
  `id_partido` int NOT NULL,
  `id_club` int NOT NULL,
  `id_jugador` int DEFAULT NULL,
  `minuto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jugadores`
--

CREATE TABLE `jugadores` (
  `id_jugador` int NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `id_club` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `jugadores`
--

INSERT INTO `jugadores` (`id_jugador`, `nombre`, `id_club`) VALUES
(1, 'Maxi Rodriguez', 7),
(2, 'Juan Perez', 3),
(3, 'Juancho', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

CREATE TABLE `partidos` (
  `id_partido` int NOT NULL,
  `id_torneo` int NOT NULL,
  `id_division` int NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `id_club_local` int NOT NULL,
  `goles_local` int DEFAULT NULL,
  `id_club_visitante` int NOT NULL,
  `goles_visitante` int DEFAULT NULL,
  `jugado` tinyint(1) DEFAULT '0',
  `arbitro` varchar(100) DEFAULT NULL,
  `estadio` varchar(100) DEFAULT NULL,
  `observaciones` text,
  `fecha_numero` int NOT NULL,
  `fase` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`id_partido`, `id_torneo`, `id_division`, `fecha_hora`, `id_club_local`, `goles_local`, `id_club_visitante`, `goles_visitante`, `jugado`, `arbitro`, `estadio`, `observaciones`, `fecha_numero`, `fase`) VALUES
(4, 1, 1, '2025-03-23 15:45:00', 8, 2, 2, 4, 1, NULL, NULL, NULL, 1, 'Primera Fase'),
(5, 1, 1, '2025-03-23 15:45:00', 4, 1, 1, 4, 1, '', 'Singlar', '', 1, 'Primera Fase'),
(6, 1, 1, '2025-03-23 15:45:00', 6, 1, 7, 2, 1, NULL, NULL, NULL, 1, 'Primera Fase'),
(7, 1, 1, '2025-03-23 15:45:00', 3, 1, 5, 0, 1, '', 'Social', '', 1, 'Primera Fase'),
(8, 1, 1, '2025-03-30 15:45:00', 2, 0, 3, 2, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(9, 1, 1, '2025-03-30 15:45:00', 7, 1, 4, 0, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(10, 1, 1, '2025-03-30 15:45:00', 1, 4, 8, 3, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(11, 1, 1, '2025-03-30 15:45:00', 6, 1, 5, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(12, 1, 1, '2025-04-06 15:45:00', 8, 1, 7, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(13, 1, 1, '2025-04-06 15:45:00', 4, 1, 5, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(14, 1, 1, '2025-04-06 15:45:00', 6, 1, 3, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(15, 1, 1, '2025-04-06 15:45:00', 1, 1, 2, 0, 1, 'Matia Bogado. Líneas: Tadeo Giambrone – Emiliano Barret', '', '', 3, 'Primera Fase'),
(28, 1, 1, '2025-04-13 15:30:00', 3, 1, 4, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(29, 1, 1, '2025-04-13 15:30:00', 5, 2, 8, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(30, 1, 1, '2025-04-13 15:30:00', 7, 0, 1, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(31, 1, 1, '2025-04-13 15:30:00', 2, 1, 6, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(32, 1, 1, '2025-04-27 15:30:00', 8, 2, 3, 2, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(33, 1, 1, '2025-04-27 15:30:00', 4, 0, 6, 2, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(34, 1, 1, '2025-05-01 16:00:00', 7, 2, 2, 1, 1, '', '', '', 5, 'Primera Fase'),
(35, 1, 1, '2025-04-27 15:30:00', 1, 1, 5, 1, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(128, 1, 2, '2025-03-23 15:45:00', 4, 0, 1, 0, 1, 'OSVALDO MARCHETTINI', 'HURACAN', '', 1, 'Primera Fase'),
(129, 1, 2, '2025-03-23 15:45:00', 6, 2, 7, 2, 1, 'FRANCISCO TAPIA', 'ARENALES F.C', '', 1, 'Primera Fase'),
(130, 1, 2, '2025-03-23 13:45:00', 3, 2, 5, 2, 1, 'JAVIER MARTIN', 'Social', '', 1, 'Primera Fase'),
(131, 1, 2, '2025-03-23 15:45:00', 8, 0, 2, 0, 1, 'TADDEO GIAMBRONE', '', '', 1, 'Primera Fase'),
(132, 1, 2, '2025-03-30 15:45:00', 2, 2, 3, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(133, 1, 2, '2025-03-30 15:45:00', 7, 0, 4, 6, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(134, 1, 2, '2025-03-30 15:45:00', 1, 2, 8, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(135, 1, 2, '2025-03-30 15:45:00', 6, 2, 5, 0, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(136, 1, 2, '2025-04-06 15:45:00', 8, 0, 7, 2, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(137, 1, 2, '2025-04-06 15:45:00', 4, 1, 5, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(138, 1, 2, '2025-04-06 15:45:00', 6, 3, 3, 2, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(139, 1, 2, '2025-04-06 15:45:00', 1, 2, 2, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(140, 1, 2, '2025-04-13 13:00:00', 7, 1, 1, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(145, 1, 2, '2025-04-13 13:00:00', 2, 0, 6, 3, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(146, 1, 2, '2025-04-13 13:00:00', 5, 0, 8, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(147, 1, 2, '2025-04-13 13:00:00', 3, 0, 4, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(148, 1, 2, '2025-05-04 00:03:00', 3, 2, 1, 2, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(149, 1, 2, '2025-05-04 00:03:00', 5, 0, 7, 1, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(150, 1, 2, '2025-05-04 00:03:00', 4, 3, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(151, 1, 2, '2025-05-04 00:03:00', 6, 1, 8, 2, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(157, 1, 5, '2025-03-22 15:45:00', 4, 1, 8, 1, 1, 'JAVIER MARTIN', 'HURACAN', '', 1, 'Primera Fase'),
(158, 1, 5, '2025-03-22 15:45:00', 3, 2, 2, 0, 1, 'SANTIAGO ALVAREZ', 'Social', '', 1, 'Primera Fase'),
(159, 1, 5, '2025-03-22 15:45:00', 1, 2, 7, 3, 1, 'OSVALDO MARCHETTINI', 'SINGLAR', '', 1, 'Primera Fase'),
(184, 1, 6, '2025-03-22 15:45:00', 1, 4, 7, 0, 1, 'JONHATAN MORENO', 'Singlar', '', 1, 'Primera Fase'),
(185, 1, 6, '2025-03-22 15:45:00', 4, 1, 8, 0, 1, 'GASTON LAGORIO', 'HURACAN', '', 1, 'Primera Fase'),
(186, 1, 6, '2025-03-22 15:45:00', 3, 2, 2, 0, 1, 'JESUS OLGUIN', 'Social', '', 1, 'Primera Fase'),
(212, 1, 4, '2025-03-22 15:45:00', 1, 2, 7, 1, 1, 'CARLOS RAJOY', 'Singlar', '', 1, 'Primera Fase'),
(213, 1, 4, '2025-03-22 15:45:00', 4, 9, 8, 0, 1, 'TADDEO GIAMBRONE', 'HURACAN', '', 1, 'Primera Fase'),
(214, 1, 4, '2025-03-22 15:45:00', 3, 3, 2, 2, 1, 'MARTIN CACERES', 'Social', '', 1, 'Primera Fase'),
(240, 1, 7, '2025-04-05 12:30:00', 10, 5, 1, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(241, 1, 7, '2025-04-05 12:30:00', 6, 3, 8, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(242, 1, 8, '2025-04-05 12:30:00', 3, 1, 7, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(244, 1, 8, '2025-03-22 12:30:00', 1, 1, 7, 0, 1, '', 'SINGLAR', '', 1, 'Primera Fase'),
(245, 1, 8, '2025-03-22 12:30:00', 3, 1, 2, 0, 1, '', 'SOCIAL', '', 1, 'Primera Fase'),
(246, 1, 7, '2025-03-22 12:30:00', 3, 5, 2, 0, 1, '', 'Social', '', 1, 'Primera Fase'),
(247, 1, 7, '2025-03-22 12:30:00', 10, 4, 8, 0, 1, '', '', 'PARTIDO A DEFINIRSE CUANDO SE JUEGA', 1, 'Primera Fase'),
(248, 1, 3, '2025-03-23 12:30:00', 8, 1, 2, 1, 1, 'SANTIAGO ALVAREZ', 'COLONIAL', '', 1, 'Primera Fase'),
(249, 1, 3, '2025-03-23 12:45:00', 4, 0, 1, 2, 1, 'JONHATAN MORENO', 'HURACAN', '', 1, 'Primera Fase'),
(250, 1, 5, '2025-03-29 16:00:00', 7, 1, 4, 0, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(251, 1, 5, '2025-03-29 16:00:00', 8, 0, 3, 7, 1, '', '', '', 2, 'Primera Fase'),
(252, 1, 5, '2025-03-29 16:00:00', 2, 3, 6, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(253, 1, 6, '2025-03-29 15:00:00', 7, 0, 4, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(254, 1, 6, '2025-03-29 15:00:00', 8, 0, 3, 0, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(255, 1, 6, '2025-03-29 15:00:00', 2, 2, 6, 0, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(256, 1, 4, '2025-03-29 14:00:00', 7, 1, 4, 5, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(257, 1, 4, '2025-03-29 14:00:00', 8, 0, 3, 3, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(258, 1, 4, '2025-03-29 14:00:00', 2, 2, 6, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(259, 1, 7, '2025-03-29 12:30:00', 8, 0, 3, 9, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(260, 1, 7, '2025-03-29 12:30:00', 2, 0, 6, 1, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(261, 1, 8, '2025-03-29 12:30:00', 2, 0, 6, 1, 1, '', '', 'se le otorga el partido ganado 1 a 0 al club Arenales F.C por jugadora mal incluida del Club Belgrano.', 2, 'Primera Fase'),
(262, 1, 3, '2025-03-30 12:30:00', 1, 1, 8, 3, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(263, 1, 3, '2025-03-30 12:30:00', 2, 1, 3, 2, 1, NULL, NULL, NULL, 2, 'Primera Fase'),
(264, 1, 5, '2025-04-05 16:00:00', 3, 3, 7, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(265, 1, 5, '2025-04-05 16:00:00', 4, 0, 1, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(266, 1, 5, '2025-04-05 16:00:00', 6, 0, 8, 0, 1, '', '', 'partido suspendido.', 3, 'Primera Fase'),
(267, 1, 6, '2025-04-05 15:00:00', 3, 1, 7, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(268, 1, 6, '2025-04-05 15:00:00', 4, 2, 1, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(269, 1, 6, '2025-04-05 15:00:00', 6, 2, 8, 2, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(270, 1, 4, '2025-04-05 14:00:00', 3, 3, 7, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(271, 1, 4, '2025-04-05 14:00:00', 4, 4, 1, 1, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(272, 1, 4, '2025-04-05 14:00:00', 6, 7, 8, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(273, 1, 3, '2025-04-06 12:00:00', 1, 1, 2, 4, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(274, 1, 3, '2025-04-06 12:00:00', 6, 2, 3, 0, 1, NULL, NULL, NULL, 3, 'Primera Fase'),
(275, 1, 4, '2025-04-26 14:00:00', 1, 1, 3, 0, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(277, 1, 4, '2025-04-26 14:00:00', 8, 0, 2, 7, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(278, 1, 6, '2025-04-26 15:00:00', 1, 0, 3, 2, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(280, 1, 6, '2025-04-26 15:00:00', 8, 1, 2, 0, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(281, 1, 5, '2025-04-26 16:00:00', 1, 0, 3, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(282, 1, 5, '2025-04-26 16:00:00', 8, 1, 2, 2, 1, '', '', '', 4, 'Primera Fase'),
(283, 1, 7, '2025-04-26 12:30:00', 1, 0, 3, 9, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(284, 1, 7, '2025-04-26 12:30:00', 8, 1, 2, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(285, 1, 8, '2025-04-26 12:30:00', 1, 0, 3, 2, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(287, 1, 3, '2025-04-13 12:00:00', 3, 3, 4, 0, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(288, 1, 3, '2025-04-13 12:00:00', 2, 2, 6, 1, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(289, 1, 1, '2025-05-04 15:30:00', 5, 1, 7, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(290, 1, 1, '2025-05-04 15:30:00', 3, 0, 1, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(291, 1, 1, '2025-05-04 15:30:00', 6, 0, 8, 2, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(292, 1, 3, '2025-04-27 14:00:00', 8, 1, 3, 1, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(293, 1, 3, '2025-04-27 12:00:00', 4, 0, 6, 3, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(294, 1, 2, '2025-04-27 15:30:00', 8, 0, 3, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(295, 1, 2, '2025-04-27 13:30:00', 4, 0, 6, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(296, 1, 2, '2025-04-27 13:30:00', 1, 0, 5, 2, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(297, 1, 2, '2025-05-01 14:00:00', 7, 1, 2, 2, 1, '', '', '', 5, 'Primera Fase'),
(298, 1, 7, '2025-05-03 12:00:00', 6, 1, 1, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(299, 1, 7, '2025-05-03 12:00:00', 10, 2, 3, 1, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(300, 1, 8, '2025-05-03 12:00:00', 6, 0, 1, 1, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(301, 1, 8, '2025-05-03 12:00:00', 2, 0, 7, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(302, 1, 4, '2025-05-03 14:00:00', 6, 5, 1, 4, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(303, 1, 4, '2025-05-03 14:00:00', 3, 2, 4, 4, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(304, 1, 4, '2025-05-03 14:00:00', 2, 2, 7, 0, 1, '', '', '', 5, 'Primera Fase'),
(308, 1, 6, '2025-05-03 15:00:00', 6, 0, 1, 3, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(309, 1, 6, '2025-05-03 15:00:00', 3, 0, 4, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(310, 1, 6, '2025-05-03 15:00:00', 2, 2, 7, 3, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(311, 1, 5, '2025-05-03 16:00:00', 6, 2, 1, 1, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(312, 1, 5, '2025-05-03 16:00:00', 3, 3, 4, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(313, 1, 5, '2025-05-03 16:00:00', 2, 1, 7, 0, 1, NULL, NULL, NULL, 5, 'Primera Fase'),
(314, 1, 3, '2025-05-04 12:00:00', 3, 0, 1, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(315, 1, 3, '2025-05-04 12:00:00', 6, 2, 8, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(316, 1, 3, '2025-05-04 16:00:00', 4, 1, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(317, 1, 7, '2025-05-10 12:00:00', 1, 0, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(318, 1, 7, '2025-05-10 12:00:00', 10, 5, 6, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(319, 1, 8, '2025-05-10 12:00:00', 1, 0, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(320, 1, 4, '2025-05-10 13:00:00', 1, 1, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(321, 1, 4, '2025-05-10 13:00:00', 7, 2, 8, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(322, 1, 4, '2025-05-10 13:00:00', 4, 0, 6, 4, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(323, 1, 6, '2025-05-10 14:00:00', 1, 3, 2, 1, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(324, 1, 6, '2025-05-10 14:00:00', 7, 1, 8, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(325, 1, 6, '2025-05-10 14:00:00', 4, 2, 6, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(326, 1, 5, '2025-05-10 15:00:00', 1, 1, 2, 2, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(327, 1, 5, '2025-05-10 15:00:00', 7, 4, 8, 1, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(328, 1, 5, '2025-05-10 15:00:00', 4, 0, 6, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(329, 1, 3, '2025-05-11 12:00:00', 1, 0, 6, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(330, 1, 3, '2025-05-11 12:00:00', 8, 2, 4, 0, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(331, 1, 2, '2025-05-11 13:30:00', 1, 4, 6, 1, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(332, 1, 2, '2025-05-11 13:30:00', 8, 1, 4, 0, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(333, 1, 2, '2025-05-11 13:30:00', 2, 0, 5, 3, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(334, 1, 2, '2025-05-11 13:30:00', 7, 0, 3, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(335, 1, 1, '2025-05-11 15:30:00', 1, 1, 6, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(336, 1, 1, '2025-05-11 15:30:00', 2, 1, 5, 1, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(337, 1, 1, '2025-05-11 15:30:00', 8, 1, 4, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(338, 1, 1, '2025-05-11 15:30:00', 7, 1, 3, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(339, 1, 3, '2025-05-25 12:00:00', 2, 2, 8, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(340, 1, 3, '2025-05-25 12:00:00', 1, 0, 4, 2, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(341, 1, 2, '2025-05-25 13:30:00', 2, 1, 8, 3, 1, '', '', '', 8, 'Primera Fase'),
(342, 1, 2, '2025-05-25 13:30:00', 1, 3, 4, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(343, 1, 2, '2025-05-25 13:30:00', 7, 2, 6, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(344, 1, 2, '2025-05-25 13:30:00', 5, 3, 3, 2, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(345, 1, 1, '2025-05-25 15:30:00', 2, 1, 8, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(346, 1, 1, '2025-05-25 15:30:00', 1, 1, 4, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(347, 1, 1, '2025-05-25 15:30:00', 7, 0, 6, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(348, 1, 1, '2025-05-25 15:30:00', 5, 2, 3, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(349, 1, 7, '2025-05-24 12:00:00', 8, 1, 1, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(350, 1, 7, '2025-05-24 12:00:00', 2, 0, 10, 2, 1, '', '', '', 7, 'Primera Fase'),
(351, 1, 4, '2025-05-24 13:30:00', 8, 0, 1, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(352, 1, 4, '2025-05-24 13:30:00', 2, 1, 4, 3, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(353, 1, 4, '2025-05-24 13:30:00', 6, 2, 3, 3, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(354, 1, 6, '2025-05-24 14:30:00', 8, 0, 1, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(355, 1, 6, '2025-05-24 14:30:00', 2, 1, 4, 0, 1, '', '', '', 7, 'Primera Fase'),
(356, 1, 6, '2025-05-24 14:30:00', 6, 0, 3, 4, 1, '', '', '', 7, 'Primera Fase'),
(357, 1, 5, '2025-05-24 15:30:00', 8, 0, 1, 0, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(358, 1, 5, '2025-05-24 15:30:00', 2, 0, 4, 1, 1, '', '', '', 7, 'Primera Fase'),
(359, 1, 5, '2025-05-24 15:30:00', 6, 2, 3, 2, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(361, 1, 1, '2025-05-14 17:30:00', 4, 3, 2, 0, 1, NULL, NULL, NULL, 6, 'Primera Fase'),
(362, 1, 7, '2025-05-31 12:00:00', 2, 0, 3, 5, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(363, 1, 8, '2025-05-31 12:00:00', 2, 0, 3, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(364, 1, 4, '2025-05-25 14:00:00', 7, 2, 1, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(365, 1, 4, '2025-05-25 14:00:00', 2, 3, 3, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(366, 1, 4, '2025-05-25 14:00:00', 8, 0, 4, 3, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(367, 1, 6, '2025-05-25 15:00:00', 7, 1, 1, 1, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(368, 1, 6, '2025-05-25 15:00:00', 2, 0, 3, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(369, 1, 6, '2025-05-25 15:00:00', 8, 2, 4, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(370, 1, 5, '2025-05-31 16:00:00', 7, 4, 1, 2, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(371, 1, 5, '2025-05-31 16:00:00', 2, 2, 3, 3, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(372, 1, 5, '2025-05-31 16:00:00', 8, 0, 4, 2, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(373, 1, 3, '2025-06-01 12:00:00', 8, 0, 1, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(374, 1, 3, '2025-06-01 12:00:00', 3, 1, 2, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(375, 1, 2, '2025-06-01 13:30:00', 4, 0, 7, 2, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(376, 1, 2, '2025-06-01 13:30:00', 8, 1, 1, 2, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(377, 1, 2, '2025-06-01 13:30:00', 3, 2, 2, 1, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(378, 1, 2, '2025-06-01 13:30:00', 6, 3, 5, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(379, 1, 1, '2025-06-01 15:30:00', 4, 1, 7, 2, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(380, 1, 1, '2025-06-01 15:30:00', 8, 4, 1, 3, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(381, 1, 1, '2025-06-01 15:30:00', 3, 0, 2, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(382, 1, 1, '2025-06-01 15:30:00', 6, 1, 5, 2, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(383, 1, 7, '2025-06-07 12:00:00', 6, 3, 2, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(384, 1, 7, '2025-06-07 12:00:00', 3, 9, 8, 1, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(385, 1, 8, '2025-06-07 12:00:00', 6, 0, 2, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(386, 1, 4, '2025-06-07 14:00:00', 4, 1, 7, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(387, 1, 4, '2025-06-07 14:00:00', 3, 3, 8, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(388, 1, 4, '2025-06-07 14:00:00', 6, 4, 2, 1, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(389, 1, 6, '2025-06-07 15:00:00', 4, 0, 7, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(390, 1, 6, '2025-06-07 15:00:00', 3, 0, 8, 0, 0, '', '', 'en este partido no sumaran puntos ninguno de los 2 equipos', 9, 'Primera Fase'),
(391, 1, 6, '2025-06-07 15:00:00', 6, 0, 2, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(392, 1, 5, '2025-06-07 15:00:00', 4, 3, 7, 0, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(393, 1, 5, '2025-06-07 15:00:00', 3, 4, 8, 1, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(394, 1, 5, '2025-06-07 15:00:00', 6, 4, 2, 1, 1, NULL, NULL, NULL, 9, 'Primera Fase'),
(395, 1, 3, '2025-06-08 12:00:00', 3, 1, 6, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(396, 1, 3, '2025-06-08 12:00:00', 2, 3, 1, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(397, 1, 2, '2025-06-08 13:30:00', 7, 1, 8, 2, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(398, 1, 2, '2025-06-08 13:30:00', 5, 2, 4, 1, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(399, 1, 2, '2025-06-08 13:30:00', 3, 1, 6, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(400, 1, 2, '2025-06-08 13:30:00', 2, 2, 1, 2, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(401, 1, 1, '2025-06-08 15:30:00', 7, 0, 8, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(402, 1, 1, '2025-06-08 15:30:00', 5, 1, 4, 2, 1, '', '', '', 10, 'Primera Fase'),
(403, 1, 1, '2025-06-08 15:30:00', 3, 1, 6, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(404, 1, 1, '2025-06-08 15:30:00', 2, 0, 1, 2, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(405, 1, 7, '2025-05-31 12:00:00', 8, 0, 10, 9, 1, NULL, NULL, NULL, 8, 'Primera Fase'),
(406, 1, 8, '2025-04-12 12:00:00', 7, 1, 6, 0, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(407, 1, 4, '2025-04-12 14:00:00', 7, 0, 6, 2, 1, '', '', '', 4, 'Primera Fase'),
(408, 1, 6, '2025-04-12 15:00:00', 7, 2, 6, 0, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(409, 1, 5, '2025-04-12 16:00:00', 7, 1, 6, 2, 1, NULL, NULL, NULL, 4, 'Primera Fase'),
(410, 1, 7, '2025-05-24 12:00:00', 6, 0, 3, 7, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(411, 1, 8, '2025-05-24 12:00:00', 6, 1, 3, 0, 1, NULL, NULL, NULL, 7, 'Primera Fase'),
(412, 1, 3, '2025-06-15 12:00:00', 4, 0, 3, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(413, 1, 3, '2025-06-15 13:30:00', 6, 1, 2, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(414, 1, 2, '2025-06-15 13:30:00', 4, 1, 3, 1, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(415, 1, 2, '2025-06-15 15:30:00', 6, 3, 2, 2, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(416, 1, 2, '2025-06-15 13:30:00', 1, 2, 7, 1, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(417, 1, 2, '2025-06-15 15:00:00', 8, 4, 5, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(418, 1, 1, '2025-06-15 15:30:00', 4, 0, 3, 1, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(419, 1, 1, '2025-06-15 15:30:00', 1, 3, 7, 1, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(420, 1, 1, '2025-06-15 15:30:00', 6, 3, 2, 0, 1, '', '', '', 11, 'Primera Fase'),
(421, 1, 1, '2025-06-15 17:00:00', 8, 1, 5, 1, 1, '', '', '', 11, 'Primera Fase'),
(422, 1, 7, '2025-06-21 12:00:00', 1, 0, 10, 18, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(423, 1, 7, '2025-06-21 12:00:00', 8, 0, 6, 1, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(424, 1, 8, '2025-06-21 12:00:00', 7, 0, 3, 1, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(425, 1, 4, '2025-06-21 14:00:00', 1, 1, 4, 1, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(426, 1, 4, '2025-06-21 14:00:00', 8, 0, 6, 7, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(427, 1, 4, '2025-06-21 14:00:00', 7, 2, 3, 3, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(428, 1, 6, '2025-06-16 15:00:00', 1, 2, 4, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(429, 1, 6, '2025-06-16 15:00:00', 8, 1, 6, 1, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(430, 1, 6, '2025-06-16 15:00:00', 7, 0, 3, 0, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(431, 1, 5, '2025-06-21 16:00:00', 1, 1, 4, 2, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(432, 1, 5, '2025-06-21 16:00:00', 8, 0, 6, 5, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(433, 1, 5, '2025-06-21 16:00:00', 7, 1, 3, 5, 1, NULL, NULL, NULL, 10, 'Primera Fase'),
(434, 1, 3, '2025-06-22 12:00:00', 6, 2, 4, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(435, 1, 3, '2025-06-22 12:00:00', 3, 1, 8, 0, 1, '', '', '', 12, 'Primera Fase'),
(436, 1, 2, '2025-06-22 13:30:00', 6, 1, 4, 2, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(437, 1, 2, '2025-06-22 13:30:00', 5, 2, 1, 2, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(438, 1, 2, '2025-06-22 13:30:00', 3, 3, 8, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(439, 1, 2, '2025-06-22 13:30:00', 2, 4, 7, 5, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(440, 1, 1, '2025-06-22 15:30:00', 6, 2, 4, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(441, 1, 1, '2025-06-22 15:30:00', 5, 1, 1, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(442, 1, 1, '2025-06-22 15:30:00', 3, 1, 8, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(443, 1, 1, '2025-06-22 15:30:00', 2, 1, 7, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(444, 1, 7, '2025-06-28 12:00:00', 3, 9, 1, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(445, 1, 7, '2025-06-28 12:00:00', 2, 1, 8, 0, 1, '', '', '', 11, 'Primera Fase'),
(446, 1, 8, '2025-06-28 12:00:00', 3, 0, 1, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(447, 1, 4, '2025-06-28 14:00:00', 3, 2, 1, 2, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(448, 1, 4, '2025-06-28 14:00:00', 2, 4, 8, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(449, 1, 4, '2025-06-28 14:00:00', 6, 2, 7, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(450, 1, 8, '2025-06-29 14:00:00', 6, 0, 7, 1, 1, '', '', '', 11, 'Primera Fase'),
(451, 1, 6, '2025-06-28 15:00:00', 3, 1, 1, 2, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(452, 1, 6, '2025-06-28 15:00:00', 2, 1, 8, 2, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(453, 1, 6, '2025-06-28 15:00:00', 6, 0, 7, 1, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(454, 1, 5, '2025-06-28 16:00:00', 3, 2, 1, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(455, 1, 5, '2025-06-28 16:00:00', 2, 4, 8, 0, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(456, 1, 5, '2025-06-28 16:00:00', 6, 3, 7, 2, 1, NULL, NULL, NULL, 11, 'Primera Fase'),
(457, 1, 3, '2025-06-29 12:00:00', 8, 0, 6, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(458, 1, 3, '2025-06-29 12:00:00', 1, 0, 3, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(459, 1, 3, '2025-06-29 12:00:00', 2, 4, 4, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(460, 1, 2, '2025-06-29 13:30:00', 8, 1, 6, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(461, 1, 2, '2025-06-29 13:30:00', 1, 0, 3, 2, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(462, 1, 2, '2025-06-29 13:30:00', 2, 0, 4, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(463, 1, 2, '2025-06-29 13:30:00', 7, 0, 5, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(464, 1, 1, '2025-06-29 15:30:00', 8, 3, 6, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(465, 1, 1, '2025-06-29 15:30:00', 1, 0, 3, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(466, 1, 1, '2025-06-29 15:30:00', 2, 0, 4, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(467, 1, 1, '2025-06-29 15:30:00', 7, 3, 5, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(468, 1, 7, '2025-07-05 12:00:00', 1, 0, 6, 9, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(469, 1, 7, '2025-07-05 12:00:00', 10, 5, 3, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(470, 1, 8, '2025-07-05 12:00:00', 1, 0, 6, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(471, 1, 8, '2025-07-05 12:00:00', 7, 1, 2, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(472, 1, 4, '2025-07-05 14:00:00', 1, 0, 6, 5, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(473, 1, 4, '2025-07-05 14:00:00', 4, 2, 3, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(474, 1, 4, '2025-07-05 14:00:00', 7, 0, 2, 4, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(475, 1, 6, '2025-07-05 15:00:00', 1, 2, 6, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(476, 1, 6, '2025-07-05 15:00:00', 7, 2, 2, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(477, 1, 6, '2025-07-05 15:00:00', 4, 0, 3, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(478, 1, 5, '2025-07-05 16:00:00', 1, 2, 6, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(479, 1, 5, '2025-07-05 16:00:00', 7, 2, 2, 1, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(480, 1, 5, '2025-07-05 16:00:00', 4, 1, 3, 0, 1, NULL, NULL, NULL, 12, 'Primera Fase'),
(481, 1, 3, '2025-07-06 12:00:00', 6, 1, 1, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(482, 1, 3, '2025-07-06 12:00:00', 4, 2, 8, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(483, 1, 2, '2025-07-06 13:30:00', 6, 3, 1, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(484, 1, 2, '2025-07-06 13:30:00', 4, 2, 8, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(485, 1, 2, '2025-07-06 13:30:00', 5, 3, 2, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(486, 1, 2, '2025-07-06 13:30:00', 3, 0, 7, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(487, 1, 1, '2025-07-06 15:30:00', 6, 1, 1, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(488, 1, 1, '2025-07-06 15:30:00', 4, 0, 8, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(489, 1, 1, '2025-07-06 15:30:00', 5, 1, 2, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(490, 1, 1, '2025-07-06 15:30:00', 3, 1, 7, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(491, 1, 7, '2025-07-12 12:00:00', 2, 6, 1, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(492, 1, 7, '2025-07-12 12:00:00', 6, 1, 10, 4, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(493, 1, 8, '2025-07-12 12:00:00', 2, 0, 1, 2, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(494, 1, 4, '2025-07-12 14:00:00', 2, 2, 1, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(495, 1, 4, '2025-07-12 14:00:00', 8, 0, 7, 3, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(496, 1, 4, '2025-07-12 14:00:00', 6, 1, 4, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(497, 1, 6, '2025-07-12 15:00:00', 2, 0, 1, 2, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(498, 1, 6, '2025-07-12 15:00:00', 8, 4, 7, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(499, 1, 6, '2025-07-12 15:00:00', 6, 0, 4, 1, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(500, 1, 5, '2025-07-12 16:00:00', 2, 0, 1, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(501, 1, 5, '2025-07-12 16:00:00', 8, 1, 7, 3, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(502, 1, 5, '2025-07-12 16:00:00', 6, 0, 4, 0, 1, NULL, NULL, NULL, 13, 'Primera Fase'),
(505, 1, 2, '2025-07-12 12:00:00', 8, NULL, 3, NULL, 0, NULL, NULL, NULL, 15, 'Cuartos de Final'),
(506, 1, 2, '2025-07-12 12:00:00', 6, NULL, 5, NULL, 0, NULL, NULL, NULL, 15, 'Cuartos de Final'),
(507, 1, 3, '2025-07-13 14:00:00', 2, 0, 1, 0, 1, '', '', 'GANADOR CLUB BELGRANO POR BENEFICIO DE VENTAJA DEPORTIVA', 15, 'Cuartos de Final'),
(508, 1, 3, '2025-07-13 14:00:00', 4, 1, 8, 1, 0, '', '', 'GANADOR CLUB HURACAN POR VENTAJA DEPORTIVA', 15, 'Cuartos de Final'),
(509, 1, 5, '2025-07-19 16:00:00', 1, 3, 8, 2, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(510, 1, 5, '2025-07-19 16:00:00', 3, 2, 6, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(511, 1, 5, '2025-07-19 16:00:00', 4, 0, 2, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(512, 1, 6, '2025-07-19 15:00:00', 1, 3, 8, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(513, 1, 6, '2025-07-19 15:00:00', 3, 2, 6, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(514, 1, 6, '2025-07-19 15:00:00', 4, 2, 2, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(515, 1, 4, '2025-07-19 14:00:00', 1, 1, 8, 7, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(516, 1, 4, '2025-07-19 14:00:00', 3, 0, 6, 1, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(517, 1, 4, '2025-07-19 14:00:00', 4, 2, 2, 3, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(518, 1, 8, '2025-07-19 12:00:00', 3, 0, 6, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(519, 1, 7, '2025-07-19 12:00:00', 1, 0, 8, 7, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(520, 1, 7, '2025-07-19 12:00:00', 3, 4, 6, 2, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(521, 1, 7, '2025-07-19 12:00:00', 10, 5, 2, 0, 1, NULL, NULL, NULL, 14, 'Primera Fase'),
(522, 1, 8, '2025-07-19 12:00:00', 7, 0, 1, 0, 1, NULL, NULL, NULL, 8, 'Primera Fase');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tarjetas`
--

CREATE TABLE `tarjetas` (
  `id_tarjeta` int NOT NULL,
  `id_partido` int NOT NULL,
  `id_club` int NOT NULL,
  `id_jugador` int DEFAULT NULL,
  `tipo` enum('amarilla','roja') NOT NULL,
  `minuto` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `torneos`
--

CREATE TABLE `torneos` (
  `id_torneo` int NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `descripcion` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `torneos`
--

INSERT INTO `torneos` (`id_torneo`, `nombre`, `fecha_inicio`, `fecha_fin`, `activo`, `descripcion`) VALUES
(1, 'Torneo Apertura 2025 \"40° Aniversario Canal 10 de Junin\"', '2025-03-21', '2025-06-30', 1, 'Torneo Homenaje al 40° Aniversario de Canal 10 de Junin');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clubes`
--
ALTER TABLE `clubes`
  ADD PRIMARY KEY (`id_club`),
  ADD UNIQUE KEY `nombre_corto` (`nombre_corto`),
  ADD UNIQUE KEY `nombre_completo` (`nombre_completo`);

--
-- Indices de la tabla `clubes_en_division`
--
ALTER TABLE `clubes_en_division`
  ADD PRIMARY KEY (`id_club_division`),
  ADD UNIQUE KEY `unique_club_division_torneo` (`id_club`,`id_division`,`id_torneo`),
  ADD KEY `id_division` (`id_division`),
  ADD KEY `id_torneo` (`id_torneo`);

--
-- Indices de la tabla `divisiones`
--
ALTER TABLE `divisiones`
  ADD PRIMARY KEY (`id_division`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `goles`
--
ALTER TABLE `goles`
  ADD PRIMARY KEY (`id_gol`),
  ADD KEY `id_partido` (`id_partido`),
  ADD KEY `id_club` (`id_club`),
  ADD KEY `id_jugador` (`id_jugador`);

--
-- Indices de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD PRIMARY KEY (`id_jugador`),
  ADD KEY `id_club` (`id_club`);

--
-- Indices de la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`id_partido`),
  ADD KEY `id_torneo` (`id_torneo`),
  ADD KEY `id_division` (`id_division`),
  ADD KEY `id_club_local` (`id_club_local`),
  ADD KEY `id_club_visitante` (`id_club_visitante`);

--
-- Indices de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD PRIMARY KEY (`id_tarjeta`),
  ADD KEY `id_partido` (`id_partido`),
  ADD KEY `id_club` (`id_club`),
  ADD KEY `id_jugador` (`id_jugador`);

--
-- Indices de la tabla `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id_torneo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clubes`
--
ALTER TABLE `clubes`
  MODIFY `id_club` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clubes_en_division`
--
ALTER TABLE `clubes_en_division`
  MODIFY `id_club_division` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de la tabla `divisiones`
--
ALTER TABLE `divisiones`
  MODIFY `id_division` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `goles`
--
ALTER TABLE `goles`
  MODIFY `id_gol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id_jugador` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id_partido` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- AUTO_INCREMENT de la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  MODIFY `id_tarjeta` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id_torneo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clubes_en_division`
--
ALTER TABLE `clubes_en_division`
  ADD CONSTRAINT `clubes_en_division_ibfk_1` FOREIGN KEY (`id_club`) REFERENCES `clubes` (`id_club`),
  ADD CONSTRAINT `clubes_en_division_ibfk_2` FOREIGN KEY (`id_division`) REFERENCES `divisiones` (`id_division`),
  ADD CONSTRAINT `clubes_en_division_ibfk_3` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`);

--
-- Filtros para la tabla `goles`
--
ALTER TABLE `goles`
  ADD CONSTRAINT `goles_ibfk_1` FOREIGN KEY (`id_partido`) REFERENCES `partidos` (`id_partido`),
  ADD CONSTRAINT `goles_ibfk_2` FOREIGN KEY (`id_club`) REFERENCES `clubes` (`id_club`),
  ADD CONSTRAINT `goles_ibfk_3` FOREIGN KEY (`id_jugador`) REFERENCES `jugadores` (`id_jugador`);

--
-- Filtros para la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD CONSTRAINT `jugadores_ibfk_1` FOREIGN KEY (`id_club`) REFERENCES `clubes` (`id_club`);

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`id_torneo`) REFERENCES `torneos` (`id_torneo`),
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`id_division`) REFERENCES `divisiones` (`id_division`),
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`id_club_local`) REFERENCES `clubes` (`id_club`),
  ADD CONSTRAINT `partidos_ibfk_4` FOREIGN KEY (`id_club_visitante`) REFERENCES `clubes` (`id_club`);

--
-- Filtros para la tabla `tarjetas`
--
ALTER TABLE `tarjetas`
  ADD CONSTRAINT `tarjetas_ibfk_1` FOREIGN KEY (`id_partido`) REFERENCES `partidos` (`id_partido`),
  ADD CONSTRAINT `tarjetas_ibfk_2` FOREIGN KEY (`id_club`) REFERENCES `clubes` (`id_club`),
  ADD CONSTRAINT `tarjetas_ibfk_3` FOREIGN KEY (`id_jugador`) REFERENCES `jugadores` (`id_jugador`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
