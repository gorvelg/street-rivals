import type {
    CarBodyDefinition,
} from '../types'

export const hatch01:
    CarBodyDefinition = {
    code: 'hatch_01',

    label: 'Hot hatch',

    bodyPath: `
    M 72 180
    C 78 157, 99 143, 133 137
    L 166 132
    L 194 88
    C 202 76, 214 70, 229 70
    L 350 70
    C 388 72, 416 91, 448 128
    L 515 138
    C 548 143, 570 159, 579 180
    L 582 197
    C 581 207, 571 212, 558 213
    L 522 214
    C 517 183, 493 165, 463 165
    C 432 165, 408 184, 403 214
    L 225 214
    C 220 184, 196 165, 165 165
    C 135 165, 111 184, 106 214
    L 85 211
    C 70 209, 63 199, 66 187
    Z
  `,

    frontWindowPath: `
    M 302 78
    L 348 78
    C 381 81, 404 97, 432 128
    L 340 126
    Z
  `,

    rearWindowPath: `
    M 289 78
    L 326 126
    L 188 126
    L 214 82
    C 219 78, 226 77, 236 77
    Z
  `,

    pillarPath: `
    M 294 75
    L 334 130
  `,

    doorLinePath: `
    M 340 132
    L 338 190
  `,

    highlightPath: `
    M 119 148
    C 239 123, 407 127, 513 148
  `,

    frontLightPath: `
    M 521 147
    L 560 156
    C 564 160, 562 165, 557 168
    L 519 164
    Z
  `,

    rearLightPath: `
    M 78 150
    L 111 147
    L 113 168
    L 77 174
    Z
  `,

    rearWheelX: 165,
    frontWheelX: 463,
    wheelY: 205,
}