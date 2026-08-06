import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import {
    apiRequest,
    getStoredToken,
    removeStoredToken,
    storeToken,
} from '../services/api'
import type { AuthTokenResponse, User } from '../types/api'

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(getStoredToken())
    const user = ref<User | null>(null)
    const loading = ref(false)

    const isAuthenticated = computed(() => token.value !== null)

    async function login(email: string, password: string): Promise<void> {
        loading.value = true

        try {
            const response = await apiRequest<AuthTokenResponse>(
                '/api/login_check',
                {
                    method: 'POST',
                    authenticated: false,
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email,
                        password,
                    }),
                },
            )

            token.value = response.token
            storeToken(response.token)

            await fetchCurrentUser()
        } catch (error) {
            logout()
            throw error
        } finally {
            loading.value = false
        }
    }

    async function register(
        email: string,
        password: string,
    ): Promise<void> {
        loading.value = true

        try {
            await apiRequest('/api/register', {
                method: 'POST',
                authenticated: false,
                body: JSON.stringify({
                    email,
                    password,
                }),
            })

            await login(email, password)
        } finally {
            loading.value = false
        }
    }

    async function fetchCurrentUser(): Promise<void> {
        if (token.value === null) {
            user.value = null
            return
        }

        try {
            user.value = await apiRequest<User>('/api/me')
        } catch (error) {
            logout()
            throw error
        }
    }

    async function bootstrap(): Promise<void> {
        if (token.value === null || user.value !== null) {
            return
        }

        await fetchCurrentUser()
    }

    function logout(): void {
        token.value = null
        user.value = null
        removeStoredToken()
    }

    return {
        token,
        user,
        loading,
        isAuthenticated,
        login,
        register,
        fetchCurrentUser,
        bootstrap,
        logout,
    }
})