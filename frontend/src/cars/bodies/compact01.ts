import type {
    CarBodyDefinition,
} from '../types'

export const compact01:
    CarBodyDefinition = {
    code: 'compact_01',

    label: 'Compacte sportive',

    bodyPath: `
    M 92 181
    C 102 158, 124 145, 157 139
    L 194 132
    C 214 99, 243 78, 280 75
    L 353 75
    C 389 78, 413 96, 432 130
    L 500 140
    C 530 145, 551 160, 558 181
    L 560 196
    C 560 207, 551 212, 538 213
    L 505 214
    C 499 183, 477 166, 449 166
    C 419 166, 397 184, 391 214
    L 238 214
    C 233 184, 211 166, 181 166
    C 152 166, 130 184, 124 214
    L 106 211
    C 92 208, 85 196, 88 185
    Z
  `,

    frontWindowPath: `
    M 313 82
    L 350 82
    C 381 85, 399 100, 416 129
    L 342 127
    Z
  `,

    rearWindowPath: `
    M 302 82
    L 329 127
    L 209 127
    C 229 99, 252 85, 302 82
    Z
  `,

    pillarPath: `
    M 306 79
    L 337 130
  `,

    doorLinePath: `
    M 338 133
    L 336 190
  `,

    highlightPath: `
    M 137 149
    C 241 128, 391 129, 492 147
  `,

    frontLightPath: `
    M 502 149
    L 538 157
    L 532 168
    L 499 164
    Z
  `,

    rearLightPath: `
    M 103 155
    L 132 151
    L 136 166
    L 100 172
    Z
  `,

    rearWheelX: 181,
    frontWheelX: 449,
    wheelY: 205,
}