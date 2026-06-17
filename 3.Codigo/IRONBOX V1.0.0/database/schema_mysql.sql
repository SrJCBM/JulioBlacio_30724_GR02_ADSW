CREATE DATABASE IF NOT EXISTS ironclad_box
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ironclad_box;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS mensajes;
DROP TABLE IF EXISTS progreso_atletas;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS membresias;
DROP TABLE IF EXISTS clases;
DROP TABLE IF EXISTS entrenadores;
DROP TABLE IF EXISTS atletas;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    cedula VARCHAR(10) NULL,
    correo VARCHAR(160) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Entrenador', 'Atleta') NOT NULL,
    estado ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
    fecha_registro DATE NOT NULL,
    UNIQUE KEY uq_usuarios_correo (correo),
    UNIQUE KEY uq_usuarios_cedula (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(160) NOT NULL,
    fecha_registro DATE NOT NULL,
    UNIQUE KEY uq_atletas_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entrenadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(160) NOT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_entrenadores_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia DATE NOT NULL,
    hora TIME NOT NULL,
    duracion INT NOT NULL,
    cupo_maximo INT NOT NULL,
    cupos_disponibles INT NOT NULL,
    entrenador_id INT NOT NULL,
    CONSTRAINT fk_clases_entrenador
      FOREIGN KEY (entrenador_id) REFERENCES entrenadores(id),
    INDEX idx_clases_fecha_hora (dia, hora),
    INDEX idx_clases_entrenador (entrenador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membresias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(80) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('Pagado', 'Pendiente', 'Vencido', 'Cancelada') NOT NULL,
    id_atleta INT NOT NULL,
    CONSTRAINT fk_membresias_atleta
      FOREIGN KEY (id_atleta) REFERENCES atletas(id),
    INDEX idx_membresias_atleta (id_atleta),
    INDEX idx_membresias_estado_vencimiento (estado, fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_atleta INT NOT NULL,
    id_clase INT NOT NULL,
    fecha_reserva DATETIME NOT NULL,
    estado ENUM('Confirmada', 'Cancelada') NOT NULL,
    CONSTRAINT fk_reservas_atleta
      FOREIGN KEY (id_atleta) REFERENCES atletas(id),
    CONSTRAINT fk_reservas_clase
      FOREIGN KEY (id_clase) REFERENCES clases(id),
    INDEX idx_reservas_atleta_estado (id_atleta, estado),
    INDEX idx_reservas_clase_estado (id_clase, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE progreso_atletas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    tiempo DECIMAL(10,2) NULL,
    repeticiones INT NULL,
    peso DECIMAL(10,2) NULL,
    puntuacion DECIMAL(10,2) NOT NULL DEFAULT 0,
    notas TEXT NOT NULL,
    id_atleta INT NOT NULL,
    CONSTRAINT fk_progreso_atleta
      FOREIGN KEY (id_atleta) REFERENCES atletas(id),
    INDEX idx_progreso_atleta_fecha (id_atleta, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenido TEXT NOT NULL,
    fecha_envio DATETIME NOT NULL,
    tipo ENUM('Mensaje', 'Anuncio') NOT NULL,
    id_atleta INT NULL,
    id_entrenador INT NULL,
    CONSTRAINT fk_mensajes_atleta
      FOREIGN KEY (id_atleta) REFERENCES atletas(id),
    CONSTRAINT fk_mensajes_entrenador
      FOREIGN KEY (id_entrenador) REFERENCES entrenadores(id),
    INDEX idx_mensajes_atleta (id_atleta),
    INDEX idx_mensajes_entrenador (id_entrenador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(80) NOT NULL,
    datos JSON NOT NULL,
    fecha_generacion DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
