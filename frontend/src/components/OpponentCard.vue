<script setup lang="ts">
import type { MatchmakingOpponent } from '../types/api'

defineProps<{
  opponent: MatchmakingOpponent
  selected: boolean
}>()

defineEmits<{
  select: [opponent: MatchmakingOpponent]
}>()

function difficultyLabel(
    difficulty: MatchmakingOpponent['difficulty'],
): string {
  switch (difficulty) {
    case 'EASY':
      return 'Facile'

    case 'HARD':
      return 'Difficile'

    default:
      return 'Équilibré'
  }
}
</script>

<template>
  <article
      class="opponent-card"
      :class="{
      'opponent-card-selected': selected,
    }"
  >
    <div class="opponent-header">
      <div class="opponent-identity">
        <div
            class="opponent-color"
            :style="{
            backgroundColor: opponent.color,
          }"
        />

        <div>
          <strong>{{ opponent.pilotName }}</strong>

          <p>
            Niveau {{ opponent.level }}
            · Puissance {{ opponent.powerScore }}
          </p>
        </div>
      </div>

      <span
          class="difficulty-badge"
          :class="`difficulty-${opponent.difficulty.toLowerCase()}`"
      >
        {{ difficultyLabel(opponent.difficulty) }}
      </span>
    </div>

    <div class="stats-grid stats-grid-small">
      <div>
        <span>Vitesse</span>
        <strong>{{ opponent.effectiveStats.speed }}</strong>
      </div>

      <div>
        <span>Accélération</span>
        <strong>
          {{ opponent.effectiveStats.acceleration }}
        </strong>
      </div>

      <div>
        <span>Grip</span>
        <strong>{{ opponent.effectiveStats.grip }}</strong>
      </div>

      <div>
        <span>Solidité</span>
        <strong>{{ opponent.effectiveStats.solidity }}</strong>
      </div>
    </div>

    <div class="potential-reward">
      <span>Victoire possible</span>

      <strong>
        +{{ opponent.potentialRewards.victory.xp }} XP ·
        +{{ opponent.potentialRewards.victory.money }} $
      </strong>
    </div>

    <button
        type="button"
        class="button button-primary button-full"
        @click="$emit('select', opponent)"
    >
      {{
        selected
            ? 'Adversaire sélectionné'
            : 'Choisir cet adversaire'
      }}
    </button>
  </article>
</template>