import type { ApiCollection } from '../types/api'

const TOKEN_STORAGE_KEY = 'street-rivals-token'

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly data: unknown = null,
        public readonly retryAfterSeconds: number | null = null,
    ) {
        super(message)
    }
}

export function getStoredToken(): string | null {
    return localStorage.getItem(TOKEN_STORAGE_KEY)
}

export function storeToken(token: string): void {
    localStorage.setItem(TOKEN_STORAGE_KEY, token)
}

export function removeStoredToken(): void {
    localStorage.removeItem(TOKEN_STORAGE_KEY)
}

interface ApiRequestOptions extends RequestInit {
    authenticated?: boolean
}

export async function apiRequest<T>(
    endpoint: string,
    options: ApiRequestOptions = {},
): Promise<T> {
    const {
        authenticated = true,
        headers: customHeaders,
        ...fetchOptions
    } = options

    const headers = new Headers(customHeaders)

    headers.set('Accept', 'application/ld+json')

    if (
        fetchOptions.body !== undefined &&
        !headers.has('Content-Type')
    ) {
        headers.set('Content-Type', 'application/ld+json')
    }

    if (authenticated) {
        const token = getStoredToken()

        if (token !== null) {
            headers.set('Authorization', `Bearer ${token}`)
        }
    }

    const response = await fetch(endpoint, {
        ...fetchOptions,
        headers,
    })

    if (response.status === 204) {
        return undefined as T
    }

    const contentType =
        response.headers.get('content-type') ?? ''

    let responseData: unknown = null

    if (contentType.includes('json')) {
        responseData = await response.json()
    } else {
        responseData = await response.text()
    }

    if (!response.ok) {
        const data = responseData as {
            detail?: string
            message?: string
            title?: string
        } | null

        const message =
            data?.detail ??
            data?.message ??
            data?.title ??
            `Erreur HTTP ${response.status}`

        const retryAfterHeader =
            response.headers.get('Retry-After')

        const retryAfterSeconds =
            retryAfterHeader !== null
                ? Number.parseInt(retryAfterHeader, 10)
                : null

        throw new ApiError(
            message,
            response.status,
            responseData,
            Number.isFinite(retryAfterSeconds)
                ? retryAfterSeconds
                : null,
        )
    }

    return responseData as T
}

export function getCollectionMembers<T>(
    collection: ApiCollection<T>,
): T[] {
    return collection.member ??
        collection['hydra:member'] ??
        []
}