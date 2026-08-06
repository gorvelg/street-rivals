<script setup lang="ts">
import type { DuelEvent } from '../types/api'

defineProps<{
  event: DuelEvent
  attackerName: string
  defenderName: string
}>()

function modifierLabel(value?: number): string {
  if (value === undefined) {
    return '0'
  }

  return value > 0
      ? `+${value}`
      : String(value)
}
</script>

<template>
  <article class="replay-event-card">
    <header class="replay-event-header">
      <div>
        <span class="event-number">
          Événement {{ event.index }}
        </span>

        <h3>{{ event.label }}</h3>
      </div>

      <strong
          class="gap-change"
          :class="{
          positive: event.gapChange > 0,
          negative: event.gapChange < 0,
        }"
      >
        {{ modifierLabel(event.gapChange) }}
      </strong>
    </header>

    <div
        v-if="event.attacker && event.defender"
        class="event-score-grid"
    >
      <div>
        <span>{{ attackerName }}</span>

        <strong>{{ event.attacker.score }}</strong>

        <small>
          Base {{ event.attacker.permanentScore }}
          · carte {{ modifierLabel(event.attacker.activeCardBonus) }}
          · hasard {{ modifierLabel(event.attacker.randomModifier) }}
        </small>

        <ul
            v-if="
            event.attacker.triggeredCards &&
            event.attacker.triggeredCards.length > 0
          "
            class="triggered-cards"
        >
          <li
              v-for="card in event.attacker.triggeredCards"
              :key="card.carCardId"
          >
            {{ card.name }}
            palier {{ card.tier }}
            : +{{ card.value }} {{ card.stat }}
          </li>
        </ul>
      </div>

      <div>
        <span>{{ defenderName }}</span>

        <strong>{{ event.defender.score }}</strong>

        <small>
          Base {{ event.defender.permanentScore }}
          · carte {{ modifierLabel(event.defender.activeCardBonus) }}
          · hasard {{ modifierLabel(event.defender.randomModifier) }}
        </small>

        <ul
            v-if="
            event.defender.triggeredCards &&
            event.defender.triggeredCards.length > 0
          "
            class="triggered-cards"
        >
          <li
              v-for="card in event.defender.triggeredCards"
              :key="card.carCardId"
          >
            {{ card.name }}
            palier {{ card.tier }}
            : +{{ card.value }} {{ card.stat }}
          </li>
        </ul>
      </div>
    </div>

    <p
        v-else
        class="photo-finish-message"
    >
      La course se joue au photo-finish.
    </p>

    <footer class="replay-event-footer">
      <span>Écart après l’événement</span>

      <strong>
        {{ modifierLabel(event.gapAfter) }}
      </strong>
    </footer>
  </article>
</template>