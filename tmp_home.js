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

        if (/^OK\b/i.test(trimmed)) {
            const rest = trimmed.replace(/^OK:?/i, '').trim();
            return { type: 'success', message: rest !== '' ? rest : '操作成功' };
        }
        if (/^ERROR\b/i.test(trimmed)) {
            const rest = trimmed.replace(/^ERROR:\s*/i, '').trim();
            return { type: 'error', message: rest !== '' ? rest : '操作失败' };
        }

        if (status >= 400) {
            return { type: 'error', message: trimmed };
        }

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
})()