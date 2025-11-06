<?php
/** @var array $session */

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
        .hero h1 { margin: 0; font-size: clamp(2.2rem, 4vw, 2.8rem); letter-spacing: 0.04em; }
        .hero p { margin: 0; max-width: 640px; color: rgba(226, 232, 240, 0.85); line-height: 1.7; }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
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
        .hero-actions button:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(14, 165, 233, 0.25); }
        .layout { width: 100%; flex: 1; display: flex; justify-content: center; padding: 0 3rem 3rem; }
        .layout-inner { width: min(1200px, 100%); display: grid; gap: 2rem; grid-template-columns: minmax(0, 1fr) 340px; }
        @media (max-width: 960px) { .layout-inner { grid-template-columns: 1fr; } .layout { padding: 0 1.5rem 2.5rem; } header { padding: 2.2rem 1.5rem 1.4rem; } }
        .glass-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 2rem;
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(24px);
        }
        .dashboard-main { display: grid; gap: 2rem; }
        .dashboard-side { display: grid; gap: 1.5rem; }
        .status-banner { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; }
        .status-info { display: inline-flex; align-items: center; gap: 0.85rem; padding: 0.65rem 1.2rem; border-radius: 999px; background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2); font-weight: 600; color: var(--accent); }
        .refresh-btn { background: linear-gradient(135deg, var(--accent), var(--accent-strong)); border: none; color: #0f172a; font-weight: 700; padding: 0.65rem 1.4rem; border-radius: 999px; cursor: pointer; transition: transform 0.18s ease, box-shadow 0.18s ease; }
        .refresh-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(14, 165, 233, 0.25); }
        .stats-grid { margin-top: 1.75rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; }
        .stat-card { background: var(--surface); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 18px; padding: 1.5rem; display: grid; gap: 0.4rem; }
        .stat-card h3 { margin: 0; font-size: 0.95rem; color: rgba(226, 232, 240, 0.7); }
        .stat-card strong { font-size: 1.9rem; }
        .stat-card span { font-size: 0.85rem; color: rgba(148, 163, 184, 0.8); }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1.4rem; }
        .section-title h2 { margin: 0; font-size: 1.3rem; letter-spacing: 0.04em; }
        .badge { background: rgba(148, 163, 184, 0.18); color: var(--text-sub); padding: 0.3rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .data-table-wrapper { border-radius: 16px; overflow: hidden; border: 1px solid rgba(148, 163, 184, 0.18); box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .data-table th, .data-table td { padding: 0.7rem 0.9rem; text-align: left; }
        .data-table thead { background: rgba(148, 163, 184, 0.18); }
        .data-table tbody tr:nth-child(odd) { background: rgba(15, 23, 42, 0.35); }
        .data-table tbody tr:nth-child(even) { background: rgba(30, 41, 59, 0.3); }
        .status-chip { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.02em; background: rgba(59, 130, 246, 0.12); color: #bfdbfe; }
        .status-chip.success { background: rgba(52, 211, 153, 0.18); color: #22c55e; }
        .status-chip.warning { background: rgba(250, 204, 21, 0.18); color: #facc15; }
        .status-chip.danger { background: rgba(248, 113, 113, 0.18); color: #f87171; }
        .empty-placeholder { font-size: 0.9rem; color: rgba(148, 163, 184, 0.75); }
        .panel-card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 20px; padding: 1.8rem; box-shadow: 0 22px 44px rgba(15, 23, 42, 0.38); backdrop-filter: blur(24px); display: grid; gap: 1.2rem; }
        .panel-card h3 { margin: 0; font-size: 1.1rem; letter-spacing: 0.04em; }
        .login-form label, .module-form label { display: grid; gap: 0.4rem; font-size: 0.9rem; color: rgba(226, 232, 240, 0.85); }
        input, textarea { padding: 0.65rem 0.8rem; border-radius: 10px; border: 1px solid rgba(148, 163, 184, 0.25); background: rgba(15, 23, 42, 0.5); color: var(--text-main); font-size: 0.95rem; }
        textarea { min-height: 110px; resize: vertical; }
        input:focus, textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22); }
        button { background: linear-gradient(135deg, var(--accent), var(--accent-strong)); border: none; color: #0f172a; border-radius: 999px; padding: 0.7rem 1.3rem; font-weight: 700; cursor: pointer; transition: transform 0.18s ease, box-shadow 0.18s ease; }
        button:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(14, 165, 233, 0.25); }
        button[disabled] { background: rgba(148, 163, 184, 0.35); color: rgba(15, 23, 42, 0.6); cursor: wait; box-shadow: none; }
        .form-result { border-radius: 10px; padding: 0.55rem 0.75rem; font-size: 0.9rem; line-height: 1.4; display: none; }
        .form-result.show { display: block; }
        .form-result.success { background: rgba(52, 211, 153, 0.18); color: #16a34a; border: 1px solid rgba(52, 211, 153, 0.4); }
        .form-result.error { background: rgba(248, 113, 113, 0.18); color: #dc2626; border: 1px solid rgba(248, 113, 113, 0.3); }
        .form-result.info { background: rgba(59, 130, 246, 0.16); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.25); }
        .module-toggle-list { display: grid; gap: 0.65rem; }
        .module-toggle { display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.75rem; border-radius: 14px; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(148, 163, 184, 0.2); }
        .module-toggle span { font-size: 0.9rem; color: rgba(226, 232, 240, 0.85); }
        .module-toggle small { display: block; font-size: 0.75rem; color: rgba(148, 163, 184, 0.7); }
        .switch { position: relative; width: 42px; height: 22px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background: rgba(148, 163, 184, 0.35); border-radius: 999px; transition: background 0.2s ease; }
        .slider::before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; top: 50%; transform: translateY(-50%); background-color: #0f172a; border-radius: 50%; transition: transform 0.2s ease; }
        .switch input:checked + .slider { background: linear-gradient(135deg, var(--accent), var(--accent-strong)); }
        .switch input:checked + .slider::before { transform: translate(18px, -50%); }
        footer { margin-top: auto; padding: 2.2rem 3rem; text-align: center; color: rgba(148, 163, 184, 0.7); font-size: 0.85rem; }
        @media (max-width: 640px) { nav { flex-direction: column; gap: 1rem; align-items: flex-start; } .nav-extra { display: none; } }
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
                    <div class="stats-grid" id="stats-grid">
                        <div class="stat-card" data-stat="projects">
                            <h3>项目总览</h3>
                            <strong data-stat-count="projects">0</strong>
                            <span>最近创建的项目条目</span>
                        </div>
                        <div class="stat-card" data-stat="devices">
                            <h3>设备数量</h3>
                            <strong data-stat-count="devices">0</strong>
                            <span>涵盖全部状态</span>
                        </div>
                        <div class="stat-card" data-stat="reservations">
                            <h3>活跃预留</h3>
                            <strong data-stat-count="reservations">0</strong>
                            <span>最新预留记录</span>
                        </div>
                        <div class="stat-card" data-stat="checkouts">
                            <h3>借用记录</h3>
                            <strong data-stat-count="checkouts">0</strong>
                            <span>近期借用与归还</span>
                        </div>
                        <div class="stat-card" data-stat="notifications">
                            <h3>通知提醒</h3>
                            <strong data-stat-count="notifications">0</strong>
                            <span>系统自动推送</span>
                        </div>
                    </div>
                </section>

                <section class="glass-card" data-dataset="projects">
                    <div class="section-title">
                        <h2>项目概览</h2>
                        <span class="badge" data-count-badge="projects">近期记录：0 条</span>
                    </div>
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
                            <tbody data-table-body="projects"></tbody>
                        </table>
                    </div>
                    <p class="empty-placeholder" data-empty="projects">暂无项目记录，请先创建一个项目。</p>
                </section>

                <section class="glass-card" data-dataset="devices">
                    <div class="section-title">
                        <h2>设备概览</h2>
                        <span class="badge" data-count-badge="devices">近期记录：0 条</span>
                    </div>
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
                            <tbody data-table-body="devices"></tbody>
                        </table>
                    </div>
                    <p class="empty-placeholder" data-empty="devices">暂无设备记录，尝试登记第一台设备。</p>
                </section>

                <section class="glass-card" data-dataset="reservations">
                    <div class="section-title">
                        <h2>预留记录</h2>
                        <span class="badge" data-count-badge="reservations">近期记录：0 条</span>
                    </div>
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
                            <tbody data-table-body="reservations"></tbody>
                        </table>
                    </div>
                    <p class="empty-placeholder" data-empty="reservations">暂无预留记录，预留设备后将在此处显示。</p>
                </section>

                <section class="glass-card" data-dataset="checkouts">
                    <div class="section-title">
                        <h2>借用记录</h2>
                        <span class="badge" data-count-badge="checkouts">近期记录：0 条</span>
                    </div>
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
                            <tbody data-table-body="checkouts"></tbody>
                        </table>
                    </div>
                    <p class="empty-placeholder" data-empty="checkouts">暂无借用记录。</p>
                </section>

                <section class="glass-card" data-dataset="notifications">
                    <div class="section-title">
                        <h2>通知记录</h2>
                        <span class="badge" data-count-badge="notifications">近期记录：0 条</span>
                    </div>
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
                            <tbody data-table-body="notifications"></tbody>
                        </table>
                    </div>
                    <p class="empty-placeholder" data-empty="notifications">当前暂无系统通知。</p>
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

                <div class="panel-card" id="module-forms">
                    <h3>操作表单</h3>
                    <div class="module-form" data-module="project">
                        <form method="post" action="/projects/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>项目名称<input type="text" name="name" required></label>
                            <label>项目地点<input type="text" name="location" required></label>
                            <label>开始时间<input type="datetime-local" name="starts_at" required></label>
                            <label>交付时间<input type="datetime-local" name="due_at" required></label>
                            <label>报价金额<input type="number" step="0.01" name="quote_amount" value="0.00"></label>
                            <label>备注<textarea name="note"></textarea></label>
                            <button type="submit">提交项目</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="device">
                        <form method="post" action="/devices/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>设备编号<input type="text" name="code" required></label>
                            <label>型号<input type="text" name="model" required></label>
                            <label>序列号（可选）<input type="text" name="serial"></label>
                            <label>照片地址（可选）<input type="url" name="photo_url"></label>
                            <button type="submit">提交设备</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="reservation">
                        <form method="post" action="/reservations/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>项目 ID<input type="number" name="project_id" min="1" required></label>
                            <label>设备 ID<input type="number" name="device_id" min="1" required></label>
                            <label>开始时间<input type="datetime-local" name="from" required></label>
                            <label>结束时间<input type="datetime-local" name="to" required></label>
                            <button type="submit">提交预留</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="checkout">
                        <form method="post" action="/checkouts/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>设备 ID<input type="number" name="device_id" min="1" required></label>
                            <label>项目 ID（可选）<input type="number" name="project_id" min="1"></label>
                            <label>借出时间<input type="datetime-local" name="now" required></label>
                            <label>归还期限<input type="datetime-local" name="due" required></label>
                            <label>借出照片（可选）<input type="url" name="photo"></label>
                            <label>备注<textarea name="note"></textarea></label>
                            <button type="submit">提交借出</button>
                            <div class="form-result" data-result></div>
                        </form>
                    </div>

                    <div class="module-form" data-module="return">
                        <form method="post" action="/returns/create" data-ajax="true">
                            <?= csrf_field() ?>
                            <label>设备 ID<input type="number" name="device_id" min="1" required></label>
                            <label>归还时间<input type="datetime-local" name="now" required></label>
                            <label>归还照片（可选）<input type="url" name="photo"></label>
                            <label>备注<textarea name="note"></textarea></label>
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

            const renderTable = (key, rows) => {
                const body = document.querySelector(`[data-table-body="${key}"]`);
                const emptyTip = document.querySelector(`[data-empty="${key}"]`);
                const badge = document.querySelector(`[data-count-badge="${key}"]`);
                const statCount = document.querySelector(`[data-stat-count="${key}"]`);

                if (!body) {
                    return;
                }

                body.innerHTML = '';

                if (badge) {
                    badge.textContent = `近期记录：${rows.length} 条`;
                }
                if (statCount) {
                    statCount.textContent = rows.length;
                }

                if (!rows.length) {
                    if (emptyTip) {
                        emptyTip.style.display = '';
                    }
                    return;
                }

                if (emptyTip) {
                    emptyTip.style.display = 'none';
                }

                const formatDate = (value) => {
                    if (!value) {
                        return '-';
                    }
                    const date = new Date(value.replace(' ', 'T'));
                    if (Number.isNaN(date.getTime())) {
                        return value;
                    }
                    return date.toISOString().slice(0, 16).replace('T', ' ');
                };

                const statusChip = (status, type = '') => {
                    const map = {
                        projects: {
                            ongoing: '进行中',
                            done: '已完成',
                        },
                        devices: {
                            in_stock: { label: '在库', cls: 'success' },
                            reserved: { label: '已预留', cls: 'warning' },
                            checked_out: { label: '借出中', cls: 'danger' },
                            transfer_pending: { label: '待转交', cls: '' },
                            lost: { label: '遗失', cls: 'danger' },
                            repair: { label: '维修中', cls: 'warning' },
                        },
                    };
                    if (type === 'device') {
                        const conf = map.devices[status] ?? { label: status || '-', cls: '' };
                        return `<span class="status-chip ${conf.cls}">${conf.label}</span>`;
                    }
                    return `<span class="status-chip">${map.projects[status] ?? status ?? '-'}</span>`;
                };

                const builders = {
                    projects: (row) => `
                        <tr>
                            <td>${row.id ?? '-'}</td>
                            <td>${row.name ?? '-'}</td>
                            <td>${row.location ?? '-'}</td>
                            <td>${statusChip(row.status ?? null)}</td>
                            <td>${formatDate(row.starts_at ?? null)}</td>
                            <td>${formatDate(row.due_at ?? null)}</td>
                            <td>${formatDate(row.created_at ?? null)}</td>
                        </tr>
                    `,
                    devices: (row) => `
                        <tr>
                            <td>${row.id ?? '-'}</td>
                            <td>${row.code ?? '-'}</td>
                            <td>${row.model ?? '-'}</td>
                            <td>${statusChip(row.status ?? null, 'device')}</td>
                            <td>${formatDate(row.created_at ?? null)}</td>
                        </tr>
                    `,
                    reservations: (row) => `
                        <tr>
                            <td>${row.id ?? '-'}</td>
                            <td>${row.project_name ?? ('#' + (row.project_id ?? '-'))}</td>
                            <td>${row.device_code ?? ('#' + (row.device_id ?? '-'))}</td>
                            <td>${formatDate(row.reserved_from ?? null)}</td>
                            <td>${formatDate(row.reserved_to ?? null)}</td>
                            <td>${formatDate(row.created_at ?? null)}</td>
                        </tr>
                    `,
                    checkouts: (row) => {
                        const returned = Boolean(row.return_at);
                        return `
                            <tr>
                                <td>${row.id ?? '-'}</td>
                                <td>${row.project_name ?? ('#' + (row.project_id ?? '-'))}</td>
                                <td>${row.device_code ?? ('#' + (row.device_id ?? '-'))}</td>
                                <td>${formatDate(row.checked_out_at ?? null)}</td>
                                <td>${formatDate(row.due_at ?? null)}</td>
                                <td>${formatDate(row.return_at ?? null)}</td>
                                <td><span class="status-chip ${returned ? 'success' : 'warning'}">${returned ? '已归还' : '借出中'}</span></td>
                            </tr>
                        `;
                    },
                    notifications: (row) => `
                        <tr>
                            <td>${row.id ?? '-'}</td>
                            <td>${row.user_id ?? '-'}</td>
                            <td>${row.title ?? '-'}</td>
                            <td>${row.body ?? '-'}</td>
                            <td>${formatDate(row.created_at ?? null)}</td>
                            <td>${row.delivered_at ? formatDate(row.delivered_at) : '未送达'}</td>
                        </tr>
                    `,
                };

                const buildRow = builders[key] ?? (() => '');
                body.innerHTML = rows.map((row) => buildRow(row)).join('');
            };

            const loadDashboardData = async () => {
                try {
                    const res = await fetch('/dashboard/data', {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    const payload = await res.json();
                    if (!payload.success) {
                        throw new Error(payload.message ?? '数据加载失败');
                    }
                    const data = payload.data ?? {};
                    renderTable('projects', data.projects ?? []);
                    renderTable('devices', data.devices ?? []);
                    renderTable('reservations', data.reservations ?? []);
                    renderTable('checkouts', data.checkouts ?? []);
                    renderTable('notifications', data.notifications ?? []);
                } catch (error) {
                    console.error('加载数据失败', error);
                }
            };

            const refreshStatus = async () => {
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
                } catch (error) {
                    console.warn('刷新页面状态失败', error);
                } finally {
                    await loadDashboardData();
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
                            await refreshStatus();
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
            loadDashboardData();
        })();
    </script>
</body>
</html>
