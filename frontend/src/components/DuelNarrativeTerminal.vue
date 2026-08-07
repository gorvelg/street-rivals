<script setup lang="ts">
import {
  computed,
  nextTick,
  ref,
  watch,
} from 'vue'

import type {
  DuelEvent,
  DuelEventSide,
  NarrativeLineType,
} from '../types/api'

interface TerminalLine {
  id: string

  type:
      | 'section'
      | 'action'
      | 'card'
      | 'cardEffect'
      | 'reaction'
      | 'status'
      | 'result'
      | 'separator'

  text: string

  tone?:
      | 'normal'
      | 'good'
      | 'bad'
      | 'important'
}

type EventContext =
    | 'start'
    | 'straight'
    | 'turn'
    | 'chicane'
    | 'finalSprint'
    | 'braking'
    | 'acceleration'
    | 'photoFinish'
    | 'other'

type Performance =
    | 'dominant'
    | 'good'
    | 'even'
    | 'poor'
    | 'bad'

const props = defineProps<{
  events: DuelEvent[]
  attackerName: string
  defenderName: string
  replayFinished: boolean
  winnerName: string
  finalGap: number
}>()

const terminalElement =
    ref<HTMLElement | null>(null)

const lines = computed<TerminalLine[]>(() => {
  const result: TerminalLine[] = []

  for (const event of props.events) {
    result.push(
        ...buildEventLines(event),
    )
  }

  if (props.replayFinished) {
    appendResult(result)
  }

  return result
})

function buildEventLines(
    event: DuelEvent,
): TerminalLine[] {
  const result: TerminalLine[] = []

  result.push({
    id: `${event.index}-section`,
    type: 'section',
    text: eventTitle(event),
  })

  /*
   * Le photo-finish généré par le backend
   * ne contient pas de scores attaquant/défenseur.
   */
  if (
      event.type === 'photo_finish'
      || (
          event.attacker === undefined
          && event.defender === undefined
      )
  ) {
    result.push({
      id: `${event.index}-photo-finish`,
      type: 'status',
      tone: 'important',
      text:
          'Les deux voitures franchissent la ligne pratiquement ensemble. La photo doit les départager !',
    })

    return result
  }

  const attackerScore =
      getScore(event.attacker)

  const defenderScore =
      getScore(event.defender)

  /*
   * Attaquant
   */
  result.push({
    id: `${event.index}-attacker`,
    type: 'action',

    text: buildActionText(
        props.attackerName,
        event,
        attackerScore,
        defenderScore,
        'attacker',
    ),

    tone: scoreTone(
        attackerScore,
        defenderScore,
    ),
  })

  appendTriggeredCards(
      result,
      event,
      props.attackerName,
      'attacker',
      event.attacker,
  )

  /*
   * Défenseur
   */
  result.push({
    id: `${event.index}-defender`,
    type: 'action',

    text: buildActionText(
        props.defenderName,
        event,
        defenderScore,
        attackerScore,
        'defender',
    ),

    tone: scoreTone(
        defenderScore,
        attackerScore,
    ),
  })

  appendTriggeredCards(
      result,
      event,
      props.defenderName,
      'defender',
      event.defender,
  )

  /*
   * Évolution de la course.
   */
  const status =
      buildGapNarration(event)

  if (status !== null) {
    result.push({
      id: `${event.index}-status`,
      type: 'status',
      text: status.text,
      tone: status.tone,
    })
  }

  return result
}

function buildActionText(
    pilotName: string,
    event: DuelEvent,
    ownScore: number,
    opponentScore: number,
    sideName: 'attacker' | 'defender',
): string {
  const context =
      getEventContext(event)

  const difference =
      ownScore - opponentScore

  const performance =
      getPerformance(difference)

  /*
   * Sélection déterministe.
   *
   * Le même événement gardera toujours
   * la même formulation lors d'un replay.
   */
  const seed =
      event.index
      + (
          sideName === 'attacker'
              ? 0
              : 17
      )

  const phrase =
      getActionPhrase(
          context,
          performance,
          seed,
      )

  return (
      `${pilotName} ${phrase}` +
      ` : ${formatSigned(ownScore)}`
  )
}

function getActionPhrase(
    context: EventContext,
    performance: Performance,
    seed: number,
): string {
  return pick(
      getPhrasePool(
          context,
          performance,
      ),
      seed,
  )
}

function getPhrasePool(
    context: EventContext,
    performance: Performance,
): readonly string[] {
  /*
   * DÉPART
   */
  if (context === 'start') {
    switch (performance) {
      case 'dominant':
        return [
          's’arrache de la grille',
          'réussit un départ canon',
          'bondit dès l’extinction des feux',
          'jaillit de la ligne de départ',
        ]

      case 'good':
        return [
          'réagit parfaitement au départ',
          's’élance très proprement',
          'prend un excellent envol',
          'trouve immédiatement de la motricité',
        ]

      case 'even':
        return [
          's’élance sans perdre une seconde',
          'reste parfaitement dans le rythme',
          'prend un départ propre',
          'reste immédiatement au contact',
        ]

      case 'poor':
        return [
          'patine légèrement au départ',
          'perd quelques mètres au démarrage',
          'doit déjà courir après son adversaire',
          'manque légèrement son envol',
        ]

      case 'bad':
        return [
          'reste scotché quelques instants',
          'rate complètement son envol',
          'se fait surprendre au départ',
          'laisse immédiatement filer son adversaire',
        ]
    }
  }

  /*
   * LIGNE DROITE
   */
  if (context === 'straight') {
    switch (performance) {
      case 'dominant':
        return [
          'écrase l’accélérateur et s’envole',
          'déchaîne toute la puissance du moteur',
          'avale la ligne droite',
          'transforme la ligne droite en piste de décollage',
          'fait rugir le moteur et disparaît vers l’horizon',
        ]

      case 'good':
        return [
          'accélère franchement',
          'fait parler la puissance',
          'gagne rapidement de la vitesse',
          'allonge parfaitement ses rapports',
          'profite pleinement de sa vitesse de pointe',
        ]

      case 'even':
        return [
          'reste pied au plancher',
          'maintient la pression',
          'accélère sans rien lâcher',
          'reste dans l’aspiration',
          'refuse de céder le moindre mètre',
        ]

      case 'poor':
        return [
          'tente de rester dans l’aspiration',
          'manque légèrement de vitesse de pointe',
          's’accroche dans la ligne droite',
          'voit son adversaire prendre quelques mètres',
          'cherche désespérément un peu plus de vitesse',
        ]

      case 'bad':
        return [
          'se fait déposer dans la ligne droite',
          'semble manquer cruellement de puissance',
          'voit son adversaire s’échapper',
          'n’arrive pas à suivre le rythme',
          'perd énormément de terrain à pleine vitesse',
        ]
    }
  }

  /*
   * VIRAGE
   */
  if (context === 'turn') {
    switch (performance) {
      case 'dominant':
        return [
          'plonge à la corde comme sur des rails',
          'attaque le virage sans lever le pied',
          'enchaîne le virage avec une précision chirurgicale',
          'passe le virage à une vitesse impressionnante',
          'frôle le point de corde sans perdre de vitesse',
        ]

      case 'good':
        return [
          'négocie parfaitement le virage',
          'prend une trajectoire très propre',
          'attaque fort à l’entrée du virage',
          'ressort très vite du virage',
          'place parfaitement sa voiture',
        ]

      case 'even':
        return [
          'reste propre dans le virage',
          'tient parfaitement sa trajectoire',
          'passe le virage sans perdre de terrain',
          'reste au contact dans la courbe',
          'garde la voiture parfaitement équilibrée',
        ]

      case 'poor':
        return [
          'élargit légèrement sa trajectoire',
          'doit lever le pied dans le virage',
          'manque un peu son point de corde',
          'perd quelques mètres dans la courbe',
          'corrige légèrement sa trajectoire',
        ]

      case 'bad':
        return [
          'se bat avec la voiture dans le virage',
          'rate complètement sa trajectoire',
          'sort beaucoup trop large',
          'doit fortement ralentir pour garder la voiture sur la route',
          'perd complètement le rythme dans la courbe',
        ]
    }
  }

  /*
   * CHICANE
   */
  if (context === 'chicane') {
    switch (performance) {
      case 'dominant':
        return [
          'avale la chicane sans presque ralentir',
          'enchaîne les changements d’appui avec une précision folle',
          'danse entre les vibreurs sans perdre de vitesse',
          'traverse la chicane comme sur des rails',
        ]

      case 'good':
        return [
          'enchaîne proprement les changements d’appui',
          'attaque franchement la chicane',
          'se faufile parfaitement entre les vibreurs',
          'garde un excellent rythme dans la chicane',
        ]

      case 'even':
        return [
          'reste propre dans la chicane',
          'enchaîne sans perdre de terrain',
          'reste parfaitement au contact',
          'contrôle ses changements d’appui',
        ]

      case 'poor':
        return [
          'perd un peu de temps dans la chicane',
          'doit corriger sa trajectoire entre les vibreurs',
          'manque de fluidité dans les changements d’appui',
          'laisse quelques mètres dans la chicane',
        ]

      case 'bad':
        return [
          'se désunit complètement dans la chicane',
          'saute maladroitement d’un vibreur à l’autre',
          'perd énormément de vitesse dans la chicane',
          'doit casser son rythme pour garder le contrôle',
        ]
    }
  }

  /*
   * SPRINT FINAL
   */
  if (context === 'finalSprint') {
    switch (performance) {
      case 'dominant':
        return [
          'jette toutes ses forces dans le sprint final',
          'libère toute la puissance disponible',
          's’envole dans les derniers mètres',
          'écrase l’accélérateur pour porter le coup de grâce',
        ]

      case 'good':
        return [
          'accélère fort vers la ligne',
          'trouve encore de la vitesse dans les derniers mètres',
          'pousse la voiture jusqu’à la limite',
          'attaque pleinement le sprint final',
        ]

      case 'even':
        return [
          'donne tout jusqu’à la ligne',
          'reste pied au plancher',
          'refuse de céder dans les derniers mètres',
          'reste collé à son adversaire',
        ]

      case 'poor':
        return [
          'tente de trouver une dernière accélération',
          's’accroche jusqu’à la ligne',
          'manque légèrement de puissance dans le sprint',
          'voit la ligne arriver trop vite',
        ]

      case 'bad':
        return [
          'n’a plus rien à répondre dans le sprint final',
          'voit son adversaire s’envoler vers l’arrivée',
          'manque complètement de vitesse dans les derniers mètres',
          'subit le sprint jusqu’à la ligne',
        ]
    }
  }

  /*
   * FREINAGE
   *
   * Prévu pour les futurs événements.
   */
  if (context === 'braking') {
    switch (performance) {
      case 'dominant':
        return [
          'repousse son freinage jusqu’à la dernière seconde',
          'plante un freinage extrêmement tardif',
          'freine au dernier moment sans perdre le contrôle',
        ]

      case 'good':
        return [
          'retarde parfaitement son freinage',
          'gagne du terrain au freinage',
          'dose parfaitement les freins',
        ]

      case 'even':
        return [
          'freine proprement',
          'reste à hauteur au freinage',
          'contrôle parfaitement son entrée',
        ]

      case 'poor':
        return [
          'freine un peu trop tôt',
          'laisse quelques mètres au freinage',
          'manque légèrement son freinage',
        ]

      case 'bad':
        return [
          'écrase les freins beaucoup trop tôt',
          'rate complètement son freinage',
          'perd énormément de terrain au freinage',
        ]
    }
  }

  /*
   * RELANCE
   *
   * Prévu pour les futurs événements.
   */
  if (context === 'acceleration') {
    switch (performance) {
      case 'dominant':
        return [
          'remet les gaz très tôt et bondit',
          'catapulte la voiture à la sortie',
          'trouve une motricité parfaite à la relance',
        ]

      case 'good':
        return [
          'ressort très fort et remet immédiatement les gaz',
          'réussit une excellente relance',
          'retrouve rapidement toute sa vitesse',
        ]

      case 'even':
        return [
          'relance proprement la voiture',
          'reste dans le rythme à la sortie',
          'remet les gaz sans perdre de terrain',
        ]

      case 'poor':
        return [
          'manque légèrement sa relance',
          'patine un instant à la remise des gaz',
          'perd quelques mètres à la sortie',
        ]

      case 'bad':
        return [
          'rate complètement sa sortie',
          'n’arrive pas à remettre la puissance au sol',
          'perd beaucoup de temps à la relance',
        ]
    }
  }

  /*
   * FALLBACK
   */
  switch (performance) {
    case 'dominant':
      return [
        'domine complètement cette portion',
        'impose un rythme infernal',
        'fait clairement la différence',
      ]

    case 'good':
      return [
        'prend l’avantage sur cette portion',
        'réalise un passage très propre',
        'gagne du terrain',
      ]

    case 'even':
      return [
        'reste au contact',
        'ne concède rien',
        'reste dans le rythme',
      ]

    case 'poor':
      return [
        'concède quelques mètres',
        'perd légèrement du terrain',
        'subit le rythme adverse',
      ]

    case 'bad':
      return [
        'est complètement débordé',
        'perd beaucoup de terrain',
        'subit lourdement cette portion',
      ]
  }
}

function getPerformance(
    difference: number,
): Performance {
  if (difference >= 7) {
    return 'dominant'
  }

  if (difference >= 3) {
    return 'good'
  }

  if (difference <= -7) {
    return 'bad'
  }

  if (difference <= -3) {
    return 'poor'
  }

  return 'even'
}

function scoreTone(
    score: number,
    opponentScore: number,
):
    | 'normal'
    | 'good'
    | 'bad' {
  const difference =
      score - opponentScore

  if (difference >= 3) {
    return 'good'
  }

  if (difference <= -3) {
    return 'bad'
  }

  return 'normal'
}

/*
 * CARTES ACTIVES
 *
 * À partir du moteur 1.2.0, le frontend
 * n'invente plus les textes propres aux cartes.
 *
 * Les phrases viennent directement du replay,
 * donc du CardNarrativeService côté backend.
 */
function appendTriggeredCards(
    result: TerminalLine[],
    event: DuelEvent,
    pilotName: string,
    sideName: 'attacker' | 'defender',
    side: DuelEventSide | undefined,
): void {
  const cards =
      side?.triggeredCards ?? []

  for (const card of cards) {
    /*
     * Nouveau format moteur 1.2.0.
     */
    if (
        card.narrativeLines !== undefined
        && card.narrativeLines.length > 0
    ) {
      for (
          const [
            narrativeIndex,
            narrativeLine,
          ] of card.narrativeLines.entries()
          ) {
        result.push({
          id:
              `${event.index}` +
              `-${sideName}` +
              `-card-${card.carCardId}` +
              `-${card.activationNumber}` +
              `-${narrativeIndex}`,

          type:
              getNarrativeLineType(
                  narrativeLine.type,
              ),

          text:
          narrativeLine.text,

          tone:
              getNarrativeTone(
                  narrativeLine.type,
              ),
        })
      }

      continue
    }

    /*
     * Compatibilité avec les anciens replays
     * moteur 1.1.0.
     *
     * Aucun texte spécifique à une carte
     * n'est codé dans le frontend.
     */
    result.push({
      id:
          `${event.index}` +
          `-${sideName}` +
          `-card-${card.carCardId}` +
          `-${card.activationNumber}`,

      type: 'card',

      text:
          `${pilotName} utilise ${card.name}` +
          ` : ${formatSigned(card.value)}` +
          ` ${statLabel(card.stat)}.`,

      tone: 'important',
    })
  }
}

function getNarrativeLineType(
    type: NarrativeLineType,
): TerminalLine['type'] {
  switch (type) {
    case 'card_activation':
      return 'card'

    case 'card_effect':
      return 'cardEffect'

    case 'card_reaction':
      return 'reaction'
  }
}

function getNarrativeTone(
    type: NarrativeLineType,
): TerminalLine['tone'] {
  switch (type) {
    case 'card_activation':
      return 'important'

    case 'card_effect':
      return 'good'

    case 'card_reaction':
      return 'normal'
  }
}

function buildGapNarration(
    event: DuelEvent,
): {
  text: string

  tone:
      | 'normal'
      | 'good'
      | 'bad'
      | 'important'
} | null {
  const after =
      event.gapAfter

  const before =
      after - event.gapChange

  const previousLeader =
      leaderFromGap(before)

  const currentLeader =
      leaderFromGap(after)

  /*
   * Changement de leader.
   */
  if (
      previousLeader !== currentLeader
      && currentLeader !== 'tie'
  ) {
    const leaderName =
        currentLeader === 'attacker'
            ? props.attackerName
            : props.defenderName

    return {
      text: pick(
          [
            `${leaderName} prend la tête !`,
            `${leaderName} passe devant !`,
            `${leaderName} renverse la situation et prend l’avantage !`,
            `${leaderName} trouve l’ouverture et s’empare de la première place !`,
          ],
          event.index,
      ),

      tone: 'important',
    }
  }

  /*
   * Retour à égalité.
   */
  if (currentLeader === 'tie') {
    return {
      text: pick(
          [
            'Les deux voitures sont côte à côte !',
            'Impossible de les départager pour le moment.',
            'Les deux pilotes reviennent exactement à hauteur.',
            'Personne ne veut céder un centimètre !',
          ],
          event.index,
      ),

      tone: 'important',
    }
  }

  const leaderName =
      currentLeader === 'attacker'
          ? props.attackerName
          : props.defenderName

  const chaserName =
      currentLeader === 'attacker'
          ? props.defenderName
          : props.attackerName

  const previousGap =
      Math.abs(before)

  const currentGap =
      Math.abs(after)

  const movement =
      currentGap - previousGap

  /*
   * Duel extrêmement serré.
   */
  if (currentGap <= 2) {
    return {
      text: pick(
          [
            `${chaserName} reste dans le pare-chocs de ${leaderName}.`,
            `${leaderName} n’arrive pas à décrocher ${chaserName}.`,
            'Les deux voitures sont presque côte à côte.',
            `${chaserName} met une pression énorme sur ${leaderName}.`,
          ],
          event.index,
      ),

      tone: 'normal',
    }
  }

  /*
   * Grosse augmentation de l'écart.
   */
  if (movement >= 5) {
    return {
      text: pick(
          [
            `${leaderName} s’échappe !`,
            `${leaderName} fait le trou !`,
            `${leaderName} inflige un gros coup à son adversaire.`,
            `${leaderName} prend une avance considérable !`,
          ],
          event.index,
      ),

      tone: 'important',
    }
  }

  /*
   * Petite augmentation.
   */
  if (movement > 0) {
    return {
      text: pick(
          [
            `${leaderName} creuse légèrement l’écart.`,
            `${leaderName} prend un peu d’air.`,
            `${leaderName} gagne quelques mètres.`,
            `${leaderName} consolide son avance.`,
          ],
          event.index,
      ),

      tone: 'good',
    }
  }

  /*
   * Gros retour.
   */
  if (movement <= -5) {
    return {
      text: pick(
          [
            `${chaserName} revient comme une balle sur ${leaderName} !`,
            `${chaserName} efface une grosse partie de son retard !`,
            `${chaserName} fond sur ${leaderName} !`,
            `${leaderName} voit soudainement ${chaserName} revenir dans ses rétroviseurs !`,
          ],
          event.index,
      ),

      tone: 'important',
    }
  }

  /*
   * Petit retour.
   */
  if (movement < 0) {
    return {
      text: pick(
          [
            `${chaserName} grignote son retard.`,
            `${chaserName} revient progressivement.`,
            `${leaderName} voit ${chaserName} revenir dans ses rétroviseurs.`,
            `${chaserName} réduit peu à peu l’écart.`,
          ],
          event.index,
      ),

      tone: 'normal',
    }
  }

  return {
    text:
        'L’écart reste parfaitement stable.',

    tone:
        'normal',
  }
}

function appendResult(
    result: TerminalLine[],
): void {
  result.push({
    id: 'result-separator',
    type: 'separator',
    text: '',
  })

  result.push({
    id: 'result-winner',
    type: 'result',
    tone: 'important',

    text:
        `${props.winnerName} franchit la ligne en tête !`,
  })

  const gap =
      Math.abs(props.finalGap)

  let resultText: string

  if (gap <= 1) {
    resultText =
        'Victoire sur le fil !'

  } else if (gap <= 3) {
    resultText =
        `Victoire très disputée avec ${gap} points d’avance.`

  } else if (gap <= 7) {
    resultText =
        `Victoire solide avec ${gap} points d’avance.`

  } else if (gap <= 12) {
    resultText =
        `Large victoire avec ${gap} points d’avance.`

  } else {
    resultText =
        `Démonstration totale : ${gap} points d’avance !`
  }

  result.push({
    id: 'result-gap',
    type: 'result',
    tone: 'important',
    text: resultText,
  })
}

function getScore(
    side: DuelEventSide | undefined,
): number {
  return (
      side?.score
      ?? side?.baseScore
      ?? side?.permanentScore
      ?? 0
  )
}

function leaderFromGap(
    gap: number,
):
    | 'attacker'
    | 'defender'
    | 'tie' {
  if (gap > 0) {
    return 'attacker'
  }

  if (gap < 0) {
    return 'defender'
  }

  return 'tie'
}

function eventTitle(
    event: DuelEvent,
): string {
  switch (getEventContext(event)) {
    case 'start':
      return 'DÉPART'

    case 'straight':
      return 'LIGNE DROITE'

    case 'turn':
      return 'VIRAGE'

    case 'chicane':
      return 'CHICANE'

    case 'finalSprint':
      return 'SPRINT FINAL'

    case 'braking':
      return 'FREINAGE'

    case 'acceleration':
      return 'RELANCE'

    case 'photoFinish':
      return 'PHOTO-FINISH'

    case 'other':
      return (
          event.label
          || event.type
          || 'SECTION'
      ).toUpperCase()
  }
}

function getEventContext(
    event: DuelEvent,
): EventContext {
  /*
   * On privilégie le type technique stable
   * fourni par le backend.
   */
  switch (event.type) {
    case 'start':
      return 'start'

    case 'straight':
      return 'straight'

    case 'turn':
      return 'turn'

    case 'chicane':
      return 'chicane'

    case 'final_sprint':
      return 'finalSprint'

    case 'photo_finish':
      return 'photoFinish'
  }

  /*
   * Compatibilité avec de futurs types
   * ou d'anciens replays.
   */
  const value =
      `${event.type} ${event.label ?? ''}`
          .toLowerCase()

  if (
      value.includes('straight')
      || value.includes('ligne droite')
  ) {
    return 'straight'
  }

  if (
      value.includes('chicane')
  ) {
    return 'chicane'
  }

  if (
      value.includes('final_sprint')
      || value.includes('sprint final')
  ) {
    return 'finalSprint'
  }

  if (
      value.includes('turn')
      || value.includes('corner')
      || value.includes('virage')
  ) {
    return 'turn'
  }

  if (
      value.includes('start')
      || value.includes('depart')
      || value.includes('départ')
  ) {
    return 'start'
  }

  if (
      value.includes('brak')
      || value.includes('frein')
  ) {
    return 'braking'
  }

  if (
      value.includes('accel')
      || value.includes('relance')
  ) {
    return 'acceleration'
  }

  if (
      value.includes('photo')
  ) {
    return 'photoFinish'
  }

  return 'other'
}

function statLabel(
    stat: string,
): string {
  switch (stat) {
    case 'speed':
      return 'vitesse'

    case 'acceleration':
      return 'accélération'

    case 'grip':
      return 'grip'

    case 'solidity':
      return 'solidité'

    default:
      return stat
  }
}

function formatSigned(
    value: number,
): string {
  if (value > 0) {
    return `+${value}`
  }

  return String(value)
}

/*
 * Sélection déterministe.
 *
 * Pas de Math.random() :
 * la formulation reste identique lorsqu'on
 * rejoue le même duel.
 */
function pick<T>(
    values: readonly T[],
    seed: number,
): T {
  if (values.length === 0) {
    throw new Error(
        'Impossible de sélectionner une phrase dans une liste vide.',
    )
  }

  const index =
      Math.abs(seed) % values.length

  return values[index] as T
}

/*
 * Scroll automatique à chaque arrivée
 * de nouvelles lignes.
 */
watch(
    () => lines.value.length,
    async () => {
      await nextTick()

      if (
          terminalElement.value === null
      ) {
        return
      }

      terminalElement.value.scrollTo({
        top:
        terminalElement.value.scrollHeight,

        behavior:
            'smooth',
      })
    },
)
</script>

<template>
  <section class="race-terminal">
    <header class="terminal-header">
      <span class="terminal-dot" />

      <strong>
        COURSE EN COURS
      </strong>

      <span
          v-if="replayFinished"
          class="terminal-status"
      >
        TERMINÉE
      </span>

      <span
          v-else
          class="terminal-status running"
      >
        ● LIVE
      </span>
    </header>

    <div
        ref="terminalElement"
        class="terminal-output"
    >
      <p
          v-if="lines.length === 0"
          class="terminal-waiting"
      >
        &gt; En attente du départ...
      </p>

      <div
          v-for="line in lines"
          :key="line.id"
          class="terminal-line"
          :class="[
            `terminal-line-${line.type}`,
            line.tone
              ? `terminal-tone-${line.tone}`
              : '',
          ]"
      >
        <template
            v-if="
              line.type === 'separator'
            "
        >
          <span class="separator" />
        </template>

        <template v-else>
          <span
              v-if="
                line.type === 'section'
              "
              class="terminal-prefix"
          >
            &gt;
          </span>

          <span
              v-else-if="
                line.type === 'card'
              "
              class="terminal-prefix"
          >
            ↳
          </span>

          <span
              v-else-if="
                line.type === 'cardEffect'
              "
              class="terminal-prefix"
          >
            │
          </span>

          <span
              v-else-if="
                line.type === 'reaction'
              "
              class="terminal-prefix"
          >
            └
          </span>

          <span
              v-else-if="
                line.type === 'status'
              "
              class="terminal-prefix"
          >
            •
          </span>

          <span
              v-else-if="
                line.type === 'result'
              "
              class="terminal-prefix"
          >
            ★
          </span>

          <span class="terminal-text">
            {{ line.text }}
          </span>
        </template>
      </div>
    </div>
  </section>
</template>

<style scoped>
.race-terminal {
  overflow: hidden;

  border:
      1px solid
      rgba(255, 255, 255, 0.12);

  border-radius: 12px;

  background: #101214;
  color: #e7eaed;
}

.terminal-header {
  display: flex;
  align-items: center;
  gap: 9px;

  padding: 11px 15px;

  border-bottom:
      1px solid
      rgba(255, 255, 255, 0.1);

  background: #171a1d;

  font-family:
      "SFMono-Regular",
      Consolas,
      "Liberation Mono",
      monospace;

  font-size: 0.76rem;
}

.terminal-dot {
  width: 8px;
  height: 8px;

  flex: 0 0 8px;

  border-radius: 50%;

  background: currentColor;

  opacity: 0.65;
}

.terminal-status {
  margin-left: auto;

  font-size: 0.7rem;

  opacity: 0.65;
}

.terminal-status.running {
  opacity: 1;
}

.terminal-output {
  min-height: 290px;
  max-height: 500px;

  overflow-y: auto;

  padding:
      18px
      18px
      24px;

  font-family:
      "SFMono-Regular",
      Consolas,
      "Liberation Mono",
      monospace;

  font-size: 0.88rem;
  line-height: 1.65;

  scroll-behavior: smooth;
}

.terminal-line {
  display: flex;
  align-items: flex-start;
  gap: 9px;

  min-height: 24px;

  animation:
      terminal-line-in
      180ms
      ease-out
      both;
}

.terminal-line-section {
  margin-top: 18px;

  font-weight: 800;

  letter-spacing: 0.06em;
}

.terminal-line-section:first-child {
  margin-top: 0;
}

.terminal-line-action {
  padding-left: 19px;
}

/*
 * Activation de capacité.
 */
.terminal-line-card {
  margin-top: 4px;

  padding-left: 19px;

  font-weight: 800;
}

/*
 * Effet produit par la capacité.
 *
 * Exemple :
 * │ La voiture bondit : +8 vitesse !
 */
.terminal-line-cardEffect {
  padding-left: 30px;

  opacity: 0.9;
}

/*
 * Réaction adverse.
 *
 * Exemple :
 * └ Ghost tente de rester dans l'aspiration.
 */
.terminal-line-reaction {
  padding-left: 30px;

  font-style: italic;

  opacity: 0.72;
}

.terminal-line-status {
  margin:
      3px
      0
      9px;

  padding-left: 19px;

  font-style: italic;

  opacity: 0.82;
}

.terminal-line-result {
  font-weight: 800;
}

.terminal-tone-normal {
  opacity: 0.9;
}

.terminal-tone-good {
  opacity: 1;
}

.terminal-tone-bad {
  opacity: 0.68;
}

.terminal-tone-important {
  font-weight: 800;
}

.terminal-prefix {
  flex: 0 0 14px;

  user-select: none;

  opacity: 0.65;
}

.terminal-text {
  min-width: 0;

  overflow-wrap: anywhere;
}

.separator {
  display: block;

  width: 100%;
  height: 1px;

  margin:
      18px
      0;

  background:
      rgba(255, 255, 255, 0.15);
}

.terminal-waiting {
  margin: 0;

  opacity: 0.6;
}

@keyframes terminal-line-in {
  from {
    opacity: 0;

    transform:
        translateY(4px);
  }

  to {
    opacity: 1;

    transform:
        translateY(0);
  }
}

@media (max-width: 600px) {
  .terminal-output {
    max-height: 420px;

    padding:
        14px
        13px
        18px;

    font-size: 0.78rem;
  }

  .terminal-line-cardEffect,
  .terminal-line-reaction {
    padding-left: 20px;
  }
}
</style>