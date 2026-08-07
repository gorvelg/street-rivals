import type {
    CarBodyDefinition,
} from '../types'

export const rally01:
    CarBodyDefinition = {
    code: 'rally_01',

    label: 'Rallye',

    bodyPath: `
    M 72 176
    C 81 151, 102 137, 138 132
    L 173 127
    L 203 84
    C 212 72, 225 66, 242 66
    L 354 66
    C 390 69, 416 91, 445 124
    L 511 133
    C 544 138, 567 153, 578 176
    L 583 195
    C 583 207, 572 213, 557 214
    L 520 214
    C 514 181, 490 161, 459 161
    C 427 161, 401 182, 396 214
    L 231 214
    C 226 182, 200 161, 168 161
    C 136 161, 111 182, 106 214
    L 85 211
    C 70 209, 63 198, 66 185
    Z
  `,

    frontWindowPath: `
    M 309 75
    L 351 75
    C 381 79, 403 95, 429 124
    L 342 123
    Z
  `,

    rearWindowPath: `
    M 297 75
    L 328 123
    L 194 123
    L 217 82
    C 224 76, 233 74, 244 74
    Z
  `,

    pillarPath: `
    M 303 72
    L 335 127
  `,

    doorLinePath: `
    M 341 129
    L 338 188
  `,

    highlightPath: `
    M 122 143
    C 245 119, 400 121, 510 141
  `,

    frontLightPath: `
    M 521 140
    L 560 150
    L 554 164
    L 517 159
    Z
  `,

    rearLightPath: `
    M 84 147
    L 116 142
    L 120 162
    L 82 168
    Z
  `,

    rearWheelX: 168,
    frontWheelX: 459,
    wheelY: 203,
}