const API_BASE = 'https://oaapi.lizmt.cn';

const state = {
  apiKey: 'devkey',
  userId: '1',
};

const content = document.getElementById('content');
const alerts = document.getElementById('alerts');
const navLinks = Array.from(document.querySelectorAll('.nav-link'));
const configForm = document.getElementById('config-form');

const routes = {
  assets: renderAssetsPage,
  repairs: renderRepairsPage,
  reports: renderReportsPage,
};

document.addEventListener('DOMContentLoaded', () => {
  initialiseConfig();
  navLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const route = link.dataset.route;
      navigate(route);
    });
  });

  window.addEventListener('popstate', () => {
    const route = resolveRoute();
    renderRoute(route);
  });

  const initialRoute = resolveRoute();
  renderRoute(initialRoute);
  history.replaceState({ route: initialRoute }, '', buildUrlForRoute(initialRoute));
});

function initialiseConfig() {
  if (!configForm) {
    return;
  }

  configForm.apiKey.value = state.apiKey;
  configForm.userId.value = state.userId;

  configForm.addEventListener('submit', (event) => {
    event.preventDefault();
    state.apiKey = configForm.apiKey.value.trim() || 'devkey';
    state.userId = configForm.userId.value.trim();
    showNotice('success', '接口配置已更新');
  });
}

function navigate(route) {
  const target = routes[route] ? route : 'assets';
  const url = buildUrlForRoute(target);
  history.pushState({ route: target }, '', url);
  renderRoute(target);
}

function resolveRoute() {
  const params = new URLSearchParams(window.location.search);
  const view = params.get('view');
  return routes[view] ? view : 'assets';
}

function buildUrlForRoute(route) {
  const url = new URL(window.location.href);
  if (route === 'assets') {
    url.searchParams.delete('view');
  } else {
    url.searchParams.set('view', route);
  }
  return url.pathname + url.search;
}

function renderRoute(route) {
  const target = routes[route] ? route : 'assets';
  setActiveNav(target);
  clearNotice();
  routes[target]();
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
  alerts.innerHTML = '';
  if (!message) {
    return;
  }
  const div = document.createElement('div');
  div.className = `notice ${type}`;
  div.textContent = message;
  alerts.appendChild(div);
}

function clearNotice() {
  alerts.innerHTML = '';
}

function buildApiUrl(path) {
  const normalisedPath = path.startsWith('/') ? path : `/${path}`;
  return `${API_BASE}${normalisedPath}`;
}

async function fetchJson(path, method = 'GET', body) {
  const url = buildApiUrl(path);
  const headers = {
    Accept: 'application/json',
    'X-Api-Key': state.apiKey || 'devkey',
  };

  if (state.userId) {
    headers['X-User-Id'] = state.userId;
  }

  const init = { method, headers };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    init.body = JSON.stringify(body);
  }

  let response;
  try {
    response = await fetch(url, init);
  } catch (error) {
    const networkError = new Error('网络请求失败');
    networkError.code = 'network_error';
    networkError.status = 0;
    throw networkError;
  }

  const text = await response.text();
  let payload = null;
  if (text) {
    try {
      payload = JSON.parse(text);
    } catch (_parseError) {
      const snippet = text.length > 180 ? `${text.slice(0, 177)}…` : text;
      const parseErr = new Error(`服务端返回了无法解析的响应: ${snippet}`);
      parseErr.code = 'invalid_response';
      parseErr.status = response.status || 500;
      parseErr.raw = text;
      parseErr.details = snippet;
      throw parseErr;
    }
  }

  if (!response.ok) {
    const error = new Error(payload?.message || '请求失败');
    error.code = payload?.error || 'error';
    error.status = response.status;
    error.details = payload?.details;
    throw error;
  }

  return payload ? payload.data : null;
}

function renderAssetsPage() {
  content.innerHTML = `
    <section class="card">
      <h2>资产列表</h2>
      <div class="table-wrapper">
        <table id="asset-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>名称</th>
              <th>型号</th>
              <th>状态</th>
              <th>创建时间</th>
            </tr>
          </thead>
          <tbody id="asset-table-body">
            <tr><td colspan="5">加载中...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
    <section class="card">
      <h2>创建资产</h2>
      <form id="asset-create-form" class="stacked-form">
        <label>资产名称 <input type="text" name="name" required /></label>
        <label>型号 <input type="text" name="model" /></label>
        <button type="submit">提交</button>
      </form>
    </section>
  `;

  const form = document.getElementById('asset-create-form');
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    await createAsset(form);
  });

  listAssets();
}

async function listAssets() {
  const tbody = document.getElementById('asset-table-body');
  if (!tbody) {
    return;
  }
  tbody.innerHTML = '<tr><td colspan="5">加载中...</td></tr>';

  try {
    const data = await fetchJson('/assets');
    const items = Array.isArray(data?.items) ? data.items : [];

    if (items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5">暂无资产</td></tr>';
      return;
    }

    tbody.innerHTML = items
      .map((item) => {
        const model = item.model ? escapeHtml(item.model) : '-';
        return `
          <tr>
            <td>${item.id}</td>
            <td>${escapeHtml(item.name)}</td>
            <td>${model}</td>
            <td><span class="badge status-${escapeHtml(item.status)}">${escapeHtml(item.status)}</span></td>
            <td>${escapeHtml(item.created_at)}</td>
          </tr>
        `;
      })
      .join('');
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="5">加载失败：${escapeHtml(error.message)}</td></tr>`;
    showNotice('error', `${error.message} (${error.code || 'error'})`);
  }
}

async function createAsset(form) {
  const nameInput = form.elements.namedItem('name');
  const modelInput = form.elements.namedItem('model');
  const name = nameInput ? nameInput.value.trim() : '';
  const model = modelInput ? modelInput.value.trim() : '';

  if (!name) {
    showNotice('error', '资产名称不能为空');
    return;
  }

  try {
    const payload = { name };
    if (model !== '') {
      payload.model = model;
    }
    const result = await fetchJson('/assets', 'POST', payload);
    showNotice('success', `资产创建成功（ID: ${result?.id ?? '未知'}）`);
    form.reset();
    listAssets();
  } catch (error) {
    showNotice('error', `${error.message} (${error.code || 'error'})`);
  }
}

function renderRepairsPage() {
  content.innerHTML = `
    <section class="card">
      <h2>维修管理</h2>
      <p>维修单前端尚未实现，请使用后端 API 进行操作。</p>
    </section>
  `;
}

function renderReportsPage() {
  content.innerHTML = `
    <section class="card">
      <h2>维修成本报表</h2>
      <div class="table-wrapper">
        <table id="repair-cost-table">
          <thead>
            <tr>
              <th>资产 ID</th>
              <th>名称</th>
              <th>型号</th>
              <th>累计成本</th>
            </tr>
          </thead>
          <tbody id="repair-cost-body">
            <tr><td colspan="4">加载中...</td></tr>
          </tbody>
        </table>
      </div>
      <p id="report-generated-at" class="muted"></p>
    </section>
  `;

  repairList();
}

async function repairList() {
  const tbody = document.getElementById('repair-cost-body');
  if (!tbody) {
    return;
  }

  tbody.innerHTML = '<tr><td colspan="4">加载中...</td></tr>';

  try {
    const data = await fetchJson('/reports/costs');
    const items = Array.isArray(data?.items) ? data.items : [];

    if (items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">暂无维修成本数据</td></tr>';
    } else {
      tbody.innerHTML = items
        .map((item) => `
          <tr>
            <td>${item.id}</td>
            <td>${escapeHtml(item.name)}</td>
            <td>${item.model ? escapeHtml(item.model) : '-'}</td>
            <td>¥${Number(item.total_cost || 0).toFixed(2)}</td>
          </tr>
        `)
        .join('');
    }

    const generatedAt = document.getElementById('report-generated-at');
    if (generatedAt) {
      generatedAt.textContent = data?.generated_at ? `生成时间：${data.generated_at}` : '';
    }
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="4">加载失败：${escapeHtml(error.message)}</td></tr>`;
    showNotice('error', `${error.message} (${error.code || 'error'})`);
  }
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
