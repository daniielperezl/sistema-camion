CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Passwords are MD5 hashed
-- 74181532 -> 0d86a8c26be29cf2e7ce14a5b872d15a
-- 1057606502 -> 44eb2c273bfcde67fa4bd1415c385a9e
INSERT INTO users (username, password) VALUES
('3213907836', '0d86a8c26be29cf2e7ce14a5b872d15a'),
('3114128612', '44eb2c273bfcde67fa4bd1415c385a9e')
ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_name ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado') NOT NULL,
    UNIQUE(day_name)
);

INSERT IGNORE INTO routes (day_name) VALUES
('Lunes'), ('Martes'), ('Miercoles'), ('Jueves'), ('Viernes'), ('Sabado');

CREATE TABLE IF NOT EXISTS stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    city VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    notes TEXT,
    lat DECIMAL(10, 8),
    lng DECIMAL(11, 8),
    order_index INT DEFAULT 0,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS daily_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planilla_number VARCHAR(50),
    delivery_date DATE NOT NULL,
    delivery_day VARCHAR(20),
    total_planilla DECIMAL(15, 2) DEFAULT 0,
    total_devoluciones DECIMAL(15, 2) DEFAULT 0,
    parciales DECIMAL(15, 2) DEFAULT 0,
    total_consignar DECIMAL(15, 2) DEFAULT 0,
    total_consignado DECIMAL(15, 2) DEFAULT 0,
    total_qr DECIMAL(15, 2) DEFAULT 0,
    total_entrega_quala DECIMAL(15, 2) DEFAULT 0,
    total_descuadre DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
