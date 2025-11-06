-- Seed data for demo maintenance system.
INSERT INTO user (username, password_hash, role) VALUES
('dispatcher', '$2y$10$dispatcherhash', 'dispatcher'),
('tech', '$2y$10$techhash', 'tech'),
('wh', '$2y$10$whhash', 'wh'),
('viewer', '$2y$10$viewerhash', 'viewer');

INSERT INTO asset (code, name, model, location, status) VALUES
('AS-1001', 'Air Handler 1', 'AHU-200', 'Building A', 'active'),
('AS-1002', 'Air Handler 2', 'AHU-200', 'Building A', 'active'),
('AS-1003', 'Air Handler 3', 'AHU-200', 'Building B', 'active'),
('AS-1004', 'Pump 1', 'P-XL', 'Building B', 'active'),
('AS-1005', 'Pump 2', 'P-XL', 'Building B', 'active'),
('AS-1006', 'Chiller 1', 'CH-500', 'Building C', 'active'),
('AS-1007', 'Chiller 2', 'CH-500', 'Building C', 'active'),
('AS-1008', 'Boiler 1', 'BL-300', 'Building D', 'active'),
('AS-1009', 'Boiler 2', 'BL-300', 'Building D', 'active'),
('AS-1010', 'Cooling Tower 1', 'CT-100', 'Building E', 'active');

INSERT INTO spare_item (code, name, uom) VALUES
('SP-001', '轴承', 'pcs'),
('SP-002', '滤芯', 'pcs'),
('SP-003', '螺栓', 'pcs'),
('SP-004', '密封圈', 'pcs'),
('SP-005', '传感器', 'pcs'),
('SP-006', '继电器', 'pcs'),
('SP-007', '风机叶片', 'pcs'),
('SP-008', '滤网', 'pcs'),
('SP-009', '阀门', 'pcs'),
('SP-010', '控制板', 'pcs');

INSERT INTO inventory (spare_id, qty_available)
SELECT id, 100 FROM spare_item;

INSERT INTO ticket (asset_id, symptom, severity, status, photos, created_at) VALUES
(1, '风机震动异常', 3, 'NEW', NULL, NOW()),
(3, '温度报警', 2, 'NEW', NULL, NOW()),
(5, '流量不足', 1, 'NEW', NULL, NOW());

INSERT INTO work_order (ticket_id, asset_id, priority, assignee_id, status, sla_start, sla_deadline, labor_minutes, result, created_at) VALUES
(1, 1, 2, 2, 'IN_PROGRESS', NOW(), DATE_ADD(NOW(), INTERVAL 4 HOUR), 30, NULL, NOW()),
(2, 3, 1, 2, 'PENDING_QA', NOW(), DATE_ADD(NOW(), INTERVAL 6 HOUR), 90, '更换滤芯并校准传感器', NOW());

INSERT INTO inv_txn (spare_id, work_order_id, qty, type, created_at) VALUES
(2, 2, 1, 'issue', NOW());
