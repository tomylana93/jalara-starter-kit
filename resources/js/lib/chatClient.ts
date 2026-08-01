import { http } from '@inertiajs/core';
import type { RouteDefinition } from '@/wayfinder';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

/**
 * Raised when a chat endpoint answers with anything other than success.
 *
 * The status is kept so the surface can tell a closed feature (403) and a
 * throttled sender (429) apart from a generic failure.
 */
export class ChatRequestError extends Error {
    public constructor(
        public readonly status: number,
        public readonly payload: unknown,
    ) {
        super(`Chat request failed with status ${status}`);
        this.name = 'ChatRequestError';
    }
}

const parse = (body: string): unknown => {
    try {
        return JSON.parse(body);
    } catch {
        return null;
    }
};

/**
 * Send one standalone JSON request to a chat endpoint.
 *
 * Inertia's own HTTP client is used rather than a bare fetch, so the request
 * carries the same CSRF handling and interceptors as the rest of the
 * application. The URL always comes from a Wayfinder route definition; nothing
 * here hardcodes a path.
 */
export const chatRequest = async <TResponse>(
    route: RouteDefinition<Method>,
    data?: Record<string, unknown> | FormData,
    signal?: AbortSignal,
    onUploadProgress?: (percentage: number) => void,
): Promise<TResponse> => {
    const response = await http.getClient().request({
        method: route.method,
        url: route.url,
        data,
        signal,
        onUploadProgress: (progress) => {
            if (progress.total && onUploadProgress) {
                onUploadProgress(
                    Math.min(
                        100,
                        Math.round((progress.loaded / progress.total) * 100),
                    ),
                );
            }
        },
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = parse(response.data);

    if (response.status < 200 || response.status >= 300) {
        throw new ChatRequestError(response.status, payload);
    }

    return payload as TResponse;
};
