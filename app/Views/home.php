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
        main { max-width: 1080px; margin: 0 auto; display: grid; gap: 1.5rem; }
        section { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12); padding: 1.75rem; }
        h1 { margin: 0 0 1rem 0; font-size: 2rem; }
        h2 { margin: 0 0 1rem 0; font-size: 1.25rem; }
        form { display: grid; gap: 0.8rem; }
        label { display: grid; gap: 0.35rem; font-weight: 600; font-size: 0.95rem; }
        input, textarea, select { padding: 0.55rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; background: #f9fafb; }
        textarea { resize: vertical; min-height: 96px; }
        button { background: #2563eb; border: none; color: #fff; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; cursor: pointer; transition: background 0.15s ease; }
        button:hover { background: #1d4ed8; }
        button[disabled] { background: #94a3b8; cursor: wait; }
        .status { background: rgba(37, 99, 235, 0.1); color: #1d4ed8; border-radius: 8px; padding: 0.5rem 0.75rem; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .form-result { border-radius: 8px; padding: 0.55rem 0.75rem; font-size: 0.9rem; line-height: 1.4; display: none; }
        .form-result.show { display: block; }
        .form-result.success { background: rgba(34, 197, 94, 0.12); color: #166534; border: 1px solid rgba(34, 197, 94, 0.25); }
        .form-result.error { background: rgba(239, 68, 68, 0.12); color: #991b1b; border: 1px solid rgba(239, 68, 68, 0.2); }
        .form-result.info { background: rgba(148, 163, 184, 0.14); color: #334155; border: 1px solid rgba(148, 163, 184, 0.3); }
        .form-hint { font-size: 0.85rem; color: #64748b; line-height: 1.6; }
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

        <section data-module="login">
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
                <div class="form-result" data-result></div>
            </form>
        </section>

        <section data-module="project">
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
                <div class="form-result" data-result></div>
            </form>
        </section>

        <section data-module="device">
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
                <div class="form-result" data-result></div>
            </form>
        </section>

        <section data-module="reservation">
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
                <div class="form-result" data-result></div>
            </form>
        </section>

        <section data-module="checkout">
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
                <div class="form-result" data-result></div>
            </form>
        </section>

        <section data-module="return">
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
                <div class="form-result" data-result></div>
            </form>
        </section>
    </main>
    <script>
        (() => {
            const forms = document.querySelectorAll('form');

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
                    const tokenInputs = doc.querySelectorAll('input[name="_token"]');
                    const formsNew = doc.querySelectorAll('form');
                    formsNew.forEach((newForm, index) => {
                        const currentForm = forms[index];
                        if (!currentForm) {
                            return;
                        }
                        const newToken = newForm.querySelector('input[name="_token"]');
                        const currentToken = currentForm.querySelector('input[name="_token"]');
                        if (newToken && currentToken) {
                            currentToken.value = newToken.value;
                        }
                    });
                    const statusNew = doc.querySelector('[data-module="login"] .status');
                    const statusCurrent = document.querySelector('[data-module="login"] .status');
                    if (statusNew && statusCurrent) {
                        statusCurrent.innerHTML = statusNew.innerHTML;
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
        })();
    </script>
</body>
</html>
