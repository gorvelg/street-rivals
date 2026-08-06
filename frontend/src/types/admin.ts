export interface AdminDashboardUsers {
    total: number
    registeredToday: number
    activeToday: number
}

export interface AdminDashboardCars {
    total: number
    createdToday: number
}

export interface AdminDashboardDuels {
    total: number
    today: number
}

export interface AdminDashboardProgression {
    levelUpsToday: number
    cardsSelectedToday: number
    cardsUpgradedToday: number
}

export interface AdminDashboard {
    generatedAt: string
    timezone: string

    users: AdminDashboardUsers
    cars: AdminDashboardCars
    duels: AdminDashboardDuels
    progression: AdminDashboardProgression
}

export interface AdminUser {
    id: number
    email: string
    roles: string[]
    isActive: boolean
    carCount: number
}

export interface AdminPagination {
    page: number
    itemsPerPage: number
    totalItems: number
    totalPages: number
}

export interface AdminUsersFilters {
    search: string | null
}

export interface AdminUsersResponse {
    members: AdminUser[]
    pagination: AdminPagination
    filters: AdminUsersFilters
}

export interface AdminUserStatusResponse {
    id: number
    email: string
    roles: string[]
    isActive: boolean
}