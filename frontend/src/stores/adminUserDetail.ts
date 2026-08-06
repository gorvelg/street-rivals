import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminUserDetail,
    AdminUserStatusResponse,
} from '../types/admin'

export const useAdminUserDetailStore = defineStore(
    'admin-user-detail',
    () => {
        const detail = ref<AdminUserDetail | null>(null)

        const isLoading = ref<boolean>(false)
        const isUpdatingStatus = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadUser(
            userId: number,
        ): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            try {
                detail.value =
                    await apiRequest<AdminUserDetail>(
                        `/api/admin/users/${userId}`,
                    )
            } catch (error: unknown) {
                detail.value = null

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger cet utilisateur.'
            } finally {
                isLoading.value = false
            }
        }

        async function updateStatus(
            isActive: boolean,
        ): Promise<void> {
            if (detail.value === null) {
                return
            }

            isUpdatingStatus.value = true
            errorMessage.value = null

            try {
                const response =
                    await apiRequest<AdminUserStatusResponse>(
                        `/api/admin/users/${detail.value.user.id}/status`,
                        {
                            method: 'PATCH',
                            body: JSON.stringify({
                                isActive,
                            }),
                        },
                    )

                detail.value.user.isActive =
                    response.isActive

                detail.value.user.roles =
                    response.roles
            } catch (error: unknown) {
                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de modifier le statut.'

                throw error
            } finally {
                isUpdatingStatus.value = false
            }
        }

        function reset(): void {
            detail.value = null
            isLoading.value = false
            isUpdatingStatus.value = false
            errorMessage.value = null
        }

        return {
            detail,
            isLoading,
            isUpdatingStatus,
            errorMessage,

            loadUser,
            updateStatus,
            reset,
        }
    },
)