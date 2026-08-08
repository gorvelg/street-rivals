<script setup lang="ts">
import type {
  Card,
  CardKind,
  EquipmentSlot,
} from '../types/api'

withDefaults(
    defineProps<{
      level: number
      pilotName: string
      firstCard: Card
      secondCard: Card
      loading: boolean
      successMessage?: string
    }>(),
    {
      successMessage: '',
    },
)

defineEmits<{
  select: [cardId: number]
}>()

/*
 * =====================================
 * TYPE DE CARTE
 * =====================================
 */

function getKind(
    card: Card,
): CardKind {
  return card.kind
      ?? 'ability'
}

function kindLabel(
    card: Card,
): string {
  switch (
      getKind(card)
      ) {
    case 'equipment':
      return 'Équipement'

    case 'stat_boost':
      return 'Bonus permanent'

    case 'ability':
    default:
      return 'Capacité'
  }
}

/*
 * =====================================
 * RARETÉ
 * =====================================
 */

function rarityTone(
    card: Card,
): string {
  const rarity =
      card.rarity
          .normalize('NFD')
          .replace(
              /[\u0300-\u036f]/g,
              '',
          )
          .trim()
          .toLowerCase()

  switch (
      rarity
      ) {
    case 'commune':
    case 'common':
      return 'common'

    case 'rare':
      return 'rare'

    case 'epique':
    case 'epic':
      return 'epic'

    case 'legendaire':
    case 'legendary':
      return 'legendary'

    case 'mythique':
    case 'mythic':
      return 'mythic'

    default:
      return 'neutral'
  }
}

/*
 * =====================================
 * ÉQUIPEMENTS
 * =====================================
 */

function equipmentSlotLabel(
    slot:
        EquipmentSlot
        | null
        | undefined,
): string | null {
  switch (
      slot
      ) {
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

/*
 * =====================================
 * EFFETS
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

function formatStatTiers(
    card: Card,
): string {
  const tiers =
      card.effectConfig
          .tiers

  if (
      typeof tiers
      !== 'object'
      || tiers === null
  ) {
    return card.description
  }

  const tierConfigurations =
      tiers as Record<
          string,
          Record<
              string,
              unknown
          >
      >

  return Object.entries(
      tierConfigurations,
  )
      .map(
          (
              [
                tier,
                configuration,
              ],
          ) => {
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
                    .join(', ')

            return (
                `T${tier} : `
                + effects
            )
          },
      )
      .join(' · ')
}

function formatLegacyAbilityEffect(
    card: Card,
): string {
  const config =
      card.effectConfig

  const stat =
      typeof config.stat
      === 'string'
          ? config.stat
          : null

  const event =
      typeof config.event
      === 'string'
          ? config.event
          : null

  const tiers =
      config.tiers

  if (
      typeof tiers
      !== 'object'
      || tiers === null
  ) {
    return card.description
  }

  const tierValues =
      Object.entries(
          tiers as Record<
              string,
              {
                value?: number
                maxActivations?: number
              }
          >,
      )
          .map(
              (
                  [
                    tier,
                    value,
                  ],
              ) => {
                const bonus =
                    typeof value.value
                    === 'number'
                        ? signedValue(
                            value.value,
                        )
                        : ''

                return (
                    `T${tier} `
                    + bonus
                )
              },
          )
          .join(' · ')

  const target =
      [
        stat !== null
            ? statLabel(
                stat,
            )
            : null,

        event !== null
            ? `sur ${event}`
            : null,
      ]
          .filter(
              Boolean,
          )
          .join(' ')

  if (
      target === ''
  ) {
    return tierValues
  }

  return (
      `${target} — `
      + tierValues
  )
}

function formatEffect(
    card: Card,
): string {
  if (
      getKind(card)
      === 'equipment'
      || getKind(card)
      === 'stat_boost'
  ) {
    return formatStatTiers(
        card,
    )
  }

  return formatLegacyAbilityEffect(
      card,
  )
}
</script>

<template>
  <section class="level-up-screen">
    <!-- =====================================
         FOND
    ====================================== -->

    <div
        class="
          level-up-background
        "
        aria-hidden="true"
    >
      <span
          v-for="
            index in 10
          "
          :key="
            index
          "
          class="
            level-ray
          "
      />
    </div>

    <!-- =====================================
         HEADER
    ====================================== -->

    <header class="level-up-header">
      <p class="level-up-eyebrow">
        Niveau supérieur
      </p>

      <div class="level-number">
        <span>
          NIV.
        </span>

        <strong>
          {{ level }}
        </strong>
      </div>

      <h2>
        Choisis ton évolution
      </h2>

      <p class="level-up-subtitle">
        Une nouvelle amélioration est disponible
        pour

        <strong>
          {{ pilotName }}
        </strong>
        .
      </p>
    </header>

    <!-- =====================================
         MESSAGE APRÈS PREMIER CHOIX
    ====================================== -->

    <div
        v-if="
          successMessage !== ''
        "
        class="
          choice-success
        "
    >
      <span class="choice-success-icon">
        ✓
      </span>

      <span>
        {{ successMessage }}
      </span>

      <strong>
        Un nouveau choix est disponible.
      </strong>
    </div>

    <!-- =====================================
         EXPLICATION
    ====================================== -->

    <div class="choice-help">
      <span>
        Choisis une seule carte
      </span>

      <span class="choice-help-separator">
        •
      </span>

      <span>
        Une carte déjà possédée améliore son palier
      </span>
    </div>

    <!-- =====================================
         CARTES
    ====================================== -->

    <div class="choice-cards-grid">
      <article
          v-for="
            (
              card,
              index
            ) in [
              firstCard,
              secondCard,
            ]
          "
          :key="
            card.id
          "
          class="
            choice-card
          "
          :class="[
            `rarity-${rarityTone(card)}`,
            {
              'choice-card-loading':
                loading,
            },
          ]"
      >
        <!-- NUMÉRO -->

        <div class="choice-number">
          0{{ index + 1 }}
        </div>

        <!-- HEADER -->

        <header class="choice-card-header">
          <span
              class="
                card-rarity
              "
          >
            {{ card.rarity }}
          </span>

          <span class="card-kind">
            {{ kindLabel(card) }}
          </span>
        </header>

        <!-- SLOT -->

        <p
            v-if="
              getKind(card)
              === 'equipment'

              && equipmentSlotLabel(
                  card.equipmentSlot,
              ) !== null
            "
            class="
              equipment-slot
            "
        >
          {{
            equipmentSlotLabel(
                card.equipmentSlot,
            )
          }}
        </p>

        <!-- CONTENU -->

        <div class="choice-card-content">
          <h3>
            {{ card.name }}
          </h3>

          <p class="card-description">
            {{ card.description }}
          </p>

          <div class="card-effect">
            <span>
              Effet
            </span>

            <strong>
              {{ formatEffect(card) }}
            </strong>
          </div>
        </div>

        <!-- PALIER -->

        <div
            v-if="
              card.maxTier
              !== undefined
            "
            class="
              card-tier
            "
        >
          <span>
            Progression
          </span>

          <strong>
            jusqu'au palier
            {{ card.maxTier }}
          </strong>
        </div>

        <!-- ACTION -->

        <button
            type="button"
            class="
              choose-card-button
            "
            :disabled="
              loading
            "
            @click="
              $emit(
                  'select',
                  card.id,
              )
            "
        >
          <template
              v-if="
                loading
              "
          >
            <span class="choice-spinner">
              ↻
            </span>

            Sélection...
          </template>

          <template v-else>
            <span>
              Choisir
            </span>

            <strong>
              {{ card.name }}
            </strong>

            <span class="choice-arrow">
              →
            </span>
          </template>
        </button>
      </article>
    </div>

    <!-- =====================================
         FOOTER
    ====================================== -->

    <p class="choice-warning">
      Ce choix est définitif.
    </p>
  </section>
</template>

<style scoped>
/*
 * =====================================
 * ÉCRAN
 * =====================================
 */

.level-up-screen {
  position: relative;

  isolation: isolate;

  overflow: hidden;

  margin-top: 18px;

  padding:
      34px
      26px
      24px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.12
      );

  border-radius: 24px;

  background:
      radial-gradient(
          circle at 50% 2%,
          rgba(
              120,
              155,
              245,
              0.15
          ),
          transparent 38%
      ),
      radial-gradient(
          circle at 50% 100%,
          rgba(
              255,
              255,
              255,
              0.045
          ),
          transparent 45%
      ),
      rgba(
          19,
          21,
          25,
          0.96
      );

  box-shadow:
      0
      30px
      80px
      rgba(
          0,
          0,
          0,
          0.3
      );

  animation:
      level-screen-enter
      420ms
      cubic-bezier(
          0.2,
          0.8,
          0.3,
          1
      );
}

/*
 * =====================================
 * RAYONS
 * =====================================
 */

.level-up-background {
  position: absolute;

  inset: 0;

  z-index: -1;

  overflow: hidden;

  pointer-events: none;

  opacity: 0.45;
}

.level-ray {
  position: absolute;

  top: -40%;
  left: 50%;

  width: 1px;
  height: 120%;

  transform-origin:
      50%
      100%;

  background:
      linear-gradient(
          transparent,
          rgba(
              255,
              255,
              255,
              0.12
          ),
          transparent
      );
}

.level-ray:nth-child(1) {
  transform:
      rotate(-72deg);
}

.level-ray:nth-child(2) {
  transform:
      rotate(-55deg);
}

.level-ray:nth-child(3) {
  transform:
      rotate(-38deg);
}

.level-ray:nth-child(4) {
  transform:
      rotate(-20deg);
}

.level-ray:nth-child(5) {
  transform:
      rotate(-7deg);
}

.level-ray:nth-child(6) {
  transform:
      rotate(7deg);
}

.level-ray:nth-child(7) {
  transform:
      rotate(20deg);
}

.level-ray:nth-child(8) {
  transform:
      rotate(38deg);
}

.level-ray:nth-child(9) {
  transform:
      rotate(55deg);
}

.level-ray:nth-child(10) {
  transform:
      rotate(72deg);
}

/*
 * =====================================
 * HEADER
 * =====================================
 */

.level-up-header {
  position: relative;

  z-index: 2;

  max-width: 650px;

  margin:
      0
      auto
      22px;

  text-align: center;
}

.level-up-eyebrow {
  margin:
      0
      0
      12px;

  font-size: 0.66rem;
  font-weight: 950;

  letter-spacing: 0.24em;

  text-transform: uppercase;

  opacity: 0.58;
}

.level-number {
  position: relative;

  display: flex;

  width: 122px;
  height: 122px;

  flex-direction: column;

  align-items: center;
  justify-content: center;

  margin:
      0
      auto
      16px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.17
      );

  border-radius: 50%;

  background:
      radial-gradient(
          circle,
          rgba(
              105,
              150,
              245,
              0.25
          ),
          rgba(
              105,
              150,
              245,
              0.05
          )
      );

  box-shadow:
      0
      0
      0
      8px
      rgba(
          105,
          150,
          245,
          0.025
      ),
      0
      0
      55px
      rgba(
          105,
          150,
          245,
          0.12
      );

  animation:
      level-number-enter
      600ms
      120ms
      cubic-bezier(
          0.2,
          0.9,
          0.3,
          1
      )
      both;
}

.level-number > span {
  margin-bottom: -3px;

  font-size: 0.55rem;
  font-weight: 850;

  letter-spacing: 0.12em;

  opacity: 0.45;
}

.level-number strong {
  font-size: 3.25rem;
  font-weight: 950;

  line-height: 1;
}

.level-up-header h2 {
  margin:
      0
      0
      6px;

  font-size:
      clamp(
          1.7rem,
          5vw,
          2.7rem
      );

  letter-spacing: -0.04em;
}

.level-up-subtitle {
  margin: 0;

  font-size: 0.76rem;

  opacity: 0.52;
}

/*
 * =====================================
 * SUCCÈS
 * =====================================
 */

.choice-success {
  display: flex;

  max-width: 720px;

  align-items: center;
  justify-content: center;

  gap: 7px;

  margin:
      0
      auto
      14px;

  padding:
      8px
      12px;

  border:
      1px solid
      rgba(
          80,
          210,
          130,
          0.2
      );

  border-radius: 10px;

  background:
      rgba(
          80,
          210,
          130,
          0.065
      );

  font-size: 0.68rem;
}

.choice-success-icon {
  display: inline-flex;

  width: 19px;
  height: 19px;

  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background:
      rgba(
          80,
          210,
          130,
          0.15
      );

  font-weight: 900;
}

/*
 * =====================================
 * AIDE
 * =====================================
 */

.choice-help {
  display: flex;

  flex-wrap: wrap;

  align-items: center;
  justify-content: center;

  gap: 7px;

  margin-bottom: 14px;

  font-size: 0.62rem;

  opacity: 0.4;
}

/*
 * =====================================
 * GRID
 * =====================================
 */

.choice-cards-grid {
  position: relative;

  z-index: 2;

  display: grid;

  grid-template-columns:
      repeat(
          2,
          minmax(
              0,
              1fr
          )
      );

  gap: 14px;

  max-width: 880px;

  margin:
      0
      auto;
}

/*
 * =====================================
 * CARTE
 * =====================================
 */

.choice-card {
  position: relative;

  display: flex;

  min-width: 0;
  min-height: 370px;

  flex-direction: column;

  overflow: hidden;

  padding: 17px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.09
      );

  border-radius: 18px;

  background:
      linear-gradient(
          145deg,
          rgba(
              255,
              255,
              255,
              0.06
          ),
          rgba(
              255,
              255,
              255,
              0.018
          )
      );

  transition:
      transform
      160ms
      ease,
      border-color
      160ms
      ease,
      box-shadow
      160ms
      ease;

  animation:
      choice-card-enter
      420ms
      250ms
      ease
      both;
}

.choice-card:nth-child(2) {
  animation-delay:
      330ms;
}

.choice-card:hover {
  transform:
      translateY(-4px);

  border-color:
      rgba(
          255,
          255,
          255,
          0.2
      );

  box-shadow:
      0
      20px
      45px
      rgba(
          0,
          0,
          0,
          0.25
      );
}

.choice-card-loading {
  pointer-events: none;

  opacity: 0.72;
}

/*
 * =====================================
 * RARETÉS
 * =====================================
 */

.rarity-common {
  border-top-color:
      rgba(
          210,
          215,
          225,
          0.45
      );
}

.rarity-rare {
  border-top-color:
      rgba(
          80,
          145,
          245,
          0.75
      );
}

.rarity-epic {
  border-top-color:
      rgba(
          170,
          90,
          235,
          0.8
      );
}

.rarity-legendary {
  border-top-color:
      rgba(
          235,
          170,
          65,
          0.85
      );
}

.rarity-mythic {
  border-top-color:
      rgba(
          235,
          80,
          125,
          0.85
      );
}

/*
 * =====================================
 * NUMÉRO
 * =====================================
 */

.choice-number {
  position: absolute;

  right: 13px;
  bottom: 10px;

  font-size: 4rem;
  font-weight: 950;

  line-height: 1;

  opacity: 0.025;

  pointer-events: none;
}

/*
 * =====================================
 * HEADER CARTE
 * =====================================
 */

.choice-card-header {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 8px;

  margin-bottom: 16px;
}

.card-rarity,
.card-kind,
.equipment-slot {
  padding:
      5px
      7px;

  border-radius: 999px;

  background:
      rgba(
          255,
          255,
          255,
          0.055
      );

  font-size: 0.57rem;
  font-weight: 800;

  text-transform: uppercase;
}

.card-rarity {
  letter-spacing: 0.06em;
}

.card-kind {
  opacity: 0.48;
}

.equipment-slot {
  width: fit-content;

  margin:
      -7px
      0
      11px;

  opacity: 0.58;
}

/*
 * =====================================
 * CONTENU
 * =====================================
 */

.choice-card-content {
  flex: 1;
}

.choice-card h3 {
  margin:
      0
      0
      9px;

  font-size:
      clamp(
          1.15rem,
          3vw,
          1.45rem
      );
}

.card-description {
  margin:
      0
      0
      15px;

  font-size: 0.72rem;
  line-height: 1.55;

  opacity: 0.55;
}

.card-effect {
  display: grid;

  gap: 5px;

  padding: 11px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.055
      );

  border-radius: 10px;

  background:
      rgba(
          255,
          255,
          255,
          0.027
      );
}

.card-effect span {
  font-size: 0.54rem;
  font-weight: 850;

  letter-spacing: 0.1em;

  text-transform: uppercase;

  opacity: 0.38;
}

.card-effect strong {
  font-size: 0.7rem;
  line-height: 1.45;
}

/*
 * =====================================
 * TIER
 * =====================================
 */

.card-tier {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 10px;

  margin-top: 12px;

  padding-top: 10px;

  border-top:
      1px solid
      rgba(
          255,
          255,
          255,
          0.055
      );

  font-size: 0.61rem;
}

.card-tier span {
  opacity: 0.38;
}

.card-tier strong {
  font-size: 0.62rem;
}

/*
 * =====================================
 * BOUTON
 * =====================================
 */

.choose-card-button {
  position: relative;

  display: flex;

  min-height: 50px;

  align-items: center;
  justify-content: center;

  gap: 6px;

  width: 100%;

  margin-top: 14px;

  padding:
      8px
      12px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.14
      );

  border-radius: 11px;

  background:
      rgba(
          255,
          255,
          255,
          0.92
      );

  color:
      rgba(
          15,
          17,
          20,
          1
      );

  cursor: pointer;

  font: inherit;

  font-size: 0.68rem;

  transition:
      transform
      130ms
      ease,
      background
      130ms
      ease;
}

.choose-card-button strong {
  max-width: 65%;

  overflow: hidden;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.choose-card-button:hover:not(
    :disabled
) {
  transform:
      translateY(-1px);

  background: #ffffff;
}

.choose-card-button:disabled {
  cursor: default;

  opacity: 0.55;
}

.choice-arrow {
  margin-left: 3px;

  font-size: 0.9rem;

  transition:
      transform
      130ms
      ease;
}

.choose-card-button:hover
.choice-arrow {
  transform:
      translateX(3px);
}

.choice-spinner {
  display: inline-block;

  animation:
      choice-spinner
      700ms
      linear
      infinite;
}

/*
 * =====================================
 * FOOTER
 * =====================================
 */

.choice-warning {
  margin:
      15px
      0
      0;

  text-align: center;

  font-size: 0.59rem;

  opacity: 0.3;
}

/*
 * =====================================
 * ANIMATIONS
 * =====================================
 */

@keyframes level-screen-enter {
  from {
    opacity: 0;

    transform:
        translateY(12px);
  }

  to {
    opacity: 1;

    transform:
        translateY(0);
  }
}

@keyframes level-number-enter {
  from {
    opacity: 0;

    transform:
        scale(0.4)
        rotate(-8deg);
  }

  65% {
    opacity: 1;

    transform:
        scale(1.08)
        rotate(1deg);
  }

  to {
    opacity: 1;

    transform:
        scale(1)
        rotate(0);
  }
}

@keyframes choice-card-enter {
  from {
    opacity: 0;

    transform:
        translateY(14px)
        scale(0.985);
  }

  to {
    opacity: 1;

    transform:
        translateY(0)
        scale(1);
  }
}

@keyframes choice-spinner {
  to {
    transform:
        rotate(360deg);
  }
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 650px
) {
  .level-up-screen {
    padding:
        24px
        12px
        18px;

    border-radius: 18px;
  }

  .level-number {
    width: 96px;
    height: 96px;
  }

  .level-number strong {
    font-size: 2.55rem;
  }

  .choice-cards-grid {
    grid-template-columns:
        1fr;

    gap: 10px;
  }

  .choice-card {
    min-height: 0;
  }

  .choice-success {
    align-items: flex-start;

    flex-direction: column;
  }
}
</style>