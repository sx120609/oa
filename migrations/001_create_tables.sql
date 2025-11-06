-- Schema creation for demo maintenance system.
CREATE TABLE asset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100) NULL,
    location VARCHAR(100) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active'
);

CREATE TABLE ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    symptom TEXT NOT NULL,
    severity TINYINT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'NEW',
    photos JSON NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_ticket_asset FOREIGN KEY (asset_id) REFERENCES asset(id),
    INDEX idx_ticket_asset_status (asset_id, status)
);

CREATE TABLE work_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NULL,
    asset_id INT NOT NULL,
    priority TINYINT NOT NULL DEFAULT 0,
    assignee_id INT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    sla_start DATETIME NULL,
    sla_deadline DATETIME NULL,
    labor_minutes INT NOT NULL DEFAULT 0,
    result TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_work_order_ticket FOREIGN KEY (ticket_id) REFERENCES ticket(id),
    CONSTRAINT fk_work_order_asset FOREIGN KEY (asset_id) REFERENCES asset(id),
    INDEX idx_work_order_status_assignee (status, assignee_id)
);

CREATE TABLE spare_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    uom VARCHAR(20) NOT NULL
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spare_id INT NOT NULL UNIQUE,
    qty_available INT NOT NULL,
    CONSTRAINT fk_inventory_spare FOREIGN KEY (spare_id) REFERENCES spare_item(id)
);

CREATE TABLE inv_txn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spare_id INT NOT NULL,
    work_order_id INT NOT NULL,
    qty INT NOT NULL,
    type ENUM('issue','return') NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_inv_txn_spare FOREIGN KEY (spare_id) REFERENCES spare_item(id),
    CONSTRAINT fk_inv_txn_work_order FOREIGN KEY (work_order_id) REFERENCES work_order(id)
);

CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('dispatcher','tech','wh','viewer') NOT NULL
);
