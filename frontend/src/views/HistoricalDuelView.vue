<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import CarVisual
  from '../components/CarVisual.vue'

import DuelNarrativeTerminal
  from '../components/DuelNarrativeTerminal.vue'

import {
  ApiError,
  apiRequest,
} from '../services/api'

import type {
  HistoricalDuelReplay,
} from '../types/api'

const route =
    useRoute()

const router =
    useRouter()

const replay =
    ref<
        HistoricalDuelReplay
        | null
    >(
        null,
    )

const loading =
    ref(true)

const errorMessage =
    ref('')

const visibleEventCount =
    ref(0)

const replayStarted =
    ref(false)

const replayFinished =
    ref(false)

const showCombatLog =
    ref(false)

let replayTimer:
    ReturnType<typeof setInterval>
    | null = null

/*
 * =====================================
 * DUEL
 * =====================================
 */

const events =
    computed(
        () =>
            replay.value
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

const attackerIsLeading =
    computed(
        () =>
            currentGap.value
            > 0,
    )

const defenderIsLeading =
    computed(
        () =>
            currentGap.value
            < 0,
    )

const finishVisible =
    computed(
        () =>
            progressPercent.value
            >= 70,
    )

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

/*
 * =====================================
 * RESULTAT
 * =====================================
 */

const winnerName =
    computed(() => {
      if (
          replay.value === null
      ) {
        return ''
      }

      return (
          replay.value
              .winnerCarId
          === replay.value
              .attackerSnapshot
              .carId
      )
          ? replay.value
              .attackerSnapshot
              .pilotName

          : replay.value
              .defenderSnapshot
              .pilotName
    })

const ratingDeltaLabel =
    computed(() => {
      const delta =
          replay.value
              ?.viewerRating
              .delta
          ?? 0

      return delta > 0
          ? `+${delta}`
          : String(delta)
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
 * CHARGEMENT
 * =====================================
 */

async function loadReplay():
    Promise<void> {
  loading.value =
      true

  errorMessage.value =
      ''

  const duelId =
      Number.parseInt(
          String(
              route.params.id,
          ),
          10,
      )

  if (
      !Number.isInteger(
          duelId,
      )
      || duelId <= 0
  ) {
    errorMessage.value =
        'Identifiant du duel invalide.'

    loading.value =
        false

    return
  }

  try {
    replay.value =
        await apiRequest<
            HistoricalDuelReplay
        >(
            `/api/duels/${duelId}/replay`,
        )

    startReplay()
  } catch (
      error
      ) {
    if (
        error
        instanceof ApiError
    ) {
      errorMessage.value =
          error.message
    } else {
      errorMessage.value =
          'Impossible de charger ce replay.'
    }
  } finally {
    loading.value =
        false
  }
}

/*
 * =====================================
 * REPLAY
 * =====================================
 */

function startReplay():
    void {
  stopReplay()

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

function finishReplay():
    void {
  stopReplay()

  visibleEventCount.value =
      events.value.length

  replayFinished.value =
      true

  showCombatLog.value =
      false
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
 * NAVIGATION
 * =====================================
 */

async function backToHistory():
    Promise<void> {
  stopReplay()

  await router.push({
    name: 'ranking',

    query: {
      tab: 'history',
    },
  })
}

onMounted(
    async () => {
      await loadReplay()
    },
)

onBeforeUnmount(
    () => {
      stopReplay()
    },
)
</script>

<template>
  <section class="historical-duel">
    <!-- HEADER -->

    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Archive
        </p>

        <h1>
          Replay du duel
        </h1>
      </div>

      <button
          type="button"
          class="
            button
            button-secondary
          "
          @click="
            backToHistory
          "
      >
        Retour à l'historique
      </button>
    </div>

    <!-- LOADING -->

    <div
        v-if="
          loading
        "
        class="
          replay-loading
        "
    >
      Chargement du replay...
    </div>

    <!-- ERREUR -->

    <div
        v-else-if="
          errorMessage !== ''
        "
        class="
          empty-state
        "
    >
      <h2>
        Replay indisponible
      </h2>

      <p>
        {{ errorMessage }}
      </p>

      <button
          type="button"
          class="
            button
            button-primary
          "
          @click="
            backToHistory
          "
      >
        Retour
      </button>
    </div>

    <template
        v-else-if="
          replay !== null
        "
    >
      <!-- =====================================
           COURSE
      ====================================== -->

      <section
          v-if="
            !replayFinished
            || !showCombatLog
          "
          class="
            panel
            race-replay
          "
      >
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Replay historique ·
              Moteur
              {{ replay.engineVersion }}
            </p>

            <h2>
              {{
                replay
                    .attackerSnapshot
                    .pilotName
              }}

              contre

              {{
                replay
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
            Passer
          </button>
        </div>

        <div
            class="race-track"
            :class="{
              'race-track-running':
                replayStarted
                && !replayFinished,
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

            <div class="road-center-line" />

            <div
                v-if="
                  finishVisible
                "
                class="
                  finish-line
                "
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
                replay
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

            <div class="race-car-visual">
              <CarVisual
                  :color="
                    replay
                        .attackerSnapshot
                        .color
                  "
                  :body-style="
                    replay
                        .attackerSnapshot
                        .bodyStyle
                    ?? 'coupe_01'
                  "
                  :wheel-style="
                    replay
                        .attackerSnapshot
                        .wheelStyle
                    ?? 'street_01'
                  "
                  :pilot-name="
                    replay
                        .attackerSnapshot
                        .pilotName
                  "
              />
            </div>
          </div>

          <!-- DEFENSEUR -->

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
                replay
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

            <div class="race-car-visual">
              <CarVisual
                  :color="
                    replay
                        .defenderSnapshot
                        .color
                  "
                  :body-style="
                    replay
                        .defenderSnapshot
                        .bodyStyle
                    ?? 'coupe_01'
                  "
                  :wheel-style="
                    replay
                        .defenderSnapshot
                        .wheelStyle
                    ?? 'street_01'
                  "
                  :pilot-name="
                    replay
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

        <div class="race-status">
          <span>
            Progression

            <strong>
              {{ progressPercent }} %
            </strong>
          </span>

          <span>
            Écart

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
            Section

            <strong>
              {{ currentEvent.label }}
            </strong>
          </span>
        </div>
      </section>

      <!-- =====================================
           JOURNAL LIVE / HISTORIQUE
      ====================================== -->

      <div
          v-if="
            (
              !replayFinished
            )
            || showCombatLog
          "
          class="
            combat-log
          "
      >
        <DuelNarrativeTerminal
            :events="
              visibleEvents
            "
            :attacker-name="
              replay
                  .attackerSnapshot
                  .pilotName
            "
            :defender-name="
              replay
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
              replay.finalGap
            "
        />
      </div>

      <!-- =====================================
           RESULTAT
      ====================================== -->

      <section
          v-if="
            replayFinished
          "
          class="
            replay-result
          "
          :class="{
            victory:
              replay.viewerWon,

            defeat:
              !replay.viewerWon,
          }"
      >
        <p class="eyebrow">
          Résultat historique
        </p>

        <div class="result-title">
          {{
            replay.viewerWon
                ? 'VICTOIRE'
                : 'DÉFAITE'
          }}
        </div>

        <h2>
          Victoire de {{ winnerName }}
        </h2>

        <div class="result-values">
          <article>
            <span>
              XP
            </span>

            <strong>
              +{{
                replay
                    .viewerRewards
                    .xp
              }}
            </strong>
          </article>

          <article>
            <span>
              $
            </span>

            <strong>
              +{{
                replay
                    .viewerRewards
                    .money
              }}
            </strong>
          </article>

          <article>
            <span>
              ELO
            </span>

            <strong>
              {{ ratingDeltaLabel }}
            </strong>

            <small>
              {{
                replay
                    .viewerRating
                    .before
              }}

              →

              {{
                replay
                    .viewerRating
                    .after
              }}
            </small>
          </article>
        </div>
      </section>

      <!-- ACTIONS -->

      <section
          v-if="
            replayFinished
          "
          class="
            replay-actions
          "
      >
        <button
            type="button"
            class="
              button
              button-primary
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
              button
              button-secondary
            "
            @click="
              backToHistory
            "
        >
          Retour à l'historique
        </button>
      </section>
    </template>
  </section>
</template>

<style scoped>
.historical-duel {
  display: grid;

  gap: 16px;
}

.replay-loading {
  padding: 60px;

  text-align: center;

  opacity: 0.5;
}

/*
 * =====================================
 * PISTE
 * =====================================
 */

.race-track {
  position: relative;

  height: 300px;

  overflow: hidden;

  margin-top: 20px;

  border-radius: 18px;

  background: #202327;

  isolation: isolate;
}

.road-background {
  position: absolute;

  inset: 0;

  overflow: hidden;

  background:
      linear-gradient(
          180deg,
          #30343a,
          #212428
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
}

.race-track-running
.road-background::before {
  animation:
      road-scroll
      600ms
      linear
      infinite;
}

.road-edge {
  position: absolute;

  left: 0;

  width: 200%;
  height: 8px;

  background:
      repeating-linear-gradient(
          90deg,
          #eeeeee 0,
          #eeeeee 30px,
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

  opacity: 0.4;
}

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
          #eee 25%,
          #171717 0 50%,
          #eee 0 75%,
          #171717 0
      );

  background-size:
      16px
      16px;
}

.finish-line span {
  position: absolute;

  top: 0;
  left: 50%;

  transform:
      translate(
          -50%,
          -100%
      );

  font-size: 0.55rem;
  font-weight: 900;
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
}

.race-car-name {
  display: flex;

  align-items: center;
  justify-content: center;

  gap: 5px;

  margin-bottom: -10px;

  font-size: 0.68rem;
  font-weight: 900;
}

.leader-badge {
  display: inline-flex;

  width: 18px;
  height: 18px;

  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: white;
  color: #111;

  font-size: 0.6rem;
}

.race-car-leading {
  z-index: 7;
}

.race-progress {
  position: absolute;

  right: 20px;
  bottom: 8px;
  left: 20px;

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
}

.race-progress span {
  display: block;

  height: 100%;

  background: currentColor;

  transition:
      width
      800ms
      ease;
}

.race-status {
  display: flex;

  flex-wrap: wrap;

  gap: 15px;

  margin-top: 10px;

  font-size: 0.7rem;

  opacity: 0.6;
}

/*
 * =====================================
 * MOUVEMENTS
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

/*
 * =====================================
 * RESULTAT
 * =====================================
 */

.replay-result {
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

  text-align: center;
}

.replay-result.victory {
  border-color:
      rgba(
          75,
          205,
          125,
          0.35
      );
}

.replay-result.defeat {
  border-color:
      rgba(
          225,
          85,
          95,
          0.3
      );
}

.result-title {
  font-size:
      clamp(
          2rem,
          8vw,
          3.5rem
      );

  font-weight: 950;
}

.result-values {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          1fr
      );

  gap: 7px;

  margin-top: 15px;
}

.result-values article {
  display: grid;

  padding: 10px;

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );
}

.result-values span,
.result-values small {
  font-size: 0.55rem;

  opacity: 0.4;
}

.replay-actions {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          minmax(
              0,
              1fr
          )
      );

  gap: 8px;
}

/*
 * =====================================
 * ANIMATIONS
 * =====================================
 */

@keyframes road-scroll {
  to {
    transform:
        translateX(-60px);
  }
}

@keyframes car-launch {
  from {
    transform:
        translateX(-7px);
  }

  to {
    transform:
        translateX(7px);
  }
}

@keyframes car-speed {
  50% {
    transform:
        translateY(-1.5px);
  }
}

@keyframes car-turn {
  40% {
    transform:
        rotate(-2deg)
        translateY(2px);
  }
}

@keyframes car-chicane {
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
}

@keyframes car-sprint {
  50% {
    transform:
        translate(
            1px,
            -1px
        );
  }
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 650px
) {
  .race-track {
    height: 220px;
  }

  .race-car {
    width: 95px;
  }

  .race-car-attacker {
    top: 22px;
  }

  .race-car-defender {
    bottom: 29px;
  }

  .result-values {
    gap: 4px;
  }

  .replay-actions {
    grid-template-columns:
        1fr;
  }
}
</style>