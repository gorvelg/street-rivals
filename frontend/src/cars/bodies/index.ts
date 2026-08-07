import {
    compact01,
} from './compact01'

import {
    hatch01,
} from './hatch01'

import {
    coupe01,
} from './coupe01'

import {
    muscle01,
} from './muscle01'

import {
    roadster01,
} from './roadster01'

import {
    sedan01,
} from './sedan01'

import {
    rally01,
} from './rally01'

import {
    super01,
} from './super01'

import {
    pickup01,
} from './pickup01'

import type {
    CarBodyStyle,
} from '../../types/api'

import type {
    CarBodyDefinition,
} from '../types'

export const CAR_BODY_DEFINITIONS:
    Record<
        CarBodyStyle,
        CarBodyDefinition
    > = {
    [compact01.code]:
    compact01,

    [hatch01.code]:
    hatch01,

    [coupe01.code]:
    coupe01,

    [muscle01.code]:
    muscle01,

    [roadster01.code]:
    roadster01,

    [sedan01.code]:
    sedan01,

    [rally01.code]:
    rally01,

    [super01.code]:
    super01,

    [pickup01.code]:
    pickup01,

}

/*
 * Liste prête à être utilisée par
 * l'interface de personnalisation.
 */
export const CAR_BODIES:
    CarBodyDefinition[] =
    Object.values(
        CAR_BODY_DEFINITIONS,
    )