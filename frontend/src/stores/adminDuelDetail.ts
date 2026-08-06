import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminDuelDetail,
} from '../types/admin'

export const useAdminDuelDetailStore = defineStore(
    'admin-duel-detail',
    () => {
        const detail = ref<AdminDuelDetail | null>(null)

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadDuel(
            duelId: number,
        ): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            try {
                detail.value =
                    await apiRequest<AdminDuelDetail>(
                        `/api/admin/duels/${duelId}`,
                    )
            } catch (error: unknown) {
                detail.value = null

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger ce duel.'
            } finally {
                isLoading.value = false
            }
        }

        function reset(): void {
            detail.value = null
            isLoading.value = false
            errorMessage.value = null
        }

        return {
            detail,
            isLoading,
            errorMessage,

            loadDuel,
            reset,
        }
    },
)