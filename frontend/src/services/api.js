// src/services/api.js
const ORIGIN = import.meta.env.VITE_API_ORIGIN || 'http://localhost:8081';

/**
 *
 * @param init
 * @returns {{headers: Headers | Headers}}
 */
function appendGeoHeaders(init = {}) {
    const h = new Headers(init.headers || {});
    const c = localStorage.getItem('geo.country');
    const ci = localStorage.getItem('geo.city');
    if (c) h.set('X-Geo-Country', c);
    if (ci) h.set('X-Geo-City', ci);
    return { ...init, headers: h };
}

/**
 *
 * @param res
 * @returns {Promise<awaited Promise<Result<RootNode>> | Promise<Result<Root>> | Promise<any>>}
 */
async function jsonOrThrow(res) {
    const data = await res.json().catch(() => null);
    if (!res.ok) {
        const err = new Error(data?.title || 'Request failed');
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data;
}

/**
 *
 * @param path
 * @returns {Promise<awaited Promise<Result<RootNode>> | Promise<Result<Root>> | Promise<any>>}
 */
export async function apiGet(path) {
    return fetch(`${ORIGIN}${path}`, appendGeoHeaders()).then(jsonOrThrow);
}

/**
 *
 * @param path
 * @param body
 * @returns {Promise<awaited Promise<Result<RootNode>> | Promise<Result<Root>> | Promise<any>>}
 */
export async function apiPost(path, body) {
    return fetch(
        `${ORIGIN}${path}`,
        appendGeoHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
    ).then(jsonOrThrow);
}

export const api = { get: apiGet, post: apiPost };
export { ORIGIN as API_ORIGIN };