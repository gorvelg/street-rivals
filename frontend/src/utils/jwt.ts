interface JwtPayload {
    exp?: number
    roles?: string[]
    username?: string
    email?: string
    [key: string]: unknown
}

function decodeBase64Url(value: string): string {
    const normalized = value
        .replace(/-/g, '+')
        .replace(/_/g, '/')

    const paddingLength =
        normalized.length % 4 === 0
            ? 0
            : 4 - (normalized.length % 4)

    return atob(
        normalized.padEnd(
            normalized.length + paddingLength,
            '=',
        ),
    )
}

export function decodeJwtPayload(
    token: string | null,
): JwtPayload | null {
    if (token === null || token.trim() === '') {
        return null
    }

    const parts = token.split('.')

    if (parts.length !== 3) {
        return null
    }

    try {
        const decodedPayload = decodeBase64Url(parts[1])

        return JSON.parse(decodedPayload) as JwtPayload
    } catch {
        return null
    }
}

export function getJwtRoles(
    token: string | null,
): string[] {
    const payload = decodeJwtPayload(token)

    if (!Array.isArray(payload?.roles)) {
        return []
    }

    return payload.roles.filter(
        (role): role is string =>
            typeof role === 'string',
    )
}

export function isJwtAdmin(
    token: string | null,
): boolean {
    return getJwtRoles(token).includes('ROLE_ADMIN')
}