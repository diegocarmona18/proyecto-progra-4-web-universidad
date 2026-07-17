-- =====================================================================
-- SCRIPT DE CREACION DE TABLAS
-- Sistema de Gestion Universitaria (Usuarios, Roles, Carreras, Cursos,
-- Profesores, Estudiantes, Matriculas y Evaluaciones)
-- Motor: MySQL
-- =====================================================================

-- Descomentar si se desea crear y usar una base de datos nueva
-- CREATE DATABASE IF NOT EXISTS bd_universidad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE bd_universidad;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- Tabla: t_rol
-- Descripcion: Almacena los diferentes roles que se pueden asignar a un usuario
-- =====================================================================
DROP TABLE IF EXISTS t_rol;
CREATE TABLE t_rol (
    id_rol            INT AUTO_INCREMENT NOT NULL,
    rol_nombre        VARCHAR(20)  NOT NULL,
    rol_estado        VARCHAR(1)   NOT NULL,
    rol_fechahorareg  DATETIME     NOT NULL,
    rol_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_rol PRIMARY KEY (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_usuario
-- Descripcion: Almacena la informacion de los usuarios que podran utilizar el sistema
-- =====================================================================
DROP TABLE IF EXISTS t_usuario;
CREATE TABLE t_usuario (
    id_usuario           INT AUTO_INCREMENT NOT NULL,
    usu_codigo           VARCHAR(15)  NOT NULL,
    usu_nombre           VARCHAR(60)  NOT NULL,
    usu_correo           VARCHAR(60)  NOT NULL,
    usu_clave            VARCHAR(255) NOT NULL,
    id_rol               INT          NOT NULL,
    usu_intentofallido   INT          NOT NULL,
    usu_estado           VARCHAR(1)   NOT NULL,
    usu_fechahorareg     DATETIME     NOT NULL,
    usu_usuarioreg       INT          NOT NULL,
    CONSTRAINT pk_t_usuario PRIMARY KEY (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_modulo
-- Descripcion: Almacena todas las opciones del menu del sistema y las que
-- se podrian asignar a los diferentes roles
-- =====================================================================
DROP TABLE IF EXISTS t_modulo;
CREATE TABLE t_modulo (
    id_modulo         INT AUTO_INCREMENT NOT NULL,
    mod_nombre        VARCHAR(20)  NOT NULL,
    mod_url           VARCHAR(255) NULL,
    mod_padre         INT          NOT NULL,
    mod_estado        VARCHAR(1)   NOT NULL,
    mod_fechahorareg  DATETIME     NOT NULL,
    mod_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_modulo PRIMARY KEY (id_modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_modulo_rol
-- Descripcion: Relacion entre modulos y roles (que modulos se asignan a cada rol)
-- =====================================================================
DROP TABLE IF EXISTS t_modulo_rol;
CREATE TABLE t_modulo_rol (
    id_modulorol      INT AUTO_INCREMENT NOT NULL,
    id_modulo         INT          NOT NULL,
    id_rol            INT          NOT NULL,
    mor_estado        VARCHAR(1)   NOT NULL,
    mor_fechahorareg  DATETIME     NOT NULL,
    mor_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_modulo_rol PRIMARY KEY (id_modulorol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_carrera
-- Descripcion: Registra las carreras que ofrece la universidad
-- =====================================================================
DROP TABLE IF EXISTS t_carrera;
CREATE TABLE t_carrera (
    id_carrera        INT AUTO_INCREMENT NOT NULL,
    car_nombre        VARCHAR(60)  NOT NULL,
    car_observacion   VARCHAR(250) NULL,
    car_estado        VARCHAR(1)   NOT NULL,
    car_fechahorareg  DATETIME     NOT NULL,
    car_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_carrera PRIMARY KEY (id_carrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_curso
-- Descripcion: Administra todas las materias impartidas por la universidad
-- =====================================================================
DROP TABLE IF EXISTS t_curso;
CREATE TABLE t_curso (
    id_curso          INT AUTO_INCREMENT NOT NULL,
    cur_nombre        VARCHAR(60)    NOT NULL,
    cur_observacion   VARCHAR(250)   NULL,
    cur_costo         DECIMAL(15,2)  NOT NULL,
    cur_estado        VARCHAR(1)     NOT NULL,
    cur_fechahorareg  DATETIME       NOT NULL,
    cur_usuarioreg    INT            NOT NULL,
    CONSTRAINT pk_t_curso PRIMARY KEY (id_curso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_profesor
-- Descripcion: Gestiona todos los profesores con los que cuenta la universidad
-- =====================================================================
DROP TABLE IF EXISTS t_profesor;
CREATE TABLE t_profesor (
    id_profesor           INT AUTO_INCREMENT NOT NULL,
    pro_identificacion    VARCHAR(60)   NOT NULL,
    pro_nombre            VARCHAR(60)   NOT NULL,
    pro_correo            VARCHAR(60)   NOT NULL,
    pro_telefono          DECIMAL(8,0)  NOT NULL,
    pro_especialidad      VARCHAR(30)   NOT NULL,
    pro_estado            VARCHAR(1)    NOT NULL,
    pro_fechahorareg      DATETIME      NOT NULL,
    pro_usuarioreg        INT           NOT NULL,
    CONSTRAINT pk_t_profesor PRIMARY KEY (id_profesor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_genero
-- Descripcion: Registra los diferentes generos de los estudiantes
-- =====================================================================
DROP TABLE IF EXISTS t_genero;
CREATE TABLE t_genero (
    id_genero         INT AUTO_INCREMENT NOT NULL,
    gen_nombre        VARCHAR(60)  NOT NULL,
    gen_comentario    VARCHAR(250) NULL,
    gen_estado        VARCHAR(1)   NOT NULL,
    gen_fechahorareg  DATETIME     NOT NULL,
    gen_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_genero PRIMARY KEY (id_genero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_estudiante
-- Descripcion: Gestiona todos los estudiantes que tiene o ha tenido la universidad
-- =====================================================================
DROP TABLE IF EXISTS t_estudiante;
CREATE TABLE t_estudiante (
    id_estudiante         INT AUTO_INCREMENT NOT NULL,
    est_identificacion    VARCHAR(15)   NOT NULL,
    est_nombre            VARCHAR(60)   NOT NULL,
    est_fechanace         DATE          NOT NULL,
    id_genero             INT           NOT NULL,
    est_correo            VARCHAR(60)   NOT NULL,
    est_telefono          DECIMAL(8,0)  NOT NULL,
    est_estado            VARCHAR(1)    NOT NULL,
    est_fechahorareg      DATETIME      NOT NULL,
    est_usuarioreg        INT           NOT NULL,
    CONSTRAINT pk_t_estudiante PRIMARY KEY (id_estudiante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_periodo
-- Descripcion: Gestiona los diferentes periodos de matricula de estudiantes
-- =====================================================================
DROP TABLE IF EXISTS t_periodo;
CREATE TABLE t_periodo (
    id_periodo         INT AUTO_INCREMENT NOT NULL,
    per_nombre         VARCHAR(20)  NOT NULL,
    per_fechainicia    DATE         NOT NULL,
    per_fechafin       DATE         NOT NULL,
    per_descripcion    VARCHAR(250) NOT NULL,
    per_estado         VARCHAR(1)   NOT NULL,
    per_fechahorareg   DATETIME     NOT NULL,
    per_usuarioreg     INT          NOT NULL,
    CONSTRAINT pk_t_periodo PRIMARY KEY (id_periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_carrera_curso
-- Descripcion: Relaciona que materias estan asignadas a una carrera
-- =====================================================================
DROP TABLE IF EXISTS t_carrera_curso;
CREATE TABLE t_carrera_curso (
    id_carreracurso    INT AUTO_INCREMENT NOT NULL,
    id_carrera         INT          NOT NULL,
    id_curso           INT          NOT NULL,
    cac_estado         VARCHAR(1)   NOT NULL,
    cac_fechahorareg   DATETIME     NOT NULL,
    cac_usuarioreg     INT          NOT NULL,
    CONSTRAINT pk_t_carrera_curso PRIMARY KEY (id_carreracurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_matricula
-- Descripcion: Realiza la matricula de materias para un estudiante
-- =====================================================================
DROP TABLE IF EXISTS t_matricula;
CREATE TABLE t_matricula (
    id_matricula       INT AUTO_INCREMENT NOT NULL,
    id_estudiante      INT          NOT NULL,
    id_periodo         INT          NOT NULL,
    id_curso           INT          NOT NULL,
    mat_estado         VARCHAR(1)   NOT NULL,
    mat_fechahorareg   DATETIME     NOT NULL,
    mat_usuarioreg     INT          NOT NULL,
    CONSTRAINT pk_t_matricula PRIMARY KEY (id_matricula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_rubro
-- Descripcion: Almacena la lista de rubros que se pueden utilizar en una evaluacion
-- =====================================================================
DROP TABLE IF EXISTS t_rubro;
CREATE TABLE t_rubro (
    id_rubro          INT AUTO_INCREMENT NOT NULL,
    rub_nombre        VARCHAR(60)  NOT NULL,
    rub_comentario    VARCHAR(250) NULL,
    rub_estado        VARCHAR(1)   NOT NULL,
    rub_fechahorareg  DATETIME     NOT NULL,
    rub_usuarioreg    INT          NOT NULL,
    CONSTRAINT pk_t_rubro PRIMARY KEY (id_rubro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_rubro_curso
-- Descripcion: Gestiona los rubros de evaluacion que tendra cada materia
-- NOTA: la columna id_rubro se definio como INT (en vez de VARCHAR(60) tal
-- como aparecia en el detalle original) ya que es una llave foranea hacia
-- t_rubro.id_rubro, la cual es INT AUTO_INCREMENT. Se ajusta para que la
-- relacion sea valida.
-- =====================================================================
DROP TABLE IF EXISTS t_rubro_curso;
CREATE TABLE t_rubro_curso (
    id_rubrocurso      INT AUTO_INCREMENT NOT NULL,
    id_curso           INT           NOT NULL,
    id_rubro           INT           NOT NULL,
    ruc_descripcion    VARCHAR(250)  NOT NULL,
    ruc_porcentaje     DECIMAL(3,2)  NOT NULL,
    ruc_estado         VARCHAR(1)    NOT NULL,
    ruc_fechahorareg   DATETIME      NOT NULL,
    ruc_usuarioreg     INT           NOT NULL,
    CONSTRAINT pk_t_rubro_curso PRIMARY KEY (id_rubrocurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Tabla: t_nota_rubro
-- Descripcion: Permite incluir por estudiante el porcentaje obtenido en cada rubro
-- =====================================================================
DROP TABLE IF EXISTS t_nota_rubro;
CREATE TABLE t_nota_rubro (
    id_notarubro         INT AUTO_INCREMENT NOT NULL,
    id_rubro_curso       INT           NOT NULL,
    id_matricula         INT           NOT NULL,
    nor_porcenorje_obt   DECIMAL(3,2)  NOT NULL,
    nor_comenorrio       VARCHAR(250)  NULL,
    nor_estado           VARCHAR(1)    NOT NULL,
    nor_fechahorareg     DATETIME      NOT NULL,
    nor_usuarioreg       INT           NOT NULL,
    CONSTRAINT pk_t_nota_rubro PRIMARY KEY (id_notarubro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- LLAVES FORANEAS (FOREIGN KEYS)
-- Se agregan al final para no depender del orden de creacion de tablas
-- =====================================================================

-- t_usuario
ALTER TABLE t_usuario
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES t_rol (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_usuario_usuarioreg FOREIGN KEY (usu_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_rol
ALTER TABLE t_rol
    ADD CONSTRAINT fk_rol_usuarioreg FOREIGN KEY (rol_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_modulo
-- mod_padre referencia a la misma tabla (modulo padre en el menu jerarquico)
ALTER TABLE t_modulo
    ADD CONSTRAINT fk_modulo_padre FOREIGN KEY (mod_padre) REFERENCES t_modulo (id_modulo)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_modulo_usuarioreg FOREIGN KEY (mod_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_modulo_rol
ALTER TABLE t_modulo_rol
    ADD CONSTRAINT fk_modulorol_modulo FOREIGN KEY (id_modulo) REFERENCES t_modulo (id_modulo)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_modulorol_rol FOREIGN KEY (id_rol) REFERENCES t_rol (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_modulorol_usuarioreg FOREIGN KEY (mor_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_carrera
ALTER TABLE t_carrera
    ADD CONSTRAINT fk_carrera_usuarioreg FOREIGN KEY (car_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_curso
ALTER TABLE t_curso
    ADD CONSTRAINT fk_curso_usuarioreg FOREIGN KEY (cur_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_profesor
ALTER TABLE t_profesor
    ADD CONSTRAINT fk_profesor_usuarioreg FOREIGN KEY (pro_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_genero
ALTER TABLE t_genero
    ADD CONSTRAINT fk_genero_usuarioreg FOREIGN KEY (gen_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_estudiante
ALTER TABLE t_estudiante
    ADD CONSTRAINT fk_estudiante_genero FOREIGN KEY (id_genero) REFERENCES t_genero (id_genero)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_estudiante_usuarioreg FOREIGN KEY (est_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_periodo
ALTER TABLE t_periodo
    ADD CONSTRAINT fk_periodo_usuarioreg FOREIGN KEY (per_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_carrera_curso
ALTER TABLE t_carrera_curso
    ADD CONSTRAINT fk_carreracurso_carrera FOREIGN KEY (id_carrera) REFERENCES t_carrera (id_carrera)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_carreracurso_curso FOREIGN KEY (id_curso) REFERENCES t_curso (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_carreracurso_usuarioreg FOREIGN KEY (cac_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_matricula
ALTER TABLE t_matricula
    ADD CONSTRAINT fk_matricula_estudiante FOREIGN KEY (id_estudiante) REFERENCES t_estudiante (id_estudiante)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_matricula_periodo FOREIGN KEY (id_periodo) REFERENCES t_periodo (id_periodo)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_matricula_curso FOREIGN KEY (id_curso) REFERENCES t_curso (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_matricula_usuarioreg FOREIGN KEY (mat_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_rubro
ALTER TABLE t_rubro
    ADD CONSTRAINT fk_rubro_usuarioreg FOREIGN KEY (rub_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_rubro_curso
ALTER TABLE t_rubro_curso
    ADD CONSTRAINT fk_rubrocurso_curso FOREIGN KEY (id_curso) REFERENCES t_curso (id_curso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_rubrocurso_rubro FOREIGN KEY (id_rubro) REFERENCES t_rubro (id_rubro)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_rubrocurso_usuarioreg FOREIGN KEY (ruc_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

-- t_nota_rubro
ALTER TABLE t_nota_rubro
    ADD CONSTRAINT fk_notarubro_rubrocurso FOREIGN KEY (id_rubro_curso) REFERENCES t_rubro_curso (id_rubrocurso)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_notarubro_matricula FOREIGN KEY (id_matricula) REFERENCES t_matricula (id_matricula)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_notarubro_usuarioreg FOREIGN KEY (nor_usuarioreg) REFERENCES t_usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;
