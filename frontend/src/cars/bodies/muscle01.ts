import type {
    CarBodyDefinition,
} from '../types'

export const muscle01:
    CarBodyDefinition = {
    code: 'muscle_01',

    label: 'Muscle',

    bodyPath: `
    M 51 176
    C 61 157, 85 147, 125 141
    L 195 133
    L 233 93
    C 244 82, 259 77, 278 77
    L 347 77
    C 370 78, 389 88, 408 106
    L 430 130
    L 536 138
    C 565 141, 586 155, 594 176
    L 598 195
    C 598 207, 588 212, 572 213
    L 551 214
    C 545 182, 520 163, 489 163
    C 457 163, 432 183, 427 214
    L 233 214
    C 228 183, 202 163, 170 163
    C 138 163, 113 183, 108 214
    L 72 211
    C 56 210, 47 200, 48 187
    Z
  `,

    frontWindowPath: `
    M 313 84
    L 347 84
    C 368 86, 387 96, 407 116
    L 419 129
    L 346 127
    Z
  `,

    rearWindowPath: `
    M 301 84
    L 333 127
    L 220 127
    L 250 92
    C 260 85, 274 83, 301 84
    Z
  `,

    pillarPath: `
    M 307 80
    L 341 130
  `,

    doorLinePath: `
    M 345 132
    L 341 192
  `,

    highlightPath: `
    M 117 150
    C 257 127, 431 128, 551 149
  `,

    frontLightPath: `
    M 550 145
    L 583 151
    L 578 164
    L 546 162
    Z
  `,

    rearLightPath: `
    M 60 151
    L 100 148
    L 103 165
    L 60 169
    Z
  `,

    rearWheelX: 170,
    frontWheelX: 489,
    wheelY: 205,
}