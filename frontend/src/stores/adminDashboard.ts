import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type { AdminDashboard } from '../types/admin'

export const useAdminDashboardStore = defineStore(
    'admin-dashboard',
    () => {
        const dashboard = ref<AdminDashboard | null>(null)
        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadDashboard(): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            try {
                dashboard.value =
                    await apiRequest<AdminDashboard>(
                        '/api/admin/dashboard',
                    )
            } catch (error: unknown) {
                dashboard.value = null

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger le dashboard.'
            } finally {
                isLoading.value = false
            }
        }

        function reset(): void {
            dashboard.value = null
            errorMessage.value = null
            isLoading.value = false
        }

        return {
            dashboard,
            isLoading,
            errorMessage,

            loadDashboard,
            reset,
        }
    },
)