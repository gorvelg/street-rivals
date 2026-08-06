import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCarCooldownResetResponse,
    AdminCarDetail,
} from '../types/admin'

export const useAdminCarDetailStore = defineStore(
    'admin-car-detail',
    () => {
        const detail = ref<AdminCarDetail | null>(null)

        const isLoading = ref<boolean>(false)
        const isResettingCooldown = ref<boolean>(false)

        const errorMessage = ref<string | null>(null)
        const successMessage = ref<string | null>(null)

        async function loadCar(
            carId: number,
        ): Promise<void> {
            isLoading.value = true
            errorMessage.value = null

            try {
                detail.value =
                    await apiRequest<AdminCarDetail>(
                        `/api/admin/cars/${carId}`,
                    )
            } catch (error: unknown) {
                detail.value = null

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger cette voiture.'
            } finally {
                isLoading.value = false
            }
        }

        async function resetCooldown():
            Promise<AdminCarCooldownResetResponse | null> {
            if (detail.value === null) {
                return null
            }

            const carId = detail.value.car.id

            isResettingCooldown.value = true
            errorMessage.value = null
            successMessage.value = null

            try {
                const response =
                    await apiRequest<AdminCarCooldownResetResponse>(
                        `/api/admin/cars/${carId}/reset-cooldown`,
                        {
                            method: 'POST',
                        },
                    )

                /*
                 * Mise à jour locale immédiate.
                 */
                detail.value.cooldown.active = false
                detail.value.cooldown.activePairCount = 0
                detail.value.cooldown.pairs = []

                if (response.updatedDuelCount === 0) {
                    successMessage.value =
                        'Aucun cooldown actif n’a été trouvé.'
                } else if (response.updatedDuelCount === 1) {
                    successMessage.value =
                        'Le cooldown a été réinitialisé pour un duel.'
                } else {
                    successMessage.value =
                        `Le cooldown a été réinitialisé pour ${response.updatedDuelCount} duels.`
                }

                return response
            } catch (error: unknown) {
                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de réinitialiser le cooldown.'

                throw error
            } finally {
                isResettingCooldown.value = false
            }
        }

        function clearMessages(): void {
            errorMessage.value = null
            successMessage.value = null
        }

        function reset(): void {
            detail.value = null

            isLoading.value = false
            isResettingCooldown.value = false

            errorMessage.value = null
            successMessage.value = null
        }

        return {
            detail,

            isLoading,
            isResettingCooldown,

            errorMessage,
            successMessage,

            loadCar,
            resetCooldown,
            clearMessages,
            reset,
        }
    },
)