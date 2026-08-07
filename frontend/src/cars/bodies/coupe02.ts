import type {
    CarBodyDefinition,
} from '../types'

export const coupe02:
    CarBodyDefinition = {
    code: 'coupe_02',

    label: 'Coupé tuner',

    bodyPath: `
    M 55 181
    C 65 158, 91 145, 131 138
    L 188 129
    C 229 94, 270 75, 321 72
    L 370 73
    C 406 78, 438 98, 468 128
    L 532 138
    C 561 143, 580 160, 590 181
    L 592 195
    C 591 207, 581 213, 566 214
    L 532 214
    C 527 181, 502 162, 470 162
    C 438 162, 413 182, 408 214
    L 220 214
    C 215 182, 190 162, 158 162
    C 126 162, 101 183, 96 214
    L 70 211
    C 55 210, 47 200, 50 187
    Z
  `,

    frontWindowPath: `
    M 325 80
    L 368 81
    C 400 86, 425 101, 452 128
    L 354 126
    Z
  `,

    rearWindowPath: `
    M 312 80
    L 340 126
    L 207 126
    C 237 99, 272 83, 312 80
    Z
  `,

    pillarPath: `
    M 318 77
    L 347 129
  `,

    doorLinePath: `
    M 353 132
    L 349 192
  `,

    highlightPath: `
    M 112 148
    C 252 121, 423 125, 536 148
  `,

    frontLightPath: `
    M 538 147
    L 575 155
    L 568 168
    L 535 164
    Z
  `,

    rearLightPath: `
    M 64 156
    L 100 150
    L 104 167
    L 64 174
    Z
  `,

    rearWheelX: 158,
    frontWheelX: 470,
    wheelY: 204,
}