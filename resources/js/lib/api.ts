declare global {
    interface Window {
        __conductor__: {
            basePath: string;
        };
    }
}

export function apiUrl(path: string): string {
    const base = window.__conductor__?.basePath ?? 'conductor';
    return `/${base}/api${path}`;
}

function getCsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));
    return match ? decodeURIComponent(match.split('=')[1]) : '';
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken(),
        ...(options.headers as Record<string, string>),
    };

    const response = await fetch(apiUrl(path), { ...options, headers });

    if (!response.ok) {
        const body = await response.text();
        throw new Error(body || `HTTP error ${response.status}`);
    }

    return response.json() as Promise<T>;
}

export function apiGet<T>(path: string): Promise<T> {
    return apiFetch<T>(path, { method: 'GET' });
}

export function apiPost<T>(path: string, body?: unknown): Promise<T> {
    return apiFetch<T>(path, {
        method: 'POST',
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });
}

export function apiDelete<T>(path: string): Promise<T> {
    return apiFetch<T>(path, { method: 'DELETE' });
}
