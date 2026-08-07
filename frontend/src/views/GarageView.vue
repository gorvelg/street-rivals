<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'
import GarageCarCard
  from '../components/GarageCarCard.vue'
import OpponentCard
  from '../components/OpponentCard.vue'
import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'
import type {
  ApiCollection,
  Car,
  CarInventoryCard,
  CarStats,
  EquipmentSlot,
  EquipCarCardResponse,
  MatchmakingOpponent,
  PendingDuel,
} from '../types/api'

const router = useRouter()

const cars = ref<Car[]>([])

const selectedCarId =
    ref<number | null>(null)

const selectedOpponent =
    ref<MatchmakingOpponent | null>(
        null,
    )

const carStats =
    ref<CarStats | null>(null)

const carCards =
    ref<CarInventoryCard[]>([])

const opponents =
    ref<MatchmakingOpponent[]>([])

const loadingCars =
    ref(false)

const loadingDetails =
    ref(false)

const creatingCar =
    ref(false)

const equippingCarCardId =
    ref<number | null>(null)

const errorMessage =
    ref('')

const successMessage =
    ref('')

const showCreateForm =
    ref(false)

const pilotName =
    ref('')

const carColor =
    ref('#e63946')

const equipmentSlots: Array<{
  key: EquipmentSlot
  label: string
}> = [
  {
    key: 'engine',
    label: 'Moteur',
  },
  {
    key: 'wheels',
    label: 'Roues',
  },
  {
    key: 'brakes',
    label: 'Freins',
  },
  {
    key: 'gearbox',
    label: 'Boîte de vitesses',
  },
  {
    key: 'chassis',
    label: 'Châssis',
  },
  {
    key: 'aero',
    label: 'Aérodynamique',
  },
]

const selectedCar =
    computed<Car | null>(() => {
      return (
          cars.value.find(
              (car) =>
                  car.id
                  === selectedCarId.value,
          )
          ?? null
      )
    })

const abilityCards =
    computed<CarInventoryCard[]>(() => {
      return carCards.value.filter(
          (carCard) =>
              carCard.card.kind
              === 'ability',
      )
    })

const statBoostCards =
    computed<CarInventoryCard[]>(() => {
      return carCards.value.filter(
          (carCard) =>
              carCard.card.kind
              === 'stat_boost',
      )
    })

const equipmentGroups = computed(() => {
  return equipmentSlots.map(
      (slot) => {
        const cards =
            carCards.value
                .filter(
                    (carCard) =>
                        carCard.card.kind
                        === 'equipment'
                        && carCard.card
                            .equipmentSlot
                        === slot.key,
                )
                .sort(
                    (first, second) =>
                        Number(
                            second.equipped,
                        )
                        - Number(
                            first.equipped,
                        ),
                )

        return {
          ...slot,
          cards,
        }
      },
  )
})

async function loadCars():
    Promise<void> {
  loadingCars.value = true
  errorMessage.value = ''

  try {
    const response =
        await apiRequest<
            ApiCollection<Car>
        >(
            '/api/cars',
        )

    cars.value =
        getCollectionMembers(
            response,
        )

    if (cars.value.length === 0) {
      showCreateForm.value = true
      selectedCarId.value = null

      return
    }

    const storedCarValue =
        localStorage.getItem(
            'street-rivals-selected-car',
        )

    const storedCarId =
        storedCarValue !== null
            ? Number.parseInt(
                storedCarValue,
                10,
            )
            : null

    const storedCarExists =
        storedCarId !== null
        && Number.isInteger(
            storedCarId,
        )
        && cars.value.some(
            (car) =>
                car.id
                === storedCarId,
        )

    selectedCarId.value =
        storedCarExists
            ? storedCarId
            : cars.value[0].id
  } catch (error) {
    handleError(error)
  } finally {
    loadingCars.value = false
  }
}

async function loadSelectedCarDetails():
    Promise<void> {
  if (selectedCarId.value === null) {
    carStats.value = null
    carCards.value = []
    opponents.value = []
    selectedOpponent.value = null

    return
  }

  loadingDetails.value = true
  errorMessage.value = ''

  selectedOpponent.value = null

  try {
    const [
      statsResponse,
      cardsResponse,
      opponentsResponse,
    ] = await Promise.all([
      apiRequest<CarStats>(
          `/api/cars/${selectedCarId.value}/stats`,
      ),

      apiRequest<
          ApiCollection<CarInventoryCard>
      >(
          `/api/cars/${selectedCarId.value}/cards`,
      ),

      apiRequest<
          ApiCollection<MatchmakingOpponent>
      >(
          `/api/cars/${selectedCarId.value}/opponents`,
      ),
    ])

    carStats.value =
        statsResponse

    carCards.value =
        getCollectionMembers(
            cardsResponse,
        )

    opponents.value =
        getCollectionMembers(
            opponentsResponse,
        )
  } catch (error) {
    carStats.value = null
    carCards.value = []
    opponents.value = []

    handleError(error)
  } finally {
    loadingDetails.value = false
  }
}

async function equipCarCard(
    carCard: CarInventoryCard,
): Promise<void> {
  if (
      selectedCarId.value === null
      || equippingCarCardId.value !== null
  ) {
    return
  }

  equippingCarCardId.value =
      carCard.carCardId

  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response =
        await apiRequest<
            EquipCarCardResponse
        >(
            `/api/cars/${selectedCarId.value}/cards/${carCard.carCardId}/equip`,
            {
              method: 'POST',
            },
        )

    /*
     * Les stats retournées par l'endpoint
     * peuvent être utilisées immédiatement.
     */
    carStats.value =
        response.stats

    /*
     * On recharge ensuite l'inventaire
     * et le matchmaking car la puissance
     * effective de la voiture a changé.
     */
    const [
      cardsResponse,
      opponentsResponse,
    ] = await Promise.all([
      apiRequest<
          ApiCollection<CarInventoryCard>
      >(
          `/api/cars/${selectedCarId.value}/cards`,
      ),

      apiRequest<
          ApiCollection<MatchmakingOpponent>
      >(
          `/api/cars/${selectedCarId.value}/opponents`,
      ),
    ])

    carCards.value =
        getCollectionMembers(
            cardsResponse,
        )

    opponents.value =
        getCollectionMembers(
            opponentsResponse,
        )

    selectedOpponent.value = null

    successMessage.value =
        response.updated
            ? `${carCard.card.name} est maintenant équipé.`
            : `${carCard.card.name} était déjà équipé.`
  } catch (error) {
    handleError(error)
  } finally {
    equippingCarCardId.value =
        null
  }
}

async function createCar():
    Promise<void> {
  creatingCar.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const createdCar =
        await apiRequest<Car>(
            '/api/cars',
            {
              method: 'POST',

              body: JSON.stringify({
                pilotName:
                pilotName.value,

                color:
                carColor.value,
              }),
            },
        )

    pilotName.value = ''
    showCreateForm.value = false

    await loadCars()

    selectedCarId.value =
        createdCar.id

    successMessage.value =
        'La voiture a été créée avec succès.'
  } catch (error) {
    handleError(error)
  } finally {
    creatingCar.value = false
  }
}

function selectCar(
    carId: number,
): void {
  successMessage.value = ''
  selectedCarId.value = carId
}

function selectOpponent(
    opponent: MatchmakingOpponent,
): void {
  if (
      selectedCarId.value === null
      || selectedCar.value === null
  ) {
    return
  }

  selectedOpponent.value =
      opponent

  const pendingDuel: PendingDuel = {
    attackerCarId:
    selectedCarId.value,

    attackerPilotName:
    selectedCar.value.pilotName,

    attackerColor:
    selectedCar.value.color,

    defenderCarId:
    opponent.carId,

    defenderPilotName:
    opponent.pilotName,

    defenderColor:
    opponent.color,

    difficulty:
    opponent.difficulty,
  }

  sessionStorage.setItem(
      'street-rivals-pending-duel',
      JSON.stringify(
          pendingDuel,
      ),
  )

  successMessage.value =
      `${opponent.pilotName} a été sélectionné.`
}

async function openDuel():
    Promise<void> {
  if (
      selectedOpponent.value === null
      || selectedCarId.value === null
  ) {
    return
  }

  await router.push({
    name: 'duel',
  })
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

function signedValue(
    value: number,
): string {
  if (value > 0) {
    return `+${value}`
  }

  return String(value)
}

function formatTierEffect(
    carCard: CarInventoryCard,
): string {
  const tiers =
      carCard.card.effectConfig
          .tiers

  if (
      typeof tiers !== 'object'
      || tiers === null
  ) {
    return carCard.card.description
  }

  const configuration =
      (
          tiers as Record<
              string,
              Record<string, unknown>
          >
      )[String(carCard.tier)]

  if (
      typeof configuration !== 'object'
      || configuration === null
  ) {
    return carCard.card.description
  }

  const effects =
      Object.entries(
          configuration,
      )
          .filter(
              (
                  entry,
              ): entry is [
                string,
                number,
              ] =>
                  typeof entry[1]
                  === 'number',
          )
          .map(
              ([stat, value]) =>
                  `${signedValue(value)} ${statLabel(stat)}`,
          )

  if (effects.length === 0) {
    return carCard.card.description
  }

  return effects.join(' · ')
}

function formatAppliedCard(
    value: number,
    stat: string,
): string {
  return `${signedValue(value)} ${statLabel(stat)}`
}

function handleError(
    error: unknown,
): void {
  if (error instanceof ApiError) {
    errorMessage.value =
        error.message

    return
  }

  errorMessage.value =
      'Une erreur inattendue est survenue.'
}

watch(
    selectedCarId,
    async (carId) => {
      if (carId === null) {
        localStorage.removeItem(
            'street-rivals-selected-car',
        )

        carStats.value = null
        carCards.value = []
        opponents.value = []
        selectedOpponent.value = null

        return
      }

      localStorage.setItem(
          'street-rivals-selected-car',
          String(carId),
      )

      await loadSelectedCarDetails()
    },
)

onMounted(
    async () => {
      await loadCars()
    },
)
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
          @click="
            showCreateForm =
              !showCreateForm
          "
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
          >
        </label>

        <label>
          Couleur

          <input
              v-model="carColor"
              type="color"
              required
          >
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
          :selected="
            car.id === selectedCarId
          "
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
                backgroundColor:
                  selectedCar.color,
              }"
          />
        </div>

        <p v-if="loadingDetails">
          Calcul des statistiques...
        </p>

        <template
            v-else-if="
              carStats !== null
            "
        >
          <div class="stats-grid">
            <div>
              <span>Vitesse</span>

              <strong>
                {{
                  carStats.effective
                      .speed
                }}
              </strong>

              <small>
                Base
                {{ carStats.base.speed }}
                ·
                {{
                  signedValue(
                      carStats.bonuses
                          .speed,
                  )
                }}
              </small>
            </div>

            <div>
              <span>
                Accélération
              </span>

              <strong>
                {{
                  carStats.effective
                      .acceleration
                }}
              </strong>

              <small>
                Base
                {{
                  carStats.base
                      .acceleration
                }}
                ·
                {{
                  signedValue(
                      carStats.bonuses
                          .acceleration,
                  )
                }}
              </small>
            </div>

            <div>
              <span>Grip</span>

              <strong>
                {{
                  carStats.effective
                      .grip
                }}
              </strong>

              <small>
                Base
                {{ carStats.base.grip }}
                ·
                {{
                  signedValue(
                      carStats.bonuses
                          .grip,
                  )
                }}
              </small>
            </div>

            <div>
              <span>
                Solidité
              </span>

              <strong>
                {{
                  carStats.effective
                      .solidity
                }}
              </strong>

              <small>
                Base
                {{
                  carStats.base
                      .solidity
                }}
                ·
                {{
                  signedValue(
                      carStats.bonuses
                          .solidity,
                  )
                }}
              </small>
            </div>
          </div>

          <div
              v-if="
                carStats.appliedCards
                    .length > 0
              "
              class="applied-cards"
          >
            <h3>
              Effets actuellement appliqués
            </h3>

            <ul>
              <li
                  v-for="
                    (
                      card,
                      index
                    ) in
                      carStats.appliedCards
                  "
                  :key="
                    `${card.carCardId}-${card.stat}-${index}`
                  "
              >
                {{ card.name }}
                · T{{ card.tier }}
                ·
                {{
                  formatAppliedCard(
                      card.value,
                      card.stat,
                  )
                }}
              </li>
            </ul>
          </div>
        </template>
      </section>

      <!-- ÉQUIPEMENTS -->

      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Configuration
            </p>

            <h2>
              Équipements
            </h2>
          </div>
        </div>

        <div class="equipment-grid">
          <article
              v-for="
                group in
                  equipmentGroups
              "
              :key="group.key"
              class="equipment-slot-card"
          >
            <h3>
              {{ group.label }}
            </h3>

            <p
                v-if="
                  group.cards.length
                  === 0
                "
                class="muted"
            >
              Aucun équipement
            </p>

            <div
                v-else
                class="inventory-list"
            >
              <div
                  v-for="
                    carCard in
                      group.cards
                  "
                  :key="
                    carCard.carCardId
                  "
                  class="inventory-card"
                  :class="{
                    'inventory-card-equipped':
                      carCard.equipped,
                  }"
              >
                <div>
                  <strong>
                    {{
                      carCard.card.name
                    }}
                  </strong>

                  <small>
                    Palier
                    {{ carCard.tier }}
                    /
                    {{
                      carCard.card
                          .maxTier
                    }}
                  </small>

                  <p>
                    {{
                      formatTierEffect(
                          carCard,
                      )
                    }}
                  </p>
                </div>

                <span
                    v-if="
                      carCard.equipped
                    "
                    class="equipped-badge"
                >
                  Équipé
                </span>

                <button
                    v-else
                    type="button"
                    class="button button-secondary"
                    :disabled="
                      equippingCarCardId
                        !== null
                    "
                    @click="
                      equipCarCard(
                          carCard,
                      )
                    "
                >
                  {{
                    equippingCarCardId
                    === carCard.carCardId
                        ? 'Équipement...'
                        : 'Équiper'
                  }}
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <!-- BONUS PERMANENTS -->

      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Progression
            </p>

            <h2>
              Bonus permanents
            </h2>
          </div>
        </div>

        <p
            v-if="
              statBoostCards.length
              === 0
            "
            class="muted"
        >
          Aucun bonus permanent obtenu.
        </p>

        <div
            v-else
            class="inventory-grid"
        >
          <article
              v-for="
                carCard in
                  statBoostCards
              "
              :key="
                carCard.carCardId
              "
              class="inventory-card"
          >
            <div>
              <strong>
                {{
                  carCard.card.name
                }}
              </strong>

              <small>
                Palier
                {{ carCard.tier }}
                /
                {{
                  carCard.card.maxTier
                }}
              </small>

              <p>
                {{
                  formatTierEffect(
                      carCard,
                  )
                }}
              </p>
            </div>

            <span class="active-badge">
              Toujours actif
            </span>
          </article>
        </div>
      </section>

      <!-- CAPACITÉS -->

      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="eyebrow">
              Compétences
            </p>

            <h2>
              Capacités
            </h2>
          </div>
        </div>

        <p
            v-if="
              abilityCards.length === 0
            "
            class="muted"
        >
          Aucune capacité obtenue.
        </p>

        <div
            v-else
            class="inventory-grid"
        >
          <article
              v-for="
                carCard in
                  abilityCards
              "
              :key="
                carCard.carCardId
              "
              class="inventory-card"
          >
            <div>
              <strong>
                {{
                  carCard.card.name
                }}
              </strong>

              <small>
                Palier
                {{ carCard.tier }}
                /
                {{
                  carCard.card.maxTier
                }}
              </small>

              <p>
                {{
                  carCard.card
                      .description
                }}
              </p>
            </div>

            <span
                v-if="
                  carCard.equipped
                "
                class="active-badge"
            >
              Active
            </span>
          </article>
        </div>
      </section>

      <!-- MATCHMAKING -->

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
              :disabled="
                loadingDetails
              "
              @click="
                loadSelectedCarDetails
              "
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
            v-else-if="
              opponents.length > 0
            "
            class="opponents-grid"
        >
          <OpponentCard
              v-for="
                opponent in
                  opponents
              "
              :key="
                opponent.carId
              "
              :opponent="
                opponent
              "
              :selected="
                selectedOpponent
                    ?.carId
                === opponent.carId
              "
              @select="
                selectOpponent
              "
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
            v-if="
              selectedOpponent
              !== null
            "
            class="pending-duel"
        >
          <div>
            <strong>
              Duel préparé
            </strong>

            <p>
              {{
                selectedCar
                    .pilotName
              }}
              contre
              {{
                selectedOpponent
                    .pilotName
              }}
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

<style scoped>
.equipment-grid,
.inventory-grid {
  display: grid;
  gap: 16px;
}

.equipment-grid {
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(280px, 1fr)
    );
}

.inventory-grid {
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(250px, 1fr)
    );
}

.equipment-slot-card {
  padding: 16px;
  border: 1px solid
  rgba(127, 127, 127, 0.25);
  border-radius: 12px;
}

.equipment-slot-card h3 {
  margin-top: 0;
}

.inventory-list {
  display: grid;
  gap: 10px;
}

.inventory-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;

  padding: 14px;

  border: 1px solid
  rgba(127, 127, 127, 0.2);

  border-radius: 10px;

  background:
      rgba(127, 127, 127, 0.05);
}

.inventory-card-equipped {
  border-color:
      rgba(50, 170, 100, 0.55);
}

.inventory-card > div {
  display: grid;
  gap: 5px;
}

.inventory-card p {
  margin: 0;
}

.inventory-card small {
  opacity: 0.65;
}

.equipped-badge,
.active-badge {
  flex-shrink: 0;

  padding: 6px 9px;

  border-radius: 999px;

  font-size: 0.75rem;
  font-weight: 800;
}

.equipped-badge {
  background:
      rgba(50, 170, 100, 0.15);
}

.active-badge {
  background:
      rgba(50, 110, 210, 0.15);
}

@media (max-width: 650px) {
  .inventory-card {
    align-items: stretch;
    flex-direction: column;
  }

  .inventory-card button {
    width: 100%;
  }
}
</style>