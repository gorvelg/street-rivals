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

import {
  ApiError,
  apiRequest,
} from '../services/api'

import {
  usePlayerCarStore,
} from '../stores/playerCar'

import type {
  DuelHistoryItem,
  DuelHistoryResponse,
  RankingEntry,
  RankingResponse,
} from '../types/api'

type RankingTab =
    | 'ranking'
    | 'history'

const router =
    useRouter()

const playerCarStore =
    usePlayerCarStore()

const activeTab =
    ref<RankingTab>(
        'ranking',
    )

const ranking =
    ref<RankingEntry[]>(
        [],
    )

const currentRanking =
    ref<RankingEntry | null>(
        null,
    )

const history =
    ref<DuelHistoryItem[]>(
        [],
    )

const loading =
    ref(false)

const errorMessage =
    ref('')

const selectedCarId =
    computed(
        () =>
            playerCarStore
                .selectedCarId,
    )

/*
 * =====================================
 * CHARGEMENT
 * =====================================
 */

async function loadData():
    Promise<void> {
  loading.value =
      true

  errorMessage.value =
      ''

  try {
    if (
        !playerCarStore.initialized
    ) {
      playerCarStore
          .restoreSelection()
    }

    const carId =
        playerCarStore
            .selectedCarId

    const rankingUrl =
        carId !== null
            ? `/api/ranking?carId=${carId}`
            : '/api/ranking'

    const rankingResponse =
        await apiRequest<
            RankingResponse
        >(
            rankingUrl,
        )

    ranking.value =
        rankingResponse.items

    currentRanking.value =
        rankingResponse.current

    /*
     * L'historique est propre
     * à la voiture active.
     */
    if (
        carId === null
    ) {
      history.value = []

      return
    }

    const historyResponse =
        await apiRequest<
            DuelHistoryResponse
        >(
            `/api/cars/${carId}/duel-history`,
        )

    history.value =
        historyResponse.items
  } catch (error) {
    if (
        error instanceof ApiError
    ) {
      errorMessage.value =
          error.message

      return
    }

    errorMessage.value =
        'Impossible de charger le classement.'
  } finally {
    loading.value =
        false
  }
}

/*
 * =====================================
 * NAVIGATION
 * =====================================
 */

async function backToDuels():
    Promise<void> {
  await router.push({
    name: 'duels',
  })
}

/*
 * =====================================
 * FORMATAGE
 * =====================================
 */

function ratingDeltaLabel(
    value: number,
): string {
  if (
      value > 0
  ) {
    return `+${value}`
  }

  return String(value)
}

function winRate(
    entry: RankingEntry,
): string {
  const total =
      entry.wins
      + entry.losses

  if (
      total === 0
  ) {
    return '0 %'
  }

  return (
      `${Math.round(
          entry.wins
          / total
          * 100,
      )} %`
  )
}

function formatDate(
    value: string,
): string {
  const date =
      new Date(value)

  if (
      Number.isNaN(
          date.getTime(),
      )
  ) {
    return value
  }

  return new Intl
      .DateTimeFormat(
          'fr-FR',
          {
            dateStyle:
                'short',

            timeStyle:
                'short',
          },
      )
      .format(date)
}

function podiumLabel(
    rank: number,
): string {
  switch (
      rank
      ) {
    case 1:
      return '🥇'

    case 2:
      return '🥈'

    case 3:
      return '🥉'

    default:
      return `#${rank}`
  }
}

onMounted(
    async () => {
      await loadData()
    },
)
</script>

<template>
  <section class="ranking-page">
    <!-- =================================
         HEADER
    ================================== -->

    <div class="page-heading">
      <div>
        <p class="eyebrow">
          Street Rivals
        </p>

        <h1>
          Classement
        </h1>

        <p class="page-description">
          Compare ta progression avec
          les autres pilotes.
        </p>
      </div>

      <div class="heading-actions">
        <button
            type="button"
            class="
              button
              button-secondary
            "
            :disabled="
              loading
            "
            @click="
              loadData
            "
        >
          Actualiser
        </button>

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
          Retour aux duels
        </button>
      </div>
    </div>

    <!-- =================================
         ERREUR
    ================================== -->

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

    <!-- =================================
         POSITION JOUEUR
    ================================== -->

    <section
        v-if="
          currentRanking !== null
        "
        class="
          current-ranking
        "
    >
      <div class="current-ranking-copy">
        <p class="eyebrow">
          Ton classement
        </p>

        <div class="current-rank">
          #{{ currentRanking.rank }}
        </div>

        <h2>
          {{
            currentRanking
                .pilotName
          }}
        </h2>

        <div class="current-ranking-meta">
          <span>
            NIV.
            {{
              currentRanking.level
            }}
          </span>

          <span>
            {{
              currentRanking.rating
            }}
            Elo
          </span>

          <span>
            {{
              currentRanking.wins
            }}
            V
            ·
            {{
              currentRanking.losses
            }}
            D
          </span>
        </div>
      </div>

      <div class="current-ranking-car">
        <CarVisual
            :color="
              currentRanking.color
            "
            :body-style="
              currentRanking.bodyStyle
            "
            :wheel-style="
              currentRanking.wheelStyle
            "
            :pilot-name="
              currentRanking.pilotName
            "
        />
      </div>
    </section>

    <!-- =================================
         TABS
    ================================== -->

    <div class="ranking-tabs">
      <button
          type="button"
          :class="{
            active:
              activeTab
              === 'ranking',
          }"
          @click="
            activeTab =
              'ranking'
          "
      >
        Classement
      </button>

      <button
          type="button"
          :class="{
            active:
              activeTab
              === 'history',
          }"
          @click="
            activeTab =
              'history'
          "
      >
        Mes duels

        <span>
          {{ history.length }}
        </span>
      </button>
    </div>

    <!-- =================================
         LOADING
    ================================== -->

    <div
        v-if="
          loading
        "
        class="
          ranking-loading
        "
    >
      Chargement...
    </div>

    <!-- =================================
         CLASSEMENT
    ================================== -->

    <section
        v-else-if="
          activeTab
          === 'ranking'
        "
        class="
          ranking-list
        "
    >
      <article
          v-for="
            entry in ranking
          "
          :key="
            entry.carId
          "
          class="
            ranking-entry
          "
          :class="{
            'ranking-entry-current':
              entry.carId
              === selectedCarId,

            'ranking-entry-podium':
              entry.rank
              <= 3,
          }"
      >
        <!-- RANG -->

        <div class="ranking-position">
          {{
            podiumLabel(
                entry.rank,
            )
          }}
        </div>

        <!-- VOITURE -->

        <div class="ranking-car">
          <CarVisual
              :color="
                entry.color
              "
              :body-style="
                entry.bodyStyle
              "
              :wheel-style="
                entry.wheelStyle
              "
              :pilot-name="
                entry.pilotName
              "
          />
        </div>

        <!-- PILOTE -->

        <div class="ranking-driver">
          <strong>
            {{
              entry.pilotName
            }}
          </strong>

          <span>
            Niveau
            {{ entry.level }}
          </span>

          <small>
            {{
              entry.wins
            }}
            victoires ·

            {{
              entry.losses
            }}
            défaites ·

            {{
              winRate(
                  entry,
              )
            }}
          </small>
        </div>

        <!-- ELO -->

        <div class="ranking-rating">
          <strong>
            {{
              entry.rating
            }}
          </strong>

          <span>
            ELO
          </span>
        </div>
      </article>

      <div
          v-if="
            ranking.length
            === 0
          "
          class="
            empty-state
          "
      >
        Aucun pilote classé.
      </div>
    </section>

    <!-- =================================
         HISTORIQUE
    ================================== -->

    <section
        v-else
        class="
          duel-history
        "
    >
      <article
          v-for="
            duel in history
          "
          :key="
            duel.duelId
          "
          class="
            history-entry
          "
          :class="{
            'history-victory':
              duel.won,

            'history-defeat':
              !duel.won,
          }"
      >
        <!-- RESULTAT -->

        <div class="history-result">
          <span>
            {{
              duel.won
                  ? 'VICTOIRE'
                  : 'DÉFAITE'
            }}
          </span>

          <small>
            {{
              formatDate(
                  duel.createdAt,
              )
            }}
          </small>
        </div>

        <!-- OPPOSANT -->

        <div class="history-main">
          <div class="history-car">
            <CarVisual
                :color="
                  duel.opponent
                      .color
                "
                :body-style="
                  duel.opponent
                      .bodyStyle
                "
                :wheel-style="
                  duel.opponent
                      .wheelStyle
                "
                :pilot-name="
                  duel.opponent
                      .pilotName
                "
            />
          </div>

          <div class="history-opponent">
            <span>
              contre
            </span>

            <strong>
              {{
                duel.opponent
                    .pilotName
              }}
            </strong>

            <small>
              NIV.
              {{
                duel.opponent
                    .level
              }}
            </small>
          </div>
        </div>

        <!-- GAINS -->

        <div class="history-rewards">
          <div>
            <span>
              ELO
            </span>

            <strong>
              {{
                ratingDeltaLabel(
                    duel.ratingDelta,
                )
              }}
            </strong>
          </div>

          <div>
            <span>
              XP
            </span>

            <strong>
              +{{ duel.xpReward }}
            </strong>
          </div>

          <div>
            <span>
              $
            </span>

            <strong>
              +{{ duel.moneyReward }}
            </strong>
          </div>
        </div>
      </article>

      <div
          v-if="
            history.length
            === 0
          "
          class="
            empty-state
          "
      >
        <h2>
          Aucun duel
        </h2>

        <p>
          Les 20 derniers duels de ta
          voiture apparaîtront ici.
        </p>
      </div>
    </section>
  </section>
</template>

<style scoped>
.ranking-page {
  display: grid;

  gap: 16px;
}

.page-description {
  margin:
      4px
      0
      0;

  opacity: 0.5;
}

.heading-actions {
  display: flex;

  flex-wrap: wrap;

  gap: 8px;
}

/*
 * =====================================
 * POSITION ACTUELLE
 * =====================================
 */

.current-ranking {
  display: grid;

  grid-template-columns:
      minmax(
          0,
          0.8fr
      )
      minmax(
          0,
          1.2fr
      );

  align-items: center;

  overflow: hidden;

  padding:
      20px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.1
      );

  border-radius: 20px;

  background:
      radial-gradient(
          circle at 75% 50%,
          rgba(
              100,
              150,
              245,
              0.12
          ),
          transparent 45%
      ),
      rgba(
          255,
          255,
          255,
          0.025
      );
}

.current-rank {
  margin:
      2px
      0;

  font-size:
      clamp(
          2.8rem,
          9vw,
          5rem
      );

  font-weight: 950;

  line-height: 1;
}

.current-ranking-copy h2 {
  margin:
      3px
      0
      10px;
}

.current-ranking-meta {
  display: flex;

  flex-wrap: wrap;

  gap: 5px;
}

.current-ranking-meta span {
  padding:
      5px
      8px;

  border-radius: 999px;

  background:
      rgba(
          255,
          255,
          255,
          0.05
      );

  font-size: 0.62rem;
}

.current-ranking-car {
  width: 100%;
}

/*
 * =====================================
 * TABS
 * =====================================
 */

.ranking-tabs {
  display: grid;

  grid-template-columns:
      repeat(
          2,
          minmax(
              0,
              1fr
          )
      );

  gap: 5px;

  padding: 5px;

  border-radius: 12px;

  background:
      rgba(
          255,
          255,
          255,
          0.03
      );
}

.ranking-tabs button {
  min-height: 42px;

  border: 0;

  border-radius: 9px;

  background:
      transparent;

  color: inherit;

  cursor: pointer;

  font: inherit;

  font-size: 0.72rem;
  font-weight: 800;
}

.ranking-tabs button.active {
  background:
      rgba(
          255,
          255,
          255,
          0.09
      );
}

.ranking-tabs span {
  margin-left: 4px;

  opacity: 0.4;
}

/*
 * =====================================
 * CLASSEMENT
 * =====================================
 */

.ranking-list {
  display: grid;

  gap: 7px;
}

.ranking-entry {
  display: grid;

  grid-template-columns:
      55px
      125px
      minmax(
          0,
          1fr
      )
      auto;

  align-items: center;

  gap: 10px;

  padding:
      8px
      13px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.065
      );

  border-radius: 13px;

  background:
      rgba(
          255,
          255,
          255,
          0.022
      );
}

.ranking-entry-podium {
  border-color:
      rgba(
          255,
          255,
          255,
          0.12
      );
}

.ranking-entry-current {
  border-color:
      rgba(
          100,
          150,
          245,
          0.55
      );

  background:
      rgba(
          100,
          150,
          245,
          0.07
      );
}

.ranking-position {
  text-align: center;

  font-size: 1rem;
  font-weight: 900;
}

.ranking-car {
  width: 125px;
}

.ranking-driver {
  display: grid;

  min-width: 0;

  gap: 2px;
}

.ranking-driver strong {
  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.ranking-driver span,
.ranking-driver small {
  font-size: 0.61rem;

  opacity: 0.45;
}

.ranking-rating {
  display: grid;

  justify-items: end;
}

.ranking-rating strong {
  font-size: 1.15rem;
}

.ranking-rating span {
  font-size: 0.5rem;

  opacity: 0.4;
}

/*
 * =====================================
 * HISTORIQUE
 * =====================================
 */

.duel-history {
  display: grid;

  gap: 8px;
}

.history-entry {
  display: grid;

  grid-template-columns:
      110px
      minmax(
          0,
          1fr
      )
      auto;

  align-items: center;

  gap: 12px;

  padding:
      11px;

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
          255,
          255,
          255,
          0.022
      );
}

.history-victory {
  border-left:
      3px solid
      rgba(
          75,
          205,
          125,
          0.75
      );
}
.history-victory .history-result > span {
  color:
      rgba(
          75,
          205,
          125,
          0.75
      );
}
.history-defeat {
  border-left:
      3px solid
      rgba(
          225,
          85,
          95,
          0.7
      );
}

.history-defeat .history-result > span {
  color: rgba(
      225,
      85,
      95,
      0.7
  );
}

.history-result {
  display: grid;

  gap: 2px;
}

.history-result span {
  font-size: 0.67rem;
  font-weight: 900;
}

.history-result small {
  font-size: 0.54rem;

  opacity: 0.4;
}

.history-main {
  display: flex;

  min-width: 0;

  align-items: center;
}

.history-car {
  width: 110px;

  flex: 0 0 auto;
}

.history-opponent {
  display: grid;

  min-width: 0;
}

.history-opponent span,
.history-opponent small {
  font-size: 0.57rem;

  opacity: 0.4;
}

.history-opponent strong {
  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.history-rewards {
  display: grid;

  grid-template-columns:
      repeat(
          3,
          auto
      );

  gap: 6px;
}

.history-rewards > div {
  display: grid;

  min-width: 55px;

  justify-items: center;

  padding:
      6px;

  border-radius: 8px;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );
}

.history-rewards span {
  font-size: 0.48rem;

  opacity: 0.38;
}

.history-rewards strong {
  font-size: 0.67rem;
}

.ranking-loading {
  padding: 30px;

  text-align: center;

  opacity: 0.5;
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 700px
) {
  .current-ranking {
    grid-template-columns:
        1fr;

    padding:
        15px;

    text-align: center;
  }

  .current-ranking-meta {
    justify-content: center;
  }

  .current-ranking-car {
    max-width: 360px;

    margin:
        -15px
        auto;
  }

  .ranking-entry {
    grid-template-columns:
        42px
        85px
        minmax(
            0,
            1fr
        )
        auto;

    padding:
        7px;
  }

  .ranking-car {
    width: 85px;
  }

  .ranking-driver small {
    display: none;
  }

  .history-entry {
    grid-template-columns:
        1fr;

    gap: 7px;
  }

  .history-result {
    display: flex;

    align-items: center;
    justify-content: space-between;
  }

  .history-car {
    width: 95px;
  }

  .history-rewards {
    grid-template-columns:
        repeat(
            3,
            1fr
        );
  }

  .history-rewards > div {
    min-width: 0;
  }
}

@media (
max-width: 430px
) {
  .heading-actions {
    width: 100%;
  }

  .heading-actions .button {
    flex: 1;
  }

  .ranking-entry {
    grid-template-columns:
        35px
        72px
        minmax(
            0,
            1fr
        )
        auto;
  }

  .ranking-car {
    width: 72px;
  }

  .ranking-rating strong {
    font-size: 0.9rem;
  }

  .ranking-driver span {
    font-size: 0.55rem;
  }
}
</style>