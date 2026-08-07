<script setup lang="ts">
import CarVisual
  from './CarVisual.vue'

import type {
  MatchmakingOpponent,
} from '../types/api'

const props = defineProps<{
  opponent: MatchmakingOpponent
  selected: boolean
}>()

const emit = defineEmits<{
  select: [
    opponent:
        MatchmakingOpponent,
  ]
}>()

function selectOpponent(): void {
  emit(
      'select',
      props.opponent,
  )
}

function difficultyLabel(
    difficulty:
    MatchmakingOpponent[
        'difficulty'
        ],
): string {
  switch (difficulty) {
    case 'EASY':
      return 'Facile'

    case 'BALANCED':
      return 'Équilibré'

    case 'HARD':
      return 'Difficile'
  }
}

function signedPercent(
    value: number,
): string {
  if (value > 0) {
    return `+${value} %`
  }

  return `${value} %`
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
      @click="
        selectOpponent
      "
  >
    <!-- VOITURE -->

    <div class="opponent-car-visual">
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

    <!-- IDENTITÉ -->

    <header class="opponent-header">
      <div>
        <strong>
          {{ opponent.pilotName }}
        </strong>

        <small>
          Niveau
          {{ opponent.level }}
        </small>
      </div>

      <span
          class="difficulty-badge"
          :class="
            `difficulty-${opponent.difficulty.toLowerCase()}`
          "
      >
        {{
          difficultyLabel(
              opponent.difficulty,
          )
        }}
      </span>
    </header>

    <!-- PUISSANCE -->

    <div class="opponent-power">
      <span>
        Puissance
      </span>

      <strong>
        {{ opponent.powerScore }}
      </strong>

      <small>
        {{
          signedPercent(
              opponent
                  .powerDifferencePercent,
          )
        }}
      </small>
    </div>

    <!-- STATS -->

    <div class="opponent-stats">
      <span>
        VIT
        <strong>
          {{
            opponent
                .effectiveStats
                .speed
          }}
        </strong>
      </span>

      <span>
        ACC
        <strong>
          {{
            opponent
                .effectiveStats
                .acceleration
          }}
        </strong>
      </span>

      <span>
        GRIP
        <strong>
          {{
            opponent
                .effectiveStats
                .grip
          }}
        </strong>
      </span>

      <span>
        SOL
        <strong>
          {{
            opponent
                .effectiveStats
                .solidity
          }}
        </strong>
      </span>
    </div>

    <!-- RÉCOMPENSE -->

    <div class="opponent-reward">
      <span>
        Victoire
      </span>

      <strong>
        +{{
          opponent
              .potentialRewards
              .victory
              .xp
        }} XP
      </strong>

      <strong>
        +{{
          opponent
              .potentialRewards
              .victory
              .money
        }} $
      </strong>
    </div>

    <span
        v-if="selected"
        class="selected-label"
    >
      Adversaire sélectionné
    </span>
  </button>
</template>

<style scoped>
.opponent-card {
  display: grid;

  width: 100%;

  gap: 12px;

  padding: 16px;

  border:
      1px solid
      rgba(
          127,
          127,
          127,
          0.24
      );

  border-radius: 14px;

  background:
      rgba(
          127,
          127,
          127,
          0.04
      );

  color: inherit;

  text-align: left;

  cursor: pointer;

  transition:
      border-color
      150ms ease,
      background
      150ms ease,
      transform
      150ms ease;
}

.opponent-card:hover {
  transform:
      translateY(-2px);

  background:
      rgba(
          127,
          127,
          127,
          0.08
      );
}

.opponent-card-selected {
  border-color:
      rgba(
          70,
          130,
          230,
          0.85
      );

  background:
      rgba(
          70,
          130,
          230,
          0.1
      );
}

.opponent-car-visual {
  width: 100%;

  margin:
      -4px
      auto
      -5px;
}

.opponent-header {
  display: flex;

  align-items: center;

  justify-content:
      space-between;

  gap: 12px;
}

.opponent-header > div {
  display: grid;

  gap: 2px;
}

.opponent-header strong {
  font-size: 1rem;
}

.opponent-header small {
  opacity: 0.65;
}

.difficulty-badge {
  padding:
      5px
      9px;

  border-radius: 999px;

  font-size: 0.7rem;
  font-weight: 800;
}

.difficulty-easy {
  background:
      rgba(
          50,
          170,
          100,
          0.16
      );
}

.difficulty-balanced {
  background:
      rgba(
          210,
          150,
          30,
          0.16
      );
}

.difficulty-hard {
  background:
      rgba(
          210,
          60,
          60,
          0.16
      );
}

.opponent-power {
  display: flex;

  align-items: baseline;

  gap: 8px;
}

.opponent-power > span {
  opacity: 0.65;

  font-size: 0.76rem;
}

.opponent-power strong {
  font-size: 1.1rem;
}

.opponent-power small {
  margin-left: auto;

  opacity: 0.65;
}

.opponent-stats {
  display: grid;

  grid-template-columns:
      repeat(
          4,
          minmax(0, 1fr)
      );

  gap: 6px;
}

.opponent-stats span {
  display: grid;

  gap: 2px;

  padding: 7px;

  border-radius: 8px;

  background:
      rgba(
          127,
          127,
          127,
          0.07
      );

  font-size: 0.62rem;

  opacity: 0.7;
}

.opponent-stats strong {
  font-size: 0.85rem;

  opacity: 1;
}

.opponent-reward {
  display: flex;

  align-items: center;

  gap: 9px;

  padding-top: 10px;

  border-top:
      1px solid
      rgba(
          127,
          127,
          127,
          0.15
      );

  font-size: 0.78rem;
}

.opponent-reward span {
  margin-right: auto;

  opacity: 0.65;
}

.selected-label {
  display: block;

  text-align: center;

  font-size: 0.72rem;
  font-weight: 800;
}

@media (
max-width: 500px
) {
  .opponent-stats {
    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );
  }
}
</style>