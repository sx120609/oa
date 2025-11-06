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
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 2rem; background: #f3f4f6; color: #111827; }
        main { max-width: 980px; margin: 0 auto; display: grid; gap: 1.5rem; }
        section { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12); padding: 1.75rem; }
        h1 { margin: 0 0 1rem 0; font-size: 2rem; }
        h2 { margin: 0 0 1rem 0; font-size: 1.3rem; }
        form { display: grid; gap: 0.8rem; }
        label { display: grid; gap: 0.35rem; font-weight: 600; }
        input, textarea, select { padding: 0.55rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; }
        textarea { resize: vertical; min-height: 96px; }
        button { background: #2563eb; border: none; color: #fff; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .grid-2 { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .status { background: rgba(37, 99, 235, 0.1); color: #1d4ed8; border-radius: 8px; padding: 0.5rem 0.75rem; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        pre { background: #0f172a; color: #f8fafc; padding: 0.75rem; border-radius: 8px; overflow: auto; }
    </style>
</head>
<body>
    <main>
        <section>
            <h1>资产管理控制台</h1>
            <p class="status">
                <strong>当前状态：</strong>
                <?php if (!empty($session['uid'])): ?>
                    已登录，账号 <?= escape($session['email'] ?? ('UID ' . $session['uid'])) ?>（角色 <?= escape($session['role'] ?? '未知') ?>）
                <?php else: ?>
                    未登录
                <?php endif; ?>
            </p>
        </section>

        <section>
            <h2>登录</h2>
            <form method="post" action="/login">
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
            </form>
        </section>

        <section>
            <h2>创建项目</h2>
            <form method="post" action="/projects/create">
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
            </form>
        </section>

        <section>
            <h2>创建设备</h2>
            <form method="post" action="/devices/create">
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
            </form>
        </section>

        <section>
            <h2>设备预留</h2>
            <form method="post" action="/reservations/create">
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
            </form>
        </section>

        <section>
            <h2>设备借用</h2>
            <form method="post" action="/checkouts/create">
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
            </form>
        </section>

        <section>
            <h2>设备归还</h2>
            <form method="post" action="/returns/create">
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
            </form>
        </section>
    </main>
</body>
</html>
