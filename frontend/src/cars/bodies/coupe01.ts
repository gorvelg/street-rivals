import type {
    CarBodyDefinition,
} from '../types'

export const coupe01:
    CarBodyDefinition = {
    code: 'coupe_01',

    label: 'Coupé sportif',

    bodyPath: `
    M 61 179
    C 72 156, 95 144, 131 138
    L 190 129
    C 218 94, 252 72, 303 69
    L 356 69
    C 395 73, 428 94, 459 126
    L 521 137
    C 553 142, 575 157, 586 178
    L 590 194
    C 591 205, 582 211, 570 212
    L 521 214
    C 514 186, 491 166, 460 166
    C 428 166, 403 186, 398 214
    L 229 214
    C 224 186, 198 166, 167 166
    C 136 166, 111 186, 106 214
    L 79 211
    C 63 210, 53 199, 56 186
    Z
  `,

    frontWindowPath: `
    M 306 77
    C 340 76, 372 79, 398 92
    L 443 128
    L 344 126
    Z
  `,

    rearWindowPath: `
    M 296 77
    L 329 126
    L 205 126
    C 228 96, 255 80, 296 77
    Z
  `,

    pillarPath: `
    M 302 75
    L 339 129
  `,

    doorLinePath: `
    M 341 132
    L 337 190
  `,

    highlightPath: `
    M 125 147
    C 250 124, 406 125, 520 146
  `,

    frontLightPath: `
    M 530 146
    L 565 154
    C 572 157, 575 162, 571 167
    L 531 164
    Z
  `,

    rearLightPath: `
    M 77 158
    L 106 150
    L 111 166
    L 76 174
    Z
  `,

    rearWheelX: 167,
    frontWheelX: 460,
    wheelY: 205,
}