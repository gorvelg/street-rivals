import type {
    CarBodyDefinition,
} from '../types'

export const super01:
    CarBodyDefinition = {
    code: 'super_01',

    label: 'Supercar',

    bodyPath: `
    M 45 184
    C 56 163, 83 149, 129 143
    L 219 132
    C 253 100, 289 85, 337 83
    L 388 87
    C 420 93, 449 108, 480 133
    L 545 141
    C 572 145, 591 160, 600 181
    L 602 195
    C 601 207, 591 213, 575 214
    L 542 214
    C 536 180, 510 160, 478 160
    C 445 160, 420 181, 414 214
    L 219 214
    C 213 181, 188 160, 155 160
    C 123 160, 97 181, 91 214
    L 63 211
    C 48 210, 39 199, 42 188
    Z
  `,

    frontWindowPath: `
    M 340 91
    L 385 94
    C 415 100, 438 112, 462 132
    L 365 128
    Z
  `,

    rearWindowPath: `
    M 328 91
    L 351 128
    L 237 128
    C 263 106, 291 94, 328 91
    Z
  `,

    pillarPath: `
    M 334 87
    L 358 131
  `,

    doorLinePath: `
    M 365 132
    L 356 192
  `,

    highlightPath: `
    M 111 151
    C 268 126, 441 130, 548 150
  `,

    frontLightPath: `
    M 551 148
    L 587 155
    L 578 166
    L 545 162
    Z
  `,

    rearLightPath: `
    M 57 160
    L 99 152
    L 103 166
    L 57 173
    Z
  `,

    rearWheelX: 155,
    frontWheelX: 478,
    wheelY: 204,
}