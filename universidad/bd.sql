CREATE DATABASE IF NOT EXISTS universidad CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE universidad;

CREATE TABLE
    IF NOT EXISTS inicio_sesion (
        cedula INT PRIMARY KEY NOT NULL,
        usuario VARCHAR(100) NOT NULL,
        correo VARCHAR(120) NOT NULL,
        contrasena VARCHAR(100) NOT NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

