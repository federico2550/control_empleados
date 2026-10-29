-- database.sql
-- Importar desde phpMyAdmin (pestaña "Importar") o con:
--   mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS control_empleados
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE control_empleados;

CREATE TABLE IF NOT EXISTS empleados (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    dni       VARCHAR(10)  NOT NULL UNIQUE,
    nombre    VARCHAR(60)  NOT NULL,
    apellido  VARCHAR(60)  NOT NULL,
    clave     VARCHAR(255) NOT NULL,
    rol       ENUM('empleado', 'admin') NOT NULL DEFAULT 'empleado',
    activo    TINYINT(1)   NOT NULL DEFAULT 1,
    creado    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS movimientos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT NOT NULL,
    tipo        ENUM('INGRESO', 'EGRESO') NOT NULL,
    fecha_hora  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    INDEX idx_empleado_fecha (empleado_id, fecha_hora)
);

-- Administrador por defecto: DNI 11111111 / clave admin123
INSERT IGNORE INTO empleados (dni, nombre, apellido, clave, rol)
VALUES ('11111111', 'Admin', 'Sistema',
        '$2y$10$C93pgY4y92r6TxKKGWuhp.qBGv9mLL/cIkowebVe0t2ezEG/Qs2z2', 'admin');
