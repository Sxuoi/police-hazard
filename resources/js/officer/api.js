/**
 * Officer API fetch wrapper.
 * Injects Authorization Bearer header from sessionStorage.
 */
export async function officerFetch(method, path, opts = {}) {
    const token = sessionStorage.getItem('ph_token');

    const headers = {
        'Accept': 'application/json',
        ...(opts.json ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    };

    const config = {
        method,
        headers,
    };

    if (opts.json) {
        config.body = JSON.stringify(opts.json);
    } else if (opts.multipart) {
        config.body = opts.multipart;
    }

    const res = await fetch(path, config);

    // Handle token expiry / invalid
    if (res.status === 401) {
        const body = await res.json().catch(() => ({}));
        if (body.reason_code === 'TOKEN_INVALID' || body.reason_code === 'TOKEN_EXPIRED') {
            sessionStorage.removeItem('ph_token');
            sessionStorage.removeItem('ph_token_exp');
            sessionStorage.removeItem('ph_officer');
            window.location.href = '/officer/login';
            return;
        }
    }

    return res;
}
