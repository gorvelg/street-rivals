import {
    computed,
    ref,
} from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCardDetail,
    AdminCardUpdateResponse,
} from '../types/admin'

export const useAdminCardDetailStore = defineStore(
    'admin-card-detail',
    () => {
        const detail = ref<AdminCardDetail | null>(
            null,
        )

        /*
         * Filtres des voitures possédant la carte.
         */
        const search = ref<string>('')
        const tier = ref<string>('')
        const equippedOnly = ref<boolean>(false)

        /*
         * Formulaire d’édition.
         */
        const editName = ref<string>('')
        const editType = ref<string>('')
        const editRarity = ref<string>('')

        const editEffectConfigText =
            ref<string>('{}')

        const isLoading = ref<boolean>(false)
        const isSaving = ref<boolean>(false)

        const errorMessage = ref<string | null>(
            null,
        )

        const saveErrorMessage =
            ref<string | null>(null)

        const validationMessage =
            ref<string | null>(null)

        const successMessage =
            ref<string | null>(null)

        const lastChangedFields = ref<string[]>([])

        const isDirty = computed<boolean>(() => {
            if (detail.value === null) {
                return false
            }

            const currentCard = detail.value.card

            if (
                editName.value.trim()
                !== currentCard.name
            ) {
                return true
            }

            if (
                editType.value.trim()
                !== currentCard.type
            ) {
                return true
            }

            if (
                editRarity.value.trim()
                !== currentCard.rarity
            ) {
                return true
            }

            try {
                const parsedConfiguration =
                    JSON.parse(
                        editEffectConfigText.value,
                    ) as unknown

                if (
                    parsedConfiguration === null
                    || typeof parsedConfiguration
                    !== 'object'
                    || Array.isArray(parsedConfiguration)
                ) {
                    return true
                }

                return JSON.stringify(
                    parsedConfiguration,
                ) !== JSON.stringify(
                    currentCard.effectConfig,
                )
            } catch {
                return true
            }
        })

        function synchronizeEditForm(): void {
            if (detail.value === null) {
                editName.value = ''
                editType.value = ''
                editRarity.value = ''
                editEffectConfigText.value = '{}'

                return
            }

            const card = detail.value.card

            editName.value = card.name
            editType.value = card.type
            editRarity.value = card.rarity

            editEffectConfigText.value =
                JSON.stringify(
                    card.effectConfig,
                    null,
                    2,
                )
        }

        function clearEditMessages(): void {
            saveErrorMessage.value = null
            validationMessage.value = null
            successMessage.value = null
            lastChangedFields.value = []
        }

        function resetEditForm(): void {
            clearEditMessages()
            synchronizeEditForm()
        }

        async function loadCard(
            cardId: number,
            requestedPage = 1,
            synchronizeForm = true,
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

                if (synchronizeForm) {
                    synchronizeEditForm()
                }
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

        async function saveCard(
            cardId: number,
        ): Promise<AdminCardUpdateResponse | null> {
            if (detail.value === null) {
                return null
            }

            clearEditMessages()

            const normalizedName =
                editName.value.trim()

            const normalizedType =
                editType.value.trim()

            const normalizedRarity =
                editRarity.value.trim()

            if (normalizedName === '') {
                validationMessage.value =
                    'Le nom ne peut pas être vide.'

                return null
            }

            if (normalizedType === '') {
                validationMessage.value =
                    'Le type ne peut pas être vide.'

                return null
            }

            if (normalizedRarity === '') {
                validationMessage.value =
                    'La rareté ne peut pas être vide.'

                return null
            }

            let parsedEffectConfig: unknown

            try {
                parsedEffectConfig = JSON.parse(
                    editEffectConfigText.value,
                )
            } catch {
                validationMessage.value =
                    'La configuration des effets contient un JSON invalide.'

                return null
            }

            if (
                parsedEffectConfig === null
                || typeof parsedEffectConfig
                !== 'object'
                || Array.isArray(parsedEffectConfig)
            ) {
                validationMessage.value =
                    'La configuration des effets doit être un objet JSON.'

                return null
            }

            const effectConfig =
                parsedEffectConfig as Record<
                    string,
                    unknown
                >

            const currentCard = detail.value.card

            const payload: Record<
                string,
                unknown
            > = {}

            if (
                normalizedName
                !== currentCard.name
            ) {
                payload.name = normalizedName
            }

            if (
                normalizedType
                !== currentCard.type
            ) {
                payload.type = normalizedType
            }

            if (
                normalizedRarity
                !== currentCard.rarity
            ) {
                payload.rarity = normalizedRarity
            }

            if (
                JSON.stringify(effectConfig)
                !== JSON.stringify(
                    currentCard.effectConfig,
                )
            ) {
                payload.effectConfig = effectConfig
            }

            if (
                Object.keys(payload).length === 0
            ) {
                successMessage.value =
                    'Aucune modification à enregistrer.'

                return null
            }

            isSaving.value = true

            try {
                const response =
                    await apiRequest<AdminCardUpdateResponse>(
                        `/api/admin/cards/${cardId}`,
                        {
                            method: 'PATCH',

                            headers: {
                                'Content-Type':
                                    'application/json',
                            },

                            body: JSON.stringify(payload),
                        },
                    )

                /*
                 * La réponse PATCH ne contient pas les
                 * statistiques d’utilisation. On conserve
                 * donc celles déjà présentes dans la fiche.
                 */
                detail.value.card = {
                    ...detail.value.card,
                    ...response.card,
                }

                lastChangedFields.value =
                    Object.keys(response.changes)

                if (response.updated) {
                    successMessage.value =
                        'La carte a été mise à jour.'
                } else {
                    successMessage.value =
                        'Aucune modification n’a été appliquée.'
                }

                synchronizeEditForm()

                return response
            } catch (error: unknown) {
                saveErrorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible d’enregistrer la carte.'

                return null
            } finally {
                isSaving.value = false
            }
        }

        async function submitFilters(
            cardId: number,
        ): Promise<void> {
            await loadCard(
                cardId,
                1,
                false,
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
                false,
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
                false,
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
                false,
            )
        }

        function reset(): void {
            detail.value = null

            search.value = ''
            tier.value = ''
            equippedOnly.value = false

            editName.value = ''
            editType.value = ''
            editRarity.value = ''
            editEffectConfigText.value = '{}'

            isLoading.value = false
            isSaving.value = false

            errorMessage.value = null
            saveErrorMessage.value = null
            validationMessage.value = null
            successMessage.value = null

            lastChangedFields.value = []
        }

        return {
            detail,

            search,
            tier,
            equippedOnly,

            editName,
            editType,
            editRarity,
            editEffectConfigText,

            isLoading,
            isSaving,
            isDirty,

            errorMessage,
            saveErrorMessage,
            validationMessage,
            successMessage,
            lastChangedFields,

            loadCard,
            saveCard,

            synchronizeEditForm,
            resetEditForm,
            clearEditMessages,

            submitFilters,
            clearFilters,
            previousPage,
            nextPage,

            reset,
        }
    },
)