<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'
import CardChoicePanel from '../components/CardChoicePanel.vue'
import ReplayEventCard from '../components/ReplayEventCard.vue'
import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'
import { useDuelStore } from '../stores/duel'
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
        <div class="versus-grid">
          <div class="versus-car">
            <div
                class="versus-color"
                :style="{
                backgroundColor:
                  pendingDuel.attackerColor,
              }"
            />

            <span>Attaquant</span>

            <strong>
              {{ pendingDuel.attackerPilotName }}
            </strong>
          </div>

          <div class="versus-symbol">
            VS
          </div>

          <div class="versus-car">
            <div
                class="versus-color"
                :style="{
                backgroundColor:
                  pendingDuel.defenderColor,
              }"
            />

            <span>Défenseur</span>

            <strong>
              {{ pendingDuel.defenderPilotName }}
            </strong>
          </div>
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
              <span
                  class="race-runner"
                  :style="{
                  left: `${attackerPosition}%`,
                  backgroundColor:
                    duelStore.duel.attackerSnapshot.color,
                }"
              >
                A
              </span>
            </div>

            <div class="race-lane">
              <span
                  class="race-runner"
                  :style="{
                  left: `${defenderPosition}%`,
                  backgroundColor:
                    duelStore.duel.defenderSnapshot.color,
                }"
              >
                D
              </span>
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

        <section class="replay-events">
          <ReplayEventCard
              v-for="event in visibleEvents"
              :key="`${event.index}-${event.type}`"
              :event="event"
              :attacker-name="
              duelStore.duel.attackerSnapshot.pilotName
            "
              :defender-name="
              duelStore.duel.defenderSnapshot.pilotName
            "
          />
        </section>

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
                / 3
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