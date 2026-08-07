<script setup lang="ts">
import type {
  Card,
  CardKind,
  EquipmentSlot,
} from '../types/api'

defineProps<{
  level: number
  firstCard: Card
  secondCard: Card
  loading: boolean
}>()

defineEmits<{
  select: [cardId: number]
}>()

function getKind(
    card: Card,
): CardKind {
  return card.kind ?? 'ability'
}

function kindLabel(
    card: Card,
): string {
  switch (getKind(card)) {
    case 'equipment':
      return 'Équipement'

    case 'stat_boost':
      return 'Bonus permanent'

    case 'ability':
    default:
      return 'Capacité'
  }
}

function equipmentSlotLabel(
    slot: EquipmentSlot | null | undefined,
): string | null {
  switch (slot) {
    case 'engine':
      return 'Moteur'

    case 'wheels':
      return 'Roues'

    case 'brakes':
      return 'Freins'

    case 'gearbox':
      return 'Boîte de vitesses'

    case 'chassis':
      return 'Châssis'

    case 'aero':
      return 'Aérodynamique'

    default:
      return null
  }
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

function formatStatTiers(
    card: Card,
): string {
  const tiers = card.effectConfig.tiers

  if (
      typeof tiers !== 'object'
      || tiers === null
  ) {
    return card.description
  }

  const tierConfigurations =
      tiers as Record<
          string,
          Record<string, unknown>
      >

  return Object.entries(
      tierConfigurations,
  )
      .map(([tier, configuration]) => {
        const effects = Object.entries(
            configuration,
        )
            .filter(
                (
                    entry,
                ): entry is [string, number] =>
                    typeof entry[1] === 'number',
            )
            .map(([stat, value]) => {
              return `${signedValue(value)} ${statLabel(stat)}`
            })
            .join(', ')

        return `T${tier} : ${effects}`
      })
      .join(' · ')
}

function formatLegacyAbilityEffect(
    card: Card,
): string {
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
      typeof tiers !== 'object'
      || tiers === null
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
                ? signedValue(value.value)
                : ''

        return `T${tier} ${bonus}`
      })
      .join(' · ')

  const target = [
    stat !== null
        ? statLabel(stat)
        : null,

    event !== null
        ? `sur ${event}`
        : null,
  ]
      .filter(Boolean)
      .join(' ')

  if (target === '') {
    return tierValues
  }

  return `${target} — ${tierValues}`
}

function formatEffect(
    card: Card,
): string {
  if (
      getKind(card) === 'equipment'
      || getKind(card) === 'stat_boost'
  ) {
    return formatStatTiers(card)
  }

  return formatLegacyAbilityEffect(card)
}
</script>

<template>
  <section class="card-choice-panel">
    <div class="section-heading">
      <div>
        <p class="eyebrow">
          Niveau {{ level }}
        </p>

        <h2>
          Choisis une amélioration
        </h2>
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
            {{ kindLabel(card) }}
          </span>
        </header>

        <p
            v-if="
              getKind(card) === 'equipment'
              && equipmentSlotLabel(
                card.equipmentSlot,
              ) !== null
            "
            class="equipment-slot"
        >
          {{
            equipmentSlotLabel(
                card.equipmentSlot,
            )
          }}
        </p>

        <h3>
          {{ card.name }}
        </h3>

        <p>
          {{ card.description }}
        </p>

        <small>
          {{ formatEffect(card) }}
        </small>

        <p
            v-if="card.maxTier !== undefined"
            class="muted"
        >
          Palier maximum :
          {{ card.maxTier }}
        </p>

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