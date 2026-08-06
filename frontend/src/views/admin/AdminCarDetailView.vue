<script setup lang="ts">
import {
  computed,
  onUnmounted,
  watch,
} from 'vue'
import {
  RouterLink,
  useRoute,
} from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminCarDetailStore }
  from '../../stores/adminCarDetail'

const route = useRoute()
const detailStore = useAdminCarDetailStore()

const carId = computed<number | null>(() => {
  const parsedId = Number.parseInt(
      String(route.params.id),
      10,
  )

  return Number.isInteger(parsedId)
  && parsedId > 0
      ? parsedId
      : null
})

function formatDate(
    value: string | null,
): string {
  if (value === null) {
    return 'Non renseigné'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

function formatDuration(
    seconds: number,
): string {
  const normalizedSeconds = Math.max(
      0,
      Math.ceil(seconds),
  )

  const minutes = Math.floor(
      normalizedSeconds / 60,
  )

  const remainingSeconds =
      normalizedSeconds % 60

  if (minutes === 0) {
    return `${remainingSeconds} s`
  }

  return `${minutes} min ${remainingSeconds} s`
}

function eventLabel(
    type: string,
): string {
  const labels: Record<string, string> = {
    car_created: 'Voiture créée',
    level_up: 'Montée de niveau',
    card_selected: 'Carte obtenue',
    card_upgraded: 'Carte améliorée',
    duel_completed: 'Duel terminé',
    admin_cooldown_reset:
        'Cooldown réinitialisé par un administrateur',
  }

  return labels[type] ?? type
}

function formatEffectConfig(
    value: Record<string, unknown>,
): string {
  try {
    return JSON.stringify(
        value,
        null,
        2,
    )
  } catch {
    return 'Configuration indisponible'
  }
}

async function confirmCooldownReset():
    Promise<void> {
  const detail = detailStore.detail

  if (detail === null) {
    return
  }

  const confirmed = window.confirm(
      `Réinitialiser tous les cooldowns actifs de ${detail.car.pilotName} ?`,
  )

  if (!confirmed) {
    return
  }

  try {
    await detailStore.resetCooldown()
  } catch {
    /*
     * Le message d’erreur est déjà géré par le store.
     */
  }
}

watch(
    carId,
    async (
        newCarId,
    ): Promise<void> => {
      detailStore.reset()

      if (newCarId === null) {
        return
      }

      await detailStore.loadCar(newCarId)
    },
    {
      immediate: true,
    },
)

onUnmounted((): void => {
  detailStore.reset()
})
</script>

<template>
  <main class="admin-car-detail">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Fiche voiture</h1>

        <p class="admin-description">
          Progression, cartes et activité détaillée.
        </p>
      </div>

      <RouterLink
          :to="{ name: 'admin-cars' }"
          class="back-link"
      >
        Retour aux voitures
      </RouterLink>
    </header>

    <AdminNavigation />

    <div
        v-if="carId === null"
        class="alert alert-error"
    >
      L’identifiant de la voiture est invalide.
    </div>

    <div
        v-else-if="detailStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ detailStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="detailStore.loadCar(carId)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-if="detailStore.successMessage !== null"
        class="alert alert-success"
    >
      {{ detailStore.successMessage }}
    </div>

    <div
        v-if="
        detailStore.isLoading
        && detailStore.detail === null
      "
        class="loading-panel"
    >
      Chargement de la voiture…
    </div>

    <template v-else-if="detailStore.detail !== null">
      <section class="car-header-card">
        <div class="car-main-identity">
          <span
              class="large-car-color"
              :style="{
              backgroundColor:
                detailStore.detail.car.color,
            }"
          />

          <div>
            <p class="car-id">
              Voiture #{{ detailStore.detail.car.id }}
            </p>

            <h2>
              {{ detailStore.detail.car.pilotName }}
            </h2>

            <div class="identity-badges">
              <span class="level-badge">
                Niveau
                {{ detailStore.detail.car.level }}
              </span>

              <span class="rating-badge">
                Classement
                {{ detailStore.detail.car.rating }}
              </span>
            </div>
          </div>
        </div>

        <div class="owner-panel">
          <span class="owner-label">
            Propriétaire
          </span>

          <RouterLink
              v-if="
              detailStore.detail.owner.id !== null
            "
              :to="{
              name: 'admin-user-detail',
              params: {
                id: detailStore.detail.owner.id,
              },
            }"
              class="owner-link"
          >
            {{
              detailStore.detail.owner.email
              ?? 'Compte inconnu'
            }}
          </RouterLink>

          <span v-else>
            Compte inconnu
          </span>

          <span
              class="owner-status"
              :class="{
              active:
                detailStore.detail.owner.isActive,
              disabled:
                !detailStore.detail.owner.isActive,
            }"
          >
            {{
              detailStore.detail.owner.isActive
                  ? 'Compte actif'
                  : 'Compte désactivé'
            }}
          </span>
        </div>
      </section>

      <section class="summary-grid">
        <article class="summary-card">
          <span>XP</span>

          <strong>
            {{ detailStore.detail.car.xp }}
          </strong>
        </article>

        <article class="summary-card">
          <span>Argent</span>

          <strong>
            {{ detailStore.detail.car.money }}
          </strong>
        </article>

        <article class="summary-card">
          <span>Victoires</span>

          <strong>
            {{ detailStore.detail.car.wins }}
          </strong>
        </article>

        <article class="summary-card">
          <span>Défaites</span>

          <strong>
            {{ detailStore.detail.car.losses }}
          </strong>
        </article>

        <article class="summary-card">
          <span>Cartes</span>

          <strong>
            {{ detailStore.detail.cards.length }}
          </strong>
        </article>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Statistiques</h2>

            <p>
              Comparaison des valeurs de base et des
              valeurs après application des cartes.
            </p>
          </div>
        </div>

        <div class="stats-table-container">
          <table class="stats-table">
            <thead>
            <tr>
              <th>Statistique</th>
              <th>Base</th>
              <th>Calculée</th>
              <th>Bonus</th>
            </tr>
            </thead>

            <tbody>
            <tr>
              <td>Vitesse</td>

              <td>
                {{
                  detailStore.detail.baseStats.speed
                }}
              </td>

              <td>
                {{
                  detailStore.detail
                      .calculatedStats.speed
                }}
              </td>

              <td class="bonus-value">
                +{{
                  detailStore.detail
                      .calculatedStats.speed
                  - detailStore.detail
                      .baseStats.speed
                }}
              </td>
            </tr>

            <tr>
              <td>Accélération</td>

              <td>
                {{
                  detailStore.detail
                      .baseStats.acceleration
                }}
              </td>

              <td>
                {{
                  detailStore.detail
                      .calculatedStats.acceleration
                }}
              </td>

              <td class="bonus-value">
                +{{
                  detailStore.detail
                      .calculatedStats.acceleration
                  - detailStore.detail
                      .baseStats.acceleration
                }}
              </td>
            </tr>

            <tr>
              <td>Adhérence</td>

              <td>
                {{
                  detailStore.detail.baseStats.grip
                }}
              </td>

              <td>
                {{
                  detailStore.detail
                      .calculatedStats.grip
                }}
              </td>

              <td class="bonus-value">
                +{{
                  detailStore.detail
                      .calculatedStats.grip
                  - detailStore.detail
                      .baseStats.grip
                }}
              </td>
            </tr>

            <tr>
              <td>Solidité</td>

              <td>
                {{
                  detailStore.detail
                      .baseStats.solidity
                }}
              </td>

              <td>
                {{
                  detailStore.detail
                      .calculatedStats.solidity
                }}
              </td>

              <td class="bonus-value">
                +{{
                  detailStore.detail
                      .calculatedStats.solidity
                  - detailStore.detail
                      .baseStats.solidity
                }}
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-section cooldown-section">
        <div class="section-heading cooldown-heading">
          <div>
            <h2>Cooldown</h2>

            <p>
              Délai entre cette voiture et ses
              adversaires récents.
            </p>
          </div>

          <button
              type="button"
              class="cooldown-reset-button"
              :disabled="
              detailStore.isResettingCooldown
              || !detailStore.detail.cooldown.active
            "
              @click="confirmCooldownReset"
          >
            {{
              detailStore.isResettingCooldown
                  ? 'Réinitialisation…'
                  : 'Réinitialiser le cooldown'
            }}
          </button>
        </div>

        <div
            class="cooldown-status"
            :class="{
            active:
              detailStore.detail.cooldown.active,
            inactive:
              !detailStore.detail.cooldown.active,
          }"
        >
          <strong>
            {{
              detailStore.detail.cooldown.active
                  ? `${detailStore.detail.cooldown.activePairCount} paire(s) en cooldown`
                  : 'Aucun cooldown actif'
            }}
          </strong>

          <span>
            Durée normale :
            {{
              formatDuration(
                  detailStore.detail.cooldown
                      .cooldownSeconds,
              )
            }}
          </span>
        </div>

        <div
            v-if="
            detailStore.detail.cooldown.pairs.length > 0
          "
            class="cooldown-pairs"
        >
          <article
              v-for="
              pair in
                detailStore.detail.cooldown.pairs
            "
              :key="pair.opponent.id"
              class="cooldown-pair"
          >
            <div class="opponent-identity">
              <span
                  class="small-car-color"
                  :style="{
                  backgroundColor:
                    pair.opponent.color,
                }"
              />

              <div>
                <strong>
                  {{ pair.opponent.pilotName }}
                </strong>

                <small>
                  Voiture #{{ pair.opponent.id }}
                </small>
              </div>
            </div>

            <div class="cooldown-pair-data">
              <span>
                Restant :
                <strong>
                  {{
                    formatDuration(
                        pair.remainingSeconds,
                    )
                  }}
                </strong>
              </span>

              <small>
                Expire le
                {{ formatDate(pair.expiresAt) }}
              </small>
            </div>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Cartes possédées</h2>

            <p>
              Paliers et configurations actuellement
              enregistrés.
            </p>
          </div>
        </div>

        <div
            v-if="detailStore.detail.cards.length === 0"
            class="empty-panel"
        >
          Cette voiture ne possède aucune carte.
        </div>

        <div v-else class="cards-grid">
          <article
              v-for="ownedCard in detailStore.detail.cards"
              :key="ownedCard.id"
              class="owned-card"
          >
            <header class="owned-card-header">
              <div>
                <span class="card-type">
                  {{
                    ownedCard.card.type
                    ?? 'Type inconnu'
                  }}
                </span>

                <h3>
                  {{
                    ownedCard.card.name
                    ?? 'Carte inconnue'
                  }}
                </h3>

                <small>
                  {{
                    ownedCard.card.code
                    ?? 'Sans code'
                  }}
                </small>
              </div>

              <div class="card-badges">
                <span class="tier-badge">
                  Palier {{ ownedCard.tier }}
                </span>

                <span
                    v-if="ownedCard.equipped"
                    class="equipped-badge"
                >
                  Équipée
                </span>
              </div>
            </header>

            <dl class="card-information">
              <div>
                <dt>Rareté</dt>

                <dd>
                  {{
                    ownedCard.card.rarity
                    ?? 'Non renseignée'
                  }}
                </dd>
              </div>

              <div>
                <dt>Niveau d’acquisition</dt>

                <dd>
                  {{ ownedCard.acquiredLevel }}
                </dd>
              </div>
            </dl>

            <details class="effect-details">
              <summary>
                Configuration de l’effet
              </summary>

              <pre>{{
                  formatEffectConfig(
                      ownedCard.card.effectConfig,
                  )
                }}</pre>
            </details>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Choix de carte en attente</h2>

            <p>
              Choix devant être résolu avant une
              nouvelle montée de niveau.
            </p>
          </div>
        </div>

        <div
            v-if="
            detailStore.detail.pendingCardChoice
              === null
          "
            class="empty-panel"
        >
          Aucun choix de carte en attente.
        </div>

        <div v-else class="pending-choice">
          <header>
            <div>
              <strong>
                Choix
                #{{
                  detailStore.detail
                      .pendingCardChoice.id
                }}
              </strong>

              <small>
                Niveau
                {{
                  detailStore.detail
                      .pendingCardChoice.level
                }}
              </small>
            </div>
          </header>

          <div class="choice-cards">
            <article
                v-if="
                detailStore.detail
                  .pendingCardChoice.firstCard
                  !== null
              "
                class="choice-card"
            >
              <span>
                {{
                  detailStore.detail
                      .pendingCardChoice.firstCard.type
                }}
              </span>

              <strong>
                {{
                  detailStore.detail
                      .pendingCardChoice.firstCard.name
                }}
              </strong>

              <small>
                {{
                  detailStore.detail
                      .pendingCardChoice.firstCard.rarity
                }}
              </small>
            </article>

            <article
                v-if="
                detailStore.detail
                  .pendingCardChoice.secondCard
                  !== null
              "
                class="choice-card"
            >
              <span>
                {{
                  detailStore.detail
                      .pendingCardChoice.secondCard.type
                }}
              </span>

              <strong>
                {{
                  detailStore.detail
                      .pendingCardChoice.secondCard.name
                }}
              </strong>

              <small>
                {{
                  detailStore.detail
                      .pendingCardChoice.secondCard.rarity
                }}
              </small>
            </article>
          </div>
        </div>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Derniers duels</h2>

            <p>
              Les dix affrontements les plus récents.
            </p>
          </div>
        </div>

        <div
            v-if="
            detailStore.detail.recentDuels.length
              === 0
          "
            class="empty-panel"
        >
          Aucun duel.
        </div>

        <div v-else class="duel-list">
          <article
              v-for="
              duel in detailStore.detail.recentDuels
            "
              :key="duel.id"
              class="duel-row"
          >
            <span
                class="result-badge"
                :class="{
                won: duel.won,
                lost: !duel.won,
              }"
            >
              {{ duel.won ? 'Victoire' : 'Défaite' }}
            </span>

            <div class="duel-opponent">
              <span
                  class="small-car-color"
                  :style="{
                  backgroundColor:
                    duel.opponent.color,
                }"
              />

              <div>
                <strong>
                  contre
                  {{ duel.opponent.pilotName }}
                </strong>

                <small>
                  Duel #{{ duel.id }}
                  —
                  {{
                    duel.wasAttacker
                        ? 'Attaquant'
                        : 'Défenseur'
                  }}
                </small>
              </div>
            </div>

            <div class="duel-result-data">
              <span>
                Écart : {{ duel.finalGap }}
              </span>

              <small>
                {{ formatDate(duel.createdAt) }}
              </small>
            </div>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <div class="section-heading">
          <div>
            <h2>Activité récente</h2>

            <p>
              Événements analytiques associés à la
              voiture.
            </p>
          </div>
        </div>

        <div
            v-if="
            detailStore.detail.recentEvents.length
              === 0
          "
            class="empty-panel"
        >
          Aucun événement.
        </div>

        <div v-else class="event-list">
          <article
              v-for="
              event in
                detailStore.detail.recentEvents
            "
              :key="event.id"
              class="event-row"
          >
            <div>
              <strong>
                {{ eventLabel(event.type) }}
              </strong>

              <small>
                {{ formatDate(event.occurredAt) }}
              </small>
            </div>

            <span v-if="event.duelId !== null">
              Duel #{{ event.duelId }}
            </span>
          </article>
        </div>
      </section>
    </template>
  </main>
</template>

<style scoped>
.admin-car-detail {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header,
.car-header-card,
.section-heading,
.cooldown-heading {
  display: flex;
  justify-content: space-between;
  gap: 24px;
}

.admin-header {
  align-items: flex-start;
  margin-bottom: 24px;
}

.admin-eyebrow,
.car-id {
  margin: 0 0 5px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1,
.car-header-card h2,
.section-heading h2 {
  margin: 0;
}

.admin-description,
.section-heading p {
  margin: 7px 0 0;
  opacity: 0.68;
}

.back-link,
.cooldown-reset-button,
.alert button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.08);
  color: inherit;
  font: inherit;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.car-header-card {
  align-items: center;
  padding: 24px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 16px;
  background: rgba(127, 127, 127, 0.06);
}

.car-main-identity {
  display: flex;
  align-items: center;
  gap: 16px;
}

.large-car-color {
  width: 58px;
  height: 58px;
  border: 3px solid rgba(127, 127, 127, 0.3);
  border-radius: 50%;
}

.identity-badges,
.card-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 10px;
}

.level-badge,
.rating-badge,
.owner-status,
.tier-badge,
.equipped-badge,
.result-badge {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 750;
}

.level-badge,
.rating-badge,
.tier-badge {
  background: rgba(127, 127, 127, 0.15);
}

.equipped-badge,
.owner-status.active,
.result-badge.won {
  background: rgba(40, 160, 90, 0.17);
}

.owner-status.disabled,
.result-badge.lost {
  background: rgba(190, 50, 50, 0.16);
}

.owner-panel {
  display: grid;
  justify-items: end;
  gap: 6px;
}

.owner-label {
  font-size: 0.76rem;
  font-weight: 700;
  opacity: 0.62;
}

.owner-link {
  color: inherit;
  font-weight: 750;
  text-decoration: none;
}

.owner-link:hover {
  text-decoration: underline;
}

.summary-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(150px, 1fr));
  gap: 13px;
  margin-top: 20px;
}

.summary-card {
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 13px;
  background: rgba(127, 127, 127, 0.05);
}

.summary-card span {
  display: block;
  font-size: 0.8rem;
  opacity: 0.65;
}

.summary-card strong {
  display: block;
  margin-top: 8px;
  font-size: 1.8rem;
}

.admin-section {
  margin-top: 36px;
}

.section-heading {
  align-items: flex-end;
  margin-bottom: 15px;
}

.stats-table-container {
  overflow: hidden;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 13px;
}

.stats-table {
  width: 100%;
  border-collapse: collapse;
}

.stats-table th,
.stats-table td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.15);
  text-align: left;
}

.stats-table tr:last-child td {
  border-bottom: 0;
}

.stats-table th {
  font-size: 0.76rem;
  text-transform: uppercase;
  opacity: 0.62;
}

.bonus-value {
  font-weight: 750;
}

.cooldown-section {
  padding: 22px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 15px;
  background: rgba(127, 127, 127, 0.05);
}

.cooldown-heading {
  align-items: flex-start;
}

.cooldown-reset-button {
  border-color: rgba(190, 110, 30, 0.45);
  background: rgba(190, 110, 30, 0.1);
}

.cooldown-status {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-top: 18px;
  padding: 14px;
  border-radius: 10px;
}

.cooldown-status.active {
  background: rgba(190, 110, 30, 0.12);
}

.cooldown-status.inactive {
  background: rgba(40, 160, 90, 0.1);
}

.cooldown-pairs {
  display: grid;
  gap: 9px;
  margin-top: 12px;
}

.cooldown-pair,
.duel-row,
.event-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border: 1px solid rgba(127, 127, 127, 0.17);
  border-radius: 10px;
}

.opponent-identity,
.duel-opponent {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.opponent-identity div,
.duel-opponent div,
.event-row div {
  display: grid;
}

.small-car-color {
  flex: 0 0 auto;
  width: 29px;
  height: 29px;
  border: 2px solid rgba(127, 127, 127, 0.28);
  border-radius: 50%;
}

.cooldown-pair-data,
.duel-result-data {
  display: grid;
  justify-items: end;
}

.cooldown-pair small,
.duel-row small,
.event-row small {
  opacity: 0.62;
}

.cards-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(270px, 1fr));
  gap: 14px;
}

.owned-card {
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.21);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.owned-card-header {
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.owned-card h3 {
  margin: 4px 0;
}

.card-type {
  font-size: 0.75rem;
  font-weight: 750;
  text-transform: uppercase;
  opacity: 0.62;
}

.card-information {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 9px;
  margin: 17px 0;
}

.card-information div {
  padding: 9px;
  border-radius: 8px;
  background: rgba(127, 127, 127, 0.08);
}

.card-information dt {
  font-size: 0.72rem;
  opacity: 0.63;
}

.card-information dd {
  margin: 3px 0 0;
  font-weight: 750;
}

.effect-details summary {
  cursor: pointer;
  font-weight: 700;
}

.effect-details pre {
  max-height: 250px;
  overflow: auto;
  padding: 12px;
  border-radius: 8px;
  background: rgba(20, 20, 20, 0.75);
  color: white;
  font-size: 0.74rem;
  white-space: pre-wrap;
}

.pending-choice {
  padding: 19px;
  border: 1px solid rgba(190, 110, 30, 0.3);
  border-radius: 14px;
  background: rgba(190, 110, 30, 0.07);
}

.pending-choice header div {
  display: grid;
}

.choice-cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 15px;
}

.choice-card {
  display: grid;
  gap: 5px;
  padding: 14px;
  border-radius: 10px;
  background: rgba(127, 127, 127, 0.1);
}

.choice-card span,
.choice-card small {
  opacity: 0.64;
}

.duel-list,
.event-list {
  display: grid;
  gap: 9px;
}

.event-row div {
  flex: 1;
}

.empty-panel,
.loading-panel,
.alert {
  padding: 32px;
  border-radius: 13px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert {
  margin-bottom: 16px;
}

.alert p {
  margin: 7px 0 14px;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

.alert-success {
  border: 1px solid rgba(40, 160, 90, 0.4);
  background: rgba(40, 160, 90, 0.1);
}

@media (max-width: 700px) {
  .admin-car-detail {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .car-header-card,
  .section-heading,
  .cooldown-heading,
  .owned-card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .back-link,
  .cooldown-reset-button {
    width: 100%;
  }

  .owner-panel {
    justify-items: start;
  }

  .cooldown-status,
  .cooldown-pair,
  .duel-row,
  .event-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .cooldown-pair-data,
  .duel-result-data {
    justify-items: start;
  }

  .choice-cards {
    grid-template-columns: 1fr;
  }
}
</style>