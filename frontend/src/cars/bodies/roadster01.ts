import type {
    CarBodyDefinition,
} from '../types'

export const roadster01:
    CarBodyDefinition = {
    code: 'roadster_01',

    label: 'Roadster',

    bodyPath: `
    M 65 181
    C 74 158, 99 146, 137 140
    L 216 130
    L 280 123

    C 305 120, 327 120, 349 124

    L 375 126

    L 523 140
    C 553 145, 574 160, 582 181

    L 584 196

    C 583 207, 573 212, 558 213

    L 523 214

    C 517 184, 493 166, 463 166
    C 432 166, 408 184, 402 214

    L 226 214

    C 221 184, 197 166, 166 166
    C 135 166, 112 184, 106 214

    L 82 211

    C 68 209, 59 198, 62 187

    Z
  `,

    /*
     * Pare-brise placé à l'avant du cockpit.
     *
     * L'avant de la voiture est à droite.
     * La base du pare-brise est donc plus proche
     * de la roue avant.
     */
    frontWindowPath: `
    M 357 91

    L 374 91

    L 414 128

    L 392 128

    Z
  `,

    /*
     * Montant avant du pare-brise.
     *
     * Il descend vers l'avant de la voiture.
     */
    pillarPath: `
    M 371 89

    L 414 130
  `,

    /*
     * La porte reste derrière le pare-brise,
     * ce qui rend maintenant la lecture
     * de la silhouette beaucoup plus logique.
     */
    doorLinePath: `
    M 348 132

    L 344 191
  `,

    highlightPath: `
    M 125 150

    C 250 131, 403 132, 518 149
  `,

    frontLightPath: `
    M 531 148

    L 566 155

    L 560 167

    L 528 164

    Z
  `,

    rearLightPath: `
    M 79 157

    L 110 151

    L 113 166

    L 78 173

    Z
  `,

    rearWheelX: 166,

    frontWheelX: 463,

    wheelY: 205,
}