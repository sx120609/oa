<?php
/** @var array $session */
/** @var array $data */
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>资产管理控制台</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 2rem; background: #f3f4f6; color: #111827; }
        main { max-width: 1160px; margin: 0 auto; display: grid; gap: 1.5rem; }
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
        .dashboard-head { display: flex; flex-direction: column; gap: 1rem; }
        .dashboard-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
        .refresh-btn { background: #0ea5e9; padding: 0.6rem 1.1rem; border-radius: 8px; font-weight: 600; border: none; color: #fff; cursor: pointer; transition: background 0.2s; }
        .refresh-btn:hover { background: #0284c7; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
        .data-table th, .data-table td { padding: 0.55rem 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        .data-table th { background: #f1f5f9; font-weight: 700; color: #334155; }
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; background: rgba(148, 163, 184, 0.16); color: #475569; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; }
        .status-chip { display: inline-flex; align-items: center; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: rgba(59, 130, 246, 0.12); color: #1d4ed8; }
        .status-chip.success { background: rgba(34, 197, 94, 0.15); color: #15803d; }
        .status-chip.warning { background: rgba(250, 204, 21, 0.18); color: #b45309; }
        .status-chip.danger { background: rgba(248, 113, 113, 0.2); color: #b91c1c; }
        .empty-placeholder { font-size: 0.9rem; color: #64748b; padding: 0.5rem 0; }
    </style>
</head>
<body>
    <main>
        <section class="dashboard-head">
            <h1>资产管理控制台</h1>
            <div class="dashboard-actions">
                <button type="button" class="refresh-btn" onclick="window.dashboardRefresh && window.dashboardRefresh(true)">刷新数据</button>
                <span class="form-hint">刷新后可同步最新项目、设备、预约与借用状态。</span>
            </div>
            <p class="status" data-current-status>
                <strong>当前状态：</strong>
                <?php if (!empty($session['uid'])): ?>
                    已登录，账号 <?= escape($session['email'] ?? ('UID ' . $session['uid'])) ?>（角色 <?= escape($session['role'] ?? '未知') ?>）
                <?php else: ?>
                    未登录
                <?php endif; ?>
            </p>
        </section>

        <section data-dataset="projects">
            <div class="section-title">
                <h2>项目概览</h2>
                <span class="badge">近期记录：<?= escape((string) count($data['projects'])) ?> 条</span>
            </div>
            <?php if (!empty($data['projects'])): ?>
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
                        <?php foreach ($data['projects'] as $project): ?>
                            <tr>
                                <td><?= escape((string) $project['id']) ?></td>
                                <td><?= escape($project['name'] ?? '-') ?></td>
                                <td><?= escape($project['location'] ?? '-') ?></td>
                                <td>
                                    <?php
                                        $status = $project['status'] ?? '';
                                        $label = $projectStatusMap[$status] ?? ($status ?: '-');
                                    ?>
                                    <span class="status-chip"><?= escape($label) ?></span>
                                </td>
                                <td><?= escape($formatDatetime($project['starts_at'] ?? null)) ?></td>
                                <td><?= escape($formatDatetime($project['due_at'] ?? null)) ?></td>
                                <td><?= escape($formatDatetime($project['created_at'] ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-placeholder">暂无项目记录。</p>
            <?php endif; ?>
        </section>

        <section data-dataset="devices">
            <div class="section-title">
                <h2>设备概览</h2>
                <span class="badge">近期记录：<?= escape((string) count($data['devices'])) ?> 条</span>
            </div>
            <?php if (!empty($data['devices'])): ?>
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
                        <?php foreach ($data['devices'] as $device): ?>
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
            <?php else: ?>
                <p class="empty-placeholder">暂无设备记录。</p>
            <?php endif; ?>
        </section>

        <section data-dataset="reservations">
            <div class="section-title">
                <h2>预留记录</h2>
                <span class="badge">近期记录：<?= escape((string) count($data['reservations'])) ?> 条</span>
            </div>
            <?php if (!empty($data['reservations'])): ?>
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
                        <?php foreach ($data['reservations'] as $reservation): ?>
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
            <?php else: ?>
                <p class="empty-placeholder">暂无预留记录。</p>
            <?php endif; ?>
        </section>

        <section data-dataset="checkouts">
            <div class="section-title">
                <h2>借用记录</h2>
                <span class="badge">近期记录：<?= escape((string) count($data['checkouts'])) ?> 条</span>
            </div>
            <?php if (!empty($data['checkouts'])): ?>
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
                        <?php foreach ($data['checkouts'] as $checkout): ?>
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
            <?php else: ?>
                <p class="empty-placeholder">暂无借用记录。</p>
            <?php endif; ?>
        </section>

        <section data-dataset="notifications">
            <div class="section-title">
                <h2>通知记录</h2>
                <span class="badge">近期记录：<?= escape((string) count($data['notifications'])) ?> 条</span>
            </div>
            <?php if (!empty($data['notifications'])): ?>
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
                        <?php foreach ($data['notifications'] as $notification): ?>
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
            <?php else: ?>
                <p class="empty-placeholder">暂无通知记录。</p>
            <?php endif; ?>
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
                            const selector = `[data-dataset="${key}"]`;
                            const sectionCurrent = document.querySelector(selector);
                            if (sectionCurrent) {
                                sectionCurrent.innerHTML = sectionNew.innerHTML;
                            }
                        });
                    }
                } catch (error) {
                    console.warn('刷新页面状态失败', error);
                }
            };

            const sectionsSelector = '[data-dataset]';

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

            window.dashboardRefresh = refreshStatus;
        })();
    </script>
</body>
</html>
