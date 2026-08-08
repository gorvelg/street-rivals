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
 *
 * L'écart entre les voitures est ensuite
 * ajouté autour de cette position.
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
 * Animation visuelle correspondant
 * à la portion du replay actuellement jouée.
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

  visibleEventCount.value =
      0

  pendingChoice.value =
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
  startReplay()
}

function finishReplay():
    void {
  stopReplay()

  visibleEventCount.value =
      events.value.length

  replayFinished.value =
      true

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

async function refreshAttackerCar():
    Promise<void> {
  if (
      pendingDuel.value === null
  ) {
    return
  }

  try {
    updatedCar.value =
        await apiRequest<Car>(
            `/api/cars/${pendingDuel.value.attackerCarId}`,
        )
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
        Retour aux Duels
      </button>
    </div>

    <!-- =====================================
         AUCUN DUEL
    ====================================== -->

    <div
        v-if="
          pendingDuel === null
        "
        class="empty-state"
    >
      <h2>
        Aucun duel préparé
      </h2>

      <p>
        Sélectionne un adversaire depuis le matchmaking.
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
        Ouvrir les duels
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
            panel
            duel-preview
          "
      >
        <div class="versus-grid">
          <!-- ATTAQUANT -->

          <div class="versus-car">
            <div
                class="
                  versus-car-visual
                "
            >
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

            <span class="versus-role">
              Attaquant
            </span>

            <strong>
              {{
                pendingDuel
                    .attackerPilotName
              }}
            </strong>
          </div>

          <!-- VS -->

          <div class="versus-symbol">
            VS
          </div>

          <!-- DÉFENSEUR -->

          <div class="versus-car">
            <div
                class="
                  versus-car-visual
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

            <span class="versus-role">
              Défenseur
            </span>

            <strong>
              {{
                pendingDuel
                    .defenderPilotName
              }}
            </strong>
          </div>
        </div>

        <p class="duel-difficulty">
          Difficulté :

          <strong>
            {{
              pendingDuel.difficulty
            }}
          </strong>
        </p>

        <p
            v-if="
              duelStore.errorMessage
              !== ''
            "
            class="
              alert
              alert-error
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
            {{ duelStore.retryAfterSeconds }}
            seconde(s).
          </span>
        </p>

        <button
            type="button"
            class="
              button
              button-primary
              button-full
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
          {{
            duelStore.loading
                ? 'Simulation en cours...'
                : 'Lancer le duel'
          }}
        </button>
      </section>

      <!-- =====================================
           REPLAY
      ====================================== -->

      <template v-else>
        <section
            class="
              panel
              race-replay
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

            <button
                v-else
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
            <!-- ROUTE -->

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

              <!-- ARRIVÉE -->

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

            <!-- =================================
                 ATTAQUANT
            ================================== -->

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

            <!-- =================================
                 DÉFENSEUR
            ================================== -->

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

            <!-- =================================
                 PROGRESSION
            ================================== -->

            <div class="race-progress">
              <span
                  :style="{
                    width:
                      `${progressPercent}%`,
                  }"
              />
            </div>
          </div>

          <!-- ÉTAT DE COURSE -->

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
             TERMINAL NARRATIF
        ====================================== -->

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

        <!-- =====================================
             RÉSULTAT
        ====================================== -->

        <section
            v-if="
              replayFinished
            "
            class="
              panel
              duel-result
            "
        >
          <p class="eyebrow">
            Résultat officiel
          </p>

          <h2>
            Victoire de
            {{ winnerName }}
          </h2>

          <p>
            Écart final :

            <strong>
              {{
                duelStore.duel
                    .finalGap > 0
                    ? '+'
                    : ''
              }}

              {{
                duelStore.duel
                    .finalGap
              }}
            </strong>
          </p>

          <div class="result-grid">
            <div>
              <span>
                Récompense XP
              </span>

              <strong>
                +{{
                  duelStore.duel
                      .attackerXpReward
                }}
              </strong>
            </div>

            <div>
              <span>
                Argent gagné
              </span>

              <strong>
                +{{
                  duelStore.duel
                      .attackerMoneyReward
                }}
                $
              </strong>
            </div>

            <div>
              <span>
                Évolution Elo
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
            </div>

            <div>
              <span>
                Duels de la paire
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
              "
          >
            Les récompenses ont été réduites en raison
            d’affrontements répétés contre cet adversaire.
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
             RETOUR GARAGE
        ====================================== -->

        <section
            v-else-if="
              replayFinished
              && !loadingChoice
            "
            class="duel-actions"
        >
          <p
              v-if="
                updatedCar !== null
              "
          >
            {{
              updatedCar.pilotName
            }}
            est niveau

            {{
              updatedCar.level
            }},
            avec

            {{
              updatedCar.xp
            }}
            XP et

            {{
              updatedCar.money
            }}
            $.
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
            Retourner aux Duels
          </button>
        </section>
      </template>
    </template>
  </section>
</template>

<style scoped>
/*
 * =====================================
 * PRÉ-DUEL
 * =====================================
 */

.versus-grid {
  display: grid;

  grid-template-columns:
      minmax(0, 1fr)
      auto
      minmax(0, 1fr);

  gap: 24px;

  align-items: center;
}

.versus-car {
  min-width: 0;

  display: grid;

  justify-items: center;

  gap: 3px;

  text-align: center;
}

.versus-car-visual {
  width: 100%;
  max-width: 360px;

  margin:
      0
      auto
      8px;
}

.versus-role {
  font-size: 0.72rem;

  text-transform: uppercase;

  letter-spacing: 0.08em;

  opacity: 0.55;
}

.versus-car strong {
  font-size: 1.15rem;
}

.versus-symbol {
  font-size: 1.4rem;

  font-weight: 900;

  opacity: 0.6;
}

.duel-difficulty {
  text-align: center;
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

/*
 * Texture mobile.
 */
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

/*
 * DÉPART
 *
 * La voiture se tasse puis bondit.
 */
.motion-start
.race-car-visual {
  animation:
      car-launch
      700ms
      ease-out;
}

/*
 * LIGNE DROITE
 *
 * Petite vibration moteur.
 */
.motion-straight
.race-car-visual {
  animation:
      car-speed
      240ms
      linear
      infinite;
}

/*
 * VIRAGE
 */
.motion-turn
.race-car-visual {
  animation:
      car-turn
      750ms
      ease-in-out;
}

/*
 * CHICANE
 */
.motion-chicane
.race-car-visual {
  animation:
      car-chicane
      520ms
      ease-in-out;
}

/*
 * SPRINT FINAL
 */
.motion-sprint
.race-car-visual {
  animation:
      car-sprint
      160ms
      linear
      infinite;
}

/*
 * PHOTO-FINISH
 */
.motion-finish
.race-car-visual {
  animation:
      car-finish
      500ms
      ease-out;
}

/*
 * =====================================
 * PROGRESSION
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

/*
 * =====================================
 * RESPONSIVE
 * =====================================
 */

@media (
max-width: 700px
) {
  .versus-grid {
    grid-template-columns: 1fr;
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
    font-size: 0.58rem;

    margin-bottom: -8px;
  }

  .finish-line {
    width: 22px;
  }

  .current-race-status {
    font-size: 0.7rem;
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