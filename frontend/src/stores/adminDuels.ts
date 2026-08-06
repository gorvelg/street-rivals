import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminDuel,
    AdminDuelsResponse,
    AdminPagination,
} from '../types/admin'

const initialPagination = (): AdminPagination => ({
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 1,
})

export const useAdminDuelsStore = defineStore(
    'admin-duels',
    () => {
        const duels = ref<AdminDuel[]>([])

        const pagination = ref<AdminPagination>(
            initialPagination(),
        )

        const search = ref<string>('')
        const winnerSide = ref<string>('')
        const engineVersion = ref<string>('')

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadDuels(
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
            const normalizedEngineVersion =
                engineVersion.value.trim()

            if (normalizedSearch !== '') {
                queryParameters.set(
                    'search',
                    normalizedSearch,
                )
            }

            if (
                winnerSide.value === 'attacker'
                || winnerSide.value === 'defender'
            ) {
                queryParameters.set(
                    'winnerSide',
                    winnerSide.value,
                )
            }

            if (normalizedEngineVersion !== '') {
                queryParameters.set(
                    'engineVersion',
                    normalizedEngineVersion,
                )
            }

            try {
                const response =
                    await apiRequest<AdminDuelsResponse>(
                        `/api/admin/duels?${queryParameters.toString()}`,
                    )

                duels.value = response.members
                pagination.value = response.pagination
            } catch (error: unknown) {
                duels.value = []

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger les duels.'
            } finally {
                isLoading.value = false
            }
        }

        async function submitFilters(): Promise<void> {
            await loadDuels(1)
        }

        async function clearFilters(): Promise<void> {
            search.value = ''
            winnerSide.value = ''
            engineVersion.value = ''

            await loadDuels(1)
        }

        async function previousPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page <= 1
            ) {
                return
            }

            await loadDuels(
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

            await loadDuels(
                pagination.value.page + 1,
            )
        }

        function reset(): void {
            duels.value = []
            pagination.value = initialPagination()

            search.value = ''
            winnerSide.value = ''
            engineVersion.value = ''

            isLoading.value = false
            errorMessage.value = null
        }

        return {
            duels,
            pagination,

            search,
            winnerSide,
            engineVersion,

            isLoading,
            errorMessage,

            loadDuels,
            submitFilters,
            clearFilters,
            previousPage,
            nextPage,
            reset,
        }
    },
)