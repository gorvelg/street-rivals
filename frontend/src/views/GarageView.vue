<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'
import GarageCarCard from '../components/GarageCarCard.vue'
import OpponentCard from '../components/OpponentCard.vue'
import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'
import type {
  ApiCollection,
  Car,
  CarStats,
  MatchmakingOpponent,
  PendingDuel,
} from '../types/api'

const router = useRouter()

const cars = ref<Car[]>([])
const selectedCarId = ref<number | null>(null)
const selectedOpponent =
    ref<MatchmakingOpponent | null>(null)

const carStats = ref<CarStats | null>(null)
const opponents = ref<MatchmakingOpponent[]>([])

const loadingCars = ref(false)
const loadingDetails = ref(false)
const creatingCar = ref(false)

const errorMessage = ref('')
const successMessage = ref('')

const showCreateForm = ref(false)
const pilotName = ref('')
const carColor = ref('#e63946')

const selectedCar = computed<Car | null>(() => {
  return (
      cars.value.find(
          (car) => car.id === selectedCarId.value,
      ) ?? null
  )
})

async function loadCars(): Promise<void> {
  loadingCars.value = true
  errorMessage.value = ''

  try {
    const response =
        await apiRequest<ApiCollection<Car>>('/api/cars')

    cars.value = getCollectionMembers(response)

    if (cars.value.length === 0) {
      showCreateForm.value = true
      selectedCarId.value = null

      return
    }

    const storedCarValue = localStorage.getItem(
        'street-rivals-selected-car',
    )

    const storedCarId =
        storedCarValue !== null
            ? Number.parseInt(storedCarValue, 10)
            : null

    const storedCarExists =
        storedCarId !== null &&
        Number.isInteger(storedCarId) &&
        cars.value.some(
            (car) => car.id === storedCarId,
        )

    selectedCarId.value = storedCarExists
        ? storedCarId
        : cars.value[0].id
  } catch (error) {
    handleError(error)
  } finally {
    loadingCars.value = false
  }
}

async function loadSelectedCarDetails(): Promise<void> {
  if (selectedCarId.value === null) {
    carStats.value = null
    opponents.value = []
    selectedOpponent.value = null

    return
  }

  loadingDetails.value = true
  errorMessage.value = ''
  selectedOpponent.value = null

  try {
    const [statsResponse, opponentsResponse] =
        await Promise.all([
          apiRequest<CarStats>(
              `/api/cars/${selectedCarId.value}/stats`,
          ),

          apiRequest<ApiCollection<MatchmakingOpponent>>(
              `/api/cars/${selectedCarId.value}/opponents`,
          ),
        ])

    carStats.value = statsResponse

    opponents.value =
        getCollectionMembers(opponentsResponse)
  } catch (error) {
    carStats.value = null
    opponents.value = []

    handleError(error)
  } finally {
    loadingDetails.value = false
  }
}

async function createCar(): Promise<void> {
  creatingCar.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const createdCar = await apiRequest<Car>(
        '/api/cars',
        {
          method: 'POST',
          body: JSON.stringify({
            pilotName: pilotName.value,
            color: carColor.value,
          }),
        },
    )

    pilotName.value = ''
    showCreateForm.value = false

    await loadCars()

    selectedCarId.value = createdCar.id

    successMessage.value =
        'La voiture a été créée avec succès.'
  } catch (error) {
    handleError(error)
  } finally {
    creatingCar.value = false
  }
}

function selectCar(carId: number): void {
  successMessage.value = ''
  selectedCarId.value = carId
}

function selectOpponent(
    opponent: MatchmakingOpponent,
): void {
  if (
      selectedCarId.value === null ||
      selectedCar.value === null
  ) {
    return
  }

  selectedOpponent.value = opponent

  const pendingDuel: PendingDuel = {
    attackerCarId: selectedCarId.value,
    attackerPilotName:
    selectedCar.value.pilotName,
    attackerColor: selectedCar.value.color,

    defenderCarId: opponent.carId,
    defenderPilotName: opponent.pilotName,
    defenderColor: opponent.color,

    difficulty: opponent.difficulty,
  }

  sessionStorage.setItem(
      'street-rivals-pending-duel',
      JSON.stringify(pendingDuel),
  )

  successMessage.value =
      `${opponent.pilotName} a été sélectionné.`
}

async function openDuel(): Promise<void> {
  if (
      selectedOpponent.value === null ||
      selectedCarId.value === null
  ) {
    return
  }

  await router.push({
    name: 'duel',
  })
}

function handleError(error: unknown): void {
  if (error instanceof ApiError) {
    errorMessage.value = error.message

    return
  }

  errorMessage.value =
      'Une erreur inattendue est survenue.'
}

watch(selectedCarId, async (carId) => {
  if (carId === null) {
    localStorage.removeItem(
        'street-rivals-selected-car',
    )

    carStats.value = null
    opponents.value = []
    selectedOpponent.value = null

    return
  }

  localStorage.setItem(
      'street-rivals-selected-car',
      String(carId),
  )

  await loadSelectedCarDetails()
})

onMounted(async () => {
  await loadCars()
})
</script>

<template>
  <section class="garage-page">
    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Mon garage
        </p>

        <h1>
          Choisis ton pilote
        </h1>
      </div>

      <button
          type="button"
          class="button button-secondary"
          @click="showCreateForm = !showCreateForm"
      >
        Ajouter une voiture
      </button>
    </div>

    <p
        v-if="errorMessage !== ''"
        class="alert alert-error"
    >
      {{ errorMessage }}
    </p>

    <p
        v-if="successMessage !== ''"
        class="alert alert-success"
    >
      {{ successMessage }}
    </p>

    <form
        v-if="showCreateForm"
        class="panel create-car-form"
        @submit.prevent="createCar"
    >
      <h2>
        Nouvelle voiture
      </h2>

      <div class="form-row">
        <label class="form-grow">
          Nom du pilote

          <input
              v-model.trim="pilotName"
              type="text"
              minlength="2"
              maxlength="32"
              autocomplete="off"
              required
          />
        </label>

        <label>
          Couleur

          <input
              v-model="carColor"
              type="color"
              required
          />
        </label>

        <button
            type="submit"
            class="button button-primary"
            :disabled="creatingCar"
        >
          {{
            creatingCar
                ? 'Création...'
                : 'Créer'
          }}
        </button>
      </div>
    </form>

    <p v-if="loadingCars">
      Chargement du garage...
    </p>

    <div
        v-else-if="cars.length > 0"
        class="garage-list"
    >
      <GarageCarCard
          v-for="car in cars"
          :key="car.id"
          :car="car"
          :selected="car.id === selectedCarId"
          @select="selectCar"
      />
    </div>

    <div
        v-else-if="!showCreateForm"
        class="empty-state"
    >
      <h2>
        Ton garage est vide
      </h2>

      <p>
        Crée ta première voiture pour commencer à jouer.
      </p>

      <button
          type="button"
          class="button button-primary"
          @click="showCreateForm = true"
      >
        Créer une voiture
      </button>
    </div>

    <template v-if="selectedCar !== null">
      <section class="panel selected-car-panel">
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Voiture sélectionnée
            </p>

            <h2>
              {{ selectedCar.pilotName }}
            </h2>
          </div>

          <div
              class="large-car-color"
              :style="{
              backgroundColor: selectedCar.color,
            }"
          />
        </div>

        <p v-if="loadingDetails">
          Calcul des statistiques...
        </p>

        <template v-else-if="carStats !== null">
          <div class="stats-grid">
            <div>
              <span>
                Vitesse
              </span>

              <strong>
                {{ carStats.effective.speed }}
              </strong>

              <small>
                Base {{ carStats.base.speed }}
                + {{ carStats.bonuses.speed }}
              </small>
            </div>

            <div>
              <span>
                Accélération
              </span>

              <strong>
                {{ carStats.effective.acceleration }}
              </strong>

              <small>
                Base {{ carStats.base.acceleration }}
                + {{ carStats.bonuses.acceleration }}
              </small>
            </div>

            <div>
              <span>
                Grip
              </span>

              <strong>
                {{ carStats.effective.grip }}
              </strong>

              <small>
                Base {{ carStats.base.grip }}
                + {{ carStats.bonuses.grip }}
              </small>
            </div>

            <div>
              <span>
                Solidité
              </span>

              <strong>
                {{ carStats.effective.solidity }}
              </strong>

              <small>
                Base {{ carStats.base.solidity }}
                + {{ carStats.bonuses.solidity }}
              </small>
            </div>
          </div>

          <div
              v-if="carStats.appliedCards.length > 0"
              class="applied-cards"
          >
            <h3>
              Bonus passifs appliqués
            </h3>

            <ul>
              <li
                  v-for="card in carStats.appliedCards"
                  :key="card.carCardId"
              >
                {{ card.name }}
                · palier {{ card.tier }}
                · +{{ card.value }} {{ card.stat }}
              </li>
            </ul>
          </div>

          <div
              v-else
              class="applied-cards"
          >
            <h3>
              Bonus passifs appliqués
            </h3>

            <p class="muted">
              Cette voiture ne possède aucun bonus passif
              équipé.
            </p>
          </div>
        </template>
      </section>

      <section class="opponents-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Matchmaking
            </p>

            <h2>
              Adversaires disponibles
            </h2>
          </div>

          <button
              type="button"
              class="button button-secondary"
              :disabled="loadingDetails"
              @click="loadSelectedCarDetails"
          >
            {{
              loadingDetails
                  ? 'Recherche...'
                  : 'Actualiser'
            }}
          </button>
        </div>

        <p v-if="loadingDetails">
          Recherche d’adversaires...
        </p>

        <div
            v-else-if="opponents.length > 0"
            class="opponents-grid"
        >
          <OpponentCard
              v-for="opponent in opponents"
              :key="opponent.carId"
              :opponent="opponent"
              :selected="
              selectedOpponent?.carId === opponent.carId
            "
              @select="selectOpponent"
          />
        </div>

        <div
            v-else
            class="empty-state"
        >
          <h3>
            Aucun adversaire compatible
          </h3>

          <p>
            Aucun pilote d’un niveau proche n’est disponible
            pour le moment.
          </p>
        </div>

        <div
            v-if="selectedOpponent !== null"
            class="pending-duel"
        >
          <div>
            <strong>
              Duel préparé
            </strong>

            <p>
              {{ selectedCar.pilotName }}
              contre
              {{ selectedOpponent.pilotName }}
            </p>
          </div>

          <button
              type="button"
              class="button button-primary"
              @click="openDuel"
          >
            Aller au duel
          </button>
        </div>
      </section>
    </template>
  </section>
</template>