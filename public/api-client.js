const DEFAULT_BASE_URL = 'https://oaapi.lizmt.cn';

export class ApiError extends Error {
    constructor(message, { status = 0, code = 'error', details = null, payload = null, cause = undefined } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.details = details;
        this.payload = payload;
        if (cause !== undefined) {
            this.cause = cause;
        }
    }
}

export function createApiClient(options = {}) {
    let baseUrl = normaliseBaseUrl(options.baseUrl ?? DEFAULT_BASE_URL);
    let auth = {
        apiKey: options.apiKey ?? '',
        userId: normaliseUserId(options.userId),
    };

    function normaliseUserId(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const str = String(value).trim();
        if (str === '') {
            return null;
        }

        return str;
    }

    function ensureApiKey() {
        const key = auth.apiKey?.trim();
        if (!key) {
            throw new ApiError('API key is required', { code: 'missing_api_key' });
        }

        return key;
    }

    function buildUrl(path, query) {
        const root = new URL(baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`);
        const targetPath = path.startsWith('/') ? path : `/${path}`;
        const url = new URL(targetPath, root);

        if (query && typeof query === 'object') {
            Object.entries(query)
                .filter(([, value]) => value !== undefined && value !== null && value !== '')
                .forEach(([key, value]) => {
                    url.searchParams.set(key, String(value));
                });
        }

        return url.toString();
    }

    async function request(path, { method = 'GET', query, body, headers: extraHeaders } = {}) {
        const url = buildUrl(path, query);
        const headers = new Headers({
            Accept: 'application/json',
            'X-Api-Key': ensureApiKey(),
        });

        if (auth.userId) {
            headers.set('X-User-Id', auth.userId);
        }

        if (extraHeaders && typeof extraHeaders === 'object') {
            Object.entries(extraHeaders).forEach(([key, value]) => {
                headers.set(key, value);
            });
        }

        let payload = undefined;
        if (body !== undefined) {
            headers.set('Content-Type', 'application/json');
            payload = JSON.stringify(body);
        }

        let response;
        try {
            response = await fetch(url, {
                method,
                headers,
                body: payload,
            });
        } catch (error) {
            throw new ApiError('Network request failed', {
                code: 'network_error',
                cause: error,
            });
        }

        const text = await response.text();
        let parsed = null;

        if (text) {
            try {
                parsed = JSON.parse(text);
            } catch (error) {
                throw new ApiError('Invalid JSON response from server', {
                    code: 'invalid_response',
                    status: response.status || 500,
                    payload: text,
                    cause: error,
                });
            }
        }

        if (!response.ok) {
            const message = parsed?.message || response.statusText || 'Request failed';
            const code = parsed?.error || 'error';
            throw new ApiError(message, {
                status: response.status,
                code,
                details: parsed?.details ?? null,
                payload: parsed ?? text,
            });
        }

        return parsed?.data ?? null;
    }

    return {
        getBaseUrl() {
            return baseUrl;
        },

        setBaseUrl(value) {
            baseUrl = normaliseBaseUrl(value);
            return baseUrl;
        },

        getAuth() {
            return { ...auth };
        },

        setAuth({ apiKey, userId }) {
            auth = {
                apiKey: apiKey ?? '',
                userId: normaliseUserId(userId),
            };
            return { ...auth };
        },

        async health() {
            return request('/health');
        },

        async listAssets({ status } = {}) {
            return request('/assets', { query: { status } });
        },

        async getAsset(assetId) {
            return request(`/assets/${assetId}`);
        },

        async createAsset(payload) {
            return request('/assets', {
                method: 'POST',
                body: payload,
            });
        },

        async assignAsset(assetId, payload) {
            return request(`/assets/${assetId}/assign`, {
                method: 'POST',
                body: payload,
            });
        },

        async returnAsset(assetId, payload) {
            return request(`/assets/${assetId}/return`, {
                method: 'POST',
                body: payload,
            });
        },

        async listRepairs() {
            return request('/repairs');
        },

        async createRepairOrder(payload) {
            return request('/repair-orders', {
                method: 'POST',
                body: payload,
            });
        },

        async closeRepairOrder(orderId) {
            return request(`/repair-orders/${orderId}/close`, {
                method: 'POST',
            });
        },

        async getSummaryReport() {
            return request('/reports/summary');
        },

        async getCostReport() {
            return request('/reports/costs');
        },

        request,
    };
}

function normaliseBaseUrl(value) {
    if (value === undefined || value === null) {
        return DEFAULT_BASE_URL;
    }

    const trimmed = String(value).trim();
    if (trimmed === '') {
        throw new ApiError('Base URL cannot be empty', { code: 'invalid_base_url' });
    }

    if (!/^https?:\/\//i.test(trimmed)) {
        throw new ApiError('Base URL must start with http:// or https://', { code: 'invalid_base_url' });
    }

    return trimmed.replace(/\/+$/, '');
}

if (typeof window !== 'undefined') {
    window.ApiError = ApiError;
    window.createApiClient = createApiClient;
}

