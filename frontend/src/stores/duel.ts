import { ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiRequest } from '../services/api'
import type { Duel } from '../types/api'

export const useDuelStore = defineStore('duel', () => {
    const duel = ref<Duel | null>(null)
    const loading = ref(false)
    const errorMessage = ref('')
    const retryAfterSeconds = ref<number | null>(null)

    async function startDuel(
        attackerCarId: number,
        defenderCarId: number,
    ): Promise<Duel> {
        loading.value = true
        errorMessage.value = ''
        retryAfterSeconds.value = null
        duel.value = null

        try {
            const response = await apiRequest<Duel>(
                '/api/duels',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        attackerCarId,
                        defenderCarId,
                    }),
                },
            )

            duel.value = response

            return response
        } catch (error) {
            if (error instanceof ApiError) {
                errorMessage.value = error.message
                retryAfterSeconds.value =
                    error.retryAfterSeconds
            } else {
                errorMessage.value =
                    'Une erreur inattendue est survenue.'
            }

            throw error
        } finally {
            loading.value = false
        }
    }

    function reset(): void {
        duel.value = null
        loading.value = false
        errorMessage.value = ''
        retryAfterSeconds.value = null
    }

    return {
        duel,
        loading,
        errorMessage,
        retryAfterSeconds,
        startDuel,
        reset,
    }
})