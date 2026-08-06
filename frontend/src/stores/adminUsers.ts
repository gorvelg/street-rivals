import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminPagination,
    AdminUser,
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

        async function submitSearch(): Promise<void> {
            await loadUsers(1)
        }

        async function clearSearch(): Promise<void> {
            search.value = ''

            await loadUsers(1)
        }

        async function previousPage(): Promise<void> {
            if (pagination.value.page <= 1) {
                return
            }

            await loadUsers(
                pagination.value.page - 1,
            )
        }

        async function nextPage(): Promise<void> {
            if (
                pagination.value.page
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
            errorMessage.value = null
        }

        return {
            users,
            pagination,
            search,
            isLoading,
            errorMessage,

            loadUsers,
            submitSearch,
            clearSearch,
            previousPage,
            nextPage,
            reset,
        }
    },
)