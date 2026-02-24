USE caceria;

CREATE TABLE IF NOT EXISTS team_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    hex VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO team_colors (name, hex) VALUES 
('Rojo', '#ef4444'),
('Azul', '#3b82f6'),
('Verde', '#22c55e'),
('Amarillo', '#eab308'),
('Rosa', '#ec4899'),
('Morado', '#a855f7'),
('Cian', '#06b6d4'),
('Naranja', '#f97316');
