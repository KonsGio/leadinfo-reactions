const ORIGIN = import.meta.env.VITE_API_ORIGIN || 'http://localhost:8081';

/**
 * Parse JSON or throw a structured error.
 * All fetch calls go through here so error handling stays consistent.
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
 * Perform a GET request.
 *
 * @param path
 * @returns {Promise<awaited Promise<Result<RootNode>> | Promise<Result<Root>> | Promise<any>>}
 */
export async function apiGet(path) {
    return fetch(`${ORIGIN}${path}`).then(jsonOrThrow);
}

/**
 * Perform a POST request with JSON body.
 *
 * @param path
 * @param body
 * @returns {Promise<awaited Promise<Result<RootNode>> | Promise<Result<Root>> | Promise<any>>}
 */
export async function apiPost(path, body) {
    return fetch(`${ORIGIN}${path}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    }).then(jsonOrThrow);
}

export const api = { get: apiGet, post: apiPost };

export { ORIGIN as API_ORIGIN };