<script setup lang="ts">
import {
  computed,
} from 'vue'

import CarVisual
  from './CarVisual.vue'

import type {
  MatchmakingOpponent,
} from '../types/api'

/*
 * =====================================
 * PROPS / EVENTS
 * =====================================
 */

const props =
    defineProps<{
      opponent:
          MatchmakingOpponent

      selected:
          boolean
    }>()

const emit =
    defineEmits<{
      select: [
        opponent:
            MatchmakingOpponent,
      ]
    }>()

/*
 * =====================================
 * DIFFICULTÉ
 * =====================================
 */

type DifficultyTone =
    | 'easy'
    | 'balanced'
    | 'hard'
    | 'neutral'

const difficultyTone =
    computed<DifficultyTone>(
        () => {
          const value =
              props.opponent
                  .difficulty
                  .trim()
                  .toLowerCase()

          if (
              value === 'easy'
              || value === 'facile'
          ) {
            return 'easy'
          }

          if (
              value === 'balanced'
              || value === 'medium'
              || value === 'normal'
              || value === 'équilibré'
              || value === 'equilibre'
          ) {
            return 'balanced'
          }

          if (
              value === 'hard'
              || value === 'difficult'
              || value === 'difficile'
          ) {
            return 'hard'
          }

          return 'neutral'
        },
    )

const difficultyLabel =
    computed(
        () => {
          switch (
              difficultyTone.value
              ) {
            case 'easy':
              return 'Facile'

            case 'balanced':
              return 'Équilibré'

            case 'hard':
              return 'Difficile'

            default:
              return props
                  .opponent
                  .difficulty
          }
        },
    )

/*
 * =====================================
 * DIFFÉRENCES
 * =====================================
 */

const levelDifferenceLabel =
    computed(
        () => {
          const difference =
              props.opponent
                  .levelDifference

          if (
              difference === 0
          ) {
            return 'Même niveau'
          }

          if (
              difference > 0
          ) {
            return `+${difference} niv.`
          }

          return `${difference} niv.`
        },
    )

const powerDifferenceLabel =
    computed(
        () => {
          const difference =
              props.opponent
                  .powerDifferencePercent

          if (
              difference === 0
          ) {
            return '0 %'
          }

          const prefix =
              difference > 0
                  ? '+'
                  : ''

          return (
              `${prefix}`
              + `${difference.toFixed(1)} %`
          )
        },
    )

/*
 * =====================================
 * SÉLECTION
 * =====================================
 */

function select():
    void {
  emit(
      'select',
      props.opponent,
  )
}
</script>

<template>
  <button
      type="button"
      class="opponent-card"
      :class="{
        'opponent-card-selected':
          selected,
      }"
      :aria-pressed="
        selected
      "
      @click="
        select
      "
  >
    <!-- =================================
         TOP
    ================================== -->

    <div class="opponent-top">
      <div class="level-block">
        <span>
          NIV.
        </span>

        <strong>
          {{
            opponent.level
          }}
        </strong>

        <small>
          {{
            levelDifferenceLabel
          }}
        </small>
      </div>

      <span
          class="
            difficulty-badge
          "
          :class="
            `difficulty-${difficultyTone}`
          "
      >
        {{
          difficultyLabel
        }}
      </span>
    </div>

    <!-- =================================
         IDENTITÉ
    ================================== -->

    <div class="opponent-identity">
      <p class="opponent-label">
        Adversaire
      </p>

      <h3>
        {{
          opponent.pilotName
        }}
      </h3>
    </div>

    <!-- =================================
         VOITURE
    ================================== -->

    <div class="opponent-car">
      <div class="car-shadow" />

      <CarVisual
          :color="
            opponent.color
          "
          :body-style="
            opponent.bodyStyle
          "
          :wheel-style="
            opponent.wheelStyle
          "
          :pilot-name="
            opponent.pilotName
          "
      />
    </div>

    <!-- =================================
         STATS
    ================================== -->

    <div class="opponent-stats">
      <div>
        <strong>
          {{
            opponent
                .effectiveStats
                .speed
          }}
        </strong>

        <span>
          VIT.
        </span>
      </div>

      <div>
        <strong>
          {{
            opponent
                .effectiveStats
                .acceleration
          }}
        </strong>

        <span>
          ACC.
        </span>
      </div>

      <div>
        <strong>
          {{
            opponent
                .effectiveStats
                .grip
          }}
        </strong>

        <span>
          GRIP
        </span>
      </div>

      <div>
        <strong>
          {{
            opponent
                .effectiveStats
                .solidity
          }}
        </strong>

        <span>
          SOL.
        </span>
      </div>
    </div>

    <!-- =================================
         PUISSANCE
    ================================== -->

    <div class="power-row">
      <div>
        <span>
          Puissance
        </span>

        <strong>
          {{
            opponent
                .powerScore
          }}
        </strong>
      </div>

      <div
          class="
            power-difference
          "
      >
        <span>
          Écart
        </span>

        <strong>
          {{
            powerDifferenceLabel
          }}
        </strong>
      </div>
    </div>

    <!-- =================================
         RÉCOMPENSES
    ================================== -->

    <div class="rewards">
      <div class="rewards-heading">
        <span>
          Victoire
        </span>

        <small>
          Récompenses potentielles
        </small>
      </div>

      <div class="reward-values">
        <div>
          <strong>
            +{{
              opponent
                  .potentialRewards
                  .victory
                  .xp
            }}
          </strong>

          <span>
            XP
          </span>
        </div>

        <div>
          <strong>
            +{{
              opponent
                  .potentialRewards
                  .victory
                  .money
            }}
          </strong>

          <span>
            $
          </span>
        </div>
      </div>
    </div>

    <!-- =================================
         ACTION
    ================================== -->

    <div
        class="
          opponent-action
        "
        :class="{
          'opponent-action-selected':
            selected,
        }"
    >
      <span
          v-if="
            selected
          "
      >
        ✓ Sélectionné
      </span>

      <span
          v-else
      >
        Choisir cet adversaire
      </span>
    </div>
  </button>
</template>

<style scoped>
/*
 * =====================================
 * CARD
 * =====================================
 */

.opponent-card {
  position: relative;

  display: flex;

  width: 100%;
  min-width: 0;

  flex-direction: column;

  overflow: hidden;

  padding:
      14px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.075
      );

  border-radius: 18px;

  background:
      radial-gradient(
          circle at 50% 31%,
          rgba(
              255,
              255,
              255,
              0.065
          ),
          transparent 42%
      ),
      rgba(
          255,
          255,
          255,
          0.025
      );

  color: inherit;

  cursor: pointer;

  font: inherit;

  text-align: left;

  transition:
      transform
      150ms
      ease,
      border-color
      150ms
      ease,
      background
      150ms
      ease,
      box-shadow
      150ms
      ease;
}

.opponent-card:hover {
  transform:
      translateY(-2px);

  border-color:
      rgba(
          255,
          255,
          255,
          0.15
      );

  background:
      radial-gradient(
          circle at 50% 31%,
          rgba(
              255,
              255,
              255,
              0.085
          ),
          transparent 42%
      ),
      rgba(
          255,
          255,
          255,
          0.04
      );
}

.opponent-card-selected {
  border-color:
      rgba(
          100,
          155,
          245,
          0.85
      );

  box-shadow:
      0
      0
      0
      1px
      rgba(
          100,
          155,
          245,
          0.2
      ),
      0
      12px
      35px
      rgba(
          0,
          0,
          0,
          0.25
      );
}

/*
 * =====================================
 * TOP
 * =====================================
 */

.opponent-top {
  position: relative;

  z-index: 3;

  display: flex;

  align-items: flex-start;

  justify-content:
      space-between;

  gap: 10px;
}

.level-block {
  display: flex;

  align-items: baseline;

  gap: 4px;
}

.level-block > span {
  font-size: 0.55rem;
  font-weight: 800;

  opacity: 0.42;
}

.level-block > strong {
  font-size: 0.9rem;
}

.level-block small {
  margin-left: 4px;

  font-size: 0.57rem;

  opacity: 0.38;
}

/*
 * =====================================
 * DIFFICULTÉ
 * =====================================
 */

.difficulty-badge {
  padding:
      5px
      8px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.08
      );

  border-radius: 999px;

  font-size: 0.58rem;
  font-weight: 850;

  letter-spacing: 0.04em;

  text-transform: uppercase;
}

.difficulty-easy {
  border-color:
      rgba(
          70,
          200,
          120,
          0.28
      );

  background:
      rgba(
          70,
          200,
          120,
          0.1
      );
}

.difficulty-balanced {
  border-color:
      rgba(
          225,
          175,
          65,
          0.3
      );

  background:
      rgba(
          225,
          175,
          65,
          0.1
      );
}

.difficulty-hard {
  border-color:
      rgba(
          225,
          75,
          85,
          0.32
      );

  background:
      rgba(
          225,
          75,
          85,
          0.1
      );
}

.difficulty-neutral {
  background:
      rgba(
          255,
          255,
          255,
          0.055
      );
}

/*
 * =====================================
 * IDENTITÉ
 * =====================================
 */

.opponent-identity {
  margin-top: 14px;

  text-align: center;
}

.opponent-label {
  margin:
      0
      0
      1px;

  font-size: 0.55rem;
  font-weight: 800;

  letter-spacing: 0.11em;

  text-transform: uppercase;

  opacity: 0.36;
}

.opponent-identity h3 {
  margin: 0;

  overflow: hidden;

  font-size: 1.15rem;

  text-overflow: ellipsis;

  white-space: nowrap;
}

/*
 * =====================================
 * VOITURE
 * =====================================
 */

.opponent-car {
  position: relative;

  width: 100%;

  margin:
      -5px
      auto
      -2px;

  padding:
      0
      4px;
}

.car-shadow {
  position: absolute;

  left: 20%;
  right: 20%;
  bottom: 17%;

  height: 10px;

  border-radius: 50%;

  background:
      rgba(
          0,
          0,
          0,
          0.34
      );

  filter:
      blur(7px);
}

/*
 * =====================================
 * STATS
 * =====================================
 */

.opponent-stats {
  display: grid;

  grid-template-columns:
      repeat(
          4,
          minmax(
              0,
              1fr
          )
      );

  gap: 4px;

  margin-top: 2px;
}

.opponent-stats > div {
  display: grid;

  justify-items: center;

  gap: 1px;

  padding:
      7px
      4px;

  border-radius: 8px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

.opponent-stats strong {
  font-size: 0.85rem;
}

.opponent-stats span {
  font-size: 0.49rem;
  font-weight: 750;

  opacity: 0.38;
}

/*
 * =====================================
 * PUISSANCE
 * =====================================
 */

.power-row {
  display: grid;

  grid-template-columns:
      1fr
      1fr;

  margin-top: 8px;

  padding:
      8px
      2px;

  border-top:
      1px solid
      rgba(
          255,
          255,
          255,
          0.055
      );

  border-bottom:
      1px solid
      rgba(
          255,
          255,
          255,
          0.055
      );
}

.power-row > div {
  display: flex;

  align-items: baseline;

  gap: 6px;
}

.power-row span {
  font-size: 0.58rem;

  opacity: 0.4;
}

.power-row strong {
  font-size: 0.74rem;
}

.power-difference {
  justify-content: flex-end;
}

/*
 * =====================================
 * REWARDS
 * =====================================
 */

.rewards {
  margin-top: 9px;
}

.rewards-heading {
  display: flex;

  align-items: baseline;

  justify-content:
      space-between;

  gap: 8px;

  margin-bottom: 6px;
}

.rewards-heading > span {
  font-size: 0.65rem;
  font-weight: 800;
}

.rewards-heading small {
  font-size: 0.52rem;

  opacity: 0.33;
}

.reward-values {
  display: grid;

  grid-template-columns:
      repeat(
          2,
          1fr
      );

  gap: 5px;
}

.reward-values > div {
  display: flex;

  align-items: baseline;
  justify-content: center;

  gap: 4px;

  padding:
      7px;

  border-radius: 8px;

  background:
      rgba(
          255,
          255,
          255,
          0.035
      );
}

.reward-values strong {
  font-size: 0.78rem;
}

.reward-values span {
  font-size: 0.53rem;

  opacity: 0.42;
}

/*
 * =====================================
 * ACTION
 * =====================================
 */

.opponent-action {
  display: flex;

  min-height: 35px;

  align-items: center;
  justify-content: center;

  margin-top: 10px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 9px;

  background:
      rgba(
          255,
          255,
          255,
          0.045
      );

  font-size: 0.65rem;
  font-weight: 800;

  transition:
      background
      150ms
      ease;
}

.opponent-card:hover
.opponent-action {
  background:
      rgba(
          255,
          255,
          255,
          0.075
      );
}

.opponent-action-selected {
  border-color:
      rgba(
          100,
          155,
          245,
          0.32
      );

  background:
      rgba(
          100,
          155,
          245,
          0.12
      );
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 600px
) {
  .opponent-card {
    padding: 12px;
  }

  .opponent-identity {
    margin-top: 10px;
  }

  .opponent-car {
    max-width: 390px;

    margin:
        -7px
        auto
        -5px;
  }

  .opponent-action {
    min-height: 39px;
  }
}
</style>