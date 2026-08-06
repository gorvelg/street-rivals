import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCard,
    AdminCardsOptions,
    AdminCardsResponse,
    AdminPagination,
} from '../types/admin'

const initialPagination = (): AdminPagination => ({
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 1,
})

const initialOptions = (): AdminCardsOptions => ({
    types: [],
    rarities: [],
    tiers: [1, 2, 3],
})

export const useAdminCardsStore = defineStore(
    'admin-cards',
    () => {
        const cards = ref<AdminCard[]>([])

        const pagination = ref<AdminPagination>(
            initialPagination(),
        )

        const options = ref<AdminCardsOptions>(
            initialOptions(),
        )

        const search = ref<string>('')
        const type = ref<string>('')
        const rarity = ref<string>('')
        const tier = ref<string>('')
        const equippedOnly = ref<boolean>(false)

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadCards(
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
            const normalizedType = type.value.trim()
            const normalizedRarity = rarity.value.trim()

            if (normalizedSearch !== '') {
                queryParameters.set(
                    'search',
                    normalizedSearch,
                )
            }

            if (normalizedType !== '') {
                queryParameters.set(
                    'type',
                    normalizedType,
                )
            }

            if (normalizedRarity !== '') {
                queryParameters.set(
                    'rarity',
                    normalizedRarity,
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
                const response =
                    await apiRequest<AdminCardsResponse>(
                        `/api/admin/cards?${queryParameters.toString()}`,
                    )

                cards.value = response.members
                pagination.value = response.pagination
                options.value = response.options
            } catch (error: unknown) {
                cards.value = []

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger les cartes.'
            } finally {
                isLoading.value = false
            }
        }

        async function submitFilters(): Promise<void> {
            await loadCards(1)
        }

        async function clearFilters(): Promise<void> {
            search.value = ''
            type.value = ''
            rarity.value = ''
            tier.value = ''
            equippedOnly.value = false

            await loadCards(1)
        }

        async function previousPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page <= 1
            ) {
                return
            }

            await loadCards(
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

            await loadCards(
                pagination.value.page + 1,
            )
        }

        function reset(): void {
            cards.value = []
            pagination.value = initialPagination()
            options.value = initialOptions()

            search.value = ''
            type.value = ''
            rarity.value = ''
            tier.value = ''
            equippedOnly.value = false

            isLoading.value = false
            errorMessage.value = null
        }

        return {
            cards,
            pagination,
            options,

            search,
            type,
            rarity,
            tier,
            equippedOnly,

            isLoading,
            errorMessage,

            loadCards,
            submitFilters,
            clearFilters,
            previousPage,
            nextPage,
            reset,
        }
    },
)