<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import CardChoicePanel
  from '../components/CardChoicePanel.vue'

import CarVisual
  from '../components/CarVisual.vue'

import DuelNarrativeTerminal
  from '../components/DuelNarrativeTerminal.vue'

import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'

import {
  useDuelStore,
} from '../stores/duel'

import type {
  ApiCollection,
  ApiRelation,
  Card,
  CardChoice,
  Car,
  PendingDuel,
} from '../types/api'

const router =
    useRouter()

const duelStore =
    useDuelStore()

const pendingDuel =
    ref<PendingDuel | null>(
        null,
    )

const visibleEventCount =
    ref(0)

const replayStarted =
    ref(false)

const replayFinished =
    ref(false)

/*
 * À la fin d'une course :
 *
 * false = affiche la piste
 * true  = affiche le journal
 *
 * Pendant la course, le journal reste
 * automatiquement visible sous la piste.
 */
const showCombatLog =
    ref(false)

const pendingChoice =
    ref<CardChoice | null>(
        null,
    )

const firstChoiceCard =
    ref<Card | null>(
        null,
    )

const secondChoiceCard =
    ref<Card | null>(
        null,
    )

const loadingChoice =
    ref(false)

const choiceSuccessMessage =
    ref('')

const updatedCar =
    ref<Car | null>(
        null,
    )

let replayTimer:
    ReturnType<typeof setInterval>
    | null = null

let cooldownTimer:
    ReturnType<typeof setInterval>
    | null = null

/*
 * =====================================
 * REPLAY
 * =====================================
 */

const events =
    computed(
        () =>
            duelStore.duel
                ?.replayData
                .events
            ?? [],
    )

const visibleEvents =
    computed(
        () =>
            events.value.slice(
                0,
                visibleEventCount.value,
            ),
    )

const currentEvent =
    computed(() => {
      if (
          visibleEventCount.value
          === 0
      ) {
        return null
      }

      return (
          events.value[
          visibleEventCount.value - 1
              ]
          ?? null
      )
    })

const currentGap =
    computed(
        () =>
            currentEvent.value
                ?.gapAfter
            ?? 0,
    )

const currentEventType =
    computed(
        () =>
            currentEvent.value
                ?.type
            ?? 'waiting',
    )

const progressPercent =
    computed(() => {
      if (
          events.value.length
          === 0
      ) {
        return 0
      }

      return Math.round(
          (
              visibleEventCount.value
              / events.value.length
          )
          * 100,
      )
    })

/*
 * Position générale de la course.
 *
 * 8% au départ.
 * Environ 84% en fin de course.
 */
const baseRacePosition =
    computed(
        () =>
            8
            + progressPercent.value
            * 0.76,
    )

const attackerPosition =
    computed(
        () =>
            clamp(
                7,
                91,
                baseRacePosition.value
                + currentGap.value
                * 0.75,
            ),
    )

const defenderPosition =
    computed(
        () =>
            clamp(
                7,
                91,
                baseRacePosition.value
                - currentGap.value
                * 0.75,
            ),
    )

/*
 * Animation correspondant
 * à la portion du replay.
 */
const raceMotionClass =
    computed(() => {
      switch (
          currentEventType.value
          ) {
        case 'start':
          return 'motion-start'

        case 'straight':
          return 'motion-straight'

        case 'turn':
          return 'motion-turn'

        case 'chicane':
          return 'motion-chicane'

        case 'final_sprint':
          return 'motion-sprint'

        case 'photo_finish':
          return 'motion-finish'

        default:
          return ''
      }
    })

const attackerIsLeading =
    computed(
        () =>
            currentGap.value > 0,
    )

const defenderIsLeading =
    computed(
        () =>
            currentGap.value < 0,
    )

const raceStarted =
    computed(
        () =>
            replayStarted.value
            && visibleEventCount.value
            > 0,
    )

const finishVisible =
    computed(
        () =>
            progressPercent.value
            >= 70,
    )

/*
 * =====================================
 * RÉSULTAT
 * =====================================
 */

const attackerWon =
    computed(() => {
      if (
          duelStore.duel === null
          || pendingDuel.value === null
      ) {
        return false
      }

      return (
          duelStore.duel
              .replayData
              .winnerCarId
          === pendingDuel.value
              .attackerCarId
      )
    })

const winnerName =
    computed(() => {
      if (
          duelStore.duel === null
      ) {
        return ''
      }

      return attackerWon.value
          ? duelStore.duel
              .attackerSnapshot
              .pilotName
          : duelStore.duel
              .defenderSnapshot
              .pilotName
    })

const attackerRatingLabel =
    computed(() => {
      const value =
          duelStore.duel
              ?.attackerRatingDelta
          ?? 0

      return value > 0
          ? `+${value}`
          : String(value)
    })

function clamp(
    minimum: number,
    maximum: number,
    value: number,
): number {
  return Math.min(
      maximum,
      Math.max(
          minimum,
          value,
      ),
  )
}

/*
 * =====================================
 * DUEL PRÉPARÉ
 * =====================================
 */

function loadPendingDuel():
    void {
  const rawValue =
      sessionStorage.getItem(
          'street-rivals-pending-duel',
      )

  if (
      rawValue === null
  ) {
    pendingDuel.value =
        null

    return
  }

  try {
    pendingDuel.value =
        JSON.parse(
            rawValue,
        ) as PendingDuel
  } catch {
    sessionStorage.removeItem(
        'street-rivals-pending-duel',
    )

    pendingDuel.value =
        null
  }
}

/*
 * =====================================
 * LANCEMENT DU DUEL
 * =====================================
 */

async function startDuel():
    Promise<void> {
  if (
      pendingDuel.value === null
  ) {
    return
  }

  stopReplay()

  replayStarted.value =
      false

  replayFinished.value =
      false

  showCombatLog.value =
      false

  visibleEventCount.value =
      0

  pendingChoice.value =
      null

  firstChoiceCard.value =
      null

  secondChoiceCard.value =
      null

  updatedCar.value =
      null

  choiceSuccessMessage.value =
      ''

  try {
    await duelStore.startDuel(
        pendingDuel.value
            .attackerCarId,

        pendingDuel.value
            .defenderCarId,
    )

    startReplay()
  } catch {
    startCooldownCountdown()
  }
}

/*
 * =====================================
 * LECTURE DU REPLAY
 * =====================================
 */

function startReplay():
    void {
  stopReplay()

  /*
   * Lorsqu'on revoit la course,
   * on repasse automatiquement
   * sur la vue piste.
   */
  showCombatLog.value =
      false

  replayStarted.value =
      true

  replayFinished.value =
      false

  visibleEventCount.value =
      0

  replayTimer =
      setInterval(
          () => {
            if (
                visibleEventCount.value
                < events.value.length
            ) {
              ++visibleEventCount.value

              return
            }

            finishReplay()
          },
          1100,
      )
}

function skipReplay():
    void {
  visibleEventCount.value =
      events.value.length

  finishReplay()
}

function replayAgain():
    void {
  showCombatLog.value =
      false

  startReplay()
}

function finishReplay():
    void {
  stopReplay()

  visibleEventCount.value =
      events.value.length

  replayFinished.value =
      true

  /*
   * IMPORTANT :
   *
   * Le journal disparaît automatiquement
   * lorsque la course se termine.
   *
   * L'écran résultat prend sa place.
   */
  showCombatLog.value =
      false

  void loadPendingCardChoice()
}

function stopReplay():
    void {
  if (
      replayTimer !== null
  ) {
    clearInterval(
        replayTimer,
    )

    replayTimer =
        null
  }
}

/*
 * =====================================
 * JOURNAL / COURSE
 * =====================================
 */

function toggleCombatLog():
    void {
  if (
      !replayFinished.value
  ) {
    return
  }

  showCombatLog.value =
      !showCombatLog.value
}

/*
 * =====================================
 * COOLDOWN
 * =====================================
 */

function startCooldownCountdown():
    void {
  stopCooldownCountdown()

  if (
      duelStore.retryAfterSeconds
      === null
      || duelStore.retryAfterSeconds
      <= 0
  ) {
    return
  }

  cooldownTimer =
      setInterval(
          () => {
            if (
                duelStore
                    .retryAfterSeconds
                === null
                || duelStore
                    .retryAfterSeconds
                <= 1
            ) {
              duelStore
                  .retryAfterSeconds =
                  0

              stopCooldownCountdown()

              return
            }

            --duelStore
                .retryAfterSeconds
          },
          1000,
      )
}

function stopCooldownCountdown():
    void {
  if (
      cooldownTimer !== null
  ) {
    clearInterval(
        cooldownTimer,
    )

    cooldownTimer =
        null
  }
}

/*
 * =====================================
 * CHOIX DE CARTE
 * =====================================
 */

async function loadPendingCardChoice():
    Promise<void> {
  if (
      pendingDuel.value === null
  ) {
    return
  }

  loadingChoice.value =
      true

  try {
    const response =
        await apiRequest<
            ApiCollection<CardChoice>
        >(
            '/api/card_choices',
        )

    const choices =
        getCollectionMembers(
            response,
        )

    const matchingChoices =
        choices
            .filter(
                (choice) => {
                  const carId =
                      getRelationId(
                          choice.car,
                      )

                  return (
                      carId
                      === pendingDuel.value
                          ?.attackerCarId

                      && choice.selectedCard
                      == null
                  )
                },
            )
            .sort(
                (
                    first,
                    second,
                ) =>
                    second.level
                    - first.level,
            )

    pendingChoice.value =
        matchingChoices[0]
        ?? null

    if (
        pendingChoice.value
        === null
    ) {
      firstChoiceCard.value =
          null

      secondChoiceCard.value =
          null

      await refreshAttackerCar()

      return
    }

    const [
      firstCard,
      secondCard,
    ] =
        await Promise.all([
          resolveCard(
              pendingChoice.value
                  .firstCard,
          ),

          resolveCard(
              pendingChoice.value
                  .secondCard,
          ),
        ])

    firstChoiceCard.value =
        firstCard

    secondChoiceCard.value =
        secondCard
  } catch (error) {
    if (
        error instanceof ApiError
    ) {
      duelStore.errorMessage =
          error.message
    }
  } finally {
    loadingChoice.value =
        false
  }
}

async function resolveCard(
    relation:
    ApiRelation<Card>,
): Promise<Card> {
  if (
      typeof relation
      !== 'string'
  ) {
    return relation
  }

  return apiRequest<Card>(
      relation,
  )
}

function getRelationId(
    relation:
    ApiRelation<{
      id: number
    }>,
): number | null {
  if (
      typeof relation
      !== 'string'
  ) {
    return relation.id
  }

  const matches =
      relation.match(
          /\/(\d+)(?:\/[^/]*)?$/,
      )

  if (
      matches === null
  ) {
    return null
  }

  return Number.parseInt(
      matches[1],
      10,
  )
}

async function selectCard(
    cardId: number,
): Promise<void> {
  if (
      pendingChoice.value
      === null
  ) {
    return
  }

  loadingChoice.value =
      true

  choiceSuccessMessage.value =
      ''

  try {
    await apiRequest(
        `/api/card_choices/${pendingChoice.value.id}/select`,
        {
          method: 'POST',

          body:
              JSON.stringify({
                cardId,
              }),
        },
    )

    choiceSuccessMessage.value =
        'La carte a été ajoutée à la voiture.'

    /*
     * Une réserve d'XP peut avoir créé
     * immédiatement un nouveau choix.
     */
    await loadPendingCardChoice()
  } catch (error) {
    if (
        error instanceof ApiError
    ) {
      duelStore.errorMessage =
          error.message
    }
  } finally {
    loadingChoice.value =
        false
  }
}

/*
 * =====================================
 * ACTUALISATION VOITURE
 * =====================================
 */

async function refreshAttackerCar():
    Promise<void> {
  if (
      pendingDuel.value === null
  ) {
    return
  }

  try {
    const response =
        await apiRequest<
            ApiCollection<Car>
        >(
            '/api/cars',
        )

    const cars =
        getCollectionMembers(
            response,
        )

    updatedCar.value =
        cars.find(
            (car) =>
                car.id
                === pendingDuel.value
                    ?.attackerCarId,
        )
        ?? null
  } catch {
    updatedCar.value =
        null
  }
}

/*
 * =====================================
 * NAVIGATION
 * =====================================
 */

async function backToDuels():
    Promise<void> {
  stopReplay()
  stopCooldownCountdown()

  sessionStorage.removeItem(
      'street-rivals-pending-duel',
  )

  duelStore.reset()

  await router.push({
    name: 'duels',
  })
}

async function backToGarage():
    Promise<void> {
  stopReplay()
  stopCooldownCountdown()

  sessionStorage.removeItem(
      'street-rivals-pending-duel',
  )

  duelStore.reset()

  await router.push({
    name: 'garage',
  })
}

/*
 * =====================================
 * CYCLE DE VIE
 * =====================================
 */

onMounted(
    () => {
      duelStore.reset()

      loadPendingDuel()
    },
)

onBeforeUnmount(
    () => {
      stopReplay()

      stopCooldownCountdown()
    },
)
</script>

<template>
  <section class="duel-page">
    <!-- =====================================
         EN-TÊTE
    ====================================== -->

    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Course classée
        </p>

        <h1>
          Duel de rue
        </h1>
      </div>

      <button
          type="button"
          class="
            button
            button-secondary
          "
          @click="
            backToDuels
          "
      >
        Quitter la course
      </button>
    </div>

    <!-- =====================================
         AUCUN DUEL
    ====================================== -->

    <div
        v-if="
          pendingDuel === null
        "
        class="
          empty-state
          duel-empty-state
        "
    >
      <div class="empty-duel-symbol">
        VS
      </div>

      <p class="eyebrow">
        Matchmaking
      </p>

      <h2>
        Aucun duel préparé
      </h2>

      <p>
        Choisis un adversaire avant de lancer
        une nouvelle course.
      </p>

      <button
          type="button"
          class="
            button
            button-primary
          "
          @click="
            backToDuels
          "
      >
        Trouver un adversaire
      </button>
    </div>

    <template v-else>
      <!-- =====================================
           PRÉPARATION DU DUEL
      ====================================== -->

      <section
          v-if="
      duelStore.duel
      === null
    "
          class="
      duel-preview
      versus-screen
    "
      >
        <!-- =====================================
             INTRO
        ====================================== -->

        <div class="versus-intro">
          <p class="eyebrow">
            Duel classé
          </p>

          <h2>
            Face-à-face
          </h2>

          <p>
            Deux pilotes. Une seule ligne d'arrivée.
          </p>
        </div>

        <!-- =====================================
             VERSUS
        ====================================== -->

        <div class="versus-arena">
          <!-- =================================
               ATTAQUANT
          ================================== -->

          <article
              class="
          versus-driver
          versus-driver-attacker
        "
          >
            <div class="driver-heading">
        <span class="driver-role">
          Attaquant
        </span>

              <strong>
                {{
                  pendingDuel
                      .attackerPilotName
                }}
              </strong>
            </div>

            <div
                class="
            versus-car-stage
            attacker-car-stage
          "
            >
              <div class="versus-car-shadow" />

              <div class="versus-car-entry">
                <CarVisual
                    :color="
                pendingDuel
                    .attackerColor
              "
                    :body-style="
                pendingDuel
                    .attackerBodyStyle
              "
                    :wheel-style="
                pendingDuel
                    .attackerWheelStyle
              "
                    :pilot-name="
                pendingDuel
                    .attackerPilotName
              "
                />
              </div>
            </div>
          </article>

          <!-- =================================
               VS
          ================================== -->

          <div class="versus-center">
            <span class="versus-line" />

            <div class="versus-badge">
              VS
            </div>

            <span class="versus-line" />
          </div>

          <!-- =================================
               DÉFENSEUR
          ================================== -->

          <article
              class="
          versus-driver
          versus-driver-defender
        "
          >
            <div class="driver-heading">
        <span class="driver-role">
          Défenseur
        </span>

              <strong>
                {{
                  pendingDuel
                      .defenderPilotName
                }}
              </strong>
            </div>

            <div
                class="
            versus-car-stage
            defender-car-stage
          "
            >
              <div class="versus-car-shadow" />

              <div
                  class="
              versus-car-entry
              defender-car-visual
            "
              >
                <CarVisual
                    :color="
                pendingDuel
                    .defenderColor
              "
                    :body-style="
                pendingDuel
                    .defenderBodyStyle
              "
                    :wheel-style="
                pendingDuel
                    .defenderWheelStyle
              "
                    :pilot-name="
                pendingDuel
                    .defenderPilotName
              "
                />
              </div>
            </div>
          </article>
        </div>

        <!-- =====================================
             DIFFICULTÉ
        ====================================== -->

        <div class="duel-briefing">
    <span>
      Difficulté estimée
    </span>

          <strong>
            {{
              pendingDuel.difficulty
            }}
          </strong>
        </div>

        <!-- =====================================
             ERREUR / COOLDOWN
        ====================================== -->

        <p
            v-if="
        duelStore.errorMessage
        !== ''
      "
            class="
        alert
        alert-error
        versus-error
      "
        >
          {{ duelStore.errorMessage }}

          <span
              v-if="
          duelStore.retryAfterSeconds
          !== null
          && duelStore.retryAfterSeconds
          > 0
        "
          >
      Nouvelle tentative possible dans

      <strong>
        {{
          duelStore
              .retryAfterSeconds
        }}
      </strong>

      seconde(s).
    </span>
        </p>

        <!-- =====================================
             ACTION
        ====================================== -->

        <div class="versus-action">
          <p>
            Prêt pour la course ?
          </p>

          <button
              type="button"
              class="
          button
          button-primary
          launch-duel-button
        "
              :disabled="
          duelStore.loading
          || (
            duelStore.retryAfterSeconds
            !== null
            && duelStore.retryAfterSeconds
            > 0
          )
        "
              @click="
          startDuel
        "
          >
            <template
                v-if="
            duelStore.loading
          "
            >
        <span class="launch-spinner">
          ↻
        </span>

              Préparation de la course...
            </template>

            <template v-else>
              Lancer le duel

              <span class="launch-arrow">
          →
        </span>
            </template>
          </button>

          <button
              type="button"
              class="
          change-opponent-button
        "
              :disabled="
          duelStore.loading
        "
              @click="
          backToDuels
        "
          >
            Choisir un autre adversaire
          </button>
        </div>
      </section>

      <!-- =====================================
           DUEL EXISTANT
      ====================================== -->

      <template v-else>
        <!-- =====================================
             COURSE

             Après la fin :
             cachée si le journal est demandé.
        ====================================== -->

        <section
            v-if="
              !replayFinished
              || !showCombatLog
            "
            class="
              panel
              race-replay
              replay-view
            "
        >
          <div class="section-heading">
            <div>
              <p class="eyebrow">
                Moteur
                {{
                  duelStore.duel
                      .engineVersion
                }}
              </p>

              <h2>
                {{
                  duelStore.duel
                      .attackerSnapshot
                      .pilotName
                }}

                contre

                {{
                  duelStore.duel
                      .defenderSnapshot
                      .pilotName
                }}
              </h2>
            </div>

            <!--
              Après la course, le bouton
              "Revoir" se trouve dans la fenêtre
              de progression.
            -->

            <button
                v-if="
                  !replayFinished
                "
                type="button"
                class="
                  button
                  button-secondary
                "
                @click="
                  skipReplay
                "
            >
              Passer le replay
            </button>
          </div>

          <!-- =================================
               PISTE
          ================================== -->

          <div
              class="race-track"
              :class="{
                'race-track-running':
                  raceStarted
                  && !replayFinished,

                'race-track-finished':
                  replayFinished,
              }"
          >
            <div class="road-background">
              <div
                  class="
                    road-edge
                    road-edge-top
                  "
              />

              <div
                  class="
                    road-edge
                    road-edge-bottom
                  "
              />

              <div
                  class="
                    road-center-line
                  "
              />

              <div
                  v-if="
                    finishVisible
                  "
                  class="finish-line"
              >
                <span>
                  ARRIVÉE
                </span>
              </div>
            </div>

            <!-- ATTAQUANT -->

            <div
                class="
                  race-car
                  race-car-attacker
                "
                :class="[
                  raceMotionClass,
                  {
                    'race-car-leading':
                      attackerIsLeading,
                  },
                ]"
                :style="{
                  left:
                    `${attackerPosition}%`,
                }"
            >
              <div class="race-car-name">
                {{
                  duelStore.duel
                      .attackerSnapshot
                      .pilotName
                }}

                <span
                    v-if="
                      attackerIsLeading
                    "
                    class="
                      leader-badge
                    "
                >
                  1
                </span>
              </div>

              <div
                  class="
                    race-car-visual
                  "
              >
                <CarVisual
                    :color="
                      duelStore.duel
                          .attackerSnapshot
                          .color
                    "
                    :body-style="
                      duelStore.duel
                          .attackerSnapshot
                          .bodyStyle
                      ?? 'coupe_01'
                    "
                    :wheel-style="
                      duelStore.duel
                          .attackerSnapshot
                          .wheelStyle
                      ?? 'street_01'
                    "
                    :pilot-name="
                      duelStore.duel
                          .attackerSnapshot
                          .pilotName
                    "
                />
              </div>
            </div>

            <!-- DÉFENSEUR -->

            <div
                class="
                  race-car
                  race-car-defender
                "
                :class="[
                  raceMotionClass,
                  {
                    'race-car-leading':
                      defenderIsLeading,
                  },
                ]"
                :style="{
                  left:
                    `${defenderPosition}%`,
                }"
            >
              <div class="race-car-name">
                {{
                  duelStore.duel
                      .defenderSnapshot
                      .pilotName
                }}

                <span
                    v-if="
                      defenderIsLeading
                    "
                    class="
                      leader-badge
                    "
                >
                  1
                </span>
              </div>

              <div
                  class="
                    race-car-visual
                  "
              >
                <CarVisual
                    :color="
                      duelStore.duel
                          .defenderSnapshot
                          .color
                    "
                    :body-style="
                      duelStore.duel
                          .defenderSnapshot
                          .bodyStyle
                      ?? 'coupe_01'
                    "
                    :wheel-style="
                      duelStore.duel
                          .defenderSnapshot
                          .wheelStyle
                      ?? 'street_01'
                    "
                    :pilot-name="
                      duelStore.duel
                          .defenderSnapshot
                          .pilotName
                    "
                />
              </div>
            </div>

            <div class="race-progress">
              <span
                  :style="{
                    width:
                      `${progressPercent}%`,
                  }"
              />
            </div>
          </div>

          <!-- ÉTAT COURSE -->

          <div
              class="
                current-race-status
              "
          >
            <span>
              Progression :

              <strong>
                {{ progressPercent }} %
              </strong>
            </span>

            <span>
              Écart :

              <strong>
                {{
                  currentGap > 0
                      ? '+'
                      : ''
                }}{{ currentGap }}
              </strong>
            </span>

            <span
                v-if="
                  currentEvent !== null
                "
            >
              Section :

              <strong>
                {{
                  currentEvent.label
                }}
              </strong>
            </span>
          </div>
        </section>

        <!-- =====================================
             JOURNAL DE COMBAT

             Pendant le replay :
             toujours visible.

             Après le replay :
             visible uniquement sur demande.
        ====================================== -->

        <div
            v-if="
              !replayFinished
              || showCombatLog
            "
            class="
              combat-log-view
            "
        >
          <div
              v-if="
                replayFinished
              "
              class="
                combat-log-heading
              "
          >
            <div>
              <p class="eyebrow">
                Replay
              </p>

              <h2>
                Journal de combat
              </h2>
            </div>

            <button
                type="button"
                class="
                  button
                  button-secondary
                "
                @click="
                  toggleCombatLog
                "
            >
              Voir la course
            </button>
          </div>

          <DuelNarrativeTerminal
              :events="
                visibleEvents
              "
              :attacker-name="
                duelStore.duel
                    .attackerSnapshot
                    .pilotName
              "
              :defender-name="
                duelStore.duel
                    .defenderSnapshot
                    .pilotName
              "
              :replay-finished="
                replayFinished
              "
              :winner-name="
                winnerName
              "
              :final-gap="
                duelStore.duel
                    .finalGap
              "
          />
        </div>

        <!-- =====================================
             RÉSULTAT
        ====================================== -->

        <section
            v-if="
              replayFinished
            "
            class="
              duel-result
            "
            :class="{
              'duel-result-victory':
                attackerWon,

              'duel-result-defeat':
                !attackerWon,
            }"
        >
          <div class="result-hero">
            <p class="eyebrow">
              Résultat officiel
            </p>

            <div class="result-status">
              {{
                attackerWon
                    ? 'VICTOIRE'
                    : 'DÉFAITE'
              }}
            </div>

            <h2>
              {{
                attackerWon
                    ? `${duelStore.duel.attackerSnapshot.pilotName} remporte le duel`
                    : `${duelStore.duel.defenderSnapshot.pilotName} remporte le duel`
              }}
            </h2>

            <p class="result-gap">
              Écart final

              <strong>
                {{
                  duelStore.duel.finalGap
                  > 0
                      ? '+'
                      : ''
                }}{{
                  duelStore.duel.finalGap
                }}
              </strong>
            </p>
          </div>

          <!-- RÉCOMPENSES -->

          <div class="result-rewards">
            <article>
              <span>
                XP
              </span>

              <strong>
                +{{
                  duelStore.duel
                      .attackerXpReward
                }}
              </strong>

              <small>
                expérience
              </small>
            </article>

            <article>
              <span>
                $
              </span>

              <strong>
                +{{
                  duelStore.duel
                      .attackerMoneyReward
                }}
              </strong>

              <small>
                argent
              </small>
            </article>

            <article>
              <span>
                ELO
              </span>

              <strong>
                {{
                  attackerRatingLabel
                }}
              </strong>

              <small>
                {{
                  duelStore.duel
                      .attackerRatingBefore
                }}

                →

                {{
                  duelStore.duel
                      .attackerRatingAfter
                }}
              </small>
            </article>
          </div>

          <!-- ANTI FARMING -->

          <div class="pair-duels">
            <span>
              Duels contre cet adversaire aujourd'hui
            </span>

            <strong>
              {{
                duelStore.duel
                    .replayData
                    .antiFarming
                    ?.pairDuelNumber
                ?? 1
              }}

              /

              {{
                duelStore.duel
                    .replayData
                    .antiFarming
                    ?.pairDailyLimit
                ?? 3
              }}
            </strong>
          </div>

          <p
              v-if="
                duelStore.duel
                    .replayData
                    .rewards
                    ?.multiplier
                !== undefined

                && duelStore.duel
                    .replayData
                    .rewards
                    .multiplier
                < 1
              "
              class="
                alert
                alert-warning
                reward-warning
              "
          >
            Les récompenses sont réduites car cet
            adversaire a déjà été affronté plusieurs fois.
          </p>
        </section>

        <!-- =====================================
             MESSAGE CARTE
        ====================================== -->

        <p
            v-if="
              choiceSuccessMessage
              !== ''
            "
            class="
              alert
              alert-success
            "
        >
          {{ choiceSuccessMessage }}
        </p>

        <!-- =====================================
             CHOIX DE CARTE
        ====================================== -->

        <CardChoicePanel
            v-if="
              replayFinished
              && pendingChoice !== null
              && firstChoiceCard !== null
              && secondChoiceCard !== null
            "
            :level="
              pendingChoice.level
            "
            :first-card="
              firstChoiceCard
            "
            :second-card="
              secondChoiceCard
            "
            :loading="
              loadingChoice
            "
            @select="
              selectCard
            "
        />

        <!-- =====================================
             PROGRESSION / ACTIONS
        ====================================== -->

        <section
            v-else-if="
              replayFinished
              && !loadingChoice
            "
            class="
              duel-actions
              duel-end-actions
            "
        >
          <!-- PROGRESSION -->

          <div
              v-if="
                updatedCar !== null
              "
              class="
                updated-car-summary
              "
          >
            <div>
              <p class="eyebrow">
                Progression
              </p>

              <strong>
                {{
                  updatedCar.pilotName
                }}
              </strong>
            </div>

            <div class="updated-car-values">
              <span>
                NIV.

                <strong>
                  {{
                    updatedCar.level
                  }}
                </strong>
              </span>

              <span>
                XP

                <strong>
                  {{
                    updatedCar.xp
                  }}
                </strong>
              </span>

              <span>
                $

                <strong>
                  {{
                    updatedCar.money
                  }}
                </strong>
              </span>
            </div>
          </div>

          <!-- ACTIONS -->

          <div class="duel-end-buttons">
            <button
                type="button"
                class="
                  button
                  button-primary
                  new-duel-button
                "
                @click="
                  backToDuels
                "
            >
              Nouveau duel
            </button>

            <button
                type="button"
                class="
                  button
                  button-secondary
                "
                @click="
                  replayAgain
                "
            >
              Revoir la course
            </button>

            <button
                type="button"
                class="
                  button
                  button-secondary
                  combat-log-button
                "
                @click="
                  toggleCombatLog
                "
            >
              {{
                showCombatLog
                    ? 'Voir la course'
                    : 'Voir le journal de combat'
              }}
            </button>

            <button
                type="button"
                class="
                  garage-link-button
                "
                @click="
                  backToGarage
                "
            >
              Retour au garage
            </button>
          </div>
        </section>
      </template>
    </template>
  </section>
</template>

<style scoped>
/*
 * =====================================
 * ÉCRAN VS
 * =====================================
 */

.versus-screen {
  position: relative;

  overflow: hidden;

  padding:
      26px
      28px
      22px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 24px;

  background:
      radial-gradient(
          circle at 22% 43%,
          rgba(
              90,
              130,
              220,
              0.11
          ),
          transparent 35%
      ),
      radial-gradient(
          circle at 78% 43%,
          rgba(
              220,
              80,
              90,
              0.1
          ),
          transparent 35%
      ),
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.versus-screen::before {
  content: '';

  position: absolute;

  top: -50%;
  left: 50%;

  width: 1px;
  height: 200%;

  background:
      linear-gradient(
          transparent,
          rgba(
              255,
              255,
              255,
              0.12
          ),
          transparent
      );

  transform:
      rotate(14deg);

  pointer-events: none;
}

/*
 * =====================================
 * INTRO
 * =====================================
 */

.versus-intro {
  position: relative;

  z-index: 4;

  text-align: center;
}

.versus-intro h2 {
  margin:
      0
      0
      4px;

  font-size:
      clamp(
          1.6rem,
          5vw,
          2.5rem
      );

  letter-spacing: -0.035em;
}

.versus-intro > p:last-child {
  margin: 0;

  font-size: 0.72rem;

  opacity: 0.42;
}

/*
 * =====================================
 * ARENA
 * =====================================
 */

.versus-arena {
  position: relative;

  z-index: 3;

  display: grid;

  grid-template-columns:
      minmax(
          0,
          1fr
      )
      85px
      minmax(
          0,
          1fr
      );

  align-items: center;

  gap: 10px;

  margin:
      10px
      0
      0;
}

/*
 * =====================================
 * PILOTES
 * =====================================
 */

.versus-driver {
  min-width: 0;
}

.driver-heading {
  position: relative;

  z-index: 3;

  display: grid;

  justify-items: center;

  gap: 2px;

  text-align: center;
}

.driver-role {
  font-size: 0.58rem;
  font-weight: 850;

  letter-spacing: 0.12em;

  text-transform: uppercase;

  opacity: 0.38;
}

.driver-heading strong {
  max-width: 100%;

  overflow: hidden;

  font-size:
      clamp(
          1.15rem,
          3vw,
          1.7rem
      );

  text-overflow: ellipsis;

  white-space: nowrap;
}

/*
 * =====================================
 * STAGE DES VOITURES
 * =====================================
 */

.versus-car-stage {
  position: relative;

  width: 100%;
  max-width: 430px;

  margin:
      -5px
      auto
      -4px;
}

.versus-car-shadow {
  position: absolute;

  left: 18%;
  right: 18%;
  bottom: 17%;

  height: 13px;

  border-radius: 50%;

  background:
      rgba(
          0,
          0,
          0,
          0.4
      );

  filter:
      blur(9px);
}

/*
 * Voiture gauche.
 */
.versus-driver-attacker
.versus-car-entry {
  animation:
      versus-attacker-enter
      500ms
      cubic-bezier(
          0.2,
          0.8,
          0.3,
          1
      )
      both;
}

/*
 * La voiture droite est inversée afin
 * que les deux véhicules se regardent.
 */
.defender-car-visual {
  transform:
      scaleX(-1);
}

.versus-driver-defender
.versus-car-entry {
  animation:
      versus-defender-enter
      500ms
      70ms
      cubic-bezier(
          0.2,
          0.8,
          0.3,
          1
      )
      both;
}

/*
 * =====================================
 * VS
 * =====================================
 */

.versus-center {
  display: grid;

  justify-items: center;

  gap: 8px;
}

.versus-line {
  display: block;

  width: 1px;
  height: 40px;

  background:
      linear-gradient(
          transparent,
          rgba(
              255,
              255,
              255,
              0.18
          )
      );
}

.versus-line:last-child {
  background:
      linear-gradient(
          rgba(
              255,
              255,
              255,
              0.18
          ),
          transparent
      );
}

.versus-badge {
  display: flex;

  width: 58px;
  height: 58px;

  align-items: center;
  justify-content: center;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.16
      );

  border-radius: 50%;

  background:
      rgba(
          17,
          19,
          23,
          0.94
      );

  box-shadow:
      0
      0
      0
      6px
      rgba(
          255,
          255,
          255,
          0.025
      ),
      0
      10px
      32px
      rgba(
          0,
          0,
          0,
          0.4
      );

  font-size: 1.05rem;
  font-weight: 950;

  animation:
      versus-badge-enter
      450ms
      120ms
      ease
      both;
}

/*
 * =====================================
 * BRIEFING
 * =====================================
 */

.duel-briefing {
  position: relative;

  z-index: 5;

  display: flex;

  width: fit-content;

  align-items: center;

  gap: 8px;

  margin:
      -2px
      auto
      16px;

  padding:
      7px
      10px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.08
      );

  border-radius: 999px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

.duel-briefing span {
  font-size: 0.6rem;

  opacity: 0.4;
}

.duel-briefing strong {
  font-size: 0.68rem;

  text-transform: capitalize;
}

/*
 * =====================================
 * ACTION
 * =====================================
 */

.versus-action {
  position: relative;

  z-index: 5;

  display: grid;

  max-width: 520px;

  gap: 7px;

  margin:
      0
      auto;
}

.versus-action > p {
  margin:
      0
      0
      3px;

  text-align: center;

  font-size: 0.68rem;

  opacity: 0.4;
}

.launch-duel-button {
  display: flex;

  min-height: 50px;

  align-items: center;
  justify-content: center;

  gap: 9px;

  width: 100%;

  font-size: 0.83rem;
  font-weight: 900;

  letter-spacing: 0.02em;
}

.launch-arrow {
  font-size: 1rem;

  transition:
      transform
      150ms
      ease;
}

.launch-duel-button:hover
.launch-arrow {
  transform:
      translateX(4px);
}

.launch-spinner {
  display: inline-block;

  animation:
      versus-spinner
      700ms
      linear
      infinite;
}

.change-opponent-button {
  min-height: 34px;

  border: 0;

  background:
      transparent;

  color: inherit;

  cursor: pointer;

  font-size: 0.65rem;

  opacity: 0.4;
}

.change-opponent-button:hover {
  opacity: 0.75;
}

.change-opponent-button:disabled {
  cursor: default;

  opacity: 0.2;
}

.versus-error {
  position: relative;

  z-index: 5;

  max-width: 520px;

  margin:
      0
      auto
      12px;
}

/*
 * =====================================
 * ANIMATIONS VS
 * =====================================
 */

@keyframes versus-attacker-enter {
  from {
    opacity: 0;

    transform:
        translateX(-55px);
  }

  to {
    opacity: 1;

    transform:
        translateX(0);
  }
}

@keyframes versus-defender-enter {
  from {
    opacity: 0;

    transform:
        translateX(55px)
        scaleX(-1);
  }

  to {
    opacity: 1;

    transform:
        translateX(0)
        scaleX(-1);
  }
}

@keyframes versus-badge-enter {
  from {
    opacity: 0;

    transform:
        scale(0.6)
        rotate(-12deg);
  }

  to {
    opacity: 1;

    transform:
        scale(1)
        rotate(0);
  }
}

@keyframes versus-spinner {
  to {
    transform:
        rotate(360deg);
  }
}

/*
 * =====================================
 * VS MOBILE
 * =====================================
 */

@media (
max-width: 700px
) {
  .versus-screen {
    padding:
        20px
        12px
        16px;
  }

  .versus-arena {
    grid-template-columns:
        minmax(
            0,
            1fr
        )
        48px
        minmax(
            0,
            1fr
        );

    gap: 2px;

    margin-top: 14px;
  }

  .driver-role {
    font-size: 0.48rem;
  }

  .driver-heading strong {
    font-size: 0.95rem;
  }

  .versus-car-stage {
    margin:
        -2px
        auto;
  }

  .versus-badge {
    width: 42px;
    height: 42px;

    font-size: 0.78rem;
  }

  .versus-line {
    height: 27px;
  }

  .duel-briefing {
    margin-top: 4px;
  }
}

@media (
max-width: 420px
) {
  .versus-screen {
    padding-left: 8px;
    padding-right: 8px;
  }

  .versus-arena {
    grid-template-columns:
        minmax(
            0,
            1fr
        )
        38px
        minmax(
            0,
            1fr
        );
  }

  .versus-badge {
    width: 34px;
    height: 34px;

    font-size: 0.67rem;
  }

  .versus-line {
    height: 20px;
  }

  .driver-heading strong {
    font-size: 0.82rem;
  }
}

/*
 * =====================================
 * DUEL VIDE
 * =====================================
 */

.duel-empty-state {
  max-width: 520px;

  margin:
      70px
      auto;

  text-align: center;
}

.empty-duel-symbol {
  display: flex;

  width: 72px;
  height: 72px;

  align-items: center;
  justify-content: center;

  margin:
      0
      auto
      15px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 50%;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );

  font-size: 1rem;
  font-weight: 900;
}

/*
 * =====================================
 * TRANSITION COURSE / JOURNAL
 * =====================================
 */

.replay-view,
.combat-log-view {
  animation:
      duel-view-enter
      180ms
      ease;
}

.combat-log-heading {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 16px;

  margin:
      18px
      0
      10px;
}

.combat-log-heading h2 {
  margin: 0;
}

@keyframes duel-view-enter {
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

/*
 * =====================================
 * PISTE
 * =====================================
 */

.race-track {
  position: relative;

  width: 100%;
  height: 300px;

  overflow: hidden;

  margin-top: 22px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.12
      );

  border-radius: 18px;

  background: #202327;

  isolation: isolate;
}

/*
 * =====================================
 * ROUTE
 * =====================================
 */

.road-background {
  position: absolute;

  inset: 0;

  overflow: hidden;

  background:
      linear-gradient(
          180deg,
          #30343a 0%,
          #25292e 48%,
          #212428 100%
      );
}

.road-background::before {
  content: '';

  position: absolute;

  inset: 0;

  width: 200%;

  background:
      repeating-linear-gradient(
          90deg,
          transparent 0,
          transparent 75px,
          rgba(
              255,
              255,
              255,
              0.025
          ) 76px,
          rgba(
              255,
              255,
              255,
              0.025
          ) 78px
      );

  transform:
      translateX(0);
}

.race-track-running
.road-background::before {
  animation:
      road-scroll
      600ms
      linear
      infinite;
}

/*
 * Bordures rouge / blanc.
 */
.road-edge {
  position: absolute;

  left: 0;

  width: 200%;
  height: 8px;

  background:
      repeating-linear-gradient(
          90deg,
          #e8e8e8 0,
          #e8e8e8 30px,
          #b72f35 30px,
          #b72f35 60px
      );
}

.road-edge-top {
  top: 11px;
}

.road-edge-bottom {
  bottom: 20px;
}

.race-track-running
.road-edge {
  animation:
      road-scroll
      600ms
      linear
      infinite;
}

/*
 * Ligne centrale.
 */
.road-center-line {
  position: absolute;

  top: 50%;
  left: 0;

  width: 200%;
  height: 4px;

  background:
      repeating-linear-gradient(
          90deg,
          rgba(
              255,
              255,
              255,
              0.7
          ) 0,
          rgba(
              255,
              255,
              255,
              0.7
          ) 42px,
          transparent 42px,
          transparent 82px
      );

  opacity: 0.45;

  transform:
      translateY(-50%);
}

.race-track-running
.road-center-line {
  animation:
      road-center-scroll
      550ms
      linear
      infinite;
}

/*
 * =====================================
 * ARRIVÉE
 * =====================================
 */

.finish-line {
  position: absolute;

  top: 12px;
  right: 7%;

  width: 30px;

  height:
      calc(
          100%
          - 33px
      );

  background:
      conic-gradient(
          #eeeeee 25%,
          #171717 0 50%,
          #eeeeee 0 75%,
          #171717 0
      );

  background-size:
      16px
      16px;

  opacity: 0;

  animation:
      finish-appear
      350ms
      ease
      forwards;
}

.finish-line span {
  position: absolute;

  top: -2px;
  left: 50%;

  transform:
      translate(
          -50%,
          -100%
      );

  padding:
      3px
      7px;

  border-radius: 5px;

  background: #15171a;

  font-size: 0.58rem;

  font-weight: 900;

  letter-spacing: 0.08em;

  white-space: nowrap;
}

/*
 * =====================================
 * VOITURES
 * =====================================
 */

.race-car {
  position: absolute;

  width: 145px;

  z-index: 5;

  transform:
      translateX(-50%);

  transition:
      left
      780ms
      cubic-bezier(
          0.22,
          0.61,
          0.36,
          1
      );
}

.race-car-attacker {
  top: 27px;
}

.race-car-defender {
  bottom: 34px;
}

.race-car-visual {
  width: 100%;

  transform-origin:
      center
      bottom;
}

.race-car-name {
  display: flex;

  align-items: center;

  justify-content: center;

  gap: 6px;

  margin-bottom: -12px;

  font-size: 0.68rem;

  font-weight: 900;

  white-space: nowrap;

  text-shadow:
      0
      2px
      4px
      rgba(
          0,
          0,
          0,
          0.75
      );
}

.leader-badge {
  display: inline-flex;

  align-items: center;

  justify-content: center;

  width: 19px;
  height: 19px;

  border-radius: 50%;

  background:
      rgba(
          255,
          255,
          255,
          0.92
      );

  color: #15171a;

  font-size: 0.65rem;

  box-shadow:
      0
      2px
      8px
      rgba(
          0,
          0,
          0,
          0.35
      );
}

.race-car-leading {
  z-index: 6;
}

/*
 * =====================================
 * ANIMATIONS PAR ÉVÉNEMENT
 * =====================================
 */

.motion-start
.race-car-visual {
  animation:
      car-launch
      700ms
      ease-out;
}

.motion-straight
.race-car-visual {
  animation:
      car-speed
      240ms
      linear
      infinite;
}

.motion-turn
.race-car-visual {
  animation:
      car-turn
      750ms
      ease-in-out;
}

.motion-chicane
.race-car-visual {
  animation:
      car-chicane
      520ms
      ease-in-out;
}

.motion-sprint
.race-car-visual {
  animation:
      car-sprint
      160ms
      linear
      infinite;
}

.motion-finish
.race-car-visual {
  animation:
      car-finish
      500ms
      ease-out;
}

/*
 * =====================================
 * PROGRESSION DE COURSE
 * =====================================
 */

.race-progress {
  position: absolute;

  left: 20px;
  right: 20px;
  bottom: 8px;

  height: 4px;

  overflow: hidden;

  border-radius: 999px;

  background:
      rgba(
          255,
          255,
          255,
          0.12
      );

  z-index: 10;
}

.race-progress span {
  display: block;

  height: 100%;

  border-radius: inherit;

  background: currentColor;

  transition:
      width
      800ms
      ease;
}

/*
 * =====================================
 * STATUT SOUS LA PISTE
 * =====================================
 */

.current-race-status {
  display: flex;

  flex-wrap: wrap;

  gap: 8px 18px;

  margin:
      12px
      0
      0;

  font-size: 0.78rem;

  opacity: 0.72;
}

.current-race-status strong {
  opacity: 1;
}

/*
 * =====================================
 * RÉSULTAT
 * =====================================
 */

.duel-result {
  overflow: hidden;

  margin-top: 18px;

  padding: 22px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 20px;

  background:
      radial-gradient(
          circle at 50% 0%,
          rgba(
              255,
              255,
              255,
              0.075
          ),
          transparent 52%
      ),
      rgba(
          255,
          255,
          255,
          0.025
      );

  text-align: center;

  animation:
      result-enter
      350ms
      ease;
}

.duel-result-victory {
  border-color:
      rgba(
          70,
          200,
          120,
          0.24
      );
}

.duel-result-defeat {
  border-color:
      rgba(
          225,
          80,
          90,
          0.18
      );
}

.result-hero {
  padding:
      8px
      0
      20px;
}

.result-status {
  margin:
      3px
      0
      4px;

  font-size:
      clamp(
          2rem,
          8vw,
          3.4rem
      );

  font-weight: 950;

  letter-spacing: -0.04em;
}

.duel-result-victory
.result-status {
  color:
      rgba(
          95,
          220,
          140,
          1
      );
}

.duel-result-defeat
.result-status {
  color:
      rgba(
          235,
          100,
          110,
          1
      );
}

.result-hero h2 {
  margin:
      0
      0
      8px;

  font-size: 1rem;
}

.result-gap {
  margin: 0;

  font-size: 0.7rem;

  opacity: 0.5;
}

.result-gap strong {
  margin-left: 4px;
}

/*
 * =====================================
 * RÉCOMPENSES
 * =====================================
 */

.result-rewards {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          1fr
      );

  gap: 8px;
}

.result-rewards article {
  display: grid;

  justify-items: center;

  padding:
      12px
      8px;

  border-radius: 12px;

  background:
      rgba(
          255,
          255,
          255,
          0.045
      );
}

.result-rewards span {
  font-size: 0.57rem;
  font-weight: 800;

  opacity: 0.4;
}

.result-rewards strong {
  margin:
      2px
      0;

  font-size: 1.15rem;
}

.result-rewards small {
  font-size: 0.56rem;

  opacity: 0.37;
}

/*
 * =====================================
 * ANTI FARMING
 * =====================================
 */

.pair-duels {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 14px;

  margin-top: 9px;

  padding:
      9px
      11px;

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );

  font-size: 0.65rem;
}

.pair-duels span {
  opacity: 0.45;
}

.reward-warning {
  margin:
      9px
      0
      0;

  text-align: left;
}

/*
 * =====================================
 * FIN DU DUEL
 * =====================================
 */

.duel-end-actions {
  display: grid;

  gap: 10px;

  margin-top: 12px;

  padding: 14px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.08
      );

  border-radius: 16px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.updated-car-summary {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 14px;

  padding:
      5px
      2px
      12px;

  border-bottom:
      1px solid
      rgba(
          255,
          255,
          255,
          0.06
      );
}

.updated-car-summary strong {
  font-size: 0.85rem;
}

.updated-car-values {
  display: flex;

  gap: 6px;
}

.updated-car-values span {
  padding:
      5px
      7px;

  border-radius: 7px;

  background:
      rgba(
          255,
          255,
          255,
          0.045
      );

  font-size: 0.58rem;

  opacity: 0.65;
}

/*
 * Nouveau duel
 * Revoir la course
 * Journal
 * Garage
 */
.duel-end-buttons {
  display: grid;

  grid-template-columns:
      minmax(
          0,
          1.5fr
      )
      minmax(
          0,
          1fr
      )
      minmax(
          0,
          1.25fr
      )
      auto;

  gap: 8px;
}

.new-duel-button {
  font-weight: 850;
}

.combat-log-button {
  white-space: nowrap;
}

.garage-link-button {
  padding:
      0
      10px;

  border: 0;

  background:
      transparent;

  color: inherit;

  cursor: pointer;

  font-size: 0.68rem;

  opacity: 0.45;
}

.garage-link-button:hover {
  opacity: 0.8;
}

/*
 * =====================================
 * ANIMATIONS
 * =====================================
 */

@keyframes road-scroll {
  from {
    transform:
        translateX(0);
  }

  to {
    transform:
        translateX(-60px);
  }
}

@keyframes road-center-scroll {
  from {
    transform:
        translate(
            0,
            -50%
        );
  }

  to {
    transform:
        translate(
            -82px,
            -50%
        );
  }
}

@keyframes finish-appear {
  from {
    opacity: 0;

    transform:
        translateX(40px);
  }

  to {
    opacity: 1;

    transform:
        translateX(0);
  }
}

@keyframes car-launch {
  0% {
    transform:
        translateX(-7px)
        scaleX(0.98);
  }

  35% {
    transform:
        translateX(-4px)
        scaleX(0.97);
  }

  100% {
    transform:
        translateX(7px)
        scaleX(1.02);
  }
}

@keyframes car-speed {
  0% {
    transform:
        translateY(0);
  }

  50% {
    transform:
        translateY(-1.5px);
  }

  100% {
    transform:
        translateY(0);
  }
}

@keyframes car-turn {
  0% {
    transform:
        rotate(0deg);
  }

  40% {
    transform:
        rotate(-1.8deg)
        translateY(2px);
  }

  100% {
    transform:
        rotate(0deg);
  }
}

@keyframes car-chicane {
  0% {
    transform:
        translateY(0)
        rotate(0deg);
  }

  25% {
    transform:
        translateY(-3px)
        rotate(-2deg);
  }

  60% {
    transform:
        translateY(3px)
        rotate(2deg);
  }

  100% {
    transform:
        translateY(0)
        rotate(0deg);
  }
}

@keyframes car-sprint {
  0% {
    transform:
        translate(
            0,
            0
        );
  }

  50% {
    transform:
        translate(
            1px,
            -1px
        );
  }

  100% {
    transform:
        translate(
            0,
            0
        );
  }
}

@keyframes car-finish {
  0% {
    transform:
        translateX(0);
  }

  100% {
    transform:
        translateX(12px);
  }
}

@keyframes result-enter {
  from {
    opacity: 0;

    transform:
        translateY(10px)
        scale(0.985);
  }

  to {
    opacity: 1;

    transform:
        translateY(0)
        scale(1);
  }
}

/*
 * =====================================
 * RESPONSIVE
 * =====================================
 */

@media (
max-width: 900px
) {
  .duel-end-buttons {
    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );
  }

  .garage-link-button {
    grid-column:
        1
        / -1;

    min-height: 32px;
  }
}

@media (
max-width: 700px
) {
  .versus-grid {
    grid-template-columns:
        1fr;
  }

  .versus-symbol {
    margin:
        -10px
        0;

    text-align: center;
  }

  .versus-car-visual {
    max-width: 280px;
  }

  .race-track {
    height: 235px;
  }

  .race-car {
    width: 105px;
  }

  .race-car-attacker {
    top: 22px;
  }

  .race-car-defender {
    bottom: 29px;
  }

  .race-car-name {
    margin-bottom: -8px;

    font-size: 0.58rem;
  }

  .finish-line {
    width: 22px;
  }

  .current-race-status {
    font-size: 0.7rem;
  }

  /*
   * JOURNAL
   */

  .combat-log-heading {
    align-items: stretch;

    flex-direction: column;
  }

  .combat-log-heading
  .button {
    width: 100%;
  }

  /*
   * RÉSULTAT
   */

  .duel-result {
    padding:
        16px
        12px;
  }

  .result-rewards {
    gap: 5px;
  }

  .result-rewards article {
    padding:
        10px
        5px;
  }

  /*
   * PROGRESSION
   */

  .updated-car-summary {
    align-items: flex-start;

    flex-direction: column;
  }

  .updated-car-values {
    width: 100%;
  }

  .updated-car-values span {
    flex: 1;

    text-align: center;
  }

  .duel-end-buttons {
    grid-template-columns:
        1fr;
  }

  .duel-end-buttons
  .button {
    width: 100%;
  }

  .garage-link-button {
    grid-column: auto;

    min-height: 35px;
  }
}

@media (
max-width: 450px
) {
  .race-track {
    height: 205px;
  }

  .race-car {
    width: 85px;
  }

  .race-car-attacker {
    top: 21px;
  }

  .race-car-defender {
    bottom: 29px;
  }
}
</style>