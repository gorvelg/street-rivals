import type {
    CarBodyDefinition,
} from '../types'

export const sedan01:
    CarBodyDefinition = {
    code: 'sedan_01',

    label: 'Berline sportive',

    bodyPath: `
    M 58 180
    C 69 157, 92 145, 131 139
    L 177 132
    L 220 87
    C 232 75, 249 69, 269 69
    L 365 69
    C 390 71, 411 82, 430 99
    L 460 129
    L 526 138
    C 557 143, 578 158, 587 180
    L 590 196
    C 589 207, 579 212, 564 213
    L 530 214
    C 524 183, 500 165, 469 165
    C 438 165, 414 184, 408 214
    L 229 214
    C 224 183, 199 165, 168 165
    C 137 165, 113 184, 107 214
    L 76 211
    C 61 210, 52 200, 55 187
    Z
  `,

    frontWindowPath: `
    M 321 78
    L 361 78
    C 385 81, 404 91, 423 108
    L 444 128
    L 348 126
    Z
  `,

    rearWindowPath: `
    M 309 78
    L 334 126
    L 195 126
    L 229 91
    C 241 81, 254 78, 270 78
    Z
  `,

    pillarPath: `
    M 314 75
    L 341 130
  `,

    doorLinePath: `
    M 347 132
    L 345 191
  `,

    highlightPath: `
    M 122 148
    C 253 124, 413 125, 526 147
  `,

    frontLightPath: `
    M 536 147
    L 573 155
    L 566 168
    L 533 164
    Z
  `,

    rearLightPath: `
    M 72 155
    L 108 150
    L 111 167
    L 71 173
    Z
  `,

    rearWheelX: 168,
    frontWheelX: 469,
    wheelY: 205,
}