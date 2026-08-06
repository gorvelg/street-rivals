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