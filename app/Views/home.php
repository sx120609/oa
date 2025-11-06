<?php
/** @var array $session */
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
            --bg: #f3f6fb;
            --sidebar: #1f2937;
            --sidebar-active: #2563eb;
            --sidebar-text: #e5e7eb;
            --content-bg: #ffffff;
            --border: #e2e8f0;
            --text: #111827;
            --muted: #6b7280;
            --primary: #2563eb;
            --primary-light: #e0ecff;
            --danger: #ef4444;
            --success: #16a34a;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: "Inter", "PingFang SC", "Microsoft YaHei", sans-serif; background: var(--bg); color: var(--text); }
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 240px; background: var(--sidebar); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 1.5rem 1rem; gap: 2rem; }
        .sidebar .logo { font-size: 1.3rem; font-weight: 700; letter-spacing: 0.06em; text-align: center; }
        .nav-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .nav-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(229, 231, 235, 0.65); margin-bottom: 0.2rem; padding: 0 0.75rem; }
        .nav-link { appearance: none; border: none; background: transparent; color: inherit; display: flex; align-items: center; justify-content: space-between; width: 100%; font-size: 0.95rem; padding: 0.7rem 0.9rem; border-radius: 0.75rem; cursor: pointer; transition: background 0.18s ease, color 0.18s ease; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.08); }
        .nav-link.active { background: rgba(59, 130, 246, 0.18); color: #ffffff; }
        .content { flex: 1; display: flex; flex-direction: column; padding: 0 2.5rem 2.5rem; }
        .content[data-login-state="guest"] .tabs-container,
        .content[data-login-state="guest"] .tabs-header,
        .content[data-login-state="guest"] .tab-content { display: none !important; }
        .topbar { padding: 1.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
        .breadcrumb { display: flex; align-items: center; gap: 0.65rem; font-size: 0.95rem; color: var(--muted); }
        .top-actions { display: flex; align-items: center; gap: 1rem; }
        .top-actions button { background: var(--primary); color: #fff; border: none; border-radius: 0.75rem; padding: 0.55rem 1.1rem; font-weight: 600; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .top-actions button:hover { transform: translateY(-1px); box-shadow: 0 12px 20px rgba(37, 99, 235, 0.2); }
        .login-card { background: #ffffff; border: 1px solid var(--border); border-radius: 1rem; padding: 1rem 1.2rem; box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08); display: flex; align-items: center; gap: 0.8rem; }
        .login-card form { display: flex; align-items: center; gap: 0.6rem; }
        .login-card input { border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.45rem 0.6rem; font-size: 0.9rem; }
        .login-card button { background: var(--primary); color: #fff; border: none; border-radius: 0.6rem; padding: 0.45rem 0.75rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
        .tabs-container { background: #ffffff; border: 1px solid var(--border); border-radius: 1.2rem; box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12); overflow: hidden; display: flex; flex-direction: column; }
        .tabs-header { display: flex; align-items: center; gap: 0.25rem; border-bottom: 1px solid var(--border); background: #f9fafb; padding: 0.35rem 0.6rem; }
        .tab-btn { appearance: none; border: none; background: transparent; padding: 0.75rem 1.35rem; border-radius: 0.9rem; font-size: 0.95rem; font-weight: 600; color: var(--muted); cursor: pointer; transition: background 0.15s ease, color 0.15s ease; }
        .tab-btn:hover { background: rgba(37, 99, 235, 0.12); color: var(--primary); }
        .tab-btn.active { background: #ffffff; color: var(--primary); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15); }
        .tab-content { display: none; padding: 1.8rem; }
        .tab-content.active { display: block; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.2rem; }
        .stat-card { background: var(--primary-light); border-radius: 1rem; padding: 1.2rem; display: grid; gap: 0.4rem; }
        .stat-card h3 { margin: 0; font-size: 0.9rem; color: var(--primary); }
        .stat-card strong { font-size: 1.8rem; }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1.4rem; }
        .section-title h2 { margin: 0; font-size: 1.3rem; letter-spacing: 0.04em; }
        .badge { background: rgba(148, 163, 184, 0.18); color: var(--text); padding: 0.3rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .data-table-wrapper { border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .data-table th, .data-table td { padding: 0.65rem 0.9rem; border-bottom: 1px solid var(--border); text-align: left; }
        .data-table thead { background: #f8fafc; }
        .empty-placeholder { margin-top: 1rem; color: var(--muted); font-size: 0.9rem; }
        .form-card { background: #f9fafb; border: 1px solid var(--border); border-radius: 1rem; padding: 1.4rem; margin-top: 1.5rem; display: grid; gap: 0.85rem; }
        .form-card h4 { margin: 0; font-size: 1rem; }
        .form-card label { display: grid; gap: 0.35rem; font-size: 0.9rem; }
        .form-card input, .form-card textarea, .form-card select { border: 1px solid var(--border); border-radius: 0.7rem; padding: 0.55rem 0.7rem; font-size: 0.95rem; }
        .form-result { border-radius: 0.75rem; padding: 0.5rem 0.75rem; font-size: 0.9rem; display: none; }
        .form-result.show { display: block; }
        .form-result.success { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .form-result.error { background: rgba(248, 113, 113, 0.15); color: var(--danger); }
        .action-btn { border: none; border-radius: 0.6rem; padding: 0.35rem 0.65rem; font-size: 0.85rem; cursor: pointer; transition: opacity 0.15s ease; }
        .action-btn.delete { background: rgba(239, 68, 68, 0.12); color: var(--danger); }
        .action-btn.delete:hover { opacity: 0.75; }
        footer { padding: 1.6rem 3rem; text-align: center; color: var(--muted); font-size: 0.85rem; }
        @media (max-width: 800px) {
            .sidebar { display: none; }
            .content { padding: 0 1rem 2rem; }
            .topbar { flex-direction: column; align-items: stretch; }
            .login-card { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="logo">资产运营平台</div>
        <div class="nav-group">
            <div class="nav-title">数据中心</div>
            <button class="nav-link active" data-tab="overview">数据概览</button>
            <button class="nav-link" data-tab="users">用户管理</button>
            <button class="nav-link" data-tab="projects">项目管理</button>
            <button class="nav-link" data-tab="devices">设备管理</button>
            <button class="nav-link" data-tab="reservations">预留管理</button>
            <button class="nav-link" data-tab="checkouts">借用管理</button>
            <button class="nav-link" data-tab="notifications">通知中心</button>
        </div>
    </aside>
    <div class="content" data-login-state="<?= !empty($session['uid']) ? 'authenticated' : 'guest' ?>">
        <header class="topbar">
            <div class="breadcrumb">
                <span>资产运营平台</span>
                <span>›</span>
                <strong id="breadcrumb-label">数据概览</strong>
            </div>
            <div class="top-actions">
                <button type="button" onclick="window.dashboardRefresh && window.dashboardRefresh()">刷新数据</button>
                <div class="login-card" data-auth-visible="guest" style="display:none;">
                    <form method="post" action="/login" data-ajax="true">
                        <?= csrf_field() ?>
                        <input type="email" name="email" placeholder="邮箱" required>
                        <input type="password" name="password" placeholder="密码" required>
                        <button type="submit">登录</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
                <div class="login-card" data-auth-visible="authenticated" style="display:none;">
                    <div>
                        <strong>当前账号：</strong><?= escape($session['email'] ?? '') ?>
                    </div>
                </div>
            </div>
        </header>
        <div class="tabs-container" data-auth-visible="authenticated" style="display:none;">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="overview">总览</button>
                <button class="tab-btn" data-tab="users">用户</button>
                <button class="tab-btn" data-tab="projects">项目</button>
                <button class="tab-btn" data-tab="devices">设备</button>
                <button class="tab-btn" data-tab="reservations">预留</button>
                <button class="tab-btn" data-tab="checkouts">借用</button>
                <button class="tab-btn" data-tab="notifications">通知</button>
            </div>
            <section class="tab-content active" data-tab-content="overview">
                <div class="stats-grid">
                    <div class="stat-card"><h3>用户总数</h3><strong data-stat-count="users">0</strong><span>最近注册</span></div>
                    <div class="stat-card"><h3>项目总数</h3><strong data-stat-count="projects">0</strong><span>最新项目</span></div>
                    <div class="stat-card"><h3>设备数量</h3><strong data-stat-count="devices">0</strong><span>全部状态</span></div>
                    <div class="stat-card"><h3>预留记录</h3><strong data-stat-count="reservations">0</strong><span>时间窗口</span></div>
                    <div class="stat-card"><h3>借用记录</h3><strong data-stat-count="checkouts">0</strong><span>借出与归还</span></div>
                    <div class="stat-card"><h3>通知数量</h3><strong data-stat-count="notifications">0</strong><span>提醒与告警</span></div>
                </div>
            </section>
            <section class="tab-content" data-tab-content="users">
                <div class="section-title"><h2>用户列表</h2><span class="badge" data-count-badge="users">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                        <tr><th>ID</th><th>姓名</th><th>邮箱</th><th>角色</th><th>创建时间</th><th>操作</th></tr>
                        </thead>
                        <tbody data-table-body="users"></tbody>
                    </table>
                </div>
                <p class="empty-placeholder" data-empty="users">暂无用户记录。</p>
                <div class="form-card">
                    <h4>新增用户</h4>
                    <form method="post" action="/users/create" data-ajax="true">
                        <?= csrf_field() ?>
                        <label>姓名<input type="text" name="name" required></label>
                        <label>邮箱<input type="email" name="email" required></label>
                        <label>密码<input type="password" name="password" required></label>
                        <label>角色
                            <select name="role" required>
                                <option value="owner">负责人</option>
                                <option value="asset_admin">资产管理员</option>
                                <option value="planner">策划</option>
                                <option value="photographer">摄影</option>
                            </select>
                        </label>
                        <button type="submit">创建用户</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="projects">
                <div class="section-title"><h2>项目列表</h2><span class="badge" data-count-badge="projects">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>名称</th><th>地点</th><th>状态</th><th>开始时间</th><th>交付时间</th><th>创建时间</th></tr></thead>
                        <tbody data-table-body="projects"></tbody>
                    </table>
                </div>
                <p class="empty-placeholder" data-empty="projects">暂无项目记录，请先创建一个项目。</p>
                <div class="form-card">
                    <h4>创建项目</h4>
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
            </section>
            <section class="tab-content" data-tab-content="devices">
                <div class="section-title"><h2>设备列表</h2><span class="badge" data-count-badge="devices">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>编号</th><th>型号</th><th>状态</th><th>创建时间</th></tr></thead><tbody data-table-body="devices"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="devices">暂无设备记录。</p>
                <div class="form-card">
                    <h4>创建设备</h4>
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
            </section>
            <section class="tab-content" data-tab-content="reservations">
                <div class="section-title"><h2>预留列表</h2><span class="badge" data-count-badge="reservations">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>项目</th><th>设备</th><th>预留开始</th><th>预留结束</th><th>创建时间</th></tr></thead><tbody data-table-body="reservations"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="reservations">当前暂无预留记录。</p>
                <div class="form-card">
                    <h4>创建预留</h4>
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
            </section>
            <section class="tab-content" data-tab-content="checkouts">
                <div class="section-title"><h2>借用列表</h2><span class="badge" data-count-badge="checkouts">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>项目</th><th>设备</th><th>借出时间</th><th>到期时间</th><th>归还时间</th><th>状态</th></tr></thead><tbody data-table-body="checkouts"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="checkouts">暂无借用记录。</p>
                <div class="form-card">
                    <h4>借出设备</h4>
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
                <div class="form-card">
                    <h4>归还设备</h4>
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
            </section>
            <section class="tab-content" data-tab-content="notifications">
                <div class="section-title"><h2>通知中心</h2><span class="badge" data-count-badge="notifications">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>用户</th><th>标题</th><th>内容</th><th>发送时间</th><th>已送达</th></tr></thead><tbody data-table-body="notifications"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="notifications">暂无通知记录。</p>
            </section>
        </div>
        <div class="glass-card" data-auth-visible="guest" style="display:none; text-align:center; padding:3rem;">
            <h2>请先登录</h2>
            <p style="color: var(--muted); margin: 0;">登陆后可查看项目、设备及操作记录。</p>
        </div>
        <footer>© <?= date('Y') ?> 资产运营平台 · 管理后台</footer>
    </div>
</div>
<script>
(() => {
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    const tabs = document.querySelectorAll('.nav-link, .tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const breadcrumb = document.getElementById('breadcrumb-label');
    let authState = document.querySelector('.content')?.dataset.loginState || 'guest';

    const syncAuthVisibility = () => {
        document.querySelectorAll('[data-auth-visible]').forEach((block) => {
            const state = block.getAttribute('data-auth-visible');
            block.style.display = state === authState ? '' : 'none';
        });
    };

    syncAuthVisibility();

    let csrfToken = document.querySelector('form[data-ajax="true"] input[name="_token"]')?.value || '';

    const parseResponse = (text) => {
        const trimmed = text.trim();
        if (!trimmed) { return { type: 'error', message: '服务器未返回任何信息。' }; }
        if (trimmed.toUpperCase().startsWith('OK')) { return { type: 'success', message: trimmed }; }
        if (trimmed.toUpperCase().startsWith('ERROR')) { return { type: 'error', message: trimmed.replace(/^ERROR:\s*/i, '') }; }
        return { type: 'info', message: trimmed };
    };

    const renderTable = (key, rows) => {
        const body = document.querySelector(`[data-table-body="${key}"]`);
        const emptyTip = document.querySelector(`[data-empty="${key}"]`);
        const badge = document.querySelector(`[data-count-badge="${key}"]`);
        const statCount = document.querySelector(`[data-stat-count="${key}"]`);
        if (!body) return;
        body.innerHTML = '';
        if (badge) badge.textContent = `共 ${rows.length} 条`;
        if (statCount) statCount.textContent = rows.length;
        if (!rows.length) { if (emptyTip) emptyTip.style.display = ''; return; }
        if (emptyTip) emptyTip.style.display = 'none';

        const formatDate = (value) => {
            if (!value) return '-';
            const date = new Date(value.replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? value : date.toISOString().slice(0, 16).replace('T', ' ');
        };

        const statusChip = (status, scope) => {
            if (scope === 'device') {
                const map = {
                    in_stock: { label: '在库', cls: 'success' },
                    reserved: { label: '已预留', cls: 'warning' },
                    checked_out: { label: '借出中', cls: 'danger' },
                    transfer_pending: { label: '待转交', cls: '' },
                    lost: { label: '遗失', cls: 'danger' },
                    repair: { label: '维修中', cls: 'warning' },
                };
                const conf = map[status] ?? { label: status ?? '-', cls: '' };
                return `<span class="status-chip ${conf.cls}">${conf.label}</span>`;
            }
            const projectMap = { ongoing: '进行中', done: '已完成' };
            return `<span class="status-chip success">${projectMap[status] ?? status ?? '-'}</span>`;
        };

        const builders = {
            users: (row) => `
                <tr>
                    <td>${row.id ?? '-'}</td>
                    <td>${row.name ?? '-'}</td>
                    <td>${row.email ?? '-'}</td>
                    <td>${row.role ?? '-'}</td>
                    <td>${formatDate(row.created_at ?? null)}</td>
                    <td><button type="button" class="action-btn delete" data-delete-user="${row.id ?? ''}">删除</button></td>
                </tr>
            `,
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

        body.innerHTML = rows.map((row) => (builders[key] ?? (() => ''))(row)).join('');
    };

    const loadDashboardData = async () => {
        try {
            const res = await fetch('/dashboard/data', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const payload = await res.json();
            if (!payload.success) throw new Error(payload.message ?? '数据加载失败');
            const data = payload.data ?? {};
            renderTable('users', data.users ?? []);
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
            if (!res.ok) return;
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const formsNew = doc.querySelectorAll('form[data-ajax="true"]');
            formsNew.forEach((newForm) => {
                const selector = `form[data-ajax="true"][action="${newForm.getAttribute('action')}"]`;
                const currentForm = document.querySelector(selector);
                if (!currentForm) return;
                const newToken = newForm.querySelector('input[name="_token"]');
                const currentToken = currentForm.querySelector('input[name="_token"]');
                if (newToken && currentToken) {
                    currentToken.value = newToken.value;
                    csrfToken = newToken.value;
                }
            });
            const statusNew = doc.querySelector('[data-current-status]');
            const statusCurrent = document.querySelector('[data-current-status]');
            if (statusNew && statusCurrent) {
                statusCurrent.innerHTML = statusNew.innerHTML;
            }

            const stateNew = doc.querySelector('.content')?.dataset.loginState || 'guest';
            const content = document.querySelector('.content');
            authState = stateNew;
            if (content) {
                content.dataset.loginState = authState;
            }
            syncAuthVisibility();
        } catch (error) {
            console.warn('刷新页面状态失败', error);
        } finally {
            if (authState === 'authenticated') {
                await loadDashboardData();
            }
        }
    };

    tabs.forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            tabs.forEach((item) => item.classList.toggle('active', item.getAttribute('data-tab') === tab));
            tabContents.forEach((section) => section.classList.toggle('active', section.getAttribute('data-tab-content') === tab));
            if (breadcrumb) {
                const map = {
                    overview: '数据概览',
                    users: '用户管理',
                    projects: '项目管理',
                    devices: '设备管理',
                    reservations: '预留管理',
                    checkouts: '借用管理',
                    notifications: '通知中心',
                };
                breadcrumb.textContent = map[tab] ?? '数据概览';
            }
        });
    });

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

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-delete-user]');
        if (!btn) return;
        const userId = btn.getAttribute('data-delete-user');
        if (!userId || !confirm('确认删除该用户？')) {
            return;
        }
        try {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('user_id', userId);
            const res = await fetch('/users/delete', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const text = await res.text();
            const { type, message } = parseResponse(text);
            if (type === 'success') {
                await refreshStatus();
            } else {
                alert(message);
            }
        } catch (error) {
            alert(error instanceof Error ? error.message : '删除失败');
        }
    });

    window.dashboardRefresh = refreshStatus;
    if (authState === 'authenticated') {
        loadDashboardData();
    }
})();
</script>
</body>
</html>
