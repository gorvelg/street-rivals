import type {
    CarBodyDefinition,
} from '../types'

export const pickup01:
    CarBodyDefinition = {
    code: 'pickup_01',

    label: 'Pick-up sportif',

    /*
     * Avant = droite
     *
     * On cherche ici une silhouette très différente
     * des coupés :
     *
     * - longue benne plate à gauche ;
     * - cabine haute au centre/droite ;
     * - capot horizontal à l'avant ;
     * - passages de roues assez larges.
     */
    bodyPath: `
    M 55 178

    L 66 137

    C 68 130, 75 126, 84 126

    L 249 126

    L 273 89

    C 281 77, 294 71, 310 71

    L 373 71

    C 393 72, 408 81, 423 96

    L 452 128

    L 523 134

    C 555 137, 577 151, 590 174

    L 596 193

    C 598 204, 588 211, 572 213

    L 533 214

    C 527 182, 503 163, 472 163

    C 439 163, 415 183, 409 214

    L 234 214

    C 228 183, 204 163, 172 163

    C 140 163, 115 183, 109 214

    L 78 211

    C 62 209, 53 199, 54 187

    Z
  `,

    /*
     * Vitre avant de la cabine.
     *
     * Le pare-brise est bien situé vers l'avant,
     * donc à droite.
     */
    frontWindowPath: `
    M 354 80

    L 372 80

    C 389 82, 401 89, 413 101

    L 438 128

    L 371 126

    Z
  `,

    /*
     * Vitre latérale arrière de la cabine.
     *
     * Elle permet d'avoir une vraie cabine
     * plutôt qu'un simple pare-brise posé
     * sur la carrosserie.
     */
    rearWindowPath: `
    M 310 80

    L 343 80

    L 357 126

    L 282 126

    L 293 92

    C 297 84, 302 81, 310 80

    Z
  `,

    /*
     * Montant central de cabine.
     */
    pillarPath: `
    M 349 77

    L 364 129
  `,

    /*
     * Séparation de la porte.
     */
    doorLinePath: `
    M 368 132

    L 365 192
  `,

    /*
     * Reflet principal limité volontairement
     * à la cabine + partie avant.
     */
    highlightPath: `
    M 268 137

    C 344 122, 441 123, 524 141
  `,

    /*
     * Phare avant.
     */
    frontLightPath: `
    M 535 142

    L 574 150

    C 580 153, 582 158, 578 164

    L 536 163

    Z
  `,

    /*
     * Feu arrière vertical,
     * très typique d'un pick-up.
     */
    rearLightPath: `
    M 66 137

    L 84 137

    L 84 166

    L 62 170

    Z
  `,

    rearWheelX: 172,

    frontWheelX: 472,

    wheelY: 205,
}