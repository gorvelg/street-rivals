import {
    defineStore,
} from 'pinia'

import {
    computed,
    ref,
} from 'vue'

import {
    apiRequest,
    getCollectionMembers,
} from '../services/api'

import type {
    ApiCollection,
    Car,
} from '../types/api'

const STORAGE_KEY =
    'street-rivals-selected-car'

export const usePlayerCarStore =
    defineStore(
        'player-car',
        () => {
            /*
             * =====================================
             * ÉTAT
             * =====================================
             */

            const selectedCarId =
                ref<number | null>(
                    null,
                )

            const car =
                ref<Car | null>(
                    null,
                )

            const loading =
                ref(false)

            const initialized =
                ref(false)

            /*
             * =====================================
             * COMPUTED
             * =====================================
             */

            const hasSelectedCar =
                computed(
                    () =>
                        selectedCarId.value
                        !== null,
                )

            /*
             * =====================================
             * SÉLECTION
             * =====================================
             */

            function selectCar(
                carId: number,
            ): void {
                /*
                 * Si on change de voiture,
                 * on évite de conserver temporairement
                 * l'ancienne voiture dans car.
                 */
                if (
                    selectedCarId.value
                    !== carId
                ) {
                    car.value =
                        null
                }

                selectedCarId.value =
                    carId

                localStorage.setItem(
                    STORAGE_KEY,
                    String(carId),
                )
            }

            function clearSelection():
                void {
                selectedCarId.value =
                    null

                car.value =
                    null

                localStorage.removeItem(
                    STORAGE_KEY,
                )
            }

            /*
             * =====================================
             * RESTAURATION
             * =====================================
             */

            function restoreSelection():
                void {
                const storedValue =
                    localStorage.getItem(
                        STORAGE_KEY,
                    )

                if (
                    storedValue === null
                ) {
                    selectedCarId.value =
                        null

                    initialized.value =
                        true

                    return
                }

                const parsedId =
                    Number.parseInt(
                        storedValue,
                        10,
                    )

                if (
                    !Number.isInteger(
                        parsedId,
                    )
                    || parsedId <= 0
                ) {
                    clearSelection()

                    initialized.value =
                        true

                    return
                }

                selectedCarId.value =
                    parsedId

                initialized.value =
                    true
            }

            /*
             * =====================================
             * CHARGEMENT DE LA VOITURE ACTIVE
             * =====================================
             *
             * IMPORTANT :
             *
             * Notre API n'expose pas :
             *
             * GET /api/cars/{id}
             *
             * On récupère donc la collection :
             *
             * GET /api/cars
             *
             * puis on retrouve la voiture active.
             * =====================================
             */

            async function loadSelectedCar():
                Promise<void> {
                if (
                    !initialized.value
                ) {
                    restoreSelection()
                }

                if (
                    selectedCarId.value
                    === null
                ) {
                    car.value =
                        null

                    return
                }

                loading.value =
                    true

                try {
                    const response =
                        await apiRequest<
                            ApiCollection<Car>
                        >(
                            '/api/cars',
                        )

                    const cars =
                        getCollectionMembers(
                            response,
                        )

                    const selectedCar =
                        cars.find(
                            (candidate) =>
                                candidate.id
                                === selectedCarId.value,
                        )
                        ?? null

                    /*
                     * La voiture sauvegardée n'existe
                     * plus ou n'appartient plus
                     * à l'utilisateur.
                     */
                    if (
                        selectedCar === null
                    ) {
                        clearSelection()

                        return
                    }

                    car.value =
                        selectedCar
                } finally {
                    loading.value =
                        false
                }
            }

            /*
             * =====================================
             * SYNCHRONISATION
             * =====================================
             *
             * Utile lorsque GarageView possède déjà
             * l'objet Car complet.
             */

            function setCar(
                value: Car,
            ): void {
                selectedCarId.value =
                    value.id

                car.value =
                    value

                localStorage.setItem(
                    STORAGE_KEY,
                    String(
                        value.id,
                    ),
                )
            }

            return {
                selectedCarId,
                car,
                loading,
                initialized,
                hasSelectedCar,

                selectCar,
                setCar,
                clearSelection,
                restoreSelection,
                loadSelectedCar,
            }
        },
    )