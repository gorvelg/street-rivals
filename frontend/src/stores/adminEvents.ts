import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiRequest } from '../services/api'
import type {
    AdminGameEvent,
    AdminGameEventsOptions,
    AdminGameEventsResponse,
    AdminPagination,
} from '../types/admin'

const initialPagination = (): AdminPagination => ({
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    totalPages: 1,
})

const initialOptions =
    (): AdminGameEventsOptions => ({
        types: [],
    })

export const useAdminEventsStore = defineStore(
    'admin-events',
    () => {
        const events = ref<AdminGameEvent[]>([])

        const pagination = ref<AdminPagination>(
            initialPagination(),
        )

        const options = ref<AdminGameEventsOptions>(
            initialOptions(),
        )

        const search = ref<string>('')
        const type = ref<string>('')
        const dateFrom = ref<string>('')
        const dateTo = ref<string>('')

        const isLoading = ref<boolean>(false)
        const errorMessage = ref<string | null>(null)

        async function loadEvents(
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

            const normalizedSearch =
                search.value.trim()

            if (normalizedSearch !== '') {
                queryParameters.set(
                    'search',
                    normalizedSearch,
                )
            }

            if (type.value !== '') {
                queryParameters.set(
                    'type',
                    type.value,
                )
            }

            if (dateFrom.value !== '') {
                queryParameters.set(
                    'dateFrom',
                    dateFrom.value,
                )
            }

            if (dateTo.value !== '') {
                queryParameters.set(
                    'dateTo',
                    dateTo.value,
                )
            }

            try {
                const response =
                    await apiRequest<AdminGameEventsResponse>(
                        `/api/admin/events?${queryParameters.toString()}`,
                    )

                events.value = response.members
                pagination.value = response.pagination
                options.value = response.options
            } catch (error: unknown) {
                events.value = []

                errorMessage.value =
                    error instanceof Error
                        ? error.message
                        : 'Impossible de charger les événements.'
            } finally {
                isLoading.value = false
            }
        }

        async function submitFilters(): Promise<void> {
            await loadEvents(1)
        }

        async function clearFilters(): Promise<void> {
            search.value = ''
            type.value = ''
            dateFrom.value = ''
            dateTo.value = ''

            await loadEvents(1)
        }

        async function previousPage(): Promise<void> {
            if (
                isLoading.value
                || pagination.value.page <= 1
            ) {
                return
            }

            await loadEvents(
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

            await loadEvents(
                pagination.value.page + 1,
            )
        }

        function reset(): void {
            events.value = []
            pagination.value = initialPagination()
            options.value = initialOptions()

            search.value = ''
            type.value = ''
            dateFrom.value = ''
            dateTo.value = ''

            isLoading.value = false
            errorMessage.value = null
        }

        return {
            events,
            pagination,
            options,

            search,
            type,
            dateFrom,
            dateTo,

            isLoading,
            errorMessage,

            loadEvents,
            submitFilters,
            clearFilters,
            previousPage,
            nextPage,
            reset,
        }
    },
)