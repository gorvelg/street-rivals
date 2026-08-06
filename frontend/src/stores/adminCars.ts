import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminCar,
    AdminCarsResponse,
    AdminPagination,
} from '../types/admin'

const initialPagination = (): AdminPagination => ({
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 1,
})

export const useAdminCarsStore = defineStore(
    'admin-cars',
    () => {
        const cars = ref<AdminCar[]>([])

        const pagination = ref<AdminPagination>(
            initialPagination(),
        )

        const search = ref<string>('')

        /*
         * Les inputs de type number renvoient des nombres.
         */
        const minLevel = ref<number | null>(null)
        const minRating = ref<number | null>(null)

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadCars(
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

            if (
                minLevel.value !== null
                && Number.isFinite(minLevel.value)
                && minLevel.value >= 1
            ) {
                queryParameters.set(
                    'minLevel',
                    String(minLevel.value),
                )
            }

            if (
                minRating.value !== null
                && Number.isFinite(minRating.value)
                && minRating.value >= 0
            ) {
                queryParameters.set(
                    'minRating',
                    String(minRating.value),
                )
            }

            try {
                const response =
                    await apiRequest<AdminCarsResponse>(
                        `/api/admin/cars?${queryParameters.toString()}`,
                    )

                cars.value = response.members
                pagination.value = response.pagination
            } catch (error: unknown) {
                cars.value = []

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger les voitures.'
            } finally {
                isLoading.value = false
            }
        }

        async function submitFilters(): Promise<void> {
            await loadCars(1)
        }

        async function clearFilters(): Promise<void> {
            search.value = ''
            minLevel.value = null
            minRating.value = null

            await loadCars(1)
        }

        async function previousPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page <= 1
            ) {
                return
            }

            await loadCars(
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

            await loadCars(
                pagination.value.page + 1,
            )
        }

        function reset(): void {
            cars.value = []
            pagination.value = initialPagination()

            search.value = ''
            minLevel.value = null
            minRating.value = null

            isLoading.value = false
            errorMessage.value = null
        }

        return {
            cars,
            pagination,

            search,
            minLevel,
            minRating,

            isLoading,
            errorMessage,

            loadCars,
            submitFilters,
            clearFilters,
            previousPage,
            nextPage,
            reset,
        }
    },
)