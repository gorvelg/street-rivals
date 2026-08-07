import type {
    CarBodyStyle,
} from '../types/api'

export interface CarBodyDefinition {
    code: CarBodyStyle

    label: string

    bodyPath: string

    frontWindowPath: string
    rearWindowPath?: string

    pillarPath?: string
    doorLinePath?: string

    highlightPath: string

    frontLightPath: string
    rearLightPath: string

    rearWheelX: number
    frontWheelX: number
    wheelY: number
}