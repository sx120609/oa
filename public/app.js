(function () {
    const state = {
        baseUrl: '/',
        apiKey: 'devkey',
        userId: null,
    };

    const consoleEl = document.getElementById('console');
    const assetsTableBody = document.querySelector('#assets-table tbody');

    function normaliseBase(base) {
        if (!base || base === '/') {
            return '';
        }
        return base.endsWith('/') ? base.slice(0, -1) : base;
    }

    function buildUrl(path) {
        const base = normaliseBase(state.baseUrl);
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }
        if (path.startsWith('/')) {
            return `${base}${path}` || path;
        }
        return `${base}/${path}`;
    }

    function logMessage(message, payload) {
        const timestamp = new Date().toISOString();
        const lines = [`[${timestamp}] ${message}`];
        if (payload !== undefined) {
            lines.push(typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2));
        }
        const newEntry = lines.join('\n');
        consoleEl.textContent = `${newEntry}\n\n${consoleEl.textContent}`.trim();
    }

    async function apiFetch(path, options = {}) {
        const headers = new Headers(options.headers || {});
        if (!state.apiKey || state.apiKey.trim() === '') {
            throw Object.assign(new Error('API key is required'), { code: 'missing_api_key' });
        }
        headers.set('X-Api-Key', state.apiKey.trim());
        if (state.userId !== null && state.userId !== '') {
            headers.set('X-User-Id', String(state.userId));
        }

        let body = options.body;
        if (body !== undefined && body !== null && !(body instanceof FormData)) {
            headers.set('Content-Type', 'application/json');
            body = JSON.stringify(body);
        }

        const response = await fetch(buildUrl(path), {
            method: options.method || 'GET',
            headers,
            body,
        });

        const text = await response.text();
        let parsed;
        if (text !== '') {
            try {
                parsed = JSON.parse(text);
            } catch (error) {
                parsed = null;
            }
        }

        if (!response.ok) {
            const code = parsed && typeof parsed.error === 'string' ? parsed.error : 'error';
            const message = parsed && typeof parsed.message === 'string' ? parsed.message : response.statusText;
            const err = new Error(message);
            err.code = code;
            err.status = response.status;
            err.details = parsed && parsed.details ? parsed.details : null;
            err.payload = parsed || text;
            throw err;
        }

        if (parsed && Object.prototype.hasOwnProperty.call(parsed, 'data')) {
            return parsed.data;
        }
        return parsed;
    }

    function renderAssets(items) {
        assetsTableBody.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'empty';
            cell.textContent = 'No data';
            row.appendChild(cell);
            assetsTableBody.appendChild(row);
            return;
        }

        for (const item of items) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.id}</td>
                <td>${escapeHtml(item.name)}</td>
                <td>${escapeHtml(item.model ?? '')}</td>
                <td><span class="status status-${escapeHtml(item.status)}">${escapeHtml(item.status)}</span></td>
                <td>${formatDate(item.created_at)}</td>
                <td>${formatDate(item.updated_at)}</td>
            `;
            assetsTableBody.appendChild(row);
        }
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value.replace(' ', 'T') + 'Z');
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString();
    }

    function parseIntOrNull(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }
        const parsed = Number.parseInt(value, 10);
        return Number.isNaN(parsed) ? null : parsed;
    }

    document.getElementById('config-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        state.baseUrl = (formData.get('api_base') || '/').toString().trim() || '/';
        state.apiKey = (formData.get('api_key') || '').toString().trim();
        const userIdRaw = (formData.get('user_id') || '').toString().trim();
        state.userId = userIdRaw === '' ? null : userIdRaw;
        logMessage('Configuration updated', {
            baseUrl: state.baseUrl,
            apiKeyPreview: state.apiKey ? `${state.apiKey.slice(0, 2)}***` : null,
            userId: state.userId,
        });
    });

    document.getElementById('list-assets-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const status = (formData.get('status') || '').toString();
        const query = status ? `?status=${encodeURIComponent(status)}` : '';
        try {
            const data = await apiFetch(`/assets${query}`);
            renderAssets(data.items || []);
            logMessage('Fetched asset list', data);
        } catch (error) {
            handleError('Fetch assets failed', error);
        }
    });

    document.getElementById('create-asset-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const name = (formData.get('name') || '').toString().trim();
        const model = (formData.get('model') || '').toString().trim();
        if (name === '') {
            logMessage('Validation error', 'Asset name is required');
            return;
        }
        try {
            const payload = { name };
            if (model !== '') {
                payload.model = model;
            }
            const data = await apiFetch('/assets', { method: 'POST', body: payload });
            logMessage('Asset created', data);
            event.currentTarget.reset();
            // Refresh asset list to show the new asset
            document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
        } catch (error) {
            handleError('Create asset failed', error);
        }
    });

    document.getElementById('assign-asset-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const assetId = parseIntOrNull(formData.get('asset_id'));
        const userId = parseIntOrNull(formData.get('user_id'));
        const projectId = parseIntOrNull(formData.get('project_id'));
        const requestNo = (formData.get('no') || '').toString().trim();

        if (!assetId || !userId || !projectId || requestNo === '') {
            logMessage('Validation error', 'All assignment fields are required');
            return;
        }

        try {
            const data = await apiFetch(`/assets/${assetId}/assign`, {
                method: 'POST',
                body: {
                    user_id: userId,
                    project_id: projectId,
                    no: requestNo,
                },
            });
            logMessage('Asset assigned', data);
            document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
        } catch (error) {
            handleError('Assign asset failed', error);
        }
    });

    document.getElementById('return-asset-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const assetId = parseIntOrNull(formData.get('asset_id'));
        const userId = parseIntOrNull(formData.get('user_id'));
        const projectId = parseIntOrNull(formData.get('project_id'));
        const requestNo = (formData.get('no') || '').toString().trim();

        if (!assetId || !userId || !projectId || requestNo === '') {
            logMessage('Validation error', 'All return fields are required');
            return;
        }

        try {
            const data = await apiFetch(`/assets/${assetId}/return`, {
                method: 'POST',
                body: {
                    user_id: userId,
                    project_id: projectId,
                    no: requestNo,
                },
            });
            logMessage('Asset returned', data);
            document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
        } catch (error) {
            handleError('Return asset failed', error);
        }
    });

    document.getElementById('create-repair-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const assetId = parseIntOrNull(formData.get('asset_id'));
        const symptom = (formData.get('symptom') || '').toString().trim();

        if (!assetId || symptom === '') {
            logMessage('Validation error', 'Asset ID and symptom are required');
            return;
        }

        try {
            const data = await apiFetch('/repair-orders', {
                method: 'POST',
                body: {
                    asset_id: assetId,
                    symptom,
                },
            });
            logMessage('Repair order created', data);
            event.currentTarget.reset();
            document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
        } catch (error) {
            handleError('Create repair order failed', error);
        }
    });

    document.getElementById('close-repair-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const orderId = parseIntOrNull(formData.get('order_id'));
        if (!orderId) {
            logMessage('Validation error', 'Order ID is required');
            return;
        }
        try {
            const data = await apiFetch(`/repair-orders/${orderId}/close`, {
                method: 'POST',
            });
            logMessage('Repair order closed', data);
            document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
        } catch (error) {
            handleError('Close repair order failed', error);
        }
    });

    function handleError(message, error) {
        logMessage(`${message}: ${error.message}`, {
            code: error.code || 'error',
            status: error.status,
            details: error.details || null,
            payload: error.payload || null,
        });
    }

    // Load initial data automatically
    document.getElementById('list-assets-form').dispatchEvent(new Event('submit'));
})();
