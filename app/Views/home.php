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
            color-scheme: dark;
            --bg: radial-gradient(circle at top, #1f2a44, #0c1224);
            --sidebar: rgba(17,25,40,0.88);
            --sidebar-active: #4fa5ff;
            --sidebar-text: #f8fafc;
            --content-bg: rgba(10,15,28,0.78);
            --panel-bg: rgba(19,27,50,0.9);
            --surface-soft: rgba(255,255,255,0.02);
            --table-head-bg: rgba(255,255,255,0.06);
            --input-bg: rgba(15,23,42,0.6);
            --border: rgba(255,255,255,0.08);
            --text: #e2e8f0;
            --muted: #94a3b8;
            --primary: #4fa5ff;
            --primary-light: rgba(79,165,255,0.18);
            --danger: #fb7185;
            --success: #34d399;
            --warning: #fbbf24;
            --shadow-soft: 0 10px 22px rgba(15, 23, 42, 0.35);
            --nav-hover-bg: rgba(255,255,255,0.08);
            --nav-active-bg: rgba(79,165,255,0.25);
            --nav-active-color: #f8fafc;
        }
body.theme-light {
            color-scheme: light;
            --bg: linear-gradient(180deg,#ffffff,#e5ecff);
            --sidebar: #fefefe;
            --sidebar-active: #2563eb;
            --sidebar-text: #0f172a;
            --content-bg: #ffffff;
            --panel-bg: #ffffff;
            --surface-soft: #f9fafb;
            --table-head-bg: #f3f4f6;
            --input-bg: #ffffff;
            --border: #e2e8f0;
            --text: #111827;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-light: rgba(37,99,235,0.12);
            --danger: #ef4444;
            --success: #16a34a;
            --warning: #f59e0b;
            --shadow-soft: 0 10px 22px rgba(15, 23, 42, 0.08);
            --nav-hover-bg: rgba(37,99,235,0.08);
            --nav-active-bg: rgba(37,99,235,0.18);
            --nav-active-color: #1e3a8a;
        }

        body.theme-light .edit-overlay {
            background: rgba(15, 23, 42, 0.2);
            backdrop-filter: blur(4px);
        }

        body.theme-light .edit-panel {
            background: rgba(255,255,255,0.95);
            color: #111827;
            border-color: rgba(148, 163, 184, 0.35);
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.18);
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: "Inter", "PingFang SC", "Microsoft YaHei", sans-serif; background: var(--bg); color: var(--text); position: relative; transition: background 0.3s ease; }
        body::after { content: ""; position: fixed; inset: 0; pointer-events: none; background-image: radial-gradient(#fff5 1px, transparent 1px); background-size: 3px 3px; opacity: 0.08; }
        .app { display: flex; min-height: 100vh; position: relative; z-index: 1; }
        .sidebar { width: 240px; background: var(--sidebar); backdrop-filter: blur(12px); color: var(--sidebar-text); display: flex; flex-direction: column; padding: 1.5rem 1rem; gap: 1.5rem; border-right: 1px solid var(--border); box-shadow: 6px 0 24px rgba(0,0,0,0.25); }
        .sidebar .logo { font-size: 1.3rem; font-weight: 700; letter-spacing: 0.06em; text-align: center; }
        .sidebar-search { display: flex; gap: 0.4rem; align-items: center; background: var(--surface-soft); border-radius: 0.8rem; padding: 0.35rem 0.6rem; }
        .sidebar-search svg { opacity: 0.6; }
        .sidebar-search input { flex: 1; background: transparent; border: none; color: inherit; font-size: 0.9rem; outline: none; }
        .nav-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .nav-title { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(229, 231, 235, 0.65); margin-bottom: 0.2rem; padding: 0 0.75rem; }
        .nav-link { appearance: none; border: none; background: transparent; color: inherit; display: flex; align-items: center; justify-content: space-between; width: 100%; font-size: 0.95rem; padding: 0.7rem 0.9rem; border-radius: 0.75rem; cursor: pointer; transition: background 0.18s ease, color 0.18s ease; }
        .nav-link:hover { background: var(--nav-hover-bg); }
        .nav-link.active { background: var(--nav-active-bg); color: var(--nav-active-color); box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08); }
.content { flex: 1; display: flex; flex-direction: column; padding: 0 2.5rem 2.5rem; background: var(--content-bg); }
        .content[data-login-state="guest"] .tabs-container,
        .content[data-login-state="guest"] .tabs-header,
        .content[data-login-state="guest"] .tab-content { display: none !important; }
        .topbar { padding: 1.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
        .breadcrumb { display: flex; align-items: center; gap: 0.65rem; font-size: 0.95rem; color: var(--muted); }
        .top-actions { display: flex; align-items: center; gap: 1rem; }
        .top-actions button { background: var(--primary); color: #fff; border: none; border-radius: 0.75rem; padding: 0.55rem 1.1rem; font-weight: 600; cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .top-actions button.logout-btn { background: var(--danger); }
        .top-actions button:hover { transform: translateY(-1px); box-shadow: 0 12px 20px rgba(37, 99, 235, 0.2); }
        .top-actions form { margin: 0; display: inline-flex; }
.login-card { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 1rem; padding: 1rem 1.2rem; box-shadow: 0 10px 22px rgba(15, 23, 42, 0.25); display: flex; align-items: center; gap: 0.8rem; }
        .login-card form { display: flex; align-items: center; gap: 0.6rem; }
.login-card input { border: 1px solid var(--border); border-radius: 0.6rem; padding: 0.45rem 0.6rem; font-size: 0.9rem; background: rgba(15,23,42,0.6); color: var(--text); }
        .login-card button { background: var(--primary); color: #fff; border: none; border-radius: 0.6rem; padding: 0.45rem 0.75rem; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.tabs-container { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 1.2rem; box-shadow: 0 12px 26px rgba(15, 23, 42, 0.35); overflow: hidden; display: flex; flex-direction: column; }
.tabs-header { display: flex; align-items: center; gap: 0.25rem; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.03); padding: 0.35rem 0.6rem; }
        .tab-btn { appearance: none; border: none; background: transparent; padding: 0.75rem 1.35rem; border-radius: 0.9rem; font-size: 0.95rem; font-weight: 600; color: var(--muted); cursor: pointer; transition: background 0.15s ease, color 0.15s ease; }
        .tab-btn:hover { background: rgba(37, 99, 235, 0.12); color: var(--primary); }
.tab-btn.active { background: rgba(255,255,255,0.1); color: var(--primary); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
        .tab-content { display: none; padding: 1.8rem; }
        .tab-content.active { display: block; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.2rem; }
.stat-card { background: var(--primary-light); border-radius: 1rem; padding: 1.2rem; display: grid; gap: 0.4rem; color: var(--text); }
        .stat-card h3 { margin: 0; font-size: 0.9rem; color: var(--primary); }
        .stat-card strong { font-size: 1.8rem; }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1.4rem; }
        .section-title h2 { margin: 0; font-size: 1.3rem; letter-spacing: 0.04em; }
        .badge { background: var(--surface-soft); color: var(--text); padding: 0.3rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .top-actions [data-theme-toggle] { width: 40px; height: 40px; padding: 0; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center; }
        .data-table-wrapper { border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .data-table th, .data-table td { padding: 0.65rem 0.9rem; border-bottom: 1px solid var(--border); text-align: left; }
.data-table thead { background: rgba(255,255,255,0.06); }
        .empty-placeholder { margin-top: 1rem; color: var(--muted); font-size: 0.9rem; }
.form-card { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 1rem; padding: 1.4rem; margin-top: 1.5rem; display: grid; gap: 0.85rem; color: var(--text); }
        .form-card.highlight { box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18); }
        .form-card h4 { margin: 0; font-size: 1rem; }
        .form-card label { display: grid; gap: 0.35rem; font-size: 0.9rem; }
.form-card input, .form-card textarea, .form-card select { border: 1px solid var(--border); border-radius: 0.7rem; padding: 0.55rem 0.7rem; font-size: 0.95rem; background: var(--input-bg); color: var(--text); }
        .input-with-helper { display: flex; align-items: center; gap: 0.6rem; }
        .input-with-helper input { flex: 1 1 auto; }
        .fill-now-btn { border: none; background: rgba(37, 99, 235, 0.12); color: var(--primary); border-radius: 0.6rem; padding: 0.4rem 0.8rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s ease, color 0.15s ease; }
        .fill-now-btn:hover { background: rgba(37, 99, 235, 0.18); color: #1d4ed8; }
        .edit-overlay { position: fixed; inset: 0; background: rgba(5, 8, 18, 0.45); backdrop-filter: blur(6px); z-index: 1000; display: none; cursor: pointer; }
        .edit-overlay.show { display: block; }
        .edit-panel { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: min(520px, 92%); max-height: 90vh; overflow-y: auto; z-index: 1100; margin: 0; box-shadow: 0 25px 60px rgba(6, 12, 30, 0.45); background: rgba(14, 19, 36, 0.95); border: 1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px); }
        .edit-panel.show { display: block; }
        .edit-panel header { display: flex; align-items: center; justify-content: space-between; margin: -0.4rem -0.4rem 0.8rem; padding: 0 0.4rem; }
        .edit-close { border: none; background: transparent; font-size: 1.3rem; padding: 0.25rem; cursor: pointer; color: var(--muted); }
        .global-message { display: none; margin: 1rem 0; padding: 0.9rem 1rem; border-radius: 0.9rem; font-size: 0.95rem; font-weight: 500; border: 1px solid transparent; }
        .global-message.show { display: block; }
        .global-message[data-type="success"] { background: rgba(22, 163, 74, 0.14); border-color: rgba(22, 163, 74, 0.4); color: #166534; }
        .global-message[data-type="error"] { background: rgba(220, 38, 38, 0.12); border-color: rgba(220, 38, 38, 0.4); color: #991b1b; }
        .global-message[data-type="info"] { background: rgba(59, 130, 246, 0.12); border-color: rgba(37, 99, 235, 0.3); color: #1d4ed8; }
        .form-result { border-radius: 0.75rem; padding: 0.5rem 0.75rem; font-size: 0.9rem; display: none; }
        .form-result.show { display: block; }
        .form-result.success { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .form-result.error { background: rgba(248, 113, 113, 0.15); color: var(--danger); }
        .action-btn { border: none; border-radius: 0.6rem; padding: 0.35rem 0.65rem; font-size: 0.85rem; cursor: pointer; transition: opacity 0.15s ease; }
        .action-btn.primary { background: rgba(37, 99, 235, 0.18); color: var(--primary); }
        .action-btn.edit { background: rgba(37, 99, 235, 0.12); color: var(--primary); }
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
        <div class="sidebar-search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="search" placeholder="Search">
        </div>
        <div class="nav-group">
            <div class="nav-title">数据中心</div>
            <button class="nav-link active" data-tab="overview">数据概览</button>
            <button class="nav-link" data-tab="users">用户管理</button>
            <button class="nav-link" data-tab="projects">项目管理</button>
            <button class="nav-link" data-tab="devices">设备管理</button>
            <button class="nav-link" data-tab="reservations">预留管理</button>
            <button class="nav-link" data-tab="checkouts">借用管理</button>
            <button class="nav-link" data-tab="transfers">设备转交</button>
            <button class="nav-link" data-tab="notifications">通知中心</button>
        </div>
    </aside>
    <div class="content" data-login-state="<?= !empty($session['uid']) ? 'authenticated' : 'guest' ?>">
        <div class="global-message" data-global-message></div>
        <header class="topbar">
            <div class="breadcrumb">
                <span>资产运营平台</span>
                <span>›</span>
                <strong id="breadcrumb-label">数据概览</strong>
            </div>
            <div class="top-actions">
                <button type="button" data-refresh-trigger>刷新数据</button>
                <button type="button" data-theme-toggle aria-label="切换主题">☀</button>
                <form method="post" action="/logout" data-ajax="true" data-logout-form="true" data-auth-visible="authenticated" style="display:none;">
                    <?= csrf_field() ?>
                    <button type="submit" class="logout-btn">退出</button>
                </form>
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
                <button class="tab-btn" data-tab="transfers">转交</button>
                <button class="tab-btn" data-tab="notifications">通知</button>
            </div>
            <section class="tab-content active" data-tab-content="overview">
                <div class="stats-grid">
                    <div class="stat-card"><h3>用户总数</h3><strong data-stat-count="users">0</strong><span>最近注册</span></div>
                    <div class="stat-card"><h3>项目总数</h3><strong data-stat-count="projects">0</strong><span>最新项目</span></div>
                    <div class="stat-card"><h3>设备数量</h3><strong data-stat-count="devices">0</strong><span>全部状态</span></div>
                    <div class="stat-card"><h3>预留记录</h3><strong data-stat-count="reservations">0</strong><span>时间窗口</span></div>
                    <div class="stat-card"><h3>借用记录</h3><strong data-stat-count="checkouts">0</strong><span>借出与归还</span></div>
                    <div class="stat-card"><h3>待转交</h3><strong data-stat-count="transfers">0</strong><span>待确认的转交</span></div>
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
                <div class="form-card edit-panel" data-edit-panel="users">
                    <header>
                        <h4>编辑用户</h4>
                        <button type="button" class="edit-close" data-edit-close>&times;</button>
                    </header>
                    <form method="post" action="/users/update" data-ajax="true" data-edit-form="users" data-reset-on-success="false">
                        <?= csrf_field() ?>
                        <label>选择用户
                            <select name="user_id" data-select="users" data-placeholder="请选择用户" required></select>
                        </label>
                        <label>姓名<input type="text" name="name" required></label>
                        <label>角色
                            <select name="role" required>
                                <option value="owner">负责人</option>
                                <option value="asset_admin">资产管理员</option>
                                <option value="planner">策划</option>
                                <option value="photographer">摄影</option>
                            </select>
                        </label>
                        <button type="submit">保存修改</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="projects">
                <div class="section-title"><h2>项目列表</h2><span class="badge" data-count-badge="projects">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>ID</th><th>名称</th><th>地点</th><th>状态</th><th>开始时间</th><th>交付时间</th><th>创建时间</th><th>操作</th></tr></thead>
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
                        <label>开始时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="starts_at" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>交付时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="due_at" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>报价金额<input type="number" step="0.01" name="quote_amount" value="0.00"></label>
                        <label>备注<textarea name="note"></textarea></label>
                        <button type="submit">提交项目</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
                <div class="form-card edit-panel" data-edit-panel="projects">
                    <header>
                        <h4>编辑项目</h4>
                        <button type="button" class="edit-close" data-edit-close>&times;</button>
                    </header>
                    <form method="post" action="/projects/update" data-ajax="true" data-edit-form="projects" data-reset-on-success="false">
                        <?= csrf_field() ?>
                        <label>选择项目
                            <select name="project_id" data-select="projects" data-placeholder="请选择项目" required></select>
                        </label>
                        <label>项目名称<input type="text" name="name" required></label>
                        <label>项目地点<input type="text" name="location" required></label>
                        <label>项目状态
                            <select name="status" required>
                                <option value="ongoing">进行中</option>
                                <option value="done">已完成</option>
                            </select>
                        </label>
                        <label>开始时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="starts_at" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>交付时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="due_at" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>报价金额<input type="number" step="0.01" name="quote_amount" required></label>
                        <label>备注<textarea name="note"></textarea></label>
                        <button type="submit">保存修改</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="devices">
                <div class="section-title"><h2>设备列表</h2><span class="badge" data-count-badge="devices">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>编号</th><th>型号</th><th>状态</th><th>当前持有人</th><th>创建时间</th><th>操作</th></tr></thead><tbody data-table-body="devices"></tbody></table>
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
                <div class="form-card edit-panel" data-edit-panel="devices">
                    <header>
                        <h4>编辑设备</h4>
                        <button type="button" class="edit-close" data-edit-close>&times;</button>
                    </header>
                    <form method="post" action="/devices/update" data-ajax="true" data-edit-form="devices" data-reset-on-success="false">
                        <?= csrf_field() ?>
                        <label>选择设备
                            <select name="device_id" data-select="devices" data-placeholder="请选择设备" required></select>
                        </label>
                        <label>设备型号<input type="text" name="model" required></label>
                        <label>设备状态
                            <select name="status" required>
                                <option value="in_stock">在库</option>
                                <option value="reserved">已预留</option>
                                <option value="checked_out">借出中</option>
                                <option value="transfer_pending">转交待确认</option>
                                <option value="lost">遗失</option>
                                <option value="repair">维修中</option>
                            </select>
                        </label>
                        <label>序列号（可选）<input type="text" name="serial"></label>
                        <label>照片地址（可选）<input type="url" name="photo_url"></label>
                        <button type="submit">保存修改</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="reservations">
                <div class="section-title"><h2>预留列表</h2><span class="badge" data-count-badge="reservations">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>项目</th><th>设备</th><th>预留开始</th><th>预留结束</th><th>创建时间</th><th>操作</th></tr></thead><tbody data-table-body="reservations"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="reservations">当前暂无预留记录。</p>
                <div class="form-card">
                    <h4>创建预留</h4>
                    <form method="post" action="/reservations/create" data-ajax="true">
                        <?= csrf_field() ?>
                        <label>项目
                            <select name="project_id" data-select="projects" data-placeholder="请选择项目" required></select>
                        </label>
                        <label>设备
                            <select name="device_id" data-select="devices" data-placeholder="请选择设备" required></select>
                        </label>
                        <label>开始时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="from" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>结束时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="to" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <button type="submit">提交预留</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
                <div class="form-card edit-panel" data-edit-panel="reservations">
                    <header>
                        <h4>编辑预留</h4>
                        <button type="button" class="edit-close" data-edit-close>&times;</button>
                    </header>
                    <form method="post" action="/reservations/update" data-ajax="true" data-edit-form="reservations" data-reset-on-success="false">
                        <?= csrf_field() ?>
                        <label>选择记录
                            <select name="reservation_id" data-select="reservations" data-placeholder="请选择预留记录" required></select>
                        </label>
                        <label>项目
                            <select name="project_id" data-select="projects" data-placeholder="请选择项目" required></select>
                        </label>
                        <label>设备
                            <select name="device_id" data-select="devices" data-placeholder="请选择设备" required></select>
                        </label>
                        <label>开始时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="from" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>结束时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="to" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <button type="submit">保存修改</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="checkouts">
                <div class="section-title"><h2>借用列表</h2><span class="badge" data-count-badge="checkouts">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>项目</th><th>设备</th><th>当前借用人</th><th>借出时间</th><th>到期时间</th><th>归还时间</th><th>状态</th><th>操作</th></tr></thead><tbody data-table-body="checkouts"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="checkouts">暂无借用记录。</p>
                <div class="form-card">
                    <h4>借出设备</h4>
                    <form method="post" action="/checkouts/create" data-ajax="true">
                        <?= csrf_field() ?>
                        <label>设备
                            <select name="device_id" data-select="devices" data-placeholder="请选择设备" required></select>
                        </label>
                        <label>借出用户
                            <select name="user_id" data-select="users" data-placeholder="请选择借用人" data-allow-empty="true" required></select>
                        </label>
                        <label>项目（可选）
                            <select name="project_id" data-select="projects" data-placeholder="关联项目" data-allow-empty="true"></select>
                        </label>
                        <label>借出时间
                            <div class="input-with-helper">
                                <input type="datetime-local" name="now" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>归还期限
                            <div class="input-with-helper">
                                <input type="datetime-local" name="due" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>借出照片（可选）<input type="url" name="photo"></label>
                        <label>备注<textarea name="note"></textarea></label>
                        <button type="submit">提交借出</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
                <div class="form-card edit-panel" data-edit-panel="checkouts">
                    <header>
                        <h4>编辑借用记录</h4>
                        <button type="button" class="edit-close" data-edit-close>&times;</button>
                    </header>
                    <form method="post" action="/checkouts/update" data-ajax="true" data-edit-form="checkouts" data-reset-on-success="false">
                        <?= csrf_field() ?>
                        <label>选择借用
                            <select name="checkout_id" data-select="checkouts" data-placeholder="请选择借用记录" required></select>
                        </label>
                        <label>借用用户
                            <select name="user_id" data-select="users" data-placeholder="请选择用户" required></select>
                        </label>
                        <label>关联项目（可选）
                            <select name="project_id" data-select="projects" data-placeholder="关联项目" data-allow-empty="true"></select>
                        </label>
                        <label>归还期限
                            <div class="input-with-helper">
                                <input type="datetime-local" name="due" required>
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>实际归还时间（可选）
                            <div class="input-with-helper">
                                <input type="datetime-local" name="return_at">
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>归还照片（可选）<input type="url" name="photo"></label>
                        <label>备注<textarea name="note"></textarea></label>
                        <button type="submit">保存修改</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="transfers">
                <div class="section-title"><h2>转交请求</h2><span class="badge" data-count-badge="transfers">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>设备</th><th>当前持有者</th><th>接收人</th><th>目标项目</th><th>目标归还时间</th><th>状态</th><th>发起时间</th><th>操作</th></tr></thead><tbody data-table-body="transfers"></tbody></table>
                </div>
                <p class="empty-placeholder" data-empty="transfers">暂无转交请求。</p>
                <div class="form-card">
                    <h4>发起转交</h4>
                    <form method="post" action="/transfers/request" data-ajax="true">
                        <?= csrf_field() ?>
                        <label>设备
                            <select name="device_id" data-select="devices" data-placeholder="请选择设备" required></select>
                        </label>
                        <label>接收用户
                            <select name="to_user_id" data-select="users" data-placeholder="请选择接收人" required></select>
                        </label>
                        <label>新项目（可选）
                            <select name="project_id" data-select="projects" data-placeholder="关联项目" data-allow-empty="true"></select>
                        </label>
                        <label>新的归还时间（可选）
                            <div class="input-with-helper">
                                <input type="datetime-local" name="due_at">
                                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
                            </div>
                        </label>
                        <label>备注（可选）<textarea name="note"></textarea></label>
                        <button type="submit">提交转交请求</button>
                        <div class="form-result" data-result></div>
                    </form>
                </div>
            </section>
            <section class="tab-content" data-tab-content="notifications">
                <div class="section-title"><h2>通知中心</h2><span class="badge" data-count-badge="notifications">共 0 条</span></div>
                <div class="data-table-wrapper">
                    <table class="data-table"><thead><tr><th>ID</th><th>用户</th><th>标题</th><th>内容</th><th>发送时间</th><th>已送达</th><th>操作</th></tr></thead><tbody data-table-body="notifications"></tbody></table>
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
<div class="edit-overlay" data-edit-overlay></div>
<div class="form-card edit-panel" data-return-panel>
    <header>
        <h4>归还设备</h4>
        <button type="button" class="edit-close" data-edit-close>&times;</button>
    </header>
    <form method="post" action="/returns/create" data-ajax="true" data-reset-on-success="false" data-return-form>
        <?= csrf_field() ?>
        <input type="hidden" name="device_id">
        <p style="margin:0; font-weight:600;" data-return-info></p>
        <label>归还时间
            <div class="input-with-helper">
                <input type="datetime-local" name="now" required>
                <button type="button" class="fill-now-btn" data-fill-now>当前时间</button>
            </div>
        </label>
        <label>归还照片（可选）<input type="url" name="photo"></label>
        <label>备注<textarea name="note"></textarea></label>
        <button type="submit">提交归还</button>
        <div class="form-result" data-result></div>
    </form>
</div>
<?php
$initialDashboard = $session['uid'] ? ($data ?? []) : [];
$initialDashboardJson = json_encode($initialDashboard, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($initialDashboardJson === false) {
    $initialDashboardJson = '{}';
}
?>
<script>
window.__DASHBOARD_DATA__ = <?= $initialDashboardJson ?>;
</script>
<script>
(() => {
    const forms = document.querySelectorAll('form[data-ajax="true"]');
    const tabs = document.querySelectorAll('.nav-link, .tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const breadcrumb = document.getElementById('breadcrumb-label');
    const editOverlay = document.querySelector('[data-edit-overlay]');
    let authState = document.querySelector('.content')?.dataset.loginState || 'guest';
    let dashboardData = {};
    const globalMessage = document.querySelector('[data-global-message]');
    let globalMessageTimer = null;
    const themeToggle = document.querySelector('[data-theme-toggle]');

    const applyTheme = (theme) => {
        const nextTheme = theme === 'light' ? 'light' : 'dark';
        if (nextTheme === 'light') {
            document.body.classList.add('theme-light');
            if (themeToggle) themeToggle.textContent = '🌙';
        } else {
            document.body.classList.remove('theme-light');
            if (themeToggle) themeToggle.textContent = '☀';
        }
        localStorage.setItem('theme', nextTheme);
    };

    applyTheme(localStorage.getItem('theme') === 'light' ? 'light' : 'dark');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const next = document.body.classList.contains('theme-light') ? 'dark' : 'light';
            applyTheme(next);
        });
    }

    const syncAuthVisibility = () => {
        document.querySelectorAll('[data-auth-visible]').forEach((block) => {
            const state = block.getAttribute('data-auth-visible');
            block.style.display = state === authState ? '' : 'none';
        });
    };

    syncAuthVisibility();

    let csrfToken = document.querySelector('form[data-ajax="true"] input[name="_token"]')?.value || '';

    const showGlobalMessage = (type, message, duration = 6000) => {
        if (!globalMessage || !message) {
            return;
        }
        if (globalMessageTimer) {
            clearTimeout(globalMessageTimer);
            globalMessageTimer = null;
        }
        globalMessage.dataset.type = type;
        globalMessage.textContent = message;
        globalMessage.classList.add('show');
        if (duration > 0) {
            globalMessageTimer = window.setTimeout(() => {
                globalMessage.classList.remove('show');
                globalMessageTimer = null;
            }, duration);
        }
    };

    const hideGlobalMessage = () => {
        if (!globalMessage) return;
        if (globalMessageTimer) {
            clearTimeout(globalMessageTimer);
            globalMessageTimer = null;
        }
        globalMessage.classList.remove('show');
    };

    const structuredMessage = (raw) => {
        try {
            const data = JSON.parse(raw);
            if (data && typeof data.message === 'string' && data.message.trim() !== '') {
                return data.message.trim();
            }
        } catch (error) {
            return null;
        }
        return null;
    };

    const parseResponse = (text, status = 200, statusText = '') => {
        const trimmed = text.trim();
        const fallback = status >= 400
            ? `请求失败（HTTP ${status}${statusText ? ` ${statusText}` : ''}）`
            : '服务器未返回任何信息。';

        if (!trimmed) {
            return { type: 'error', message: fallback };
        }

        if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
            const structured = structuredMessage(trimmed);
            if (structured) {
                return { type: status >= 400 ? 'error' : 'info', message: structured };
            }
        }

        const stripTags = (value) => value.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        const normalized = stripTags(trimmed);

        if (normalized) {
            const okMatch = normalized.match(/(?:^|\s)OK\b[:：]?\s*(.*)$/i);
            if (okMatch) {
                const rest = (okMatch[1] ?? '').trim();
                return { type: 'success', message: rest !== '' ? rest : '操作成功' };
            }

            const errorMatch = normalized.match(/(?:^|\s)ERROR\b[:：]?\s*(.*)$/i);
            if (errorMatch) {
                const rest = (errorMatch[1] ?? '').trim();
                return { type: 'error', message: rest !== '' ? rest : '操作失败' };
            }
        }

        if (status >= 400) {
            return { type: 'error', message: normalized || trimmed };
        }

        return { type: 'info', message: normalized || trimmed };
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

        const pad = (num) => String(num).padStart(2, '0');
        const formatDate = (value) => {
            if (!value) return '-';
            const original = String(value).trim();
            if (original === '') return '-';
            if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(original)) {
                return original.slice(0, 16);
            }
            if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(original)) {
                return original.replace('T', ' ').slice(0, 16);
            }
            const parsed = new Date(original);
            if (Number.isNaN(parsed.getTime())) {
                return original;
            }
            return `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())} ${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`;
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
                    <td>
                        <button type="button" class="action-btn edit" data-edit-trigger="users" data-record-id="${row.id ?? ''}">编辑</button>
                        <button type="button" class="action-btn delete" data-delete-record="users" data-record-id="${row.id ?? ''}">删除</button>
                    </td>
                </tr>
            `,
            transfers: (row) => {
                const statusMap = { pending: '待确认', accepted: '已完成', rejected: '已拒绝', cancelled: '已取消' };
                return `
                    <tr>
                        <td>${row.id ?? '-'}</td>
                        <td>#${row.device_id ?? '-'}</td>
                        <td>${row.from_user_id ? `#${row.from_user_id} ${row.from_user_name ?? ''}` : '-'}</td>
                        <td>${row.to_user_id ? `#${row.to_user_id} ${row.to_user_name ?? ''}` : '-'}</td>
                        <td>${row.target_project_id ? '#' + row.target_project_id : '-'}</td>
                        <td>${formatDate(row.target_due_at ?? null)}</td>
                        <td>${statusMap[row.status ?? ''] ?? (row.status ?? '-')}</td>
                        <td>${formatDate(row.requested_at ?? null)}</td>
                        <td>
                            ${row.status === 'pending'
                                ? `<button type="button" class="action-btn primary" data-confirm-transfer="${row.id ?? ''}">确认</button>
                                   <button type="button" class="action-btn delete" data-delete-record="transfers" data-record-id="${row.id ?? ''}">取消</button>`
                                : `<button type="button" class="action-btn delete" data-delete-record="transfers" data-record-id="${row.id ?? ''}">删除</button>`}
                        </td>
                    </tr>
                `;
            },
            projects: (row) => `
                <tr>
                    <td>${row.id ?? '-'}</td>
                    <td>${row.name ?? '-'}</td>
                    <td>${row.location ?? '-'}</td>
                    <td>${statusChip(row.status ?? null)}</td>
                    <td>${formatDate(row.starts_at ?? null)}</td>
                    <td>${formatDate(row.due_at ?? null)}</td>
                    <td>${formatDate(row.created_at ?? null)}</td>
                    <td>
                        <button type="button" class="action-btn edit" data-edit-trigger="projects" data-record-id="${row.id ?? ''}">编辑</button>
                        <button type="button" class="action-btn delete" data-delete-record="projects" data-record-id="${row.id ?? ''}">删除</button>
                    </td>
                </tr>
            `,
            devices: (row) => `
                <tr>
                    <td>${row.id ?? '-'}</td>
                    <td>${row.code ?? '-'}</td>
                    <td>${row.model ?? '-'}</td>
                    <td>${statusChip(row.status ?? null, 'device')}</td>
                    <td>${(() => {
                        const status = row.status ?? '';
                        const showHolder = status === 'checked_out' || status === 'transfer_pending';
                        if (!showHolder) {
                            return '—';
                        }
                        if (!row.holder_name) {
                            return '待确认';
                        }
                        return `${row.holder_name}${row.holder_email ? ` (${row.holder_email})` : ''}`;
                    })()}</td>
                    <td>${formatDate(row.created_at ?? null)}</td>
                    <td>
                        <button type="button" class="action-btn edit" data-edit-trigger="devices" data-record-id="${row.id ?? ''}">编辑</button>
                        <button type="button" class="action-btn delete" data-delete-record="devices" data-record-id="${row.id ?? ''}">删除</button>
                    </td>
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
                    <td>
                        <button type="button" class="action-btn edit" data-edit-trigger="reservations" data-record-id="${row.id ?? ''}">编辑</button>
                        <button type="button" class="action-btn delete" data-delete-record="reservations" data-record-id="${row.id ?? ''}">删除</button>
                    </td>
                </tr>
            `,
            checkouts: (row) => {
                const now = Date.now();
                const checkedOutAt = new Date((row.checked_out_at ?? '').replace(' ', 'T'));
                const dueAt = new Date((row.due_at ?? '').replace(' ', 'T'));
                const hasReturned = Boolean(row.return_at);
                let label = '借出中';
                let chip = 'warning';

                if (hasReturned) {
                    label = '已归还';
                    chip = 'success';
                } else if (checkedOutAt instanceof Date && dueAt instanceof Date) {
                    if (checkedOutAt.getTime() > now) {
                        label = '待生效';
                        chip = '';
                    } else if (dueAt.getTime() < now) {
                        label = '已超期';
                        chip = 'danger';
                    }
                }

                return `
                    <tr>
                        <td>${row.id ?? '-'}</td>
                        <td>${row.project_name ?? ('#' + (row.project_id ?? '-'))}</td>
                        <td>${row.device_code ?? ('#' + (row.device_id ?? '-'))}</td>
                        <td>${row.user_id ? 
                            (row.user_id && row.user_name ? `#${row.user_id} ${row.user_name}` : '#' + row.user_id) : '-'}</td>
                        <td>${formatDate(row.checked_out_at ?? null)}</td>
                        <td>${formatDate(row.due_at ?? null)}</td>
                        <td>${formatDate(row.return_at ?? null)}</td>
                        <td><span class="status-chip ${chip}">${label}</span></td>
                        <td>
                            ${!row.return_at ? `<button type="button" class="action-btn primary" data-return-checkout="${row.id ?? ''}" data-return-device="${row.device_code ?? ('#' + (row.device_id ?? '-'))}" data-device-id="${row.device_id ?? ''}">归还</button>` : ''}
                            <button type="button" class="action-btn edit" data-edit-trigger="checkouts" data-record-id="${row.id ?? ''}">编辑</button>
                            <button type="button" class="action-btn delete" data-delete-record="checkouts" data-record-id="${row.id ?? ''}">删除</button>
                        </td>
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
                    <td><button type="button" class="action-btn delete" data-delete-record="notifications" data-record-id="${row.id ?? ''}">删除</button></td>
                </tr>
            `,
        };

        body.innerHTML = rows.map((row) => (builders[key] ?? (() => ''))(row)).join('');
    };

    const selectBuilders = {
        users: (item) => ({ value: item.id, label: `#${item.id} ${item.name ?? ''} (${item.email ?? ''})` }),
        projects: (item) => ({ value: item.id, label: `#${item.id} ${item.name ?? ''}` }),
        devices: (item) => ({ value: item.id, label: `#${item.id} ${item.code ?? ''}${item.model ? ' · ' + item.model : ''}`, status: item.status ?? '' }),
        reservations: (item) => ({
            value: item.id,
            label: `#${item.id} ${item.device_code ?? ('设备#' + (item.device_id ?? '-'))} · ${item.project_name ?? ('项目#' + (item.project_id ?? '-'))}`,
        }),
            checkouts: (item) => ({
            value: item.id,
            label: `#${item.id} ${item.device_code ?? ('设备#' + (item.device_id ?? '-'))} → #${item.user_id ?? '-'}${item.user_name ? ' ' + item.user_name : ''}`,
            status: item.return_at ? 'closed' : 'open',
        }),
        transfers: (item) => ({
            value: item.id,
            label: `#${item.id} 设备#${item.device_id} → #${item.to_user_id} ${item.to_user_name ?? ''}`,
            status: item.status ?? '',
        }),
    };

    const toLocalDateTimeValue = (value) => {
        if (!value) {
            return '';
        }
        const text = String(value).trim();
        if (text.includes('T')) {
            return text.slice(0, 16);
        }
        if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(text)) {
            return text.replace(' ', 'T').slice(0, 16);
        }
        return text;
    };

    const currentLocalDateTime = () => {
        const now = new Date();
        now.setSeconds(0, 0);
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}T${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    };

    const setSelectValue = (select, value) => {
        if (!select) {
            return;
        }
        const target = value === null || value === undefined ? '' : String(value);
        const options = Array.from(select.options);
        if (target !== '' && options.some((opt) => opt.value === target)) {
            select.value = target;
        } else if (target === '' && options.some((opt) => opt.value === '')) {
            select.value = '';
        } else if (select.dataset.allowEmpty === 'true') {
            select.value = '';
        } else if (options.length > 0) {
            select.value = options[0].value;
        } else {
            select.value = '';
        }
    };

    const setFieldValue = (form, name, value) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) {
            return;
        }
        if (field.tagName === 'SELECT') {
            setSelectValue(field, value);
        } else {
            field.value = value ?? '';
        }
    };

    const editForms = {};
    const editPanels = {};
    const returnPanel = document.querySelector('[data-return-panel]');
    const returnForm = returnPanel?.querySelector('form[data-return-form]') || null;
    const returnInfo = returnPanel?.querySelector('[data-return-info]') || null;

    const migratePanelToBody = (node) => {
        if (node && node.parentElement !== document.body) {
            document.body.appendChild(node);
        }
    };

    migratePanelToBody(editOverlay);
    migratePanelToBody(returnPanel);
    document.querySelectorAll('[data-edit-panel]').forEach((panel) => migratePanelToBody(panel));
    let activeEditKey = null;
    const editConfigs = {
        users: {
            dataset: 'users',
            selectName: 'user_id',
            fill: (item, form) => {
                setFieldValue(form, 'name', item?.name ?? '');
                setFieldValue(form, 'role', item?.role ?? 'owner');
            },
        },
        projects: {
            dataset: 'projects',
            selectName: 'project_id',
            fill: (item, form) => {
                setFieldValue(form, 'name', item?.name ?? '');
                setFieldValue(form, 'location', item?.location ?? '');
                setFieldValue(form, 'status', item?.status ?? 'ongoing');
                setFieldValue(form, 'starts_at', toLocalDateTimeValue(item?.starts_at ?? ''));
                setFieldValue(form, 'due_at', toLocalDateTimeValue(item?.due_at ?? ''));
                const quote = item?.quote_amount;
                setFieldValue(form, 'quote_amount', quote === null || quote === undefined ? '' : String(quote));
                setFieldValue(form, 'note', item?.note ?? '');
            },
        },
        devices: {
            dataset: 'devices',
            selectName: 'device_id',
            fill: (item, form) => {
                setFieldValue(form, 'model', item?.model ?? '');
                setFieldValue(form, 'status', item?.status ?? 'in_stock');
                setFieldValue(form, 'serial', item?.serial ?? '');
                setFieldValue(form, 'photo_url', item?.photo_url ?? '');
            },
        },
        reservations: {
            dataset: 'reservations',
            selectName: 'reservation_id',
            fill: (item, form) => {
                setFieldValue(form, 'project_id', item?.project_id ?? '');
                setFieldValue(form, 'device_id', item?.device_id ?? '');
                setFieldValue(form, 'from', toLocalDateTimeValue(item?.reserved_from ?? ''));
                setFieldValue(form, 'to', toLocalDateTimeValue(item?.reserved_to ?? ''));
            },
        },
        checkouts: {
            dataset: 'checkouts',
            selectName: 'checkout_id',
            fill: (item, form) => {
                setFieldValue(form, 'user_id', item?.user_id ?? '');
                setFieldValue(form, 'project_id', item?.project_id ?? '');
                setFieldValue(form, 'due', toLocalDateTimeValue(item?.due_at ?? ''));
                setFieldValue(form, 'note', item?.note ?? '');
            },
        },
    };

    const closeEditPanels = () => {
        activeEditKey = null;
        Object.values(editPanels).forEach((panel) => panel.classList.remove('show'));
        if (returnPanel) {
            returnPanel.classList.remove('show');
        }
        if (editOverlay) {
            editOverlay.classList.remove('show');
        }
    };

    const openEditPanel = (key) => {
        const panel = editPanels[key];
        if (!panel) {
            showGlobalMessage('error', '没有可用的编辑窗格');
            return;
        }
        activeEditKey = key;
        if (editOverlay) {
            editOverlay.classList.add('show');
        }
        panel.classList.add('show');
    };

    const openReturnPanel = () => {
        if (!returnPanel) {
            showGlobalMessage('error', '无法打开归还窗口');
            return;
        }
        if (editOverlay) {
            editOverlay.classList.add('show');
        }
        returnPanel.classList.add('show');
    };

    const deleteConfigs = {
        users: { url: '/users/delete', idField: 'user_id', confirm: '确认删除该用户？' },
        projects: { url: '/projects/delete', idField: 'project_id', confirm: '确认删除该项目？相关记录可能会被清理。' },
        devices: { url: '/devices/delete', idField: 'device_id', confirm: '确认删除该设备？' },
        reservations: { url: '/reservations/delete', idField: 'reservation_id', confirm: '确认删除该预留记录？' },
        checkouts: { url: '/checkouts/delete', idField: 'checkout_id', confirm: '确认删除该借用记录？' },
        transfers: { url: '/transfers/cancel', idField: 'transfer_id', confirm: '确认取消该转交请求？' },
        notifications: { url: '/notifications/delete', idField: 'notification_id', confirm: '确认删除该通知？' },
    };

    const syncEditForm = (key) => {
        const config = editConfigs[key];
        const form = editForms[key];
        if (!config || !form) {
            return;
        }

        const select = form.querySelector(`[name="${config.selectName}"]`);
        if (!select) {
            return;
        }

        const dataset = dashboardData[config.dataset] ?? [];
        const hasData = dataset.length > 0;
        const idField = config.idField ?? 'id';
        const matchesSelection = (item, value) => String(item?.[idField] ?? '') === String(value ?? '');

        form.querySelectorAll('input, select, textarea, button').forEach((field) => {
            if (field.name === '_token') {
                return;
            }
            if (field.matches(`[name="${config.selectName}"]`)) {
                field.disabled = !hasData;
            } else if (field.tagName === 'BUTTON') {
                field.disabled = !hasData;
            } else {
                field.disabled = !hasData;
            }
        });

        if (!hasData) {
            config.fill(null, form);
            if (select.options.length > 0) {
                select.selectedIndex = 0;
            } else {
                select.value = '';
            }
            return;
        }

        if (select.value && !dataset.some((item) => matchesSelection(item, select.value))) {
            select.value = '';
        }

        if (!select.value && select.options.length > 0) {
            select.value = select.options[0].value;
        }

        const current = dataset.find((item) => matchesSelection(item, select.value)) ?? null;
        config.fill(current, form);
    };

    const syncEditForms = () => {
        Object.keys(editForms).forEach((key) => syncEditForm(key));
    };

    document.querySelectorAll('[data-edit-form]').forEach((form) => {
        const key = form.dataset.editForm;
        if (!key || !editConfigs[key]) {
            return;
        }
        editForms[key] = form;
        const panel = form.closest('[data-edit-panel]');
        if (panel) {
            editPanels[key] = panel;
        }
        const select = form.querySelector(`[name="${editConfigs[key].selectName}"]`);
        if (select) {
            select.addEventListener('change', () => syncEditForm(key));
        }
        syncEditForm(key);
    });

    const populateSelects = (data) => {
        document.querySelectorAll('select[data-select]').forEach((select) => {
            const key = select.dataset.select;
            const builder = selectBuilders[key];
            if (!builder) {
                return;
            }
            const records = data[key] ?? [];
            const filterStatus = select.dataset.selectFilter ? select.dataset.selectFilter.toLowerCase() : null;
            const selectStatus = select.dataset.selectStatus
                ? select.dataset.selectStatus.split(',').map((s) => s.trim().toLowerCase()).filter(Boolean)
                : null;
            const allowEmpty = select.dataset.allowEmpty === 'true';
            const placeholder = select.dataset.placeholder || '请选择';
            const previous = select.value;

            select.innerHTML = '';

            if (allowEmpty) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholder;
                select.appendChild(opt);
            }

            records.forEach((item) => {
                const built = builder(item);
                if (!built) {
                    return;
                }
                const status = (built.status ?? item.status ?? '').toLowerCase();
                if (filterStatus && status !== filterStatus) {
                    return;
                }
                if (selectStatus && !selectStatus.includes(status)) {
                    return;
                }
                const opt = document.createElement('option');
                opt.value = String(built.value ?? item.id ?? '');
                opt.textContent = built.label ?? String(built.value ?? item.id ?? '');
                select.appendChild(opt);
            });

            if (previous && Array.from(select.options).some((opt) => opt.value === previous)) {
                select.value = previous;
            } else if (!allowEmpty && select.options.length > 0) {
                select.selectedIndex = 0;
            }
        });
    };

    const initialDashboardData = window.__DASHBOARD_DATA__ || null;

    const applyDashboardData = (data) => {
        if (!data || typeof data !== 'object') {
            return;
        }
        dashboardData = data;
        renderTable('users', data.users ?? []);
        renderTable('projects', data.projects ?? []);
        renderTable('devices', data.devices ?? []);
        renderTable('reservations', data.reservations ?? []);
        renderTable('checkouts', data.checkouts ?? []);
        renderTable('transfers', data.transfers ?? []);
        renderTable('notifications', data.notifications ?? []);
        populateSelects(data);
        syncEditForms();
    };

    const loadDashboardData = async () => {
        try {
            const res = await fetch('/dashboard/data', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                const text = await res.text();
                const { message } = parseResponse(text, res.status, res.statusText);
                showGlobalMessage('error', message || '数据加载失败');
                return;
            }
            const payload = await res.json();
            if (!payload.success) {
                showGlobalMessage('error', payload.message ?? '数据加载失败');
                return;
            }
            applyDashboardData(payload.data ?? {});
        } catch (error) {
            console.error('加载数据失败', error);
            showGlobalMessage('error', error instanceof Error ? error.message : '数据加载失败');
        }
    };

    if (authState === 'authenticated' && initialDashboardData && Object.keys(initialDashboardData).length) {
        applyDashboardData(initialDashboardData);
    }

    const refreshStatus = async () => {
        try {
            const res = await fetch(window.location.href, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin',
            });
            const html = await res.text();
            if (!res.ok) {
                const { message } = parseResponse(html, res.status, res.statusText);
                showGlobalMessage('error', message || '页面刷新失败');
                return;
            }
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
            showGlobalMessage('error', error instanceof Error ? error.message : '页面刷新失败');
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
                    transfers: '设备转交',
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
            form.querySelectorAll('input[type="datetime-local"]').forEach((input) => {
                if (!input.name) return;
                const raw = input.value;
                if (!raw) {
                    formData.delete(input.name);
                    return;
                }
                formData.set(input.name, raw.replace('T', ' '));
            });
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
                const { type, message } = parseResponse(text, response.status, response.statusText);
                if (resultBox) {
                    resultBox.className = `form-result show ${type}`;
                    resultBox.textContent = message;
                }
                if (type === 'error') {
                    showGlobalMessage('error', message || '操作失败');
                } else if (type === 'success') {
                    showGlobalMessage('success', message || '操作成功');
                } else if (type === 'info' && message) {
                    showGlobalMessage('info', message);
                }
                if (type === 'success') {
                    if (form.dataset.logoutForm !== undefined) {
                        window.location.href = '/';
                        return;
                    }
                    await refreshStatus();
                    if (form.dataset.editForm || form === returnForm) {
                        closeEditPanels();
                    }
                    if (form.dataset.resetOnSuccess !== 'false') {
                        form.reset();
                    }
                }
            } catch (error) {
                if (resultBox) {
                    resultBox.className = 'form-result show error';
                    resultBox.textContent = `请求失败：${error instanceof Error ? error.message : '未知错误'}`;
                }
                showGlobalMessage('error', error instanceof Error ? error.message : '请求失败');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.dataset.originalText || '提交';
                }
            }
        });
    });

    document.addEventListener('click', async (event) => {
        if (event.target === editOverlay) {
            closeEditPanels();
            return;
        }

        const closeBtn = event.target.closest('[data-edit-close]');
        if (closeBtn) {
            event.preventDefault();
            closeEditPanels();
            return;
        }
        const refreshBtn = event.target.closest('[data-refresh-trigger]');
        if (refreshBtn) {
            event.preventDefault();
            if (refreshBtn.disabled) {
                return;
            }
            refreshBtn.disabled = true;
            refreshBtn.dataset.originalText = refreshBtn.dataset.originalText || refreshBtn.textContent;
            refreshBtn.textContent = '刷新中...';
            (window.dashboardRefresh ? window.dashboardRefresh(true) : Promise.resolve())
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = refreshBtn.dataset.originalText || '刷新数据';
                });
            return;
        }

        const fillBtn = event.target.closest('[data-fill-now]');
        if (fillBtn) {
            event.preventDefault();
            const container = fillBtn.closest('.input-with-helper') || fillBtn.closest('label');
            const input = container?.querySelector('input[type="datetime-local"]');
            if (input) {
                input.value = currentLocalDateTime();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return;
        }

        const deleteBtn = event.target.closest('[data-delete-record]');
        if (deleteBtn) {
            const dataset = deleteBtn.getAttribute('data-delete-record');
            const recordId = deleteBtn.getAttribute('data-record-id');
            const config = dataset ? deleteConfigs[dataset] : null;
            if (!config || !recordId) {
                showGlobalMessage('error', '缺少删除参数');
                return;
            }
            const confirmed = window.confirm(config.confirm ?? '确认删除该记录？');
            if (!confirmed) {
                return;
            }
            try {
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append(config.idField, recordId);
                const res = await fetch(config.url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const text = await res.text();
                const { type, message } = parseResponse(text, res.status, res.statusText);
                showGlobalMessage(type === 'success' ? 'success' : 'error', message || (type === 'success' ? '删除成功' : '删除失败'));
                if (type === 'success') {
                    await refreshStatus();
                }
            } catch (error) {
                showGlobalMessage('error', error instanceof Error ? error.message : '删除失败');
            }
            return;
        }

        const editBtn = event.target.closest('[data-edit-trigger]');
        if (editBtn) {
            event.preventDefault();
            const key = editBtn.getAttribute('data-edit-trigger');
            const recordId = editBtn.getAttribute('data-record-id') ?? '';
            if (!key || !editConfigs[key]) {
                return;
            }

            const tabButton = document.querySelector(`.tab-btn[data-tab="${key}"]`);
            if (tabButton) {
                tabButton.click();
            }

            const form = editForms[key];
            if (!form) {
                return;
            }

            const select = form.querySelector(`[name="${editConfigs[key].selectName}"]`);
            if (select) {
                if (recordId) {
                    const hasOption = Array.from(select.options).some((opt) => opt.value === recordId);
                    if (hasOption) {
                        select.value = recordId;
                    }
                }
                if (!select.options.length) {
                    showGlobalMessage('info', '暂无可编辑的记录');
                    return;
                }
            }

            syncEditForm(key);
            openEditPanel(key);
        }

        if (event.target.closest('[data-refresh-trigger]')) {
            event.preventDefault();
            window.dashboardRefresh && window.dashboardRefresh(true);
            return;
        }

        const confirmBtn = event.target.closest('[data-confirm-transfer]');
        if (confirmBtn) {
            event.preventDefault();
            const transferId = confirmBtn.getAttribute('data-confirm-transfer');
            if (!transferId) {
                showGlobalMessage('error', '缺少转交编号');
                return;
            }
            try {
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('transfer_id', transferId);
                const res = await fetch('/transfers/confirm', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const text = await res.text();
                const { type, message } = parseResponse(text, res.status, res.statusText);
                showGlobalMessage(type === 'success' ? 'success' : 'error', message || (type === 'success' ? '操作成功' : '操作失败'));
                if (type === 'success') {
                    await refreshStatus();
                }
            } catch (error) {
                showGlobalMessage('error', error instanceof Error ? error.message : '确认转交失败');
            }
            return;
        }

        const returnBtn = event.target.closest('[data-return-checkout]');
        if (returnBtn) {
            event.preventDefault();
            if (!returnForm || !returnPanel) {
                showGlobalMessage('error', '无法打开归还表单');
                return;
            }
            const deviceLabel = returnBtn.getAttribute('data-return-device') || '—';
            const recordId = returnBtn.getAttribute('data-return-checkout');
            const dataset = dashboardData.checkouts || [];
            const checkout = dataset.find((item) => String(item.id ?? '') === String(recordId ?? '')) || null;
            const deviceId = checkout?.device_id ?? returnBtn.getAttribute('data-device-id') ?? '';
            const deviceInput = returnForm.querySelector('input[name="device_id"]');
            const dateInput = returnForm.querySelector('input[name="now"]');
            if (deviceInput) {
                deviceInput.value = String(deviceId);
            }
            if (returnInfo) {
                const userLabel = checkout?.user_name ? ` · ${checkout.user_name}` : '';
                returnInfo.textContent = `${deviceLabel}${userLabel}`;
            }
            if (dateInput) {
                dateInput.value = currentLocalDateTime();
            }
            openReturnPanel();
            return;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeEditPanels();
        }
    });

    window.dashboardRefresh = (showToast = false) => {
        return refreshStatus().then(() => {
            if (showToast) {
                showGlobalMessage('info', '数据已刷新');
            }
        });
    };
    if (authState === 'authenticated') {
        loadDashboardData();
    }
})();
</script>
</body>
</html>
