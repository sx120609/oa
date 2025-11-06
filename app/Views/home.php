<?php
/** @var array $session */
/** @var array|null $data */
/** @var string|null $loadError */

$data = $data ?? [];
$loadError = $loadError ?? null;
$projects = $data['projects'] ?? [];
$devices = $data['devices'] ?? [];
$reservations = $data['reservations'] ?? [];
$checkouts = $data['checkouts'] ?? [];
$notifications = $data['notifications'] ?? [];

$formatDatetime = static function (?string $value): string {
    if (empty($value)) {
        return '-';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return escape($value);
    }
    return date('Y-m-d H:i', $timestamp);
};

$projectStatusMap = [
    'ongoing' => '进行中',
    'done' => '已完成',
];

$deviceStatusMap = [
    'in_stock' => '在库',
    'reserved' => '已预留',
    'checked_out' => '借出中',
    'transfer_pending' => '待转交',
    'lost' => '遗失',
    'repair' => '维修中',
];

$modulesInfo = [
    'project' => ['title' => '创建项目', 'desc' => '录入新的外拍/运营项目'],
    'device' => ['title' => '创建设备', 'desc' => '登记设备档案与状态'],
    'reservation' => ['title' => '设备预留', 'desc' => '为项目锁定设备时间段'],
    'checkout' => ['title' => '设备借用', 'desc' => '发起设备领用/借出流程'],
    'return' => ['title' => '设备归还', 'desc' => '完成归还并上传凭证'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>资产管理控制台</title>
    <style>
        :root {
            color-scheme: light;
            --bg-gradient: radial-gradient(120% 120% at 15% 20%, #38bdf8 0%, #111827 38%, #0f172a 100%);
            --glass: rgba(15, 23, 42, 0.52);
            --glass-border: rgba(148, 163, 184, 0.22);
            --text-main: #f8fafc;
            --text-sub: #cbd5f5;
            --accent: #38bdf8;
            --accent-strong: #0ea5e9;
            --surface: rgba(30, 41, 59, 0.55);
            --danger: #f87171;
            --success: #34d399;
            --warning: #facc15;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            display: flex;
            flex-direction: column;
        }
        header {
            width: 100%;
            padding: 2.5rem 3rem 1.8rem;
            display: flex;
            justify-content: center;
        }
        .header-inner {
            width: min(1200px, 100%);
            display: grid;
            gap: 1.5rem;
        }
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
        .brand span { color: var(--accent); }
        .hero {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 55px rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(28px);
            display: grid;
            gap: 1.25rem;
        }
        .hero h1 {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 2.8rem);
            letter-spacing: 0.04em;
        }
        .hero p {
            margin: 0;
            max-width: 640px;
            color: rgba(226, 232, 240, 0.85);
            line-height: 1.7;
        }
        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .hero-actions button {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border: none;
            color: #0b1627;
            font-weight: 700;
            padding: 0.65rem 1.6rem;
            border-radius: 999px;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .hero-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.25);
        }
        .layout {
            width: 100%;
            flex: 1;
            display: flex;
            justify-content: center;
            padding: 0 3rem 3rem;
        }
        .layout-inner {
            width: min(1200px, 100%);
            display: grid;
            gap: 2rem;
            grid-template-columns: minmax(0, 1fr) 340px;
        }
        @media (max-width: 960px) {
            .layout-inner {
                grid-template-columns: 1fr;
            }
            .layout { padding: 0 1.5rem 2.5rem; }
            header { padding: 2.2rem 1.5rem 1.4rem; }
        }
        .dashboard-main { display: grid; gap: 2rem; }
        .dashboard-side { display: grid; gap: 1.5rem; }
        .glass-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 2rem;
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(24px);
        }
        .status-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .status-info {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.65rem 1.2rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.08);
            border: 1px solid rgba(56, 189, 248, 0.2);
            font-weight: 600;
            color: var(--accent);
        }
        .stats-grid {
            margin-top: 1.75rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            padding: 1.5rem;
            display: grid;
            gap: 0.4rem;
        }
        .stat-card h3 { margin: 0; font-size: 0.95rem; color: rgba(226, 232, 240, 0.7); }
        .stat-card strong { font-size: 1.9rem; }
        .stat-card span { font-size: 0.85rem; color: rgba(148, 163, 184, 0.8); }
        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.4rem;
        }
        .section-title h2 { margin: 0; font-size: 1.3rem; letter-spacing: 0.04em; }
        .badge {
            background: rgba(148, 163, 184, 0.18);
            color: var(--text-sub);
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .data-table-wrapper {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }
        .data-table th,
        .data-table td {
            padding: 0.7rem 0.9rem;
            text-align: left;
        }
        .data-table thead { background: rgba(148, 163, 184, 0.18); }
        .data-table tbody tr:nth-child(odd) { background: rgba(15, 23, 42, 0.35); }
        .data-table tbody tr:nth-child(even) { background: rgba(30, 41, 59, 0.3); }
        .status-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            background: rgba(59, 130, 246, 0.12);
            color: #bfdbfe;
        }
        .status-chip.success { background: rgba(52, 211, 153, 0.18); color: #22c55e; }
        .status-chip.warning { background: rgba(250, 204, 21, 0.18); color: #facc15; }
        .status-chip.danger { background: rgba(248, 113, 113, 0.18); color: #f87171; }
        .empty-placeholder { font-size: 0.9rem; color: rgba(148, 163, 184, 0.75); }
        .panel-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.38);
            backdrop-filter: blur(24px);
            display: grid;
            gap: 1.2rem;
        }
        .panel-card h3 { margin: 0; font-size: 1.1rem; letter-spacing: 0.04em; }
        .login-form label,
        .module-form label {
            display: grid;
            gap: 0.4rem;
            font-size: 0.9rem;
            color: rgba(226, 232, 240, 0.85);
        }
        input,
        textarea {
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-main);
            font-size: 0.95rem;
        }
        textarea { min-height: 110px; resize: vertical; }
        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22);
        }
        button {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            border: none;
            color: #0f172a;
            border-radius: 999px;
            padding: 0.7rem 1.3rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(14, 165, 233, 0.25);
        }
        button[disabled] {
            background: rgba(148, 163, 184, 0.35);
            color: rgba(15, 23, 42, 0.6);
            cursor: wait;
            box-shadow: none;
        }
        .form-result {
            border-radius: 10px;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            line-height: 1.4;
            display: none;
        }
        .form-result.show { display: block; }
        .form-result.success { background: rgba(52, 211, 153, 0.18); color: #16a34a; border: 1px solid rgba(52, 211, 153, 0.4); }
        .form-result.error { background: rgba(248, 113, 113, 0.18); color: #dc2626; border: 1px solid rgba(248, 113, 113, 0.3); }
        .form-result.info { background: rgba(59, 130, 246, 0.16); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.25); }
        .module-toggle-list {
            display: grid;
            gap: 0.65rem;
        }
        .module-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0.75rem;
            border-radius: 14px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .module-toggle span {
            font-size: 0.9rem;
            color: rgba(226, 232, 240, 0.85);
        }
        .module-toggle small {
            display: block;
            font-size: 0.75rem;
            color: rgba(148, 163, 184, 0.7);
        }
        .switch {
            position: relative;
            width: 42px;
            height: 22px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
            transition: background 0.2s ease;
        }
        .slider::before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #0f172a;
            border-radius: 50%;
            transition: transform 0.2s ease;
        }
        .switch input:checked + .slider {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
        }
        .switch input:checked + .slider::before {
            transform: translate(18px, -50%);
        }
        footer {
            margin-top: auto;
            padding: 2.2rem 3rem;
            text-align: center;
            color: rgba(148, 163, 184, 0.7);
            font-size: 0.85rem;
        }
        @media (max-width: 640px) {
            nav { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .nav-extra { display: none; }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <nav>
                <div class="brand">资产 · <span>运营平台</span></div>
                <div class="nav-extra">
                    <span>统一入口 · 安全可靠</span>
                    <button type="button" onclick="window.dashboardRefresh && window.dashboardRefresh(true)">刷新数据</button>
                </div>
            </nav>
            <div class="hero">
                <h1>全链路资产管理，尽在掌握之中</h1>
                <p>一键完成项目创建、设备登记、预留与借用流程。所有操作均通过安全的单入口路由完成，并实时同步审计日志与通知系统。</p>
                <div class="hero-actions">
                    <button type="button" onclick="window.dashboardRefresh && window.dashboardRefresh(true)">立即同步数据</button>
                </div>
            </div>
        </div>
    </header>
    <div class="layout">
        <div class="layout-inner">
            <div class="dashboard-main">
                <section class="glass-card">
                    <div class="status-banner">
                <div class="status-info" data-current-status>
                    <span>登录状态：</span>
                    <?php if (!empty($session['uid'])): ?>
                        <strong>已登录</strong> · 账号 <?= escape($session['email'] ?? ('UID ' . $session['uid'])) ?>（角色 <?= escape($session['role'] ?? '未知') ?>）
                    <?php else: ?>
                        <strong>未登录</strong> · 请在右侧完成登录
                    <?php endif; ?>
                </div>
                <button type="button" class="refresh-btn" onclick="window.dashboardRefresh && window.dashboardRefresh(true)">刷新数据</button>
            </div>
            <?php if ($loadError): ?>
                <div class="form-result show error" style="margin-top:1rem;">
                    数据加载出现异常：<?= escape($loadError) ?>
                </div>
            <?php endif; ?>
            <div class="stats-grid">
                        <div class="stat-card">
                            <h3>项目总览</h3>
                            <strong><?= escape((string) count($projects)) ?></strong>
                            <span>最近创建的项目条目</span>
                        </div>
                        <div class="stat-card">
                            <h3>设备数量</h3>
                            <strong><?= escape((string) count($devices)) ?></strong>
                            <span>涵盖全部状态</span>
                        </div>
                        <div class="stat-card">
                            <h3>活跃预留</h3>
                            <strong><?= escape((string) count($reservations)) ?></strong>
                            <span>最新预留记录</span>
                        </div>
                        <div class="stat-card">
                            <h3>借用记录</h3>
                            <strong><?= escape((string) count($checkouts)) ?></strong>
                            <span>近期借用与归还</span>
                        </div>
                        <div class="stat-card">
                            <h3>通知提醒</h3>
                            <strong><?= escape((string) count($notifications)) ?></strong>
                            <span>系统自动推送</span>
                        </div>
                    </div>
                </section>

                <section class="glass-card" data-dataset="projects">
                    <div class="section-title">
                        <h2>项目概览</h2>
                        <span class="badge">近期记录：<?= escape((string) count($projects)) ?> 条</span>
                    </div>
                    <?php if (!empty($projects)): ?>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>项目名称</th>
                                    <th>地点</th>
                                    <th>状态</th>
                                    <th>开始时间</th>
                                    <th>交付时间</th>
                                    <th>创建时间</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><?= escape((string) $project['id']) ?></td>
                                        <td><?= escape($project['name'] ?? '-') ?></td>
                                        <td><?= escape($project['location'] ?? '-') ?></td>
                                        <td><span class="status-chip"><?= escape($projectStatusMap[$project['status'] ?? ''] ?? '-') ?></span></td>
                                        <td><?= escape($formatDatetime($project['starts_at'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($project['due_at'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($project['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-placeholder">暂无项目记录，请先创建一个项目。</p>
                    <?php endif; ?>
                </section>

                <section class="glass-card" data-dataset="devices">
                    <div class="section-title">
                        <h2>设备概览</h2>
                        <span class="badge">近期记录：<?= escape((string) count($devices)) ?> 条</span>
                    </div>
                    <?php if (!empty($devices)): ?>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>编号</th>
                                    <th>型号</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($devices as $device): ?>
                                    <?php
                                    $status = $device['status'] ?? '';
                                    $statusLabel = $deviceStatusMap[$status] ?? ($status ?: '-');
                                    $chipClass = match ($status) {
                                        'in_stock' => 'success',
                                        'reserved' => 'warning',
                                        'checked_out' => 'danger',
                                        default => '',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= escape((string) $device['id']) ?></td>
                                        <td><?= escape($device['code'] ?? '-') ?></td>
                                        <td><?= escape($device['model'] ?? '-') ?></td>
                                        <td><span class="status-chip <?= escape($chipClass) ?>"><?= escape($statusLabel) ?></span></td>
                                        <td><?= escape($formatDatetime($device['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-placeholder">暂无设备记录，尝试登记第一台设备。</p>
                    <?php endif; ?>
                </section>

                <section class="glass-card" data-dataset="reservations">
                    <div class="section-title">
                        <h2>预留记录</h2>
                        <span class="badge">近期记录：<?= escape((string) count($reservations)) ?> 条</span>
                    </div>
                    <?php if (!empty($reservations)): ?>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>项目</th>
                                    <th>设备</th>
                                    <th>预留开始</th>
                                    <th>预留结束</th>
                                    <th>创建时间</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td><?= escape((string) $reservation['id']) ?></td>
                                        <td><?= escape($reservation['project_name'] ?? ('#' . ($reservation['project_id'] ?? '-'))) ?></td>
                                        <td><?= escape($reservation['device_code'] ?? ('#' . ($reservation['device_id'] ?? '-'))) ?></td>
                                        <td><?= escape($formatDatetime($reservation['reserved_from'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($reservation['reserved_to'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($reservation['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-placeholder">暂无预留记录，预留设备后将在此处显示。</p>
                    <?php endif; ?>
                </section>

                <section class="glass-card" data-dataset="checkouts">
                    <div class="section-title">
                        <h2>借用记录</h2>
                        <span class="badge">近期记录：<?= escape((string) count($checkouts)) ?> 条</span>
                    </div>
                    <?php if (!empty($checkouts)): ?>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>项目</th>
                                    <th>设备</th>
                                    <th>借出时间</th>
                                    <th>到期时间</th>
                                    <th>归还时间</th>
                                    <th>状态</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($checkouts as $checkout): ?>
                                    <?php
                                    $returned = !empty($checkout['return_at']);
                                    $chipClass = $returned ? 'success' : 'warning';
                                    $chipLabel = $returned ? '已归还' : '借出中';
                                    ?>
                                    <tr>
                                        <td><?= escape((string) $checkout['id']) ?></td>
                                        <td><?= escape($checkout['project_name'] ?? ('#' . ($checkout['project_id'] ?? '-'))) ?></td>
                                        <td><?= escape($checkout['device_code'] ?? ('#' . ($checkout['device_id'] ?? '-'))) ?></td>
                                        <td><?= escape($formatDatetime($checkout['checked_out_at'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($checkout['due_at'] ?? null)) ?></td>
                                        <td><?= escape($formatDatetime($checkout['return_at'] ?? null)) ?></td>
                                        <td><span class="status-chip <?= escape($chipClass) ?>"><?= escape($chipLabel) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-placeholder">暂无借用记录。</p>
                    <?php endif; ?>
                </section>

                <section class="glass-card" data-dataset="notifications">
                    <div class="section-title">
                        <h2>通知记录</h2>
                        <span class="badge">近期记录：<?= escape((string) count($notifications)) ?> 条</span>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户</th>
                                    <th>标题</th>
                                    <th>内容</th>
                                    <th>发送时间</th>
                                    <th>已送达</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($notifications as $notification): ?>
                                    <tr>
                                        <td><?= escape((string) $notification['id']) ?></td>
                                        <td><?= escape((string) ($notification['user_id'] ?? '-')) ?></td>
                                        <td><?= escape($notification['title'] ?? '-') ?></td>
                                        <td><?= escape($notification['body'] ?? '-') ?></td>
                                        <td><?= escape($formatDatetime($notification['created_at'] ?? null)) ?></td>
                                        <td><?= escape($notification['delivered_at'] ? $formatDatetime($notification['delivered_at']) : '未送达') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-placeholder">当前暂无系统通知。</p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="dashboard-side">
                <div class="panel-card">
                    <h3>快速登录</h3>
                    <form class="login-form" method="post" action="/login" data-ajax="true">
                        <?= csrf_field() ?>
                        <label>
                            邮箱
                            <input type="email" name="email" required>
                        </label>
                        <label>
                            密码
                            <input type="password" name="password" required>
                        </label>
                        <button type="submit">登录</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>

                <div class="panel-card">
                    <h3>功能开关</h3>
                    <div class="module-toggle-list">
                        <?php foreach ($modulesInfo as $moduleId => $moduleMeta): ?>
                            <label class="module-toggle">
                                <div>
                                    <span><?= escape($moduleMeta['title']) ?></span>
                                    <small><?= escape($moduleMeta['desc']) ?></small>
                                </div>
                                <span class="switch">
                                    <input type="checkbox" data-module-toggle="<?= escape($moduleId) ?>" checked>
                                    <span class="slider"></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel-card">
                    <h3>操作表单</h3>
                    <div class="module-form" data-module="project">
                        <form method="post" action="/projects/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>
                                项目名称
                                <input type="text" name="name" required>
                            </label>
                            <label>
                                项目地点
                                <input type="text" name="location" required>
                            </label>
                            <label>
                                开始时间
                                <input type="datetime-local" name="starts_at" required>
                            </label>
                            <label>
                                交付时间
                                <input type="datetime-local" name="due_at" required>
                            </label>
                            <label>
                                报价金额
                                <input type="number" step="0.01" name="quote_amount" value="0.00">
                            </label>
                            <label>
                                备注
                                <textarea name="note"></textarea>
                            </label>
                            <button type="submit">提交项目</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="device">
                        <form method="post" action="/devices/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>
                                设备编号
                                <input type="text" name="code" required>
                            </label>
                            <label>
                                型号
                                <input type="text" name="model" required>
                            </label>
                            <label>
                                序列号（可选）
                                <input type="text" name="serial">
                            </label>
                            <label>
                                照片地址（可选）
                                <input type="url" name="photo_url">
                            </label>
                            <button type="submit">提交设备</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="reservation">
                        <form method="post" action="/reservations/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>
                                项目 ID
                                <input type="number" name="project_id" min="1" required>
                            </label>
                            <label>
                                设备 ID
                                <input type="number" name="device_id" min="1" required>
                            </label>
                            <label>
                                开始时间
                                <input type="datetime-local" name="from" required>
                            </label>
                            <label>
                                结束时间
                                <input type="datetime-local" name="to" required>
                            </label>
                            <button type="submit">提交预留</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="checkout">
                        <form method="post" action="/checkouts/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>
                                设备 ID
                                <input type="number" name="device_id" min="1" required>
                            </label>
                            <label>
                                项目 ID（可选）
                                <input type="number" name="project_id" min="1">
                            </label>
                            <label>
                                借出时间
                                <input type="datetime-local" name="now" required>
                            </label>
                            <label>
                                归还期限
                                <input type="datetime-local" name="due" required>
                            </label>
                            <label>
                                借出照片（可选）
                                <input type="url" name="photo">
                            </label>
                            <label>
                                备注（可选）
                                <textarea name="note"></textarea>
                            </label>
                            <button type="submit">提交借出</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="return">
                        <form method="post" action="/returns/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>
                                设备 ID
                                <input type="number" name="device_id" min="1" required>
                            </label>
                            <label>
                                归还时间
                                <input type="datetime-local" name="now" required>
                            </label>
                            <label>
                                归还照片（可选）
                                <input type="url" name="photo">
                            </label>
                            <label>
                                备注（可选）
                                <textarea name="note"></textarea>
                            </label>
                            <button type="submit">提交归还</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    <footer>
        © <?= date('Y') ?> 资产运营平台 · 全流程资产与项目管理
    </footer>
    <script>
        (() => {
            const forms = document.querySelectorAll('form[data-ajax="true"]');

            const parseResponse = (text) => {
                const trimmed = text.trim();
                if (!trimmed) {
                    return { type: 'error', message: '服务器未返回任何信息。' };
                }
                if (trimmed.toUpperCase().startsWith('OK')) {
                    return { type: 'success', message: trimmed };
                }
                if (trimmed.toUpperCase().startsWith('ERROR')) {
                    return { type: 'error', message: trimmed.replace(/^ERROR:\s*/i, '') };
                }
                return { type: 'info', message: trimmed };
            };

            const refreshStatus = async (syncData = false) => {
                try {
                    const res = await fetch(window.location.href, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        return;
                    }
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const formsNew = doc.querySelectorAll('form[data-ajax="true"]');
                    formsNew.forEach((newForm) => {
                        const module = newForm.closest('[data-module]');
                        if (!module) {
                            return;
                        }
                        const selector = `[data-module="${module.getAttribute('data-module')}"] form[data-ajax="true"]`;
                        const currentForm = document.querySelector(selector);
                        if (!currentForm) {
                            return;
                        }
                        const newToken = newForm.querySelector('input[name="_token"]');
                        const currentToken = currentForm.querySelector('input[name="_token"]');
                        if (newToken && currentToken) {
                            currentToken.value = newToken.value;
                        }
                    });
                    const statusNew = doc.querySelector('[data-current-status]');
                    const statusCurrent = document.querySelector('[data-current-status]');
                    if (statusNew && statusCurrent) {
                        statusCurrent.innerHTML = statusNew.innerHTML;
                    }
                    if (syncData) {
                        doc.querySelectorAll('[data-dataset]').forEach((sectionNew) => {
                            const key = sectionNew.getAttribute('data-dataset');
                            if (!key) {
                                return;
                            }
                            const sectionCurrent = document.querySelector(`[data-dataset="${key}"]`);
                            if (sectionCurrent) {
                                sectionCurrent.innerHTML = sectionNew.innerHTML;
                            }
                        });
                    }
                } catch (error) {
                    console.warn('刷新页面状态失败', error);
                }
            };

            forms.forEach((form) => {
                const resultBox = form.querySelector('[data-result]');
                const submitBtn = form.querySelector('button[type="submit"]');
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const formData = new FormData(form);
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.dataset.originalText = submitBtn.dataset.originalText || submitBtn.textContent;
                        submitBtn.textContent = '提交中...';
                    }

                    if (resultBox) {
                        resultBox.className = 'form-result show info';
                        resultBox.textContent = '正在提交，请稍候...';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: form.method.toUpperCase(),
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const text = await response.text();
                        const { type, message } = parseResponse(text);
                        if (resultBox) {
                            resultBox.className = `form-result show ${type}`;
                            resultBox.textContent = message;
                        }
                        if (type === 'success') {
                            await refreshStatus(true);
                            if (form.dataset.resetOnSuccess !== 'false') {
                                form.reset();
                            }
                        }
                    } catch (error) {
                        if (resultBox) {
                            resultBox.className = 'form-result show error';
                            resultBox.textContent = `请求失败：${error instanceof Error ? error.message : '未知错误'}`;
                        }
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = submitBtn.dataset.originalText || '提交';
                        }
                    }
                });
            });

            document.querySelectorAll('[data-module-toggle]').forEach((toggle) => {
                const moduleId = toggle.getAttribute('data-module-toggle');
                const moduleForm = document.querySelector(`[data-module="${moduleId}"]`);
                if (!moduleForm) {
                    return;
                }
                toggle.addEventListener('change', () => {
                    moduleForm.style.display = toggle.checked ? '' : 'none';
                });
                moduleForm.style.display = toggle.checked ? '' : 'none';
            });

            window.dashboardRefresh = refreshStatus;
        })();
    </script>
</body>
</html>
