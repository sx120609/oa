class ApiError extends Error {
    constructor(message, status = 500, code = 'error', details = null) {
        super(message);
        this.status = status;
        this.code = code;
        this.details = details;
    }
}

const state = {
    baseUrl: '',
    apiKey: 'devkey',
    userId: '1',
};

const routes = {
    assets: renderAssets,
    repairs: renderRepairs,
    reports: renderReports,
};

const alertsContainer = document.getElementById('alerts');
const content = document.getElementById('content');
const navLinks = Array.from(document.querySelectorAll('.nav-link'));

function init() {
    const configForm = document.getElementById('config-form');
    configForm.baseUrl.value = state.baseUrl;
    configForm.apiKey.value = state.apiKey;
    configForm.userId.value = state.userId;

    configForm.addEventListener('submit', (event) => {
        event.preventDefault();
        state.baseUrl = configForm.baseUrl.value.trim();
        state.apiKey = configForm.apiKey.value.trim() || 'devkey';
        state.userId = configForm.userId.value.trim();
        showNotice('success', '配置已更新。');
    });

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const route = link.dataset.route;
            if (!route) {
                return;
            }
            if (window.location.hash !== `#${route}`) {
                window.location.hash = `#${route}`;
            } else {
                handleRouteChange();
            }
        });
    });

    window.addEventListener('hashchange', handleRouteChange);
    handleRouteChange();
}

function handleRouteChange() {
    const route = resolveRoute();
    if (window.location.hash !== `#${route}`) {
        window.location.hash = `#${route}`;
        return;
    }

    setActiveNav(route);

    const render = routes[route];
    if (typeof render === 'function') {
        render();
    }
}

function resolveRoute() {
    const hash = window.location.hash.replace(/^#/, '');
    if (!hash || !(hash in routes)) {
        return 'assets';
    }
    return hash;
}

function setActiveNav(route) {
    navLinks.forEach((link) => {
        if (link.dataset.route === route) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function showNotice(type, message) {
    alertsContainer.innerHTML = '';
    if (!message) {
        return;
    }
    const notice = document.createElement('div');
    notice.className = `notice ${type}`;
    notice.textContent = message;
    alertsContainer.appendChild(notice);
}

function clearNotice() {
    alertsContainer.innerHTML = '';
}

function buildUrl(path) {
    if (/^https?:/i.test(path)) {
        return path;
    }
    const base = state.baseUrl || window.location.origin;
    return new URL(path, base).toString();
}

async function apiRequest(method, path, body) {
    const url = buildUrl(path);
    const headers = {
        'Accept': 'application/json',
        'X-Api-Key': state.apiKey || 'devkey',
    };

    if (state.userId) {
        headers['X-User-Id'] = state.userId;
    }

    const init = {
        method,
        headers,
    };

    if (body !== undefined && body !== null) {
        init.body = JSON.stringify(body);
        headers['Content-Type'] = 'application/json';
    }

    let response;
    try {
        response = await fetch(url, init);
    } catch (networkError) {
        throw new ApiError(networkError.message || 'Network error', 0, 'network_error');
    }

    const text = await response.text();
    let payload = null;
    if (text) {
        try {
            payload = JSON.parse(text);
        } catch (parseError) {
            throw new ApiError('响应解析失败', response.status || 500, 'invalid_response');
        }
    }

    if (!response.ok) {
        const code = payload && typeof payload === 'object' ? payload.error || 'error' : 'error';
        const message = payload && typeof payload === 'object' ? payload.message || '请求失败' : '请求失败';
        throw new ApiError(message, response.status, code, payload && payload.details ? payload.details : null);
    }

    return payload ? payload.data : null;
}

function appendLog(logElement, message) {
    if (!logElement) {
        return;
    }
    const timestamp = new Date().toISOString();
    const entry = `[${timestamp}] ${message}`;
    logElement.textContent = `${entry}\n${logElement.textContent}`;
}

async function renderAssets() {
    clearNotice();
    content.innerHTML = `
        <section class="card">
            <h2>创建资产</h2>
            <form id="asset-create-form" class="form-grid">
                <label>资产名称
                    <input type="text" name="name" required placeholder="例如 3D 打印机" />
                </label>
                <label>型号
                    <input type="text" name="model" placeholder="可选" />
                </label>
                <button type="submit" class="primary">创建</button>
            </form>
        </section>
        <section class="card">
            <h2>资产领用</h2>
            <form id="asset-assign-form" class="form-grid">
                <label>资产 ID
                    <input type="number" name="assetId" min="1" required />
                </label>
                <label>使用人用户 ID
                    <input type="number" name="userId" min="1" required />
                </label>
                <label>项目 ID
                    <input type="number" name="projectId" min="1" required />
                </label>
                <label>业务单号
                    <input type="text" name="requestNo" required placeholder="唯一编号" />
                </label>
                <button type="submit" class="primary">提交领用</button>
            </form>
        </section>
        <section class="card">
            <h2>资产归还</h2>
            <form id="asset-return-form" class="form-grid">
                <label>资产 ID
                    <input type="number" name="assetId" min="1" required />
                </label>
                <label>归还人用户 ID
                    <input type="number" name="userId" min="1" required />
                </label>
                <label>项目 ID
                    <input type="number" name="projectId" min="1" required />
                </label>
                <label>业务单号
                    <input type="text" name="requestNo" required placeholder="唯一编号" />
                </label>
                <button type="submit" class="primary">提交归还</button>
            </form>
        </section>
        <section class="card">
            <h2>资产列表</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>名称</th>
                            <th>型号</th>
                            <th>状态</th>
                            <th>创建时间</th>
                            <th>更新时间</th>
                        </tr>
                    </thead>
                    <tbody id="asset-table-body">
                        <tr><td colspan="6">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="card">
            <h2>操作日志</h2>
            <pre id="asset-log" class="log-area" aria-live="polite"></pre>
        </section>
    `;

    const logArea = document.getElementById('asset-log');
    const tableBody = document.getElementById('asset-table-body');

    document.getElementById('asset-create-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = {
            name: form.name.value.trim(),
            model: form.model.value.trim() || undefined,
        };
        if (!payload.name) {
            showNotice('error', '资产名称为必填项。');
            return;
        }
        try {
            const data = await apiRequest('POST', '/assets', payload);
            showNotice('success', `资产已创建，ID=${data.id}`);
            appendLog(logArea, `✅ 创建资产成功 (#${data.id})`);
            form.reset();
            await loadAssets(tableBody, logArea);
        } catch (error) {
            handleError(error, logArea);
        }
    });

    document.getElementById('asset-assign-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const assetId = Number(form.assetId.value);
        const payload = {
            user_id: Number(form.userId.value),
            project_id: Number(form.projectId.value),
            no: form.requestNo.value.trim(),
        };
        if (!assetId || !payload.user_id || !payload.project_id || payload.no === '') {
            showNotice('error', '请完整填写领用信息。');
            return;
        }
        try {
            const data = await apiRequest('POST', `/assets/${assetId}/assign`, payload);
            showNotice('success', `资产 ${assetId} 领用成功${data.idempotent ? '（幂等重试）' : ''}`);
            appendLog(logArea, `✅ 领用资产 #${assetId} (${payload.no})`);
            form.reset();
            await loadAssets(tableBody, logArea);
        } catch (error) {
            handleError(error, logArea);
        }
    });

    document.getElementById('asset-return-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const assetId = Number(form.assetId.value);
        const payload = {
            user_id: Number(form.userId.value),
            project_id: Number(form.projectId.value),
            no: form.requestNo.value.trim(),
        };
        if (!assetId || !payload.user_id || !payload.project_id || payload.no === '') {
            showNotice('error', '请完整填写归还信息。');
            return;
        }
        try {
            const data = await apiRequest('POST', `/assets/${assetId}/return`, payload);
            showNotice('success', `资产 ${assetId} 归还成功${data.idempotent ? '（幂等重试）' : ''}`);
            appendLog(logArea, `✅ 归还资产 #${assetId} (${payload.no})`);
            form.reset();
            await loadAssets(tableBody, logArea);
        } catch (error) {
            handleError(error, logArea);
        }
    });

    await loadAssets(tableBody, logArea);
}

async function loadAssets(tableBody, logArea) {
    try {
        const data = await apiRequest('GET', '/assets');
        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6">暂无资产</td></tr>';
            return;
        }
        tableBody.innerHTML = items.map((item) => `
            <tr>
                <td>${item.id}</td>
                <td>${escapeHtml(item.name)}</td>
                <td>${escapeHtml(item.model ?? '')}</td>
                <td><span class="status-badge status-${item.status}">${item.status}</span></td>
                <td>${item.created_at}</td>
                <td>${item.updated_at}</td>
            </tr>
        `).join('');
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="6">资产列表加载失败</td></tr>';
        handleError(error, logArea);
    }
}

async function renderRepairs() {
    clearNotice();
    content.innerHTML = `
        <section class="card">
            <h2>创建维修单</h2>
            <form id="repair-create-form" class="form-grid">
                <label>资产 ID
                    <input type="number" name="assetId" min="1" required />
                </label>
                <label>故障描述
                    <textarea name="symptom" rows="3" required placeholder="输入现象"></textarea>
                </label>
                <button type="submit" class="primary">提交维修申请</button>
            </form>
        </section>
        <section class="card">
            <h2>关闭维修单</h2>
            <form id="repair-close-form" class="form-inline">
                <label>维修单 ID
                    <input type="number" name="orderId" min="1" required />
                </label>
                <button type="submit" class="primary">关闭维修单</button>
            </form>
            <p class="help-text">仅 created / repairing / qa 状态的维修单可关闭。</p>
        </section>
        <section class="card">
            <h2>操作日志</h2>
            <pre id="repair-log" class="log-area" aria-live="polite"></pre>
        </section>
    `;

    const repairLog = document.getElementById('repair-log');

    document.getElementById('repair-create-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const payload = {
            asset_id: Number(form.assetId.value),
            symptom: form.symptom.value.trim(),
        };
        if (!payload.asset_id || !payload.symptom) {
            showNotice('error', '请填写资产 ID 与故障描述。');
            return;
        }
        try {
            const data = await apiRequest('POST', '/repair-orders', payload);
            showNotice('success', `维修单创建成功，ID=${data.order.id}`);
            appendLog(repairLog, `🛠️ 创建维修单 #${data.order.id} （资产 #${data.order.asset_id}）`);
            form.reset();
        } catch (error) {
            handleError(error, repairLog);
        }
    });

    document.getElementById('repair-close-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const orderId = Number(form.orderId.value);
        if (!orderId) {
            showNotice('error', '请输入要关闭的维修单 ID。');
            return;
        }
        try {
            const data = await apiRequest('POST', `/repair-orders/${orderId}/close`, {});
            showNotice('success', `维修单 ${orderId} 已关闭。`);
            appendLog(repairLog, `✅ 关闭维修单 #${orderId} （资产 #${data.order.asset_id}）`);
            form.reset();
        } catch (error) {
            handleError(error, repairLog);
        }
    });
}

async function renderReports() {
    clearNotice();
    content.innerHTML = `
        <section class="card">
            <div class="form-inline" style="justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">维修成本报表</h2>
                <button type="button" id="refresh-report" class="primary">刷新</button>
            </div>
            <div class="table-wrapper" style="margin-top: 1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>资产 ID</th>
                            <th>名称</th>
                            <th>型号</th>
                            <th>累计成本</th>
                        </tr>
                    </thead>
                    <tbody id="report-table-body">
                        <tr><td colspan="4">加载中...</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="help-text" id="report-generated"></p>
        </section>
        <section class="card">
            <h2>日志</h2>
            <pre id="report-log" class="log-area" aria-live="polite"></pre>
        </section>
    `;

    const tableBody = document.getElementById('report-table-body');
    const generated = document.getElementById('report-generated');
    const logArea = document.getElementById('report-log');

    const refresh = document.getElementById('refresh-report');
    refresh.addEventListener('click', async () => {
        await loadReport(tableBody, generated, logArea);
    });

    await loadReport(tableBody, generated, logArea);
}

async function loadReport(tableBody, generated, logArea) {
    try {
        const data = await apiRequest('GET', '/reports/costs');
        const items = Array.isArray(data.items) ? data.items : [];
        if (items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4">暂无数据</td></tr>';
        } else {
            tableBody.innerHTML = items.map((item) => `
                <tr>
                    <td>${item.id}</td>
                    <td>${escapeHtml(item.name ?? '')}</td>
                    <td>${escapeHtml(item.model ?? '')}</td>
                    <td>¥${Number(item.total_cost || 0).toFixed(2)}</td>
                </tr>
            `).join('');
        }
        const timestamp = data.generated_at ? new Date(data.generated_at).toLocaleString() : new Date().toLocaleString();
        generated.textContent = `生成时间：${timestamp}`;
        appendLog(logArea, '📊 更新维修成本报表');
    } catch (error) {
        tableBody.innerHTML = '<tr><td colspan="4">报表加载失败</td></tr>';
        generated.textContent = '';
        handleError(error, logArea);
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function handleError(error, logArea) {
    if (error instanceof ApiError) {
        const details = error.details && Array.isArray(error.details)
            ? `（${error.details.join(', ')}）`
            : '';
        showNotice('error', `请求失败：${error.message} [${error.code}]${details}`);
        appendLog(logArea, `❌ ${error.message} [${error.code}]`);
    } else if (error && typeof error === 'object' && 'message' in error) {
        showNotice('error', error.message);
        appendLog(logArea, `❌ ${error.message}`);
    } else {
        const message = String(error);
        showNotice('error', message);
        appendLog(logArea, `❌ ${message}`);
    }
}

init();
