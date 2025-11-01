-- Schema definitions

-- Reset tables so the migration is idempotent when rerun
DROP TABLE IF EXISTS repair_orders;
DROP TABLE IF EXISTS asset_logs;
DROP TABLE IF EXISTS usages;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    model VARCHAR(120) NULL,
    status VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    request_no VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(30) NOT NULL,
    occurred_at DATETIME NOT NULL,
    note TEXT NULL,
    CONSTRAINT fk_usages_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_usages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_usages_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    request_id VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_logs_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS repair_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT NULL,
    labor_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_repairs_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data
INSERT INTO users (name, email, role) VALUES
    ('Alice Chen', 'alice@example.com', 'asset_admin'),
    ('Ben Liu', 'ben@example.com', 'technician'),
    ('Dana Xu', 'dana@example.com', 'admin');

INSERT INTO projects (name, code) VALUES
    ('Office Expansion', 'OFF-001'),
    ('R&D Refresh', 'RND-002');

INSERT INTO assets (name, model, status, created_at, updated_at) VALUES
    ('Dell Latitude 7440', '7440', 'in_stock', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    ('3D Printer Mark II', 'Mark II', 'in_use', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO usages (asset_id, user_id, project_id, request_no, type, occurred_at, note) VALUES
    (2, 1, 1, 'REQ-202309-001', 'assign', CURRENT_TIMESTAMP, 'Initial assignment to Alice');

INSERT INTO asset_logs (asset_id, from_status, to_status, action, request_id, created_at) VALUES
    (2, 'in_stock', 'in_use', 'assign', 'REQ-202309-001', CURRENT_TIMESTAMP);

INSERT INTO repair_orders (asset_id, status, description, labor_cost, parts_cost, created_at, updated_at) VALUES
    (2, 'created', 'Extruder calibration required', 120.00, 45.50, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
