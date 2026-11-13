-- ============================================================
-- seed.sql - Datos iniciales del back-office
-- Asignatura: Soluciones Informáticas para la Empresa (SIE)
-- Práctica 2
-- Autor: José Gómez Reinaldos (alumno)
--
-- Importar DESPUÉS de schema.sql (en local y en la nube).
--
-- Solo el gestor de prueba: el back-office empieza vacío para la
-- evidencia del "antes". Pedidos e incidencias llegan por n8n.
--
-- La contraseña del gestor NO está aquí, solo su hash generado con
-- password_hash() de PHP. Ver README.
-- ============================================================

SET NAMES utf8mb4;

INSERT INTO bo_users (id, email, password_hash, role) VALUES
    (1, 'gestor@blacklotus.test', '$2y$10$457IKlT/goL5TBMYlVamjuvnY6e5vTKo.RDJOA5sYwYIGHnYl5.0q', 'gestor');
