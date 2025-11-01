-- Schema definitions
CREATE TABLE IF NOT EXISTS users (
    id %%AUTO_ID%%,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
    id %%AUTO_ID%%,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assets (
    id %%AUTO_ID%%,
    project_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    serial_number VARCHAR(120) UNIQUE,
    status VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS usages (
    id %%AUTO_ID%%,
    asset_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    request_no VARCHAR(100) NOT NULL UNIQUE,
    assigned_at DATETIME NOT NULL,
    returned_at DATETIME NULL,
    note TEXT NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS asset_logs (
    id %%AUTO_ID%%,
    asset_id INTEGER NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    request_id VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS repair_orders (
    id %%AUTO_ID%%,
    asset_id INTEGER NOT NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
);

-- Seed data
INSERT INTO users (name, email) VALUES
    ('Alice Chen', 'alice@example.com'),
    ('Ben Liu', 'ben@example.com');

INSERT INTO projects (name, code) VALUES
    ('Office Expansion', 'OFF-001'),
    ('R&D Refresh', 'RND-002');

INSERT INTO assets (project_id, name, serial_number, status, created_at, updated_at) VALUES
    (1, 'Dell Latitude 7440', 'DL7440-001', 'in_stock', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
    (2, '3D Printer Mark II', 'PRNT-3D-002', 'in_use', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO usages (asset_id, user_id, request_no, assigned_at, note) VALUES
    (2, 1, 'REQ-202309-001', CURRENT_TIMESTAMP, 'Initial assignment to Alice');

INSERT INTO asset_logs (asset_id, from_status, to_status, action, request_id, created_at) VALUES
    (2, 'in_stock', 'in_use', 'assign', 'REQ-202309-001', CURRENT_TIMESTAMP);

INSERT INTO repair_orders (asset_id, status, description, created_at, updated_at) VALUES
    (2, 'created', 'Extruder calibration required', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
