import { ApiError, createApiClient } from './api-client.js';

const api = createApiClient({ apiKey: 'devkey' });

const consoleEl = document.getElementById('console');
const assetsTableBody = document.querySelector('#assets-table tbody');

function init() {
    wireConfigForm();
    wireAssetForms();
    wireRepairForms();

    refreshAssets();
}

function wireConfigForm() {
    const form = document.getElementById('config-form');
    if (!form) {
        return;
    }

    const apiKeyInput = form.elements.namedItem('api_key');
    const userIdInput = form.elements.namedItem('user_id');

    if (apiKeyInput) {
        apiKeyInput.value = api.getAuth().apiKey || '';
    }
    if (userIdInput) {
        userIdInput.value = api.getAuth().userId || '';
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const apiKey = (formData.get('api_key') || '').toString().trim();
        const userId = (formData.get('user_id') || '').toString().trim();

        const auth = api.setAuth({
            apiKey,
            userId: userId === '' ? null : userId,
        });

        logMessage('Configuration updated', {
            apiKeyPreview: auth.apiKey ? `${auth.apiKey.slice(0, 2)}***` : null,
            userId: auth.userId,
            endpoint: `${api.getBaseUrl()}/*`,
        });
    });
}

function wireAssetForms() {
    const listForm = document.getElementById('list-assets-form');
    if (listForm) {
        listForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(listForm);
            const status = (formData.get('status') || '').toString().trim();

            try {
                const data = await api.listAssets({ status: status === '' ? undefined : status });
                renderAssets(data?.items ?? []);
                logMessage('Fetched asset list', data ?? {});
            } catch (error) {
                handleError('Fetch assets failed', error);
            }
        });
    }

    const createForm = document.getElementById('create-asset-form');
    if (createForm) {
        createForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(createForm);
            const name = (formData.get('name') || '').toString().trim();
            const model = (formData.get('model') || '').toString().trim();

            if (name === '') {
                logMessage('Validation error', 'Asset name is required');
                return;
            }

            try {
                const data = await api.createAsset({
                    name,
                    ...(model ? { model } : {}),
                });
                logMessage('Asset created', data ?? {});
                createForm.reset();
                refreshAssets();
            } catch (error) {
                handleError('Create asset failed', error);
            }
        });
    }

    const assignForm = document.getElementById('assign-asset-form');
    if (assignForm) {
        assignForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(assignForm);
            const assetId = parsePositiveInt(formData.get('asset_id'));
            const userId = parsePositiveInt(formData.get('user_id'));
            const projectId = parsePositiveInt(formData.get('project_id'));
            const requestNo = (formData.get('no') || '').toString().trim();

            if (!assetId || !userId || !projectId || requestNo === '') {
                logMessage('Validation error', 'All assignment fields are required');
                return;
            }

            try {
                const data = await api.assignAsset(assetId, {
                    user_id: userId,
                    project_id: projectId,
                    no: requestNo,
                });
                logMessage('Asset assigned', data ?? {});
                refreshAssets();
            } catch (error) {
                handleError('Assign asset failed', error);
            }
        });
    }

    const returnForm = document.getElementById('return-asset-form');
    if (returnForm) {
        returnForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(returnForm);
            const assetId = parsePositiveInt(formData.get('asset_id'));
            const userId = parsePositiveInt(formData.get('user_id'));
            const projectId = parsePositiveInt(formData.get('project_id'));
            const requestNo = (formData.get('no') || '').toString().trim();

            if (!assetId || !userId || !projectId || requestNo === '') {
                logMessage('Validation error', 'All return fields are required');
                return;
            }

            try {
                const data = await api.returnAsset(assetId, {
                    user_id: userId,
                    project_id: projectId,
                    no: requestNo,
                });
                logMessage('Asset returned', data ?? {});
                refreshAssets();
            } catch (error) {
                handleError('Return asset failed', error);
            }
        });
    }
}

function wireRepairForms() {
    const createRepairForm = document.getElementById('create-repair-form');
    if (createRepairForm) {
        createRepairForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(createRepairForm);
            const assetId = parsePositiveInt(formData.get('asset_id'));
            const symptom = (formData.get('symptom') || '').toString().trim();

            if (!assetId || symptom === '') {
                logMessage('Validation error', 'Asset ID and symptom are required');
                return;
            }

            try {
                const data = await api.createRepairOrder({
                    asset_id: assetId,
                    symptom,
                });
                logMessage('Repair order created', data ?? {});
                createRepairForm.reset();
                refreshAssets();
            } catch (error) {
                handleError('Create repair order failed', error);
            }
        });
    }

    const closeRepairForm = document.getElementById('close-repair-form');
    if (closeRepairForm) {
        closeRepairForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(closeRepairForm);
            const orderId = parsePositiveInt(formData.get('order_id'));

            if (!orderId) {
                logMessage('Validation error', 'Order ID is required');
                return;
            }

            try {
                const data = await api.closeRepairOrder(orderId);
                logMessage('Repair order closed', data ?? {});
                refreshAssets();
            } catch (error) {
                handleError('Close repair order failed', error);
            }
        });
    }
}

function refreshAssets() {
    const listForm = document.getElementById('list-assets-form');
    if (listForm) {
        listForm.dispatchEvent(new Event('submit'));
    }
}

function renderAssets(items) {
    if (!assetsTableBody) {
        return;
    }

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

function parsePositiveInt(value) {
    if (value === null || value === undefined) {
        return null;
    }

    const parsed = Number.parseInt(value, 10);
    return Number.isNaN(parsed) || parsed <= 0 ? null : parsed;
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

function logMessage(message, payload) {
    const timestamp = new Date().toISOString();
    const lines = [`[${timestamp}] ${message}`];
    if (payload !== undefined) {
        lines.push(typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2));
    }
    const newEntry = lines.join('\n');
    consoleEl.textContent = `${newEntry}\n\n${consoleEl.textContent}`.trim();
}

function handleError(message, error) {
    if (error instanceof ApiError) {
        logMessage(`${message}: ${error.message}`, {
            code: error.code,
            status: error.status,
            details: error.details,
            payload: error.payload,
        });
        return;
    }

    logMessage(`${message}: ${error.message}`, 'Unexpected error');
}

init();

