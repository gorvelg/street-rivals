import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminPagination,
    AdminUser,
    AdminUserStatusResponse,
    AdminUsersResponse,
} from '../types/admin'

const initialPagination = (): AdminPagination => ({
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 1,
})

export const useAdminUsersStore = defineStore(
    'admin-users',
    () => {
        const users = ref<AdminUser[]>([])

        const pagination = ref<AdminPagination>(
            initialPagination(),
        )

        const search = ref<string>('')
        const isLoading = ref<boolean>(false)
        const updatingUserId = ref<number | null>(null)
        const errorMessage = ref<string | null>(null)

        async function loadUsers(
            requestedPage = 1,
        ): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            const queryParameters = new URLSearchParams({
                page: String(requestedPage),
                itemsPerPage: String(
                    pagination.value.itemsPerPage,
                ),
            })

            const normalizedSearch = search.value.trim()

            if (normalizedSearch !== '') {
                queryParameters.set(
                    'search',
                    normalizedSearch,
                )
            }

            try {
                const response =
                    await apiRequest<AdminUsersResponse>(
                        `/api/admin/users?${queryParameters.toString()}`,
                    )

                users.value = response.members
                pagination.value = response.pagination
            } catch (error: unknown) {
                users.value = []

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger les utilisateurs.'
            } finally {
                isLoading.value = false
            }
        }

        async function updateUserStatus(
            user: AdminUser,
            isActive: boolean,
        ): Promise<void> {
            updatingUserId.value = user.id
            errorMessage.value = null

            try {
                const response =
                    await apiRequest<AdminUserStatusResponse>(
                        `/api/admin/users/${user.id}/status`,
                        {
                            method: 'PATCH',
                            body: JSON.stringify({
                                isActive,
                            }),
                        },
                    )

                const existingUser = users.value.find(
                    (item) => item.id === response.id,
                )

                if (existingUser !== undefined) {
                    existingUser.isActive = response.isActive
                    existingUser.roles = response.roles
                    existingUser.email = response.email
                }
            } catch (error: unknown) {
                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de modifier le statut du compte.'

                throw error
            } finally {
                updatingUserId.value = null
            }
        }

        async function submitSearch(): Promise<void> {
            await loadUsers(1)
        }

        async function clearSearch(): Promise<void> {
            search.value = ''

            await loadUsers(1)
        }

        async function previousPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page <= 1
            ) {
                return
            }

            await loadUsers(
                pagination.value.page - 1,
            )
        }

        async function nextPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page
                >= pagination.value.totalPages
            ) {
                return
            }

            await loadUsers(
                pagination.value.page + 1,
            )
        }

        function reset(): void {
            users.value = []
            pagination.value = initialPagination()
            search.value = ''
            isLoading.value = false
            updatingUserId.value = null
            errorMessage.value = null
        }

        return {
            users,
            pagination,
            search,
            isLoading,
            updatingUserId,
            errorMessage,

            loadUsers,
            updateUserStatus,
            submitSearch,
            clearSearch,
            previousPage,
            nextPage,
            reset,
        }
    },
)