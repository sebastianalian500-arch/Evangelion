-- Evangelion Database
CREATE DATABASE IF NOT EXISTS evangelion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evangelion_db;

-- Personajes del anime
CREATE TABLE IF NOT EXISTS personajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB;

-- EVAs (Unidades)
CREATE TABLE IF NOT EXISTS evas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    ataque INT NOT NULL DEFAULT 50,
    defensa INT NOT NULL DEFAULT 50,
    velocidad INT NOT NULL DEFAULT 50
) ENGINE=InnoDB;

-- Ãngeles
CREATE TABLE IF NOT EXISTS angeles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    ataque INT NOT NULL DEFAULT 50,
    defensa INT NOT NULL DEFAULT 50,
    velocidad INT NOT NULL DEFAULT 50
) ENGINE=InnoDB;

-- Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    personaje_id INT NOT NULL,
    eva_id INT NOT NULL,
    angel_id INT NOT NULL,
    nivel INT NOT NULL DEFAULT 1,
    exp INT NOT NULL DEFAULT 0,
    puntos INT NOT NULL DEFAULT 0,
    victorias INT NOT NULL DEFAULT 0,
    derrotas INT NOT NULL DEFAULT 0,
    batallas_hoy INT NOT NULL DEFAULT 0,
    ultima_batalla DATE DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personaje_id) REFERENCES personajes(id),
    FOREIGN KEY (eva_id) REFERENCES evas(id),
    FOREIGN KEY (angel_id) REFERENCES angeles(id)
) ENGINE=InnoDB;

-- Batallas
CREATE TABLE IF NOT EXISTS batallas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_1_id INT NOT NULL,
    usuario_2_id INT NOT NULL,
    ganador_id INT NOT NULL,
    registro TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_1_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_2_id) REFERENCES usuarios(id),
    FOREIGN KEY (ganador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Misiones
CREATE TABLE IF NOT EXISTS misiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo ENUM('batalla', 'chat', 'registro') NOT NULL,
    exp_reward INT NOT NULL DEFAULT 10,
    puntos_reward INT NOT NULL DEFAULT 5
) ENGINE=InnoDB;

-- Misiones de usuario
CREATE TABLE IF NOT EXISTS misiones_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    mision_id INT NOT NULL,
    completada BOOLEAN DEFAULT FALSE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (mision_id) REFERENCES misiones(id),
    UNIQUE KEY unique_user_mission (usuario_id, mision_id)
) ENGINE=InnoDB;

-- Chat messages
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== SEED DATA ==========

-- Personajes
INSERT INTO personajes (nombre, imagen, descripcion) VALUES
('Shinji Ikari', 'img/personajes/shinji.svg', 'Tercer NiÃ±o, piloto de la Unidad-01. Hijo de Gendo Ikari.'),
('Rei Ayanami', 'img/personajes/rei.svg', 'Primer NiÃ±o, piloto de la Unidad-00. EnigmÃ¡tica y reservada.'),
('Asuka Langley', 'img/personajes/asuka.svg', 'Segundo NiÃ±o, piloto de la Unidad-02. Alemana, orgullosa y talentosa.'),
('Kaworu Nagisa', 'img/personajes/kaworu.svg', 'Quinto NiÃ±o y el decimosÃ©ptimo Ãngel. Amable y misterioso.'),
('Misato Katsuragi', 'img/personajes/misato.svg', 'CapitÃ¡n de NERV. LÃ­der de los pilotos.'),
('Ritsuko Akagi', 'img/personajes/ritsuko.svg', 'CientÃ­fica jefe de NERV.'),
('Gendo Ikari', 'img/personajes/gendo.svg', 'Comandante de NERV. Padre de Shinji.');

-- EVAs
INSERT INTO evas (nombre, imagen, ataque, defensa, velocidad) VALUES
('Unit-00', 'img/evas/unit00.svg', 55, 60, 45),
('Unit-01', 'img/evas/unit01.svg', 85, 75, 70),
('Unit-02', 'img/evas/unit02.svg', 80, 65, 80),
('Unit-03', 'img/evas/unit03.svg', 60, 70, 55),
('Unit-04', 'img/evas/unit04.svg', 65, 55, 75);

-- Ãngeles
INSERT INTO angeles (nombre, imagen, ataque, defensa, velocidad) VALUES
('Sachiel', 'img/angeles/sachiel.svg', 60, 50, 40),
('Ramiel', 'img/angeles/ramiel.svg', 90, 70, 30),
('Zeruel', 'img/angeles/zeruel.svg', 95, 80, 50),
('Armisael', 'img/angeles/armisael.svg', 75, 60, 65),
('Kaworu (Ãngel)', 'img/angeles/kaworu_angel.svg', 85, 75, 90);

-- Misiones
INSERT INTO misiones (nombre, descripcion, tipo, exp_reward, puntos_reward) VALUES
('Primera Batalla', 'Gana tu primera batalla contra otro piloto.', 'batalla', 20, 10),
('Serie de Victorias', 'Gana 3 batallas en total.', 'batalla', 50, 25),
('Veterano de NERV', 'Gana 10 batallas.', 'batalla', 100, 50),
('Chat Activo', 'EnvÃ­a 5 mensajes en el chat de NERV.', 'chat', 15, 8),
('ComunicaciÃ³n', 'EnvÃ­a 20 mensajes en el chat.', 'chat', 40, 20),
('Reclutamiento', 'Completa tu registro en el sistema NERV.', 'registro', 10, 5);



