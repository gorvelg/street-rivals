import {
    computed,
    ref,
} from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminGameSetting,
    AdminGameSettingsResponse,
    AdminGameSettingsUpdateResponse,
} from '../types/admin'

export const useAdminGameSettingsStore =
    defineStore(
        'admin-game-settings',
        () => {
            const settings =
                ref<AdminGameSetting[]>([])

            const draftValues =
                ref<Record<string, number>>({})

            const isLoading = ref<boolean>(false)
            const isSaving = ref<boolean>(false)

            const errorMessage =
                ref<string | null>(null)

            const successMessage =
                ref<string | null>(null)

            const isDirty = computed<boolean>(() => {
                return settings.value.some(
                    (setting) => {
                        return draftValues.value[
                            setting.key
                            ] !== setting.value
                    },
                )
            })

            function synchronizeDraft(): void {
                draftValues.value =
                    Object.fromEntries(
                        settings.value.map(
                            (setting) => [
                                setting.key,
                                setting.value,
                            ],
                        ),
                    )
            }

            async function loadSettings():
                Promise<void> {
                isLoading.value = true
                errorMessage.value = null
                successMessage.value = null

                try {
                    const response =
                        await apiRequest<AdminGameSettingsResponse>(
                            '/api/admin/game-settings',
                        )

                    settings.value = response.members
                    synchronizeDraft()
                } catch (error: unknown) {
                    settings.value = []

                    errorMessage.value =
                        error instanceof Error
                            ? error.message
                            : 'Impossible de charger les paramètres.'
                } finally {
                    isLoading.value = false
                }
            }

            async function saveSettings():
                Promise<void> {
                errorMessage.value = null
                successMessage.value = null

                const changedValues:
                    Record<string, number> = {}

                for (const setting of settings.value) {
                    const draftValue =
                        draftValues.value[setting.key]

                    if (
                        draftValue !== setting.value
                    ) {
                        changedValues[setting.key] =
                            draftValue
                    }
                }

                if (
                    Object.keys(changedValues)
                        .length === 0
                ) {
                    successMessage.value =
                        'Aucune modification à enregistrer.'

                    return
                }

                isSaving.value = true

                try {
                    const response =
                        await apiRequest<AdminGameSettingsUpdateResponse>(
                            '/api/admin/game-settings',
                            {
                                method: 'PATCH',

                                headers: {
                                    'Content-Type':
                                        'application/json',
                                },

                                body: JSON.stringify({
                                    values: changedValues,
                                }),
                            },
                        )

                    settings.value = response.settings
                    synchronizeDraft()

                    successMessage.value =
                        response.updated
                            ? 'Les paramètres ont été enregistrés.'
                            : 'Aucune modification n’a été appliquée.'
                } catch (error: unknown) {
                    errorMessage.value =
                        error instanceof Error
                            ? error.message
                            : 'Impossible d’enregistrer les paramètres.'
                } finally {
                    isSaving.value = false
                }
            }

            function resetDraft(): void {
                synchronizeDraft()
                errorMessage.value = null
                successMessage.value = null
            }

            function reset(): void {
                settings.value = []
                draftValues.value = {}

                isLoading.value = false
                isSaving.value = false

                errorMessage.value = null
                successMessage.value = null
            }

            return {
                settings,
                draftValues,

                isLoading,
                isSaving,
                isDirty,

                errorMessage,
                successMessage,

                loadSettings,
                saveSettings,
                resetDraft,
                reset,
            }
        },
    )