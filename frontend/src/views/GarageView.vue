<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue'

import {
  usePlayerCarStore,
} from '../stores/playerCar'

import CarVisual
  from '../components/CarVisual.vue'

import GarageCarCard
  from '../components/GarageCarCard.vue'

import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'

import {
  CAR_BODIES,
} from '../cars/bodies'

import type {
  ApiCollection,
  Car,
  CarBodyStyle,
  CarInventoryCard,
  CarStats,
  CarWheelStyle,
  EquipmentSlot,
  EquipCarCardResponse,
} from '../types/api'

/*
 * =====================================
 * TYPES
 * =====================================
 */

type GarageTab =
    | 'overview'
    | 'equipment'
    | 'cards'
    | 'style'

/*
 * =====================================
 * STORE
 * =====================================
 */

const playerCarStore =
    usePlayerCarStore()

/*
 * =====================================
 * ÉTAT
 * =====================================
 */

const cars =
    ref<Car[]>([])

const selectedCarId =
    ref<number | null>(
        null,
    )

const carStats =
    ref<CarStats | null>(
        null,
    )

const carCards =
    ref<CarInventoryCard[]>(
        [],
    )

const loadingCars =
    ref(false)

const loadingDetails =
    ref(false)

const creatingCar =
    ref(false)

const equippingCarCardId =
    ref<number | null>(
        null,
    )

const errorMessage =
    ref('')

const successMessage =
    ref('')

/*
 * =====================================
 * NAVIGATION INTERNE
 * =====================================
 */

const activeTab =
    ref<GarageTab>(
        'overview',
    )

const tabs: Array<{
  value: GarageTab
  label: string
}> = [
  {
    value: 'overview',
    label: 'Aperçu',
  },
  {
    value: 'equipment',
    label: 'Équipement',
  },
  {
    value: 'cards',
    label: 'Cartes',
  },
  {
    value: 'style',
    label: 'Style',
  },
]

/*
 * =====================================
 * CRÉATION
 * =====================================
 */

const showCreateForm =
    ref(false)

const pilotName =
    ref('')

const carColor =
    ref('#E63946')

/*
 * =====================================
 * PERSONNALISATION
 * =====================================
 */

const savingCustomization =
    ref(false)

const customizationColor =
    ref('#E63946')

const customizationBodyStyle =
    ref<CarBodyStyle>(
        'coupe_01',
    )

const customizationWheelStyle =
    ref<CarWheelStyle>(
        'street_01',
    )

const wheelStyles: Array<{
  value: CarWheelStyle
  label: string
}> = [
  {
    value: 'street_01',
    label: 'Street',
  },
  {
    value: 'five_spoke',
    label: '5 branches',
  },
  {
    value: 'multi_spoke',
    label: 'Multi-branches',
  },
]

const quickColors = [
  '#E63946',
  '#2563EB',
  '#16A34A',
  '#F59E0B',
  '#7C3AED',
  '#EC4899',
  '#111827',
  '#F8FAFC',
]

/*
 * =====================================
 * ÉQUIPEMENTS
 * =====================================
 */

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
    label: 'Boîte',
  },
  {
    key: 'chassis',
    label: 'Châssis',
  },
  {
    key: 'aero',
    label: 'Aéro',
  },
]

/*
 * =====================================
 * COMPUTED
 * =====================================
 */

const selectedCar =
    computed<Car | null>(
        () =>
            cars.value.find(
                (car) =>
                    car.id
                    === selectedCarId.value,
            )
            ?? null,
    )

const abilityCards =
    computed(
        () =>
            carCards.value.filter(
                (carCard) =>
                    carCard.card.kind
                    === 'ability',
            ),
    )

const statBoostCards =
    computed(
        () =>
            carCards.value.filter(
                (carCard) =>
                    carCard.card.kind
                    === 'stat_boost',
            ),
    )

const equipmentCards =
    computed(
        () =>
            carCards.value.filter(
                (carCard) =>
                    carCard.card.kind
                    === 'equipment',
            ),
    )

const equippedCards =
    computed(
        () =>
            equipmentCards.value.filter(
                (carCard) =>
                    carCard.equipped,
            ),
    )

const equipmentGroups =
    computed(
        () => {
          return equipmentSlots.map(
              (slot) => {
                const cards =
                    equipmentCards.value
                        .filter(
                            (carCard) =>
                                carCard.card
                                    .equipmentSlot
                                === slot.key,
                        )
                        .sort(
                            (
                                first,
                                second,
                            ) =>
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
        },
    )

/*
 * =====================================
 * CHARGEMENT
 * =====================================
 */

async function loadCars():
    Promise<void> {
  loadingCars.value =
      true

  errorMessage.value =
      ''

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

    if (
        cars.value.length
        === 0
    ) {
      selectedCarId.value =
          null

      showCreateForm.value =
          true

      playerCarStore
          .clearSelection()

      return
    }

    if (
        !playerCarStore.initialized
    ) {
      playerCarStore
          .restoreSelection()
    }

    const storedId =
        playerCarStore
            .selectedCarId

    const storedExists =
        storedId !== null
        && cars.value.some(
            (car) =>
                car.id
                === storedId,
        )

    selectedCarId.value =
        storedExists
            ? storedId
            : cars.value[0].id
  } catch (error) {
    handleError(
        error,
    )
  } finally {
    loadingCars.value =
        false
  }
}

async function loadSelectedCarDetails():
    Promise<void> {
  if (
      selectedCarId.value
      === null
  ) {
    carStats.value =
        null

    carCards.value =
        []

    return
  }

  loadingDetails.value =
      true

  errorMessage.value =
      ''

  try {
    const [
      statsResponse,
      cardsResponse,
    ] =
        await Promise.all([
          apiRequest<CarStats>(
              `/api/cars/${selectedCarId.value}/stats`,
          ),

          apiRequest<
              ApiCollection<
                  CarInventoryCard
              >
          >(
              `/api/cars/${selectedCarId.value}/cards`,
          ),
        ])

    carStats.value =
        statsResponse

    carCards.value =
        getCollectionMembers(
            cardsResponse,
        )
  } catch (error) {
    carStats.value =
        null

    carCards.value =
        []

    handleError(
        error,
    )
  } finally {
    loadingDetails.value =
        false
  }
}

/*
 * =====================================
 * SÉLECTION
 * =====================================
 */

function selectCar(
    carId: number,
): void {
  if (
      selectedCarId.value
      === carId
  ) {
    return
  }

  errorMessage.value =
      ''

  successMessage.value =
      ''

  activeTab.value =
      'overview'

  selectedCarId.value =
      carId
}

/*
 * =====================================
 * CRÉATION
 * =====================================
 */

async function createCar():
    Promise<void> {
  if (
      creatingCar.value
  ) {
    return
  }

  creatingCar.value =
      true

  errorMessage.value =
      ''

  successMessage.value =
      ''

  try {
    const createdCar =
        await apiRequest<Car>(
            '/api/cars',
            {
              method: 'POST',

              body:
                  JSON.stringify({
                    pilotName:
                    pilotName.value,

                    color:
                    carColor.value,

                    bodyStyle:
                        'coupe_01',

                    wheelStyle:
                        'street_01',
                  }),
            },
        )

    pilotName.value =
        ''

    showCreateForm.value =
        false

    await loadCars()

    selectedCarId.value =
        createdCar.id

    playerCarStore.setCar(
        createdCar,
    )

    successMessage.value =
        'Voiture créée.'

    activeTab.value =
        'overview'
  } catch (error) {
    handleError(
        error,
    )
  } finally {
    creatingCar.value =
        false
  }
}

/*
 * =====================================
 * STYLE
 * =====================================
 */

function loadCustomizationValues():
    void {
  if (
      selectedCar.value
      === null
  ) {
    return
  }

  customizationColor.value =
      selectedCar.value.color

  customizationBodyStyle.value =
      selectedCar.value
          .bodyStyle

  customizationWheelStyle.value =
      selectedCar.value
          .wheelStyle
}

function openCustomization():
    void {
  loadCustomizationValues()

  activeTab.value =
      'style'
}

async function saveCustomization():
    Promise<void> {
  if (
      selectedCar.value
      === null
      || savingCustomization.value
  ) {
    return
  }

  savingCustomization.value =
      true

  errorMessage.value =
      ''

  successMessage.value =
      ''

  try {
    const updatedCar =
        await apiRequest<Car>(
            `/api/cars/${selectedCar.value.id}`,
            {
              method: 'PATCH',

              headers: {
                'Content-Type':
                    'application/merge-patch+json',
              },

              body:
                  JSON.stringify({
                    color:
                    customizationColor.value,

                    bodyStyle:
                    customizationBodyStyle.value,

                    wheelStyle:
                    customizationWheelStyle.value,
                  }),
            },
        )

    const index =
        cars.value.findIndex(
            (car) =>
                car.id
                === updatedCar.id,
        )

    if (
        index !== -1
    ) {
      cars.value[index] =
          updatedCar
    }

    playerCarStore.setCar(
        updatedCar,
    )

    successMessage.value =
        'Style enregistré.'
  } catch (error) {
    handleError(
        error,
    )
  } finally {
    savingCustomization.value =
        false
  }
}

/*
 * =====================================
 * ÉQUIPEMENT
 * =====================================
 */

async function equipCarCard(
    carCard:
    CarInventoryCard,
): Promise<void> {
  if (
      selectedCarId.value
      === null
      || equippingCarCardId.value
      !== null
  ) {
    return
  }

  equippingCarCardId.value =
      carCard.carCardId

  errorMessage.value =
      ''

  successMessage.value =
      ''

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

    carStats.value =
        response.stats

    const cardsResponse =
        await apiRequest<
            ApiCollection<
                CarInventoryCard
            >
        >(
            `/api/cars/${selectedCarId.value}/cards`,
        )

    carCards.value =
        getCollectionMembers(
            cardsResponse,
        )

    successMessage.value =
        response.updated
            ? `${carCard.card.name} équipé.`
            : `${carCard.card.name} était déjà équipé.`
  } catch (error) {
    handleError(
        error,
    )
  } finally {
    equippingCarCardId.value =
        null
  }
}

/*
 * =====================================
 * FORMATAGE
 * =====================================
 */

function statLabel(
    stat: string,
): string {
  switch (
      stat
      ) {
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
  if (
      value > 0
  ) {
    return `+${value}`
  }

  return String(
      value,
  )
}

function formatTierEffect(
    carCard:
    CarInventoryCard,
): string {
  const tiers =
      carCard.card
          .effectConfig
          .tiers

  if (
      typeof tiers
      !== 'object'
      || tiers === null
  ) {
    return carCard.card
        .description
  }

  const configuration =
      (
          tiers as Record<
              string,
              Record<
                  string,
                  unknown
              >
          >
      )[
          String(
              carCard.tier,
          )
          ]

  if (
      typeof configuration
      !== 'object'
      || configuration === null
  ) {
    return carCard.card
        .description
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
              (
                  [
                    stat,
                    value,
                  ],
              ) =>
                  `${signedValue(value)} ${statLabel(stat)}`,
          )

  if (
      effects.length
      === 0
  ) {
    return carCard.card
        .description
  }

  return effects.join(
      ' · ',
  )
}

function formatAppliedCard(
    value: number,
    stat: string,
): string {
  return (
      `${signedValue(value)}`
      + ` ${statLabel(stat)}`
  )
}

/*
 * =====================================
 * ERREURS
 * =====================================
 */

function handleError(
    error: unknown,
): void {
  if (
      error instanceof ApiError
  ) {
    errorMessage.value =
        error.message

    return
  }

  errorMessage.value =
      'Une erreur inattendue est survenue.'
}

/*
 * =====================================
 * WATCHERS
 * =====================================
 */

watch(
    selectedCarId,

    async (
        carId,
    ) => {
      if (
          carId === null
      ) {
        playerCarStore
            .clearSelection()

        carStats.value =
            null

        carCards.value =
            []

        return
      }

      playerCarStore.selectCar(
          carId,
      )

      loadCustomizationValues()

      await loadSelectedCarDetails()
    },
)

/*
 * =====================================
 * INIT
 * =====================================
 */

onMounted(
    async () => {
      await loadCars()
    },
)
</script>

<template>
  <section class="garage-page">
    <!-- =====================================
         TOP BAR
    ====================================== -->

    <header class="garage-header">
      <div>
        <p class="eyebrow">
          Street Rivals
        </p>

        <h1>
          Garage
        </h1>
      </div>

      <button
          type="button"
          class="
            button
            button-secondary
            add-car-button
          "
          @click="
            showCreateForm =
              !showCreateForm
          "
      >
        <span class="add-symbol">
          +
        </span>

        <span class="add-label">
          Ajouter
        </span>
      </button>
    </header>

    <!-- =====================================
         MESSAGES
    ====================================== -->

    <p
        v-if="
          errorMessage !== ''
        "
        class="
          alert
          alert-error
        "
    >
      {{ errorMessage }}
    </p>

    <p
        v-if="
          successMessage !== ''
        "
        class="
          alert
          alert-success
        "
    >
      {{ successMessage }}
    </p>

    <!-- =====================================
         CRÉATION
    ====================================== -->

    <form
        v-if="
          showCreateForm
        "
        class="
          garage-card
          create-car-form
        "
        @submit.prevent="
          createCar
        "
    >
      <div class="card-heading">
        <div>
          <p class="eyebrow">
            Nouveau pilote
          </p>

          <h2>
            Ajouter une voiture
          </h2>
        </div>

        <button
            type="button"
            class="close-button"
            aria-label="Fermer"
            @click="
              showCreateForm =
                false
            "
        >
          ×
        </button>
      </div>

      <div class="create-fields">
        <label class="field">
          <span>
            Nom du pilote
          </span>

          <input
              v-model.trim="
                pilotName
              "
              type="text"
              minlength="3"
              maxlength="32"
              autocomplete="off"
              placeholder="Raven"
              required
          >
        </label>

        <label class="field color-field">
          <span>
            Couleur
          </span>

          <input
              v-model="
                carColor
              "
              type="color"
              required
          >
        </label>

        <button
            type="submit"
            class="
              button
              button-primary
              create-button
            "
            :disabled="
              creatingCar
            "
        >
          {{
            creatingCar
                ? 'Création...'
                : 'Créer'
          }}
        </button>
      </div>
    </form>

    <!-- =====================================
         LOADING
    ====================================== -->

    <div
        v-if="
          loadingCars
        "
        class="
          garage-card
          loading-card
        "
    >
      Chargement du garage...
    </div>

    <!-- =====================================
         GARAGE VIDE
    ====================================== -->

    <div
        v-else-if="
          cars.length === 0
          && !showCreateForm
        "
        class="
          garage-card
          empty-garage
        "
    >
      <div class="empty-icon">
        +
      </div>

      <h2>
        Ton garage est vide
      </h2>

      <p>
        Crée ta première voiture pour commencer
        à jouer.
      </p>

      <button
          type="button"
          class="
            button
            button-primary
          "
          @click="
            showCreateForm =
              true
          "
      >
        Créer ma voiture
      </button>
    </div>

    <template
        v-else-if="
          selectedCar !== null
        "
    >
      <!-- =================================
           SÉLECTEUR DE VOITURES
      ================================== -->

      <section
          v-if="
            cars.length > 1
          "
          class="
            garage-selector-section
          "
      >
        <div class="section-title-row">
          <span>
            Mes voitures
          </span>

          <small>
            {{
              cars.length
            }}
          </small>
        </div>

        <div class="garage-list">
          <GarageCarCard
              v-for="
                car in cars
              "
              :key="
                car.id
              "
              :car="
                car
              "
              :selected="
                car.id
                === selectedCarId
              "
              @select="
                selectCar
              "
          />
        </div>
      </section>

      <!-- =================================
           HERO
      ================================== -->

      <section
          class="
            garage-card
            car-hero
          "
      >
        <div class="hero-heading">
          <div>
            <p class="eyebrow">
              Pilote actif
            </p>

            <h2>
              {{
                selectedCar
                    .pilotName
              }}
            </h2>

            <div class="pilot-meta">
              <span>
                NIV.
                <strong>
                  {{
                    selectedCar.level
                  }}
                </strong>
              </span>

              <span>
                ELO
                <strong>
                  {{
                    selectedCar.rating
                    ?? 1000
                  }}
                </strong>
              </span>

              <span>
                <strong>
                  {{
                    selectedCar.wins
                  }}
                </strong>
                V
              </span>

              <span>
                <strong>
                  {{
                    selectedCar.losses
                  }}
                </strong>
                D
              </span>
            </div>
          </div>

          <button
              type="button"
              class="
                style-shortcut
              "
              @click="
                openCustomization
              "
          >
            Modifier
          </button>
        </div>

        <div class="hero-car">
          <div class="garage-floor" />

          <CarVisual
              :color="
                selectedCar.color
              "
              :body-style="
                selectedCar.bodyStyle
              "
              :wheel-style="
                selectedCar.wheelStyle
              "
              :pilot-name="
                selectedCar.pilotName
              "
          />
        </div>

        <!-- STATS -->

        <div
            v-if="
              loadingDetails
            "
            class="stats-loading"
        >
          Chargement des stats...
        </div>

        <div
            v-else-if="
              carStats !== null
            "
            class="hero-stats"
        >
          <article>
            <span>
              Vitesse
            </span>

            <strong>
              {{
                carStats
                    .effective
                    .speed
              }}
            </strong>

            <small>
              {{
                signedValue(
                    carStats
                        .bonuses
                        .speed,
                )
              }}
            </small>
          </article>

          <article>
            <span>
              Accél.
            </span>

            <strong>
              {{
                carStats
                    .effective
                    .acceleration
              }}
            </strong>

            <small>
              {{
                signedValue(
                    carStats
                        .bonuses
                        .acceleration,
                )
              }}
            </small>
          </article>

          <article>
            <span>
              Grip
            </span>

            <strong>
              {{
                carStats
                    .effective
                    .grip
              }}
            </strong>

            <small>
              {{
                signedValue(
                    carStats
                        .bonuses
                        .grip,
                )
              }}
            </small>
          </article>

          <article>
            <span>
              Solidité
            </span>

            <strong>
              {{
                carStats
                    .effective
                    .solidity
              }}
            </strong>

            <small>
              {{
                signedValue(
                    carStats
                        .bonuses
                        .solidity,
                )
              }}
            </small>
          </article>
        </div>
      </section>

      <!-- =================================
           ONGLETS
      ================================== -->

      <nav
          class="
            garage-tabs
          "
          aria-label="
            Sections du garage
          "
      >
        <button
            v-for="
              tab in tabs
            "
            :key="
              tab.value
            "
            type="button"
            class="garage-tab"
            :class="{
              'garage-tab-active':
                activeTab
                === tab.value,
            }"
            @click="
              activeTab =
                tab.value
            "
        >
          {{
            tab.label
          }}
        </button>
      </nav>

      <!-- =================================
           APERÇU
      ================================== -->

      <section
          v-if="
            activeTab
            === 'overview'
          "
          class="
            garage-tab-content
          "
      >
        <!-- RÉSUMÉ -->

        <div class="summary-grid">
          <article class="summary-card">
            <span class="summary-number">
              {{
                equippedCards.length
              }}
              /
              {{
                equipmentSlots.length
              }}
            </span>

            <strong>
              Équipements
            </strong>

            <small>
              emplacements utilisés
            </small>

            <button
                type="button"
                class="
                  summary-link
                "
                @click="
                  activeTab =
                    'equipment'
                "
            >
              Voir l'équipement
            </button>
          </article>

          <article class="summary-card">
            <span class="summary-number">
              {{
                statBoostCards.length
              }}
            </span>

            <strong>
              Bonus
            </strong>

            <small>
              améliorations permanentes
            </small>

            <button
                type="button"
                class="
                  summary-link
                "
                @click="
                  activeTab =
                    'cards'
                "
            >
              Voir les cartes
            </button>
          </article>

          <article class="summary-card">
            <span class="summary-number">
              {{
                abilityCards.length
              }}
            </span>

            <strong>
              Capacités
            </strong>

            <small>
              compétences débloquées
            </small>

            <button
                type="button"
                class="
                  summary-link
                "
                @click="
                  activeTab =
                    'cards'
                "
            >
              Voir les capacités
            </button>
          </article>
        </div>

        <!-- EFFETS -->

        <section
            v-if="
              carStats !== null
            "
            class="
              garage-card
              applied-effects
            "
        >
          <div class="card-heading">
            <div>
              <p class="eyebrow">
                Performance
              </p>

              <h3>
                Effets actifs
              </h3>
            </div>
          </div>

          <p
              v-if="
                carStats
                    .appliedCards
                    .length
                === 0
              "
              class="muted-text"
          >
            Aucun effet de carte n'est
            actuellement appliqué.
          </p>

          <div
              v-else
              class="
                effects-list
              "
          >
            <div
                v-for="
                  (
                    card,
                    index
                  ) in
                    carStats
                        .appliedCards
                "
                :key="
                  `${card.carCardId}-${card.stat}-${index}`
                "
                class="effect-row"
            >
              <div>
                <strong>
                  {{
                    card.name
                  }}
                </strong>

                <small>
                  Palier
                  {{
                    card.tier
                  }}
                </small>
              </div>

              <span>
                {{
                  formatAppliedCard(
                      card.value,
                      card.stat,
                  )
                }}
              </span>
            </div>
          </div>
        </section>
      </section>

      <!-- =================================
           ÉQUIPEMENTS
      ================================== -->

      <section
          v-else-if="
            activeTab
            === 'equipment'
          "
          class="
            garage-tab-content
          "
      >
        <div class="tab-heading">
          <div>
            <p class="eyebrow">
              Configuration
            </p>

            <h2>
              Équipement
            </h2>
          </div>

          <span>
            {{
              equippedCards.length
            }}
            /
            {{
              equipmentSlots.length
            }}
          </span>
        </div>

        <div class="equipment-grid">
          <article
              v-for="
                group in
                  equipmentGroups
              "
              :key="
                group.key
              "
              class="
                equipment-slot
              "
          >
            <div
                class="
                  equipment-slot-heading
                "
            >
              <span
                  class="
                    slot-indicator
                  "
                  :class="{
                    'slot-indicator-filled':
                      group.cards.some(
                          (card) =>
                              card.equipped,
                      ),
                  }"
              />

              <h3>
                {{
                  group.label
                }}
              </h3>
            </div>

            <p
                v-if="
                  group.cards.length
                  === 0
                "
                class="
                  empty-slot
                "
            >
              Aucun équipement
            </p>

            <div
                v-else
                class="
                  equipment-options
                "
            >
              <article
                  v-for="
                    carCard in
                      group.cards
                  "
                  :key="
                    carCard.carCardId
                  "
                  class="
                    inventory-card
                  "
                  :class="{
                    'inventory-card-equipped':
                      carCard.equipped,
                  }"
              >
                <div class="inventory-info">
                  <div
                      class="
                        inventory-title
                      "
                  >
                    <strong>
                      {{
                        carCard.card
                            .name
                      }}
                    </strong>

                    <span>
                      T{{
                        carCard.tier
                      }}
                    </span>
                  </div>

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
                    class="
                      status-badge
                      status-equipped
                    "
                >
                  Équipé
                </span>

                <button
                    v-else
                    type="button"
                    class="
                      equip-button
                    "
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
                        ? '...'
                        : 'Équiper'
                  }}
                </button>
              </article>
            </div>
          </article>
        </div>
      </section>

      <!-- =================================
           CARTES
      ================================== -->

      <section
          v-else-if="
            activeTab
            === 'cards'
          "
          class="
            garage-tab-content
          "
      >
        <div class="tab-heading">
          <div>
            <p class="eyebrow">
              Collection
            </p>

            <h2>
              Cartes
            </h2>
          </div>
        </div>

        <!-- BONUS -->

        <section
            class="
              card-category
            "
        >
          <div
              class="
                category-heading
              "
          >
            <div>
              <h3>
                Bonus permanents
              </h3>

              <p>
                Toujours appliqués aux
                statistiques de la voiture.
              </p>
            </div>

            <span>
              {{
                statBoostCards.length
              }}
            </span>
          </div>

          <p
              v-if="
                statBoostCards.length
                === 0
              "
              class="
                empty-category
              "
          >
            Aucun bonus permanent.
          </p>

          <div
              v-else
              class="
                cards-grid
              "
          >
            <article
                v-for="
                  carCard in
                    statBoostCards
                "
                :key="
                  carCard.carCardId
                "
                class="game-card"
            >
              <div
                  class="
                    game-card-tier
                  "
              >
                T{{
                  carCard.tier
                }}
              </div>

              <strong>
                {{
                  carCard.card.name
                }}
              </strong>

              <p>
                {{
                  formatTierEffect(
                      carCard,
                  )
                }}
              </p>

              <small>
                Palier
                {{
                  carCard.tier
                }}
                /
                {{
                  carCard.card
                      .maxTier
                }}
              </small>
            </article>
          </div>
        </section>

        <!-- CAPACITÉS -->

        <section
            class="
              card-category
            "
        >
          <div
              class="
                category-heading
              "
          >
            <div>
              <h3>
                Capacités
              </h3>

              <p>
                Compétences pouvant intervenir
                pendant les courses.
              </p>
            </div>

            <span>
              {{
                abilityCards.length
              }}
            </span>
          </div>

          <p
              v-if="
                abilityCards.length
                === 0
              "
              class="
                empty-category
              "
          >
            Aucune capacité débloquée.
          </p>

          <div
              v-else
              class="
                cards-grid
              "
          >
            <article
                v-for="
                  carCard in
                    abilityCards
                "
                :key="
                  carCard.carCardId
                "
                class="game-card"
            >
              <div
                  class="
                    game-card-tier
                  "
              >
                T{{
                  carCard.tier
                }}
              </div>

              <strong>
                {{
                  carCard.card.name
                }}
              </strong>

              <p>
                {{
                  carCard.card
                      .description
                }}
              </p>

              <small>
                Palier
                {{
                  carCard.tier
                }}
                /
                {{
                  carCard.card
                      .maxTier
                }}
              </small>
            </article>
          </div>
        </section>
      </section>

      <!-- =================================
           STYLE
      ================================== -->

      <section
          v-else-if="
            activeTab
            === 'style'
          "
          class="
            garage-tab-content
          "
      >
        <div class="tab-heading">
          <div>
            <p class="eyebrow">
              Atelier
            </p>

            <h2>
              Personnalisation
            </h2>
          </div>
        </div>

        <!-- PREVIEW -->

        <section
            class="
              garage-card
              style-preview
            "
        >
          <div class="preview-car">
            <div
                class="
                  garage-floor
                "
            />

            <CarVisual
                :color="
                  customizationColor
                "
                :body-style="
                  customizationBodyStyle
                "
                :wheel-style="
                  customizationWheelStyle
                "
                :pilot-name="
                  selectedCar.pilotName
                "
            />
          </div>
        </section>

        <div class="style-sections">
          <!-- COULEUR -->

          <section
              class="
                style-section
              "
          >
            <h3>
              Couleur
            </h3>

            <div class="color-list">
              <button
                  v-for="
                    color in
                      quickColors
                  "
                  :key="
                    color
                  "
                  type="button"
                  class="
                    color-button
                  "
                  :class="{
                    'color-button-selected':
                      customizationColor
                          .toUpperCase()
                      === color
                          .toUpperCase(),
                  }"
                  :style="{
                    backgroundColor:
                      color,
                  }"
                  :aria-label="
                    `Couleur ${color}`
                  "
                  @click="
                    customizationColor =
                      color
                  "
              />
            </div>

            <label
                class="
                  custom-color-row
                "
            >
              <span>
                Personnalisée
              </span>

              <div>
                <input
                    v-model="
                      customizationColor
                    "
                    type="color"
                >

                <strong>
                  {{
                    customizationColor
                        .toUpperCase()
                  }}
                </strong>
              </div>
            </label>
          </section>

          <!-- CARROSSERIE -->

          <section
              class="
                style-section
              "
          >
            <h3>
              Carrosserie
            </h3>

            <div
                class="
                  body-options
                "
            >
              <button
                  v-for="
                    body in
                      CAR_BODIES
                  "
                  :key="
                    body.code
                  "
                  type="button"
                  class="
                    body-option
                  "
                  :class="{
                    'body-option-selected':
                      customizationBodyStyle
                      === body.code,
                  }"
                  @click="
                    customizationBodyStyle =
                      body.code
                  "
              >
                {{
                  body.label
                }}
              </button>
            </div>
          </section>

          <!-- JANTES -->

          <section
              class="
                style-section
              "
          >
            <h3>
              Jantes
            </h3>

            <div
                class="
                  wheel-options
                "
            >
              <button
                  v-for="
                    wheel in
                      wheelStyles
                  "
                  :key="
                    wheel.value
                  "
                  type="button"
                  class="
                    body-option
                  "
                  :class="{
                    'body-option-selected':
                      customizationWheelStyle
                      === wheel.value,
                  }"
                  @click="
                    customizationWheelStyle =
                      wheel.value
                  "
              >
                {{
                  wheel.label
                }}
              </button>
            </div>
          </section>
        </div>

        <div class="save-style-bar">
          <span>
            Les modifications ne sont appliquées
            qu'après enregistrement.
          </span>

          <button
              type="button"
              class="
                button
                button-primary
              "
              :disabled="
                savingCustomization
              "
              @click="
                saveCustomization
              "
          >
            {{
              savingCustomization
                  ? 'Enregistrement...'
                  : 'Enregistrer'
            }}
          </button>
        </div>
      </section>
    </template>
  </section>
</template>

<style scoped>
/*
 * =====================================
 * PAGE
 * =====================================
 */

.garage-page {
  width:
      min(
          calc(
              100%
              - 28px
          ),
          1100px
      );

  margin:
      0
      auto;

  padding:
      22px
      0
      30px;
}

/*
 * =====================================
 * HEADER
 * =====================================
 */

.garage-header {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 18px;

  margin-bottom: 18px;
}

.garage-header h1 {
  margin: 0;

  font-size:
      clamp(
          1.65rem,
          4vw,
          2.2rem
      );
}

.eyebrow {
  margin:
      0
      0
      3px;

  font-size: 0.68rem;
  font-weight: 800;

  letter-spacing: 0.12em;

  text-transform: uppercase;

  opacity: 0.45;
}

.add-symbol {
  font-size: 1.1rem;
}

/*
 * =====================================
 * BASE CARD
 * =====================================
 */

.garage-card {
  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.08
      );

  border-radius: 20px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

/*
 * =====================================
 * CRÉATION
 * =====================================
 */

.create-car-form {
  margin-bottom: 18px;

  padding: 18px;
}

.card-heading {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 16px;

  margin-bottom: 16px;
}

.card-heading h2,
.card-heading h3 {
  margin: 0;
}

.close-button {
  width: 34px;
  height: 34px;

  border: 0;
  border-radius: 50%;

  background:
      rgba(
          255,
          255,
          255,
          0.07
      );

  color: inherit;

  cursor: pointer;

  font-size: 1.25rem;
}

.create-fields {
  display: grid;

  grid-template-columns:
      minmax(
          0,
          1fr
      )
      auto
      auto;

  align-items: end;

  gap: 12px;
}

.field {
  display: grid;

  gap: 7px;
}

.field > span {
  font-size: 0.72rem;

  opacity: 0.6;
}

.field input[type='text'] {
  min-height: 42px;

  padding:
      0
      12px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.12
      );

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );

  color: inherit;
}

.color-field input {
  width: 54px;
  height: 42px;
}

/*
 * =====================================
 * SÉLECTEUR VOITURES
 * =====================================
 */

.garage-selector-section {
  margin-bottom: 14px;
}

.section-title-row {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  margin-bottom: 8px;

  padding:
      0
      3px;

  font-size: 0.75rem;
  font-weight: 700;

  opacity: 0.6;
}

.section-title-row small {
  display: flex;

  min-width: 24px;
  height: 24px;

  align-items: center;
  justify-content: center;

  border-radius: 99px;

  background:
      rgba(
          255,
          255,
          255,
          0.07
      );
}

.garage-list {
  display: flex;

  gap: 10px;

  overflow-x: auto;

  padding:
      2px
      2px
      8px;

  scrollbar-width: thin;
}

.garage-list > * {
  flex:
      0
      0
      min(
          280px,
          82vw
      );
}

/*
 * =====================================
 * HERO
 * =====================================
 */

.car-hero {
  overflow: hidden;

  padding:
      20px
      22px
      16px;

  background:
      radial-gradient(
          circle at 50% 45%,
          rgba(
              255,
              255,
              255,
              0.075
          ),
          transparent 55%
      ),
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.hero-heading {
  position: relative;

  z-index: 3;

  display: flex;

  align-items: flex-start;

  justify-content:
      space-between;

  gap: 16px;
}

.hero-heading h2 {
  margin:
      0
      0
      8px;

  font-size:
      clamp(
          1.55rem,
          5vw,
          2.2rem
      );
}

.pilot-meta {
  display: flex;

  flex-wrap: wrap;

  gap: 6px;
}

.pilot-meta span {
  padding:
      5px
      8px;

  border-radius: 7px;

  background:
      rgba(
          255,
          255,
          255,
          0.055
      );

  font-size: 0.65rem;

  opacity: 0.7;
}

.style-shortcut {
  padding:
      7px
      10px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.1
      );

  border-radius: 9px;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );

  color: inherit;

  cursor: pointer;

  font-size: 0.72rem;
  font-weight: 700;
}

.hero-car,
.preview-car {
  position: relative;

  width: 100%;
  max-width: 720px;

  margin:
      -4px
      auto
      0;

  padding:
      8px
      20px
      2px;
}

.garage-floor {
  position: absolute;

  left: 18%;
  right: 18%;
  bottom: 15%;

  height: 12px;

  border-radius: 50%;

  background:
      rgba(
          0,
          0,
          0,
          0.32
      );

  filter:
      blur(8px);
}

/*
 * =====================================
 * STATS
 * =====================================
 */

.hero-stats {
  display: grid;

  grid-template-columns:
      repeat(
          4,
          1fr
      );

  gap: 8px;
}

.hero-stats article {
  position: relative;

  display: grid;

  padding:
      10px
      11px;

  border-radius: 11px;

  background:
      rgba(
          255,
          255,
          255,
          0.045
      );
}

.hero-stats span {
  font-size: 0.63rem;

  opacity: 0.5;
}

.hero-stats strong {
  margin-top: 2px;

  font-size: 1.15rem;
}

.hero-stats small {
  position: absolute;

  right: 9px;
  bottom: 9px;

  font-size: 0.6rem;

  opacity: 0.45;
}

.stats-loading {
  padding: 14px;

  text-align: center;

  font-size: 0.75rem;

  opacity: 0.5;
}

/*
 * =====================================
 * TABS
 * =====================================
 */

.garage-tabs {
  position: sticky;

  top: 6px;

  z-index: 30;

  display: grid;

  grid-template-columns:
      repeat(
          4,
          minmax(
              90px,
              1fr
          )
      );

  gap: 4px;

  overflow-x: auto;

  margin:
      14px
      0;

  padding: 5px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.07
      );

  border-radius: 14px;

  background:
      rgba(
          20,
          22,
          26,
          0.93
      );

  backdrop-filter:
      blur(14px);
}

.garage-tab {
  min-height: 40px;

  padding:
      7px
      10px;

  border: 0;

  border-radius: 10px;

  background:
      transparent;

  color: inherit;

  cursor: pointer;

  font-size: 0.72rem;
  font-weight: 700;

  opacity: 0.45;
}

.garage-tab-active {
  background:
      rgba(
          255,
          255,
          255,
          0.09
      );

  opacity: 1;
}

.garage-tab-content {
  animation:
      tab-enter
      170ms
      ease;
}

@keyframes tab-enter {
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
 * APERÇU
 * =====================================
 */

.summary-grid {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          minmax(
              0,
              1fr
          )
      );

  gap: 10px;
}

.summary-card {
  display: flex;

  min-height: 145px;

  flex-direction: column;

  padding: 16px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.07
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

.summary-number {
  margin-bottom: 8px;

  font-size: 1.55rem;
  font-weight: 900;
}

.summary-card strong {
  font-size: 0.9rem;
}

.summary-card small {
  margin-top: 2px;

  opacity: 0.45;
}

.summary-link {
  margin-top: auto;
  padding: 0;

  border: 0;

  background: none;

  color: inherit;

  cursor: pointer;

  text-align: left;

  font-size: 0.7rem;
  font-weight: 700;

  opacity: 0.6;
}

.applied-effects {
  margin-top: 10px;

  padding: 18px;
}

.effects-list {
  display: grid;

  gap: 7px;
}

.effect-row {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 16px;

  padding:
      9px
      10px;

  border-radius: 9px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

.effect-row > div {
  display: grid;
}

.effect-row small {
  opacity: 0.45;
}

.effect-row > span {
  font-size: 0.75rem;
  font-weight: 700;
}

.muted-text {
  opacity: 0.5;
}

/*
 * =====================================
 * TITRES D'ONGLET
 * =====================================
 */

.tab-heading,
.category-heading {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 14px;
}

.tab-heading {
  margin-bottom: 12px;
}

.tab-heading h2,
.category-heading h3 {
  margin: 0;
}

.tab-heading > span,
.category-heading > span {
  display: flex;

  min-width: 30px;
  height: 30px;

  align-items: center;
  justify-content: center;

  border-radius: 99px;

  background:
      rgba(
          255,
          255,
          255,
          0.06
      );

  font-size: 0.7rem;
}

/*
 * =====================================
 * ÉQUIPEMENT
 * =====================================
 */

.equipment-grid {
  display: grid;

  grid-template-columns:
      repeat(
          2,
          minmax(
              0,
              1fr
          )
      );

  gap: 10px;
}

.equipment-slot {
  padding: 14px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.07
      );

  border-radius: 15px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.equipment-slot-heading {
  display: flex;

  align-items: center;

  gap: 8px;

  margin-bottom: 10px;
}

.equipment-slot-heading h3 {
  margin: 0;

  font-size: 0.85rem;
}

.slot-indicator {
  width: 8px;
  height: 8px;

  border-radius: 50%;

  background:
      rgba(
          255,
          255,
          255,
          0.15
      );
}

.slot-indicator-filled {
  background:
      rgba(
          70,
          200,
          120,
          0.9
      );

  box-shadow:
      0
      0
      8px
      rgba(
          70,
          200,
          120,
          0.35
      );
}

.empty-slot {
  margin: 0;

  font-size: 0.72rem;

  opacity: 0.4;
}

.equipment-options {
  display: grid;

  gap: 7px;
}

.inventory-card {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 10px;

  padding: 10px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.06
      );

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.inventory-card-equipped {
  border-color:
      rgba(
          70,
          200,
          120,
          0.32
      );

  background:
      rgba(
          70,
          200,
          120,
          0.045
      );
}

.inventory-info {
  min-width: 0;
}

.inventory-title {
  display: flex;

  align-items: center;

  gap: 6px;
}

.inventory-title strong {
  overflow: hidden;

  font-size: 0.76rem;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.inventory-title span {
  font-size: 0.6rem;

  opacity: 0.45;
}

.inventory-info p {
  margin:
      3px
      0
      0;

  font-size: 0.66rem;

  opacity: 0.55;
}

.status-badge,
.equip-button {
  flex: 0 0 auto;

  padding:
      5px
      7px;

  border-radius: 7px;

  font-size: 0.62rem;
  font-weight: 800;
}

.status-badge {
  background:
      rgba(
          70,
          200,
          120,
          0.12
      );
}

.equip-button {
  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.11
      );

  background:
      rgba(
          255,
          255,
          255,
          0.05
      );

  color: inherit;

  cursor: pointer;
}

/*
 * =====================================
 * CARTES
 * =====================================
 */

.card-category {
  margin-bottom: 24px;
}

.category-heading {
  margin-bottom: 10px;
}

.category-heading p {
  margin:
      3px
      0
      0;

  font-size: 0.7rem;

  opacity: 0.45;
}

.cards-grid {
  display: grid;

  grid-template-columns:
      repeat(
          auto-fill,
          minmax(
              190px,
              1fr
          )
      );

  gap: 9px;
}

.game-card {
  position: relative;

  min-height: 130px;

  padding: 14px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.075
      );

  border-radius: 14px;

  background:
      linear-gradient(
          145deg,
          rgba(
              255,
              255,
              255,
              0.055
          ),
          rgba(
              255,
              255,
              255,
              0.018
          )
      );
}

.game-card-tier {
  position: absolute;

  top: 9px;
  right: 9px;

  padding:
      4px
      6px;

  border-radius: 6px;

  background:
      rgba(
          255,
          255,
          255,
          0.07
      );

  font-size: 0.58rem;
  font-weight: 900;
}

.game-card strong {
  display: block;

  max-width:
      calc(
          100%
          - 35px
      );

  font-size: 0.82rem;
}

.game-card p {
  margin:
      8px
      0;

  font-size: 0.7rem;

  opacity: 0.55;
}

.game-card small {
  font-size: 0.62rem;

  opacity: 0.4;
}

.empty-category {
  padding: 18px;

  border-radius: 12px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );

  text-align: center;

  font-size: 0.75rem;

  opacity: 0.45;
}

/*
 * =====================================
 * STYLE
 * =====================================
 */

.style-preview {
  overflow: hidden;

  margin-bottom: 12px;

  padding: 5px;
}

.style-sections {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          minmax(
              0,
              1fr
          )
      );

  gap: 10px;
}

.style-section {
  padding: 15px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.07
      );

  border-radius: 15px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.style-section h3 {
  margin:
      0
      0
      12px;

  font-size: 0.85rem;
}

.color-list {
  display: flex;

  flex-wrap: wrap;

  gap: 8px;
}

.color-button {
  width: 32px;
  height: 32px;

  padding: 0;

  border:
      3px solid
      transparent;

  border-radius: 50%;

  cursor: pointer;
}

.color-button-selected {
  border-color:
      rgba(
          255,
          255,
          255,
          0.95
      );

  box-shadow:
      0
      0
      0
      2px
      rgba(
          0,
          0,
          0,
          0.35
      );
}

.custom-color-row {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 10px;

  margin-top: 12px;

  font-size: 0.68rem;

  opacity: 0.7;
}

.custom-color-row > div {
  display: flex;

  align-items: center;

  gap: 7px;
}

.custom-color-row input {
  width: 38px;
  height: 30px;
}

.body-options,
.wheel-options {
  display: grid;

  grid-template-columns:
      repeat(
          2,
          minmax(
              0,
              1fr
          )
      );

  gap: 6px;
}

.body-option {
  min-height: 36px;

  padding:
      6px
      8px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.08
      );

  border-radius: 8px;

  background:
      rgba(
          255,
          255,
          255,
          0.025
      );

  color: inherit;

  cursor: pointer;

  font-size: 0.68rem;
}

.body-option-selected {
  border-color:
      rgba(
          80,
          140,
          235,
          0.7
      );

  background:
      rgba(
          80,
          140,
          235,
          0.12
      );

  font-weight: 800;
}

.save-style-bar {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 14px;

  margin-top: 12px;

  padding: 12px;

  border-radius: 13px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

.save-style-bar span {
  font-size: 0.68rem;

  opacity: 0.45;
}

/*
 * =====================================
 * STATES
 * =====================================
 */

.loading-card,
.empty-garage {
  padding: 28px;

  text-align: center;
}

.empty-icon {
  display: flex;

  width: 54px;
  height: 54px;

  align-items: center;
  justify-content: center;

  margin:
      0
      auto
      12px;

  border-radius: 50%;

  background:
      rgba(
          255,
          255,
          255,
          0.06
      );

  font-size: 1.3rem;
}

/*
 * =====================================
 * TABLETTE
 * =====================================
 */

@media (
max-width: 800px
) {
  .style-sections {
    grid-template-columns:
        1fr;
  }
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 600px
) {
  .garage-page {
    width:
        calc(
            100%
            - 20px
        );

    padding-top: 14px;
  }

  .garage-header {
    margin-bottom: 12px;
  }

  .add-label {
    display: none;
  }

  .add-car-button {
    min-width: 42px;

    padding:
        8px
        12px;
  }

  .create-fields {
    grid-template-columns:
        1fr
        auto;
  }

  .create-button {
    grid-column:
        1
        / -1;

    width: 100%;
  }

  /*
   * HERO MOBILE
   */

  .car-hero {
    padding:
        16px
        12px
        12px;
  }

  .hero-car {
    margin-top: 0;

    padding:
        0;
  }

  .hero-stats {
    gap: 5px;
  }

  .hero-stats article {
    padding:
        8px
        7px;
  }

  .hero-stats span {
    font-size: 0.56rem;
  }

  .hero-stats strong {
    font-size: 1rem;
  }

  .hero-stats small {
    display: none;
  }

  /*
   * TABS MOBILE
   */

  .garage-tabs {
    grid-template-columns:
        repeat(
            4,
            minmax(
                80px,
                1fr
            )
        );

    overflow-x: auto;
  }

  /*
   * APERÇU MOBILE
   */

  .summary-grid {
    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap: 6px;
  }

  .summary-card {
    min-height: 125px;

    padding: 11px;
  }

  .summary-number {
    font-size: 1.25rem;
  }

  .summary-card strong {
    font-size: 0.72rem;
  }

  .summary-card small {
    font-size: 0.58rem;
  }

  .summary-link {
    font-size: 0.6rem;
  }

  /*
   * ÉQUIPEMENTS MOBILE
   */

  .equipment-grid {
    grid-template-columns:
        1fr;
  }

  /*
   * CARTES MOBILE
   */

  .cards-grid {
    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );
  }

  /*
   * STYLE MOBILE
   */

  .preview-car {
    padding:
        0;
  }

  .save-style-bar {
    position: sticky;

    bottom:
        calc(
            86px
            + env(
            safe-area-inset-bottom
            )
        );

    z-index: 20;

    flex-direction: column;

    align-items: stretch;

    background:
        rgba(
            20,
            22,
            26,
            0.96
        );

    backdrop-filter:
        blur(16px);
  }

  .save-style-bar .button {
    width: 100%;
  }
}

/*
 * =====================================
 * PETITS MOBILES
 * =====================================
 */

@media (
max-width: 390px
) {
  .pilot-meta span {
    font-size: 0.58rem;
  }

  .cards-grid {
    grid-template-columns:
        1fr;
  }

  .summary-card {
    padding: 9px;
  }
}
</style>