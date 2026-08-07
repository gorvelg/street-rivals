import type {
    CarBodyDefinition,
} from '../types'

export const retro01:
    CarBodyDefinition = {
    code: 'retro_01',

    label: 'Coupé rétro',

    bodyPath: `
    M 65 181
    C 76 153, 101 143, 139 138
    L 183 132
    C 207 92, 249 67, 305 66
    C 360 66, 401 84, 438 125
    L 513 137
    C 544 142, 568 157, 579 181
    L 582 196
    C 581 207, 571 212, 556 213
    L 519 214
    C 514 182, 489 163, 458 163
    C 426 163, 402 183, 396 214
    L 229 214
    C 223 183, 199 163, 167 163
    C 136 163, 111 183, 105 214
    L 83 211
    C 68 209, 59 198, 62 187
    Z
  `,

    frontWindowPath: `
    M 310 75
    C 346 75, 379 82, 403 97
    L 430 125
    L 343 124
    Z
  `,

    rearWindowPath: `
    M 298 75
    L 329 124
    L 203 124
    C 227 94, 258 77, 298 75
    Z
  `,

    pillarPath: `
    M 304 72
    L 336 128
  `,

    doorLinePath: `
    M 340 130
    L 337 190
  `,

    highlightPath: `
    M 123 147
    C 248 122, 397 123, 509 146
  `,

    frontLightPath: `
    M 522 146
    C 540 146, 551 151, 560 159
    L 549 168
    L 520 163
    Z
  `,

    rearLightPath: `
    M 79 154
    C 92 149, 104 149, 114 152
    L 111 167
    L 78 171
    Z
  `,

    rearWheelX: 167,
    frontWheelX: 458,
    wheelY: 205,
}