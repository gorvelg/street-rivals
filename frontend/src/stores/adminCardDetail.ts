import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCardDetail,
} from '../types/admin'

export const useAdminCardDetailStore = defineStore(
    'admin-card-detail',
    () => {
        const detail = ref<AdminCardDetail | null>(
            null,
        )

        const search = ref<string>('')
        const tier = ref<string>('')
        const equippedOnly = ref<boolean>(false)

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadCard(
            cardId: number,
            requestedPage = 1,
        ): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            const queryParameters =
                new URLSearchParams({
                    page: String(requestedPage),
                    itemsPerPage: String(
                        detail.value?.holders.pagination
                            .itemsPerPage ?? 20,
                    ),
                })

            const normalizedSearch =
                search.value.trim()

            if (normalizedSearch !== '') {
                queryParameters.set(
                    'search',
                    normalizedSearch,
                )
            }

            if (
                tier.value === '1'
                || tier.value === '2'
                || tier.value === '3'
            ) {
                queryParameters.set(
                    'tier',
                    tier.value,
                )
            }

            if (equippedOnly.value) {
                queryParameters.set(
                    'equippedOnly',
                    '1',
                )
            }

            try {
                detail.value =
                    await apiRequest<AdminCardDetail>(
                        `/api/admin/cards/${cardId}?${queryParameters.toString()}`,
                    )
            } catch (error: unknown) {
                detail.value = null

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger cette carte.'
            } finally {
                isLoading.value = false
            }
        }

        async function submitFilters(
            cardId: number,
        ): Promise<void> {
            await loadCard(
                cardId,
                1,
            )
        }

        async function clearFilters(
            cardId: number,
        ): Promise<void> {
            search.value = ''
            tier.value = ''
            equippedOnly.value = false

            await loadCard(
                cardId,
                1,
            )
        }

        async function previousPage(
            cardId: number,
        ): Promise<void> {
            const pagination =
                detail.value?.holders.pagination

            if (
                isLoading.value
                || pagination === undefined
                || pagination.page <= 1
            ) {
                return
            }

            await loadCard(
                cardId,
                pagination.page - 1,
            )
        }

        async function nextPage(
            cardId: number,
        ): Promise<void> {
            const pagination =
                detail.value?.holders.pagination

            if (
                isLoading.value
                || pagination === undefined
                || pagination.page
                >= pagination.totalPages
            ) {
                return
            }

            await loadCard(
                cardId,
                pagination.page + 1,
            )
        }

        function reset(): void {
            detail.value = null

            search.value = ''
            tier.value = ''
            equippedOnly.value = false

            isLoading.value = false
            errorMessage.value = null
        }

        return {
            detail,

            search,
            tier,
            equippedOnly,

            isLoading,
            errorMessage,

            loadCard,
            submitFilters,
            clearFilters,
            previousPage,
            nextPage,
            reset,
        }
    },
)