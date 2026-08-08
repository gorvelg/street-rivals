<script setup lang="ts">
import {
  computed,
  onMounted,
  ref,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import CarVisual
  from '../components/CarVisual.vue'

import OpponentCard
  from '../components/OpponentCard.vue'

import {
  ApiError,
  apiRequest,
  getCollectionMembers,
} from '../services/api'

import {
  usePlayerCarStore,
} from '../stores/playerCar'

import type {
  ApiCollection,
  Car,
  CarStats,
  MatchmakingOpponent,
  PendingDuel,
} from '../types/api'

const router =
    useRouter()

const playerCarStore =
    usePlayerCarStore()

/*
 * =====================================
 * ÉTAT
 * =====================================
 */

const selectedCar =
    ref<Car | null>(
        null,
    )

const stats =
    ref<CarStats | null>(
        null,
    )

const opponents =
    ref<
        MatchmakingOpponent[]
    >(
        [],
    )

const selectedOpponent =
    ref<
        MatchmakingOpponent | null
    >(
        null,
    )

const loadingCar =
    ref(false)

const loadingMatchmaking =
    ref(false)

const errorMessage =
    ref('')

/*
 * =====================================
 * COMPUTED
 * =====================================
 */

const hasCar =
    computed(
        () =>
            selectedCar.value
            !== null,
    )

const isLoading =
    computed(
        () =>
            loadingCar.value
            || loadingMatchmaking.value,
    )

/*
 * =====================================
 * VOITURE ACTIVE
 * =====================================
 */

async function loadActiveCar():
    Promise<Car | null> {
  loadingCar.value =
      true

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

    /*
     * Aucun véhicule.
     */
    if (
        cars.length
        === 0
    ) {
      selectedCar.value =
          null

      playerCarStore
          .clearSelection()

      return null
    }

    /*
     * Restauration de la voiture active.
     */
    if (
        !playerCarStore.initialized
    ) {
      playerCarStore
          .restoreSelection()
    }

    let car:
        Car | undefined

    if (
        playerCarStore
            .selectedCarId
        !== null
    ) {
      car =
          cars.find(
              (candidate) =>
                  candidate.id
                  === playerCarStore
                      .selectedCarId,
          )
    }

    /*
     * Si la voiture mémorisée n'existe plus,
     * on prend la première.
     */
    if (
        car === undefined
    ) {
      car =
          cars[0]
    }

    selectedCar.value =
        car

    playerCarStore.setCar(
        car,
    )

    return car
  } catch (error) {
    selectedCar.value =
        null

    stats.value =
        null

    opponents.value =
        []

    handleError(
        error,
        'Impossible de charger le garage.',
    )

    return null
  } finally {
    loadingCar.value =
        false
  }
}

/*
 * =====================================
 * MATCHMAKING
 * =====================================
 */

async function loadMatchmaking(
    car: Car,
): Promise<void> {
  loadingMatchmaking.value =
      true

  errorMessage.value =
      ''

  selectedOpponent.value =
      null

  try {
    const [
      statsResponse,
      opponentsResponse,
    ] =
        await Promise.all([
          apiRequest<CarStats>(
              `/api/cars/${car.id}/stats`,
          ),

          apiRequest<
              ApiCollection<
                  MatchmakingOpponent
              >
          >(
              `/api/cars/${car.id}/opponents`,
          ),
        ])

    stats.value =
        statsResponse

    opponents.value =
        getCollectionMembers(
            opponentsResponse,
        )
  } catch (error) {
    stats.value =
        null

    opponents.value =
        []

    handleError(
        error,
        'Impossible de charger le matchmaking.',
    )
  } finally {
    loadingMatchmaking.value =
        false
  }
}

/*
 * =====================================
 * CHARGEMENT COMPLET
 * =====================================
 */

async function loadDuels():
    Promise<void> {
  errorMessage.value =
      ''

  selectedOpponent.value =
      null

  const car =
      await loadActiveCar()

  if (
      car === null
  ) {
    return
  }

  await loadMatchmaking(
      car,
  )
}

/*
 * =====================================
 * SÉLECTION ADVERSAIRE
 * =====================================
 */

function selectOpponent(
    opponent:
    MatchmakingOpponent,
): void {
  if (
      selectedCar.value
      === null
  ) {
    return
  }

  selectedOpponent.value =
      opponent
}

/*
 * =====================================
 * DUEL
 * =====================================
 */

async function startDuel():
    Promise<void> {
  if (
      selectedCar.value
      === null
      || selectedOpponent.value
      === null
  ) {
    return
  }

  const car =
      selectedCar.value

  const opponent =
      selectedOpponent.value

  const pendingDuel:
      PendingDuel = {
    attackerCarId:
    car.id,

    attackerPilotName:
    car.pilotName,

    attackerColor:
    car.color,

    attackerBodyStyle:
    car.bodyStyle,

    attackerWheelStyle:
    car.wheelStyle,

    defenderCarId:
    opponent.carId,

    defenderPilotName:
    opponent.pilotName,

    defenderColor:
    opponent.color,

    defenderBodyStyle:
    opponent.bodyStyle,

    defenderWheelStyle:
    opponent.wheelStyle,

    difficulty:
    opponent.difficulty,
  }

  sessionStorage.setItem(
      'street-rivals-pending-duel',
      JSON.stringify(
          pendingDuel,
      ),
  )

  await router.push({
    name: 'duel',
  })
}

/*
 * =====================================
 * NAVIGATION
 * =====================================
 */

async function openGarage():
    Promise<void> {
  await router.push({
    name: 'garage',
  })
}

/*
 * =====================================
 * FORMATAGE
 * =====================================
 */

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

/*
 * =====================================
 * ERREURS
 * =====================================
 */

function handleError(
    error: unknown,
    fallback: string,
): void {
  if (
      error instanceof ApiError
  ) {
    errorMessage.value =
        error.message

    return
  }

  errorMessage.value =
      fallback
}

/*
 * =====================================
 * INIT
 * =====================================
 */

onMounted(
    async () => {
      await loadDuels()
    },
)
</script>

<template>
  <section class="duels-page">
    <!-- =====================================
         HEADER
    ====================================== -->

    <header class="duels-header">
      <div>
        <p class="eyebrow">
          Street Rivals
        </p>

        <h1>
          Duels
        </h1>
      </div>

      <button
          v-if="
            hasCar
          "
          type="button"
          class="
            refresh-button
          "
          :disabled="
            isLoading
          "
          @click="
            loadDuels
          "
      >
        <span
            :class="{
              'refresh-icon-loading':
                isLoading,
            }"
        >
          ↻
        </span>

        <span class="refresh-label">
          Actualiser
        </span>
      </button>
    </header>

    <!-- =====================================
         ERREUR
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

    <!-- =====================================
         CHARGEMENT VOITURE
    ====================================== -->

    <section
        v-if="
          loadingCar
        "
        class="
          duel-card
          loading-state
        "
    >
      Chargement du pilote...
    </section>

    <!-- =====================================
         PAS DE VOITURE
    ====================================== -->

    <section
        v-else-if="
          !hasCar
        "
        class="
          duel-card
          no-car-state
        "
    >
      <div class="no-car-symbol">
        ?
      </div>

      <div>
        <p class="eyebrow">
          Garage
        </p>

        <h2>
          Aucune voiture
        </h2>

        <p>
          Crée ou sélectionne une voiture avant
          de rechercher un adversaire.
        </p>
      </div>

      <button
          type="button"
          class="
            button
            button-primary
          "
          @click="
            openGarage
          "
      >
        Aller au garage
      </button>
    </section>

    <!-- =====================================
         CONTENU
    ====================================== -->

    <template
        v-else-if="
          selectedCar !== null
        "
    >
      <!-- =================================
           PILOTE ACTIF
      ================================== -->

      <section
          class="
            duel-card
            player-card
          "
      >
        <div class="player-heading">
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

            <div class="player-meta">
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
                change-car-button
              "
              @click="
                openGarage
              "
          >
            Changer
          </button>
        </div>

        <!-- VOITURE -->

        <div class="player-car-visual">
          <div class="car-shadow" />

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
              stats !== null
            "
            class="
              player-stats
            "
        >
          <article>
            <span>
              Vitesse
            </span>

            <strong>
              {{
                stats.effective
                    .speed
              }}
            </strong>

            <small>
              {{
                signedValue(
                    stats.bonuses
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
                stats.effective
                    .acceleration
              }}
            </strong>

            <small>
              {{
                signedValue(
                    stats.bonuses
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
                stats.effective
                    .grip
              }}
            </strong>

            <small>
              {{
                signedValue(
                    stats.bonuses
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
                stats.effective
                    .solidity
              }}
            </strong>

            <small>
              {{
                signedValue(
                    stats.bonuses
                        .solidity,
                )
              }}
            </small>
          </article>
        </div>
      </section>

      <!-- =================================
           ADVERSAIRES
      ================================== -->

      <section class="matchmaking">
        <div class="matchmaking-heading">
          <div>
            <p class="eyebrow">
              Matchmaking
            </p>

            <h2>
              Choisis ton adversaire
            </h2>
          </div>

          <span
              v-if="
                !loadingMatchmaking
              "
              class="
                opponent-counter
              "
          >
            {{
              opponents.length
            }}
          </span>
        </div>

        <!-- CHARGEMENT -->

        <div
            v-if="
              loadingMatchmaking
            "
            class="
              duel-card
              matchmaking-loading
            "
        >
          <span class="loading-spinner">
            ↻
          </span>

          <span>
            Recherche d'adversaires...
          </span>
        </div>

        <!-- LISTE -->

        <div
            v-else-if="
              opponents.length
              > 0
            "
            class="
              opponents-grid
            "
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

        <!-- AUCUN ADVERSAIRE -->

        <div
            v-else-if="
              errorMessage === ''
            "
            class="
              duel-card
              empty-opponents
            "
        >
          <div class="empty-opponents-symbol">
            VS
          </div>

          <h3>
            Aucun adversaire disponible
          </h3>

          <p>
            Aucun pilote compatible n'est disponible
            pour le moment.
          </p>

          <button
              type="button"
              class="
                button
                button-secondary
              "
              @click="
                loadMatchmaking(
                    selectedCar,
                )
              "
          >
            Relancer la recherche
          </button>
        </div>
      </section>

      <!-- =================================
           ADVERSAIRE SÉLECTIONNÉ
      ================================== -->

      <section
          v-if="
            selectedOpponent
            !== null
          "
          class="
            fight-panel
          "
      >
        <div class="fight-summary">
          <div class="fight-driver">
            <span>
              Toi
            </span>

            <strong>
              {{
                selectedCar
                    .pilotName
              }}
            </strong>
          </div>

          <div class="fight-vs">
            VS
          </div>

          <div
              class="
                fight-driver
                fight-driver-right
              "
          >
            <span>
              Adversaire
            </span>

            <strong>
              {{
                selectedOpponent
                    .pilotName
              }}
            </strong>
          </div>
        </div>

        <button
            type="button"
            class="
              fight-button
            "
            @click="
              startDuel
            "
        >
          <span>
            Affronter
          </span>

          <strong>
            {{
              selectedOpponent
                  .pilotName
            }}
          </strong>
        </button>
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

.duels-page {
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
      34px;
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

/*
 * =====================================
 * HEADER
 * =====================================
 */

.duels-header {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 16px;

  margin-bottom: 18px;
}

.duels-header h1 {
  margin: 0;

  font-size:
      clamp(
          1.65rem,
          4vw,
          2.2rem
      );
}

.refresh-button {
  display: inline-flex;

  align-items: center;

  gap: 7px;

  min-height: 38px;

  padding:
      0
      12px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );

  color: inherit;

  cursor: pointer;

  font-size: 0.7rem;
  font-weight: 700;
}

.refresh-button:disabled {
  cursor: default;

  opacity: 0.45;
}

.refresh-icon-loading {
  display: inline-block;

  animation:
      spin
      850ms
      linear
      infinite;
}

/*
 * =====================================
 * CARDS
 * =====================================
 */

.duel-card {
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
          0.03
      );
}

/*
 * =====================================
 * PILOTE
 * =====================================
 */

.player-card {
  overflow: hidden;

  padding:
      20px
      22px
      16px;

  background:
      radial-gradient(
          circle at 50% 44%,
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

.player-heading {
  position: relative;

  z-index: 3;

  display: flex;

  align-items: flex-start;

  justify-content:
      space-between;

  gap: 16px;
}

.player-heading h2 {
  margin:
      0
      0
      8px;

  font-size:
      clamp(
          1.5rem,
          4vw,
          2rem
      );
}

.player-meta {
  display: flex;

  flex-wrap: wrap;

  gap: 6px;
}

.player-meta span {
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

  font-size: 0.63rem;

  opacity: 0.7;
}

.change-car-button {
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

  font-size: 0.7rem;
  font-weight: 700;
}

/*
 * =====================================
 * VOITURE
 * =====================================
 */

.player-car-visual {
  position: relative;

  width: 100%;
  max-width: 650px;

  margin:
      -6px
      auto
      0;

  padding:
      4px
      18px
      0;
}

.car-shadow {
  position: absolute;

  left: 19%;
  right: 19%;
  bottom: 16%;

  height: 12px;

  border-radius: 50%;

  background:
      rgba(
          0,
          0,
          0,
          0.34
      );

  filter:
      blur(8px);
}

/*
 * =====================================
 * STATS
 * =====================================
 */

.player-stats {
  display: grid;

  grid-template-columns:
      repeat(
          4,
          minmax(
              0,
              1fr
          )
      );

  gap: 7px;
}

.player-stats article {
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

.player-stats span {
  font-size: 0.61rem;

  opacity: 0.48;
}

.player-stats strong {
  margin-top: 2px;

  font-size: 1.1rem;
}

.player-stats small {
  position: absolute;

  right: 9px;
  bottom: 9px;

  font-size: 0.57rem;

  opacity: 0.42;
}

/*
 * =====================================
 * MATCHMAKING
 * =====================================
 */

.matchmaking {
  margin-top: 24px;
}

.matchmaking-heading {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 16px;

  margin-bottom: 12px;
}

.matchmaking-heading h2 {
  margin: 0;

  font-size:
      clamp(
          1.15rem,
          3vw,
          1.45rem
      );
}

.opponent-counter {
  display: flex;

  min-width: 32px;
  height: 32px;

  align-items: center;
  justify-content: center;

  border-radius: 99px;

  background:
      rgba(
          255,
          255,
          255,
          0.065
      );

  font-size: 0.72rem;
  font-weight: 800;

  opacity: 0.7;
}

.matchmaking-loading {
  display: flex;

  min-height: 100px;

  align-items: center;
  justify-content: center;

  gap: 10px;

  font-size: 0.75rem;

  opacity: 0.6;
}

.loading-spinner {
  display: inline-block;

  font-size: 1.2rem;

  animation:
      spin
      850ms
      linear
      infinite;
}

@keyframes spin {
  to {
    transform:
        rotate(360deg);
  }
}

/*
 * =====================================
 * ADVERSAIRES
 * =====================================
 */

.opponents-grid {
  display: grid;

  grid-template-columns:
      repeat(
          auto-fit,
          minmax(
              250px,
              1fr
          )
      );

  gap: 10px;
}

/*
 * =====================================
 * EMPTY
 * =====================================
 */

.empty-opponents,
.no-car-state {
  padding: 28px;

  text-align: center;
}

.empty-opponents-symbol,
.no-car-symbol {
  display: flex;

  width: 58px;
  height: 58px;

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

  font-size: 0.9rem;
  font-weight: 900;
}

.empty-opponents h3,
.no-car-state h2 {
  margin:
      0
      0
      6px;
}

.empty-opponents p,
.no-car-state p {
  margin:
      0
      0
      16px;

  font-size: 0.75rem;

  opacity: 0.55;
}

.loading-state {
  padding: 30px;

  text-align: center;

  opacity: 0.55;
}

/*
 * =====================================
 * FIGHT PANEL
 * =====================================
 */

.fight-panel {
  position: sticky;

  bottom:
      calc(
          88px
          + env(
          safe-area-inset-bottom
          )
      );

  z-index: 25;

  display: grid;

  grid-template-columns:
      minmax(
          0,
          1fr
      )
      auto;

  align-items: center;

  gap: 16px;

  margin-top: 18px;

  padding: 12px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.12
      );

  border-radius: 17px;

  background:
      rgba(
          18,
          20,
          24,
          0.96
      );

  box-shadow:
      0
      18px
      40px
      rgba(
          0,
          0,
          0,
          0.34
      );

  backdrop-filter:
      blur(18px);
}

.fight-summary {
  display: flex;

  min-width: 0;

  align-items: center;

  gap: 12px;
}

.fight-driver {
  display: grid;

  min-width: 0;
}

.fight-driver span {
  font-size: 0.58rem;

  opacity: 0.4;
}

.fight-driver strong {
  overflow: hidden;

  font-size: 0.82rem;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.fight-driver-right {
  text-align: right;
}

.fight-vs {
  flex: 0 0 auto;

  font-size: 0.65rem;
  font-weight: 900;

  opacity: 0.38;
}

.fight-button {
  display: grid;

  min-width: 155px;

  padding:
      9px
      16px;

  border: 0;

  border-radius: 11px;

  background:
      rgba(
          255,
          255,
          255,
          0.94
      );

  color:
      rgba(
          10,
          12,
          15,
          1
      );

  cursor: pointer;

  text-align: center;

  font: inherit;
}

.fight-button span {
  font-size: 0.58rem;

  text-transform: uppercase;

  opacity: 0.55;
}

.fight-button strong {
  font-size: 0.82rem;
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 600px
) {
  .duels-page {
    width:
        calc(
            100%
            - 20px
        );

    padding-top: 14px;
  }

  /*
   * HEADER
   */

  .duels-header {
    margin-bottom: 12px;
  }

  .refresh-label {
    display: none;
  }

  .refresh-button {
    min-width: 40px;

    justify-content: center;

    padding:
        0
        10px;

    font-size: 1rem;
  }

  /*
   * PILOTE
   */

  .player-card {
    padding:
        16px
        12px
        12px;
  }

  .player-car-visual {
    padding: 0;
  }

  .player-stats {
    gap: 5px;
  }

  .player-stats article {
    padding:
        8px
        7px;
  }

  .player-stats span {
    font-size: 0.54rem;
  }

  .player-stats strong {
    font-size: 1rem;
  }

  .player-stats small {
    display: none;
  }

  /*
   * MATCHMAKING
   */

  .matchmaking {
    margin-top: 18px;
  }

  .opponents-grid {
    grid-template-columns:
        1fr;
  }

  /*
   * FIGHT
   */

  .fight-panel {
    bottom:
        calc(
            92px
            + env(
            safe-area-inset-bottom
            )
        );

    grid-template-columns:
        1fr;

    gap: 8px;

    padding: 10px;
  }

  .fight-summary {
    justify-content:
        center;
  }

  .fight-button {
    width: 100%;
  }
}
</style>