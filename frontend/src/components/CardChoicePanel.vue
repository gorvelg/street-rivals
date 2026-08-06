<script setup lang="ts">
import type { Card } from '../types/api'

defineProps<{
  level: number
  firstCard: Card
  secondCard: Card
  loading: boolean
}>()

defineEmits<{
  select: [cardId: number]
}>()

function formatEffect(card: Card): string {
  const config = card.effectConfig

  const stat =
      typeof config.stat === 'string'
          ? config.stat
          : null

  const event =
      typeof config.event === 'string'
          ? config.event
          : null

  const tiers = config.tiers

  if (
      typeof tiers !== 'object' ||
      tiers === null
  ) {
    return card.description
  }

  const tierValues = Object.entries(
      tiers as Record<
          string,
          {
            value?: number
            maxActivations?: number
          }
      >,
  )
      .map(([tier, value]) => {
        const bonus =
            typeof value.value === 'number'
                ? `+${value.value}`
                : ''

        return `T${tier} ${bonus}`
      })
      .join(' · ')

  const target = [
    stat,
    event !== null
        ? `sur ${event}`
        : null,
  ]
      .filter(Boolean)
      .join(' ')

  return `${target} — ${tierValues}`
}
</script>

<template>
  <section class="card-choice-panel">
    <div class="section-heading">
      <div>
        <p class="eyebrow">Niveau {{ level }}</p>

        <h2>Choisis une amélioration</h2>
      </div>
    </div>

    <p class="muted">
      Une seule carte peut être sélectionnée.
      Repiocher une carte possédée améliore son palier.
    </p>

    <div class="choice-cards-grid">
      <article
          v-for="card in [firstCard, secondCard]"
          :key="card.id"
          class="choice-card"
      >
        <header>
          <span class="card-rarity">
            {{ card.rarity }}
          </span>

          <span class="card-type">
            {{ card.type }}
          </span>
        </header>

        <h3>{{ card.name }}</h3>

        <p>{{ card.description }}</p>

        <small>
          {{ formatEffect(card) }}
        </small>

        <button
            type="button"
            class="button button-primary button-full"
            :disabled="loading"
            @click="$emit('select', card.id)"
        >
          Choisir {{ card.name }}
        </button>
      </article>
    </div>
  </section>
</template>