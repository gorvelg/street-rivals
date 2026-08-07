import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCard,
    AdminCardKind,
    AdminCardsOptions,
    AdminCardsResponse,
    AdminEquipmentSlot,
    AdminPagination,
} from '../types/admin'

const initialPagination =
    (): AdminPagination => ({
        page: 1,
        itemsPerPage: 20,
        totalItems: 0,
        totalPages: 1,
    })

const initialOptions =
    (): AdminCardsOptions => ({
        types: [],
        rarities: [],

        kinds: [
            'ability',
            'equipment',
            'stat_boost',
        ],

        equipmentSlots: [
            'engine',
            'wheels',
            'brakes',
            'gearbox',
            'chassis',
            'aero',
        ],

        tiers: Array.from(
            {
                length: 10,
            },
            (_, index) =>
                index + 1,
        ),
    })

export const useAdminCardsStore =
    defineStore(
        'admin-cards',
        () => {
            const cards =
                ref<AdminCard[]>([])

            const pagination =
                ref<AdminPagination>(
                    initialPagination(),
                )

            const options =
                ref<AdminCardsOptions>(
                    initialOptions(),
                )

            const search =
                ref<string>('')

            const type =
                ref<string>('')

            const rarity =
                ref<string>('')

            const kind =
                ref<AdminCardKind | ''>(
                    '',
                )

            const equipmentSlot =
                ref<
                    AdminEquipmentSlot | ''
                >('')

            const tier =
                ref<string>('')

            const equippedOnly =
                ref<boolean>(false)

            const isLoading =
                ref<boolean>(false)

            const errorMessage =
                ref<string | null>(null)

            function changeKind(): void {
                /*
                 * Un slot ne peut être filtré
                 * que pour un équipement.
                 */
                if (
                    kind.value
                    !== 'equipment'
                ) {
                    equipmentSlot.value =
                        ''
                }
            }

            async function loadCards(
                requestedPage = 1,
            ): Promise<void> {
                isLoading.value = true
                errorMessage.value = null

                const queryParameters =
                    new URLSearchParams({
                        page:
                            String(
                                requestedPage,
                            ),

                        itemsPerPage:
                            String(
                                pagination
                                    .value
                                    .itemsPerPage,
                            ),
                    })

                const normalizedSearch =
                    search.value.trim()

                const normalizedType =
                    type.value.trim()

                const normalizedRarity =
                    rarity.value.trim()

                if (
                    normalizedSearch !== ''
                ) {
                    queryParameters.set(
                        'search',
                        normalizedSearch,
                    )
                }

                if (
                    normalizedType !== ''
                ) {
                    queryParameters.set(
                        'type',
                        normalizedType,
                    )
                }

                if (
                    normalizedRarity !== ''
                ) {
                    queryParameters.set(
                        'rarity',
                        normalizedRarity,
                    )
                }

                if (kind.value !== '') {
                    queryParameters.set(
                        'kind',
                        kind.value,
                    )
                }

                if (
                    kind.value
                    === 'equipment'
                    && equipmentSlot.value
                    !== ''
                ) {
                    queryParameters.set(
                        'equipmentSlot',
                        equipmentSlot.value,
                    )
                }

                const parsedTier =
                    Number.parseInt(
                        tier.value,
                        10,
                    )

                if (
                    Number.isInteger(
                        parsedTier,
                    )
                    && parsedTier >= 1
                    && parsedTier <= 10
                ) {
                    queryParameters.set(
                        'tier',
                        String(parsedTier),
                    )
                }

                if (
                    equippedOnly.value
                ) {
                    queryParameters.set(
                        'equippedOnly',
                        '1',
                    )
                }

                try {
                    const response =
                        await apiRequest<
                            AdminCardsResponse
                        >(
                            `/api/admin/cards?${queryParameters.toString()}`,
                        )

                    cards.value =
                        response.members

                    pagination.value =
                        response.pagination

                    options.value =
                        response.options
                } catch (
                    error: unknown
                    ) {
                    cards.value = []

                    errorMessage.value =
                        error
                        instanceof Error
                            ? error.message
                            : 'Impossible de charger les cartes.'
                } finally {
                    isLoading.value =
                        false
                }
            }

            async function submitFilters():
                Promise<void> {
                await loadCards(1)
            }

            async function clearFilters():
                Promise<void> {
                search.value = ''
                type.value = ''
                rarity.value = ''
                kind.value = ''
                equipmentSlot.value = ''
                tier.value = ''
                equippedOnly.value = false

                await loadCards(1)
            }

            async function previousPage():
                Promise<void> {
                if (
                    isLoading.value
                    || pagination.value.page
                    <= 1
                ) {
                    return
                }

                await loadCards(
                    pagination.value.page
                    - 1,
                )
            }

            async function nextPage():
                Promise<void> {
                if (
                    isLoading.value
                    || pagination.value.page
                    >= pagination.value
                        .totalPages
                ) {
                    return
                }

                await loadCards(
                    pagination.value.page
                    + 1,
                )
            }

            function reset(): void {
                cards.value = []

                pagination.value =
                    initialPagination()

                options.value =
                    initialOptions()

                search.value = ''
                type.value = ''
                rarity.value = ''
                kind.value = ''
                equipmentSlot.value = ''
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
                kind,
                equipmentSlot,
                tier,
                equippedOnly,

                isLoading,
                errorMessage,

                changeKind,

                loadCards,
                submitFilters,
                clearFilters,
                previousPage,
                nextPage,
                reset,
            }
        },
    )