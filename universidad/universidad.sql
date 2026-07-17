-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 17, 2026 at 12:12 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `universidad`
--

-- --------------------------------------------------------

--
-- Table structure for table `inicio_sesion`
--

CREATE TABLE `inicio_sesion` (
  `cedula` int NOT NULL,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `intentos` int NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inicio_sesion`
--

INSERT INTO `inicio_sesion` (`cedula`, `usuario`, `correo`, `contrasena`, `estado`, `intentos`, `creado_en`) VALUES
(112, 'juan', 'j@gmail.com', '$2y$10$I2RVtuLmR7CR774vxOczQOpG25ZDiKHRhgL34jtNkZtc.00xjpVa2', 'A', 3, '2026-07-04 02:06:34'),
(1111, 'maria', 'm@hotmail.com', '$2y$10$gYvElZDK6Pbu3rwjFC7aY.JP4SqyWuB1INEC5i43rS58B5CytR4Ku', 'A', 0, '2026-07-04 00:12:46');

-- --------------------------------------------------------

--
-- Table structure for table `t_carrera`
--

CREATE TABLE `t_carrera` (
  `id_carrera` int NOT NULL,
  `car_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `car_observacion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `car_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `car_fechahorareg` datetime NOT NULL,
  `car_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_carrera_curso`
--

CREATE TABLE `t_carrera_curso` (
  `id_carreracurso` int NOT NULL,
  `id_carrera` int NOT NULL,
  `id_curso` int NOT NULL,
  `cac_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cac_fechahorareg` datetime NOT NULL,
  `cac_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_curso`
--

CREATE TABLE `t_curso` (
  `id_curso` int NOT NULL,
  `cur_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cur_observacion` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cur_costo` decimal(15,2) NOT NULL,
  `cur_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cur_fechahorareg` datetime NOT NULL,
  `cur_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_estudiante`
--

CREATE TABLE `t_estudiante` (
  `id_estudiante` int NOT NULL,
  `est_identificacion` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_fechanace` date NOT NULL,
  `id_genero` int NOT NULL,
  `est_correo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_telefono` decimal(8,0) NOT NULL,
  `est_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_fechahorareg` datetime NOT NULL,
  `est_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_genero`
--

CREATE TABLE `t_genero` (
  `id_genero` int NOT NULL,
  `gen_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gen_comentario` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gen_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gen_fechahorareg` datetime NOT NULL,
  `gen_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_matricula`
--

CREATE TABLE `t_matricula` (
  `id_matricula` int NOT NULL,
  `id_estudiante` int NOT NULL,
  `id_periodo` int NOT NULL,
  `id_curso` int NOT NULL,
  `mat_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mat_fechahorareg` datetime NOT NULL,
  `mat_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_modulo`
--

CREATE TABLE `t_modulo` (
  `id_modulo` int NOT NULL,
  `mod_nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mod_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mod_padre` int NOT NULL,
  `mod_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mod_fechahorareg` datetime NOT NULL,
  `mod_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_modulo_rol`
--

CREATE TABLE `t_modulo_rol` (
  `id_modulorol` int NOT NULL,
  `id_modulo` int NOT NULL,
  `id_rol` int NOT NULL,
  `mor_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mor_fechahorareg` datetime NOT NULL,
  `mor_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_nota_rubro`
--

CREATE TABLE `t_nota_rubro` (
  `id_notarubro` int NOT NULL,
  `id_rubro_curso` int NOT NULL,
  `id_matricula` int NOT NULL,
  `nor_porcenorje_obt` decimal(3,2) NOT NULL,
  `nor_comenorrio` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nor_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nor_fechahorareg` datetime NOT NULL,
  `nor_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_periodo`
--

CREATE TABLE `t_periodo` (
  `id_periodo` int NOT NULL,
  `per_nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `per_fechainicia` date NOT NULL,
  `per_fechafin` date NOT NULL,
  `per_descripcion` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `per_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `per_fechahorareg` datetime NOT NULL,
  `per_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_profesor`
--

CREATE TABLE `t_profesor` (
  `id_profesor` int NOT NULL,
  `pro_identificacion` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_correo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_telefono` decimal(8,0) NOT NULL,
  `pro_especialidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pro_fechahorareg` datetime NOT NULL,
  `pro_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_rol`
--

CREATE TABLE `t_rol` (
  `id_rol` int NOT NULL,
  `rol_nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_fechahorareg` datetime NOT NULL,
  `rol_usuarioreg` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `t_rol`
--

INSERT INTO `t_rol` (`id_rol`, `rol_nombre`, `rol_estado`, `rol_fechahorareg`, `rol_usuarioreg`) VALUES
(1, 'ADMINISTRADOR', 'A', '2026-07-16 16:54:59', NULL),
(2, 'PROFESOR', 'A', '2026-07-16 17:14:16', NULL),
(3, 'ESTUDIANTE', 'A', '2026-07-16 17:14:16', NULL),
(4, 'SECRETARIA', 'A', '2026-07-16 17:14:16', NULL),
(5, 'COORDINADOR', 'A', '2026-07-16 17:14:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `t_rubro`
--

CREATE TABLE `t_rubro` (
  `id_rubro` int NOT NULL,
  `rub_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rub_comentario` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rub_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rub_fechahorareg` datetime NOT NULL,
  `rub_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_rubro_curso`
--

CREATE TABLE `t_rubro_curso` (
  `id_rubrocurso` int NOT NULL,
  `id_curso` int NOT NULL,
  `id_rubro` int NOT NULL,
  `ruc_descripcion` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruc_porcentaje` decimal(3,2) NOT NULL,
  `ruc_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruc_fechahorareg` datetime NOT NULL,
  `ruc_usuarioreg` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `t_usuario`
--

CREATE TABLE `t_usuario` (
  `id_usuario` int NOT NULL,
  `usu_codigo` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usu_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usu_correo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usu_clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_rol` int NOT NULL,
  `usu_intentofallido` int NOT NULL,
  `usu_estado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usu_fechahorareg` datetime NOT NULL,
  `usu_usuarioreg` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `t_usuario`
--

INSERT INTO `t_usuario` (`id_usuario`, `usu_codigo`, `usu_nombre`, `usu_correo`, `usu_clave`, `id_rol`, `usu_intentofallido`, `usu_estado`, `usu_fechahorareg`, `usu_usuarioreg`) VALUES
(1, 'admim', 'Sysadmin', 'Sysadmin@uces.com', '$2y$10$yGM7Mpz3enA.efSEbmFtb..3NuxZ1G3TH3yXQ0EQ9fRZ1hEvXwbma', 1, 0, 'A', '2026-07-16 17:00:53', NULL),
(5, 'kmoraga2', 'kenneth Profesor', 'prof@uces.com', '$2y$10$hL7nfesnACYlsSPA6n.YmOdpQX9SyN46Wpf7hXleio7rpOASkMjCO', 2, 0, 'A', '2026-07-16 17:21:05', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inicio_sesion`
--
ALTER TABLE `inicio_sesion`
  ADD PRIMARY KEY (`cedula`);

--
-- Indexes for table `t_carrera`
--
ALTER TABLE `t_carrera`
  ADD PRIMARY KEY (`id_carrera`),
  ADD KEY `fk_carrera_usuarioreg` (`car_usuarioreg`);

--
-- Indexes for table `t_carrera_curso`
--
ALTER TABLE `t_carrera_curso`
  ADD PRIMARY KEY (`id_carreracurso`),
  ADD KEY `fk_carreracurso_carrera` (`id_carrera`),
  ADD KEY `fk_carreracurso_curso` (`id_curso`),
  ADD KEY `fk_carreracurso_usuarioreg` (`cac_usuarioreg`);

--
-- Indexes for table `t_curso`
--
ALTER TABLE `t_curso`
  ADD PRIMARY KEY (`id_curso`),
  ADD KEY `fk_curso_usuarioreg` (`cur_usuarioreg`);

--
-- Indexes for table `t_estudiante`
--
ALTER TABLE `t_estudiante`
  ADD PRIMARY KEY (`id_estudiante`),
  ADD KEY `fk_estudiante_genero` (`id_genero`),
  ADD KEY `fk_estudiante_usuarioreg` (`est_usuarioreg`);

--
-- Indexes for table `t_genero`
--
ALTER TABLE `t_genero`
  ADD PRIMARY KEY (`id_genero`),
  ADD KEY `fk_genero_usuarioreg` (`gen_usuarioreg`);

--
-- Indexes for table `t_matricula`
--
ALTER TABLE `t_matricula`
  ADD PRIMARY KEY (`id_matricula`),
  ADD KEY `fk_matricula_estudiante` (`id_estudiante`),
  ADD KEY `fk_matricula_periodo` (`id_periodo`),
  ADD KEY `fk_matricula_curso` (`id_curso`),
  ADD KEY `fk_matricula_usuarioreg` (`mat_usuarioreg`);

--
-- Indexes for table `t_modulo`
--
ALTER TABLE `t_modulo`
  ADD PRIMARY KEY (`id_modulo`),
  ADD KEY `fk_modulo_padre` (`mod_padre`),
  ADD KEY `fk_modulo_usuarioreg` (`mod_usuarioreg`);

--
-- Indexes for table `t_modulo_rol`
--
ALTER TABLE `t_modulo_rol`
  ADD PRIMARY KEY (`id_modulorol`),
  ADD KEY `fk_modulorol_modulo` (`id_modulo`),
  ADD KEY `fk_modulorol_rol` (`id_rol`),
  ADD KEY `fk_modulorol_usuarioreg` (`mor_usuarioreg`);

--
-- Indexes for table `t_nota_rubro`
--
ALTER TABLE `t_nota_rubro`
  ADD PRIMARY KEY (`id_notarubro`),
  ADD KEY `fk_notarubro_rubrocurso` (`id_rubro_curso`),
  ADD KEY `fk_notarubro_matricula` (`id_matricula`),
  ADD KEY `fk_notarubro_usuarioreg` (`nor_usuarioreg`);

--
-- Indexes for table `t_periodo`
--
ALTER TABLE `t_periodo`
  ADD PRIMARY KEY (`id_periodo`),
  ADD KEY `fk_periodo_usuarioreg` (`per_usuarioreg`);

--
-- Indexes for table `t_profesor`
--
ALTER TABLE `t_profesor`
  ADD PRIMARY KEY (`id_profesor`),
  ADD KEY `fk_profesor_usuarioreg` (`pro_usuarioreg`);

--
-- Indexes for table `t_rol`
--
ALTER TABLE `t_rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD KEY `fk_rol_usuarioreg` (`rol_usuarioreg`);

--
-- Indexes for table `t_rubro`
--
ALTER TABLE `t_rubro`
  ADD PRIMARY KEY (`id_rubro`),
  ADD KEY `fk_rubro_usuarioreg` (`rub_usuarioreg`);

--
-- Indexes for table `t_rubro_curso`
--
ALTER TABLE `t_rubro_curso`
  ADD PRIMARY KEY (`id_rubrocurso`),
  ADD KEY `fk_rubrocurso_curso` (`id_curso`),
  ADD KEY `fk_rubrocurso_rubro` (`id_rubro`),
  ADD KEY `fk_rubrocurso_usuarioreg` (`ruc_usuarioreg`);

--
-- Indexes for table `t_usuario`
--
ALTER TABLE `t_usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `fk_usuario_rol` (`id_rol`),
  ADD KEY `fk_usuario_usuarioreg` (`usu_usuarioreg`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `t_carrera`
--
ALTER TABLE `t_carrera`
  MODIFY `id_carrera` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_carrera_curso`
--
ALTER TABLE `t_carrera_curso`
  MODIFY `id_carreracurso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_curso`
--
ALTER TABLE `t_curso`
  MODIFY `id_curso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_estudiante`
--
ALTER TABLE `t_estudiante`
  MODIFY `id_estudiante` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_genero`
--
ALTER TABLE `t_genero`
  MODIFY `id_genero` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_matricula`
--
ALTER TABLE `t_matricula`
  MODIFY `id_matricula` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_modulo`
--
ALTER TABLE `t_modulo`
  MODIFY `id_modulo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_modulo_rol`
--
ALTER TABLE `t_modulo_rol`
  MODIFY `id_modulorol` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_nota_rubro`
--
ALTER TABLE `t_nota_rubro`
  MODIFY `id_notarubro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_periodo`
--
ALTER TABLE `t_periodo`
  MODIFY `id_periodo` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_profesor`
--
ALTER TABLE `t_profesor`
  MODIFY `id_profesor` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_rol`
--
ALTER TABLE `t_rol`
  MODIFY `id_rol` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `t_rubro`
--
ALTER TABLE `t_rubro`
  MODIFY `id_rubro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_rubro_curso`
--
ALTER TABLE `t_rubro_curso`
  MODIFY `id_rubrocurso` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `t_usuario`
--
ALTER TABLE `t_usuario`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `t_carrera`
--
ALTER TABLE `t_carrera`
  ADD CONSTRAINT `fk_carrera_usuarioreg` FOREIGN KEY (`car_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_carrera_curso`
--
ALTER TABLE `t_carrera_curso`
  ADD CONSTRAINT `fk_carreracurso_carrera` FOREIGN KEY (`id_carrera`) REFERENCES `t_carrera` (`id_carrera`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carreracurso_curso` FOREIGN KEY (`id_curso`) REFERENCES `t_curso` (`id_curso`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_carreracurso_usuarioreg` FOREIGN KEY (`cac_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_curso`
--
ALTER TABLE `t_curso`
  ADD CONSTRAINT `fk_curso_usuarioreg` FOREIGN KEY (`cur_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_estudiante`
--
ALTER TABLE `t_estudiante`
  ADD CONSTRAINT `fk_estudiante_genero` FOREIGN KEY (`id_genero`) REFERENCES `t_genero` (`id_genero`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_estudiante_usuarioreg` FOREIGN KEY (`est_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_genero`
--
ALTER TABLE `t_genero`
  ADD CONSTRAINT `fk_genero_usuarioreg` FOREIGN KEY (`gen_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_matricula`
--
ALTER TABLE `t_matricula`
  ADD CONSTRAINT `fk_matricula_curso` FOREIGN KEY (`id_curso`) REFERENCES `t_curso` (`id_curso`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matricula_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `t_estudiante` (`id_estudiante`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matricula_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `t_periodo` (`id_periodo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matricula_usuarioreg` FOREIGN KEY (`mat_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_modulo`
--
ALTER TABLE `t_modulo`
  ADD CONSTRAINT `fk_modulo_padre` FOREIGN KEY (`mod_padre`) REFERENCES `t_modulo` (`id_modulo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modulo_usuarioreg` FOREIGN KEY (`mod_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_modulo_rol`
--
ALTER TABLE `t_modulo_rol`
  ADD CONSTRAINT `fk_modulorol_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `t_modulo` (`id_modulo`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modulorol_rol` FOREIGN KEY (`id_rol`) REFERENCES `t_rol` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_modulorol_usuarioreg` FOREIGN KEY (`mor_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_nota_rubro`
--
ALTER TABLE `t_nota_rubro`
  ADD CONSTRAINT `fk_notarubro_matricula` FOREIGN KEY (`id_matricula`) REFERENCES `t_matricula` (`id_matricula`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notarubro_rubrocurso` FOREIGN KEY (`id_rubro_curso`) REFERENCES `t_rubro_curso` (`id_rubrocurso`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notarubro_usuarioreg` FOREIGN KEY (`nor_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_periodo`
--
ALTER TABLE `t_periodo`
  ADD CONSTRAINT `fk_periodo_usuarioreg` FOREIGN KEY (`per_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_profesor`
--
ALTER TABLE `t_profesor`
  ADD CONSTRAINT `fk_profesor_usuarioreg` FOREIGN KEY (`pro_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_rol`
--
ALTER TABLE `t_rol`
  ADD CONSTRAINT `fk_rol_usuarioreg` FOREIGN KEY (`rol_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_rubro`
--
ALTER TABLE `t_rubro`
  ADD CONSTRAINT `fk_rubro_usuarioreg` FOREIGN KEY (`rub_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_rubro_curso`
--
ALTER TABLE `t_rubro_curso`
  ADD CONSTRAINT `fk_rubrocurso_curso` FOREIGN KEY (`id_curso`) REFERENCES `t_curso` (`id_curso`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rubrocurso_rubro` FOREIGN KEY (`id_rubro`) REFERENCES `t_rubro` (`id_rubro`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rubrocurso_usuarioreg` FOREIGN KEY (`ruc_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `t_usuario`
--
ALTER TABLE `t_usuario`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `t_rol` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_usuarioreg` FOREIGN KEY (`usu_usuarioreg`) REFERENCES `t_usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
