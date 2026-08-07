<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'
import CardChoicePanel from '../components/CardChoicePanel.vue'
import DuelNarrativeTerminal from '../components/DuelNarrativeTerminal.vue'
import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'
import { useDuelStore } from '../stores/duel'
import CarVisual
  from '../components/CarVisual.vue'
import type {
  ApiCollection,
  ApiRelation,
  Card,
  CardChoice,
  Car,
  PendingDuel,
} from '../types/api'

const router = useRouter()
const duelStore = useDuelStore()

const pendingDuel = ref<PendingDuel | null>(null)

const visibleEventCount = ref(0)
const replayStarted = ref(false)
const replayFinished = ref(false)

const pendingChoice = ref<CardChoice | null>(null)
const firstChoiceCard = ref<Card | null>(null)
const secondChoiceCard = ref<Card | null>(null)

const loadingChoice = ref(false)
const choiceSuccessMessage = ref('')
const updatedCar = ref<Car | null>(null)

let replayTimer: ReturnType<typeof setInterval> | null = null
let cooldownTimer: ReturnType<typeof setInterval> | null = null

const events = computed(
    () => duelStore.duel?.replayData.events ?? [],
)

const visibleEvents = computed(
    () => events.value.slice(0, visibleEventCount.value),
)

const currentEvent = computed(() => {
  if (visibleEventCount.value === 0) {
    return null
  }

  return events.value[visibleEventCount.value - 1] ?? null
})

const currentGap = computed(
    () => currentEvent.value?.gapAfter ?? 0,
)

const progressPercent = computed(() => {
  if (events.value.length === 0) {
    return 0
  }

  return Math.round(
      (visibleEventCount.value / events.value.length) * 100,
  )
})

const attackerPosition = computed(() =>
    clamp(
        4,
        96,
        4 +
        progressPercent.value * 0.9 +
        currentGap.value * 0.7,
    ),
)

const defenderPosition = computed(() =>
    clamp(
        4,
        96,
        4 +
        progressPercent.value * 0.9 -
        currentGap.value * 0.7,
    ),
)

const attackerWon = computed(() => {
  if (
      duelStore.duel === null ||
      pendingDuel.value === null
  ) {
    return false
  }

  return (
      duelStore.duel.replayData.winnerCarId ===
      pendingDuel.value.attackerCarId
  )
})

const winnerName = computed(() => {
  if (duelStore.duel === null) {
    return ''
  }

  return attackerWon.value
      ? duelStore.duel.attackerSnapshot.pilotName
      : duelStore.duel.defenderSnapshot.pilotName
})

const attackerRatingLabel = computed(() => {
  const value = duelStore.duel?.attackerRatingDelta ?? 0

  return value > 0
      ? `+${value}`
      : String(value)
})

function clamp(
    minimum: number,
    maximum: number,
    value: number,
): number {
  return Math.min(maximum, Math.max(minimum, value))
}

function loadPendingDuel(): void {
  const rawValue = sessionStorage.getItem(
      'street-rivals-pending-duel',
  )

  if (rawValue === null) {
    pendingDuel.value = null
    return
  }

  try {
    pendingDuel.value =
        JSON.parse(rawValue) as PendingDuel
  } catch {
    sessionStorage.removeItem(
        'street-rivals-pending-duel',
    )

    pendingDuel.value = null
  }
}

async function startDuel(): Promise<void> {
  if (pendingDuel.value === null) {
    return
  }

  stopReplay()
  replayStarted.value = false
  replayFinished.value = false
  visibleEventCount.value = 0
  pendingChoice.value = null
  choiceSuccessMessage.value = ''

  try {
    await duelStore.startDuel(
        pendingDuel.value.attackerCarId,
        pendingDuel.value.defenderCarId,
    )

    startReplay()
  } catch {
    startCooldownCountdown()
  }
}

function startReplay(): void {
  stopReplay()

  replayStarted.value = true
  replayFinished.value = false
  visibleEventCount.value = 0

  replayTimer = setInterval(() => {
    if (visibleEventCount.value < events.value.length) {
      ++visibleEventCount.value
      return
    }

    finishReplay()
  }, 1100)
}

function skipReplay(): void {
  visibleEventCount.value = events.value.length
  finishReplay()
}

function replayAgain(): void {
  startReplay()
}

function finishReplay(): void {
  stopReplay()

  visibleEventCount.value = events.value.length
  replayFinished.value = true

  void loadPendingCardChoice()
}

function stopReplay(): void {
  if (replayTimer !== null) {
    clearInterval(replayTimer)
    replayTimer = null
  }
}

function startCooldownCountdown(): void {
  stopCooldownCountdown()

  if (
      duelStore.retryAfterSeconds === null ||
      duelStore.retryAfterSeconds <= 0
  ) {
    return
  }

  cooldownTimer = setInterval(() => {
    if (
        duelStore.retryAfterSeconds === null ||
        duelStore.retryAfterSeconds <= 1
    ) {
      duelStore.retryAfterSeconds = 0
      stopCooldownCountdown()
      return
    }

    --duelStore.retryAfterSeconds
  }, 1000)
}

function stopCooldownCountdown(): void {
  if (cooldownTimer !== null) {
    clearInterval(cooldownTimer)
    cooldownTimer = null
  }
}

async function loadPendingCardChoice(): Promise<void> {
  if (pendingDuel.value === null) {
    return
  }

  loadingChoice.value = true

  try {
    const response =
        await apiRequest<ApiCollection<CardChoice>>(
            '/api/card_choices',
        )

    const choices = getCollectionMembers(response)

    const matchingChoices = choices
        .filter((choice) => {
          const carId = getRelationId(choice.car)

          return (
              carId === pendingDuel.value?.attackerCarId &&
              choice.selectedCard == null
          )
        })
        .sort((first, second) =>
            second.level - first.level,
        )

    pendingChoice.value =
        matchingChoices[0] ?? null

    if (pendingChoice.value === null) {
      firstChoiceCard.value = null
      secondChoiceCard.value = null

      await refreshAttackerCar()
      return
    }

    const [firstCard, secondCard] =
        await Promise.all([
          resolveCard(pendingChoice.value.firstCard),
          resolveCard(pendingChoice.value.secondCard),
        ])

    firstChoiceCard.value = firstCard
    secondChoiceCard.value = secondCard
  } catch (error) {
    if (error instanceof ApiError) {
      duelStore.errorMessage = error.message
    }
  } finally {
    loadingChoice.value = false
  }
}

async function resolveCard(
    relation: ApiRelation<Card>,
): Promise<Card> {
  if (typeof relation !== 'string') {
    return relation
  }

  return apiRequest<Card>(relation)
}

function getRelationId(
    relation: ApiRelation<{ id: number }>,
): number | null {
  if (typeof relation !== 'string') {
    return relation.id
  }

  const matches = relation.match(/\/(\d+)(?:\/[^/]*)?$/)

  if (matches === null) {
    return null
  }

  return Number.parseInt(matches[1], 10)
}

async function selectCard(
    cardId: number,
): Promise<void> {
  if (pendingChoice.value === null) {
    return
  }

  loadingChoice.value = true
  choiceSuccessMessage.value = ''

  try {
    await apiRequest(
        `/api/card_choices/${pendingChoice.value.id}/select`,
        {
          method: 'POST',
          body: JSON.stringify({
            cardId,
          }),
        },
    )

    choiceSuccessMessage.value =
        'La carte a été ajoutée à la voiture.'

    /*
     * Une réserve d’XP peut avoir créé un autre choix
     * immédiatement après le premier.
     */
    await loadPendingCardChoice()
  } catch (error) {
    if (error instanceof ApiError) {
      duelStore.errorMessage = error.message
    }
  } finally {
    loadingChoice.value = false
  }
}

async function refreshAttackerCar(): Promise<void> {
  if (pendingDuel.value === null) {
    return
  }

  try {
    updatedCar.value = await apiRequest<Car>(
        `/api/cars/${pendingDuel.value.attackerCarId}`,
    )
  } catch {
    updatedCar.value = null
  }
}

async function backToGarage(): Promise<void> {
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

onMounted(() => {
  duelStore.reset()
  loadPendingDuel()
})

onBeforeUnmount(() => {
  stopReplay()
  stopCooldownCountdown()
})
</script>

<template>
  <section class="duel-page">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Course classée</p>

        <h1>Duel de rue</h1>
      </div>

      <button
          type="button"
          class="button button-secondary"
          @click="backToGarage"
      >
        Retour au garage
      </button>
    </div>

    <div
        v-if="pendingDuel === null"
        class="empty-state"
    >
      <h2>Aucun duel préparé</h2>

      <p>
        Sélectionne un adversaire depuis le garage.
      </p>

      <button
          type="button"
          class="button button-primary"
          @click="backToGarage"
      >
        Ouvrir le garage
      </button>
    </div>

    <template v-else>
      <section
          v-if="duelStore.duel === null"
          class="panel duel-preview"
      >
        <div class="versus-car">
          <div class="versus-car-visual">
            <CarVisual
                :color="
          pendingDuel.attackerColor
        "
                :body-style="
          pendingDuel.attackerBodyStyle
        "
                :wheel-style="
          pendingDuel.attackerWheelStyle
        "
                :pilot-name="
          pendingDuel.attackerPilotName
        "
            />
          </div>

          <span>
    Attaquant
  </span>

          <strong>
            {{
              pendingDuel
                  .attackerPilotName
            }}
          </strong>
        </div>

        <div class="versus-symbol">
          VS
        </div>

        <div class="versus-car">
          <div class="versus-car-visual">
            <CarVisual
                :color="
          pendingDuel.defenderColor
        "
                :body-style="
          pendingDuel.defenderBodyStyle
        "
                :wheel-style="
          pendingDuel.defenderWheelStyle
        "
                :pilot-name="
          pendingDuel.defenderPilotName
        "
            />
          </div>

          <span>
    Défenseur
  </span>

          <strong>
            {{
              pendingDuel
                  .defenderPilotName
            }}
          </strong>
        </div>

        <p class="duel-difficulty">
          Difficulté :
          <strong>{{ pendingDuel.difficulty }}</strong>
        </p>

        <p
            v-if="duelStore.errorMessage !== ''"
            class="alert alert-error"
        >
          {{ duelStore.errorMessage }}

          <span
              v-if="
              duelStore.retryAfterSeconds !== null &&
              duelStore.retryAfterSeconds > 0
            "
          >
            Nouvelle tentative possible dans
            {{ duelStore.retryAfterSeconds }} seconde(s).
          </span>
        </p>

        <button
            type="button"
            class="button button-primary button-full"
            :disabled="
            duelStore.loading ||
            (
              duelStore.retryAfterSeconds !== null &&
              duelStore.retryAfterSeconds > 0
            )
          "
            @click="startDuel"
        >
          {{
            duelStore.loading
                ? 'Simulation en cours...'
                : 'Lancer le duel'
          }}
        </button>
      </section>

      <template v-else>
        <section class="panel race-replay">
          <div class="section-heading">
            <div>
              <p class="eyebrow">
                Moteur {{ duelStore.duel.engineVersion }}
              </p>

              <h2>
                {{ duelStore.duel.attackerSnapshot.pilotName }}
                contre
                {{ duelStore.duel.defenderSnapshot.pilotName }}
              </h2>
            </div>

            <button
                v-if="!replayFinished"
                type="button"
                class="button button-secondary"
                @click="skipReplay"
            >
              Passer le replay
            </button>

            <button
                v-else
                type="button"
                class="button button-secondary"
                @click="replayAgain"
            >
              Revoir la course
            </button>
          </div>

          <div class="race-track">
            <div class="race-lane">
              <div
                  class="
      race-runner
      race-runner-attacker
    "
                  :style="{
      left:
        `${attackerPosition}%`,
    }"
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

            <div class="race-lane">
              <div
                  class="
      race-runner
      race-runner-defender
    "
                  :style="{
      left:
        `${defenderPosition}%`,
    }"
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
                  width: `${progressPercent}%`,
                }"
              />
            </div>
          </div>

          <p class="current-race-status">
            Progression : {{ progressPercent }} %
            · écart :
            {{ currentGap > 0 ? '+' : '' }}{{ currentGap }}
          </p>
        </section>

        <DuelNarrativeTerminal
            :events="visibleEvents"
            :attacker-name="
      duelStore.duel.attackerSnapshot.pilotName
    "
            :defender-name="
      duelStore.duel.defenderSnapshot.pilotName
    "
            :replay-finished="replayFinished"
            :winner-name="winnerName"
            :final-gap="duelStore.duel.finalGap"
        />

        <section
            v-if="replayFinished"
            class="panel duel-result"
        >
          <p class="eyebrow">
            Résultat officiel
          </p>

          <h2>
            Victoire de {{ winnerName }}
          </h2>

          <p>
            Écart final :
            <strong>
              {{ duelStore.duel.finalGap > 0 ? '+' : '' }}
              {{ duelStore.duel.finalGap }}
            </strong>
          </p>

          <div class="result-grid">
            <div>
              <span>Récompense XP</span>

              <strong>
                +{{ duelStore.duel.attackerXpReward }}
              </strong>
            </div>

            <div>
              <span>Argent gagné</span>

              <strong>
                +{{ duelStore.duel.attackerMoneyReward }} $
              </strong>
            </div>

            <div>
              <span>Évolution Elo</span>

              <strong>{{ attackerRatingLabel }}</strong>

              <small>
                {{ duelStore.duel.attackerRatingBefore }}
                →
                {{ duelStore.duel.attackerRatingAfter }}
              </small>
            </div>

            <div>
              <span>Duels de la paire</span>

              <strong>
                {{
                  duelStore.duel.replayData.antiFarming
                      ?.pairDuelNumber ?? 1
                }}
                /
                {{
                  duelStore.duel.replayData.antiFarming
                      ?.pairDailyLimit ?? 3
                }}
              </strong>
            </div>
          </div>

          <p
              v-if="
              duelStore.duel.replayData.rewards
                ?.multiplier !== undefined &&
              duelStore.duel.replayData.rewards.multiplier < 1
            "
              class="alert alert-warning"
          >
            Les récompenses ont été réduites en raison
            d’affrontements répétés contre cet adversaire.
          </p>
        </section>

        <p
            v-if="choiceSuccessMessage !== ''"
            class="alert alert-success"
        >
          {{ choiceSuccessMessage }}
        </p>

        <CardChoicePanel
            v-if="
            replayFinished &&
            pendingChoice !== null &&
            firstChoiceCard !== null &&
            secondChoiceCard !== null
          "
            :level="pendingChoice.level"
            :first-card="firstChoiceCard"
            :second-card="secondChoiceCard"
            :loading="loadingChoice"
            @select="selectCard"
        />

        <section
            v-else-if="replayFinished && !loadingChoice"
            class="duel-actions"
        >
          <p v-if="updatedCar !== null">
            {{ updatedCar.pilotName }} est niveau
            {{ updatedCar.level }}, avec
            {{ updatedCar.xp }} XP et
            {{ updatedCar.money }} $.
          </p>

          <button
              type="button"
              class="button button-primary"
              @click="backToGarage"
          >
            Retourner au garage
          </button>
        </section>
      </template>
    </template>
  </section>
</template>
<style scoped>
.versus-car-visual {
  width: 100%;
  max-width: 360px;

  margin: 0 auto 8px;
}

.versus-car {
  min-width: 0;
}
.race-runner {
  position: absolute;

  width: 110px;

  transform:
      translateX(-50%);

  transition:
      left
      700ms
      cubic-bezier(
          0.22,
          0.61,
          0.36,
          1
      );

  z-index: 2;
}

.race-runner-attacker {
  bottom: -15px;
}

.race-runner-defender {
  bottom: -15px;
}

@media (
max-width: 650px
) {
  .race-runner {
    width: 75px;
  }
}
</style>