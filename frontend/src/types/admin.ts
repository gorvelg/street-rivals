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
export interface AdminUserDetailAccount {
    id: number
    email: string
    roles: string[]
    isActive: boolean
}

export interface AdminUserDetailStatistics {
    carCount: number
    duelCount: number
    wins: number
    losses: number
    lastLoginAt: string | null
}

export interface AdminUserDetailCarStats {
    speed: number
    acceleration: number
    grip: number
    solidity: number
}

export interface AdminUserDetailCar {
    id: number
    pilotName: string
    color: string

    level: number
    xp: number
    money: number

    rating: number
    wins: number
    losses: number

    stats: AdminUserDetailCarStats
}

export interface AdminUserDetailEvent {
    id: number
    type: string
    carId: number | null
    duelId: number | null
    payload: Record<string, unknown>
    occurredAt: string
}

export interface AdminUserDetailDuelCar {
    id: number
    pilotName: string
    color: string
}

export interface AdminUserDetailDuel {
    id: number

    userCar: AdminUserDetailDuelCar
    opponentCar: AdminUserDetailDuelCar

    winnerCarId: number
    won: boolean
    finalGap: number
    createdAt: string
}

export interface AdminUserDetail {
    user: AdminUserDetailAccount
    statistics: AdminUserDetailStatistics
    cars: AdminUserDetailCar[]
    recentEvents: AdminUserDetailEvent[]
    recentDuels: AdminUserDetailDuel[]
}

export interface AdminCarOwner {
    id: number
    email: string
    isActive: boolean
}

export interface AdminCar {
    id: number
    pilotName: string
    color: string

    owner: AdminCarOwner

    level: number
    xp: number
    money: number
    rating: number

    wins: number
    losses: number
    cardCount: number

    lastDuelAt: string | null
}

export interface AdminCarsFilters {
    search: string | null
    minLevel: number | null
    minRating: number | null
}

export interface AdminCarsResponse {
    members: AdminCar[]
    pagination: AdminPagination
    filters: AdminCarsFilters
}