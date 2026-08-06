export interface ApiCollection<T> {
    '@context'?: string
    '@id'?: string
    '@type'?: string
    totalItems?: number
    member?: T[]
    'hydra:member'?: T[]
    'hydra:totalItems'?: number
}

export interface AuthTokenResponse {
    token: string
}

export interface User {
    id?: number
    email: string
    roles?: string[]
}

export interface Car {
    '@id'?: string
    id: number
    pilotName: string
    color: string
    money: number
    speed: number
    acceleration: number
    grip: number
    solidity: number
    level: number
    xp: number
    rating?: number
    wins?: number
    losses?: number
    duelsPlayed?: number
    winRate?: number
}

export interface AppliedCard {
    carCardId: number
    cardId: number
    code: string
    name: string
    tier: number
    stat: string
    value: number
}

export interface CarStats {
    carId: number
    pilotName: string
    level: number

    base: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    bonuses: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    effective: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    appliedCards: AppliedCard[]
}

export type OpponentDifficulty = 'EASY' | 'BALANCED' | 'HARD'

export interface MatchmakingOpponent {
    carId: number
    pilotName: string
    color: string
    level: number
    levelDifference: number

    effectiveStats: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    powerScore: number
    powerDifferencePercent: number
    difficulty: OpponentDifficulty

    potentialRewards: {
        victory: {
            xp: number
            money: number
        }

        defeat: {
            xp: number
            money: number
        }
    }
}
export interface Card {
    '@id'?: string
    id: number
    code: string
    name: string
    description: string
    rarity: string
    type: 'PASSIVE' | 'ACTIVE'
    effectConfig: Record<string, unknown>
}

export type ApiRelation<T> = string | T

export interface CardChoice {
    '@id'?: string
    id: number
    car: ApiRelation<Car>
    level: number
    firstCard: ApiRelation<Card>
    secondCard: ApiRelation<Card>
    selectedCard?: ApiRelation<Card> | null
    selectedAt?: string | null
    createdAt?: string
}

export interface TriggeredCard {
    carCardId: number
    cardId: number
    code: string
    name: string
    tier: number
    stat: string
    value: number
    activationNumber: number
    maxActivations: number
}

export interface DuelEventSide {
    permanentScore?: number
    activeCardBonus?: number
    baseScore?: number
    randomModifier?: number
    score?: number
    triggeredCards?: TriggeredCard[]
}

export interface DuelEvent {
    index: number
    type: string
    label: string

    attacker?: DuelEventSide
    defender?: DuelEventSide

    rawDifference?: number
    gapChange: number
    gapAfter: number
    leader: 'attacker' | 'defender' | 'tie'
}

export interface DuelCarSnapshot {
    carId: number
    pilotName: string
    color: string
    level: number

    base: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    bonuses: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    effective: {
        speed: number
        acceleration: number
        grip: number
        solidity: number
    }

    passiveCards?: AppliedCard[]
    activeCards?: Array<{
        carCardId: number
        cardId: number
        code: string
        name: string
        tier: number
        effect: Record<string, unknown>
    }>
}

export interface DuelReplayData {
    engineVersion: string
    randomSeed: string
    events: DuelEvent[]
    finalGap: number
    winnerCarId: number

    rewards?: {
        multiplier?: number

        attacker: {
            xp: number
            money: number
        }

        defender: {
            xp: number
            money: number
        }
    }

    ranking?: {
        multiplier?: number

        attacker: {
            before: number
            after: number
            delta: number
        }

        defender: {
            before: number
            after: number
            delta: number
        }
    }

    antiFarming?: {
        dailyDuelNumber: number
        pairDuelNumber: number
        rewardMultiplier: number
        ratingMultiplier: number
        dailyLimit: number
        pairDailyLimit: number
        cooldownSeconds: number
        dayTimezone: string
    }
}

export interface Duel {
    '@id'?: string
    id: number

    attackerCar: string
    defenderCar: string
    winnerCar: string

    finalGap: number
    randomSeed: string
    engineVersion: string

    attackerSnapshot: DuelCarSnapshot
    defenderSnapshot: DuelCarSnapshot
    replayData: DuelReplayData

    attackerXpReward: number
    attackerMoneyReward: number
    defenderXpReward: number
    defenderMoneyReward: number

    attackerRatingBefore: number
    attackerRatingAfter: number
    attackerRatingDelta: number

    defenderRatingBefore: number
    defenderRatingAfter: number
    defenderRatingDelta: number

    createdAt: string
}

export interface PendingDuel {
    attackerCarId: number
    attackerPilotName: string
    attackerColor: string

    defenderCarId: number
    defenderPilotName: string
    defenderColor: string

    difficulty: OpponentDifficulty
}