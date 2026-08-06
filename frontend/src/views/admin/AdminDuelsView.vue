<script setup lang="ts">
import {
  onMounted,
  onUnmounted,
} from 'vue'
import { RouterLink } from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminDuelsStore }
  from '../../stores/adminDuels'

const duelsStore = useAdminDuelsStore()

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

function formatDelta(value: number): string {
  return value > 0
      ? `+${value}`
      : String(value)
}

onMounted(async (): Promise<void> => {
  await duelsStore.loadDuels()
})

onUnmounted((): void => {
  duelsStore.reset()
})
</script>

<template>
  <main class="admin-duels">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Duels</h1>

        <p class="admin-description">
          Historique global des affrontements.
        </p>
      </div>

      <button
          type="button"
          class="refresh-button"
          :disabled="duelsStore.isLoading"
          @click="
          duelsStore.loadDuels(
            duelsStore.pagination.page,
          )
        "
      >
        {{
          duelsStore.isLoading
              ? 'Actualisation…'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <form
        class="filters"
        @submit.prevent="duelsStore.submitFilters"
    >
      <label class="filter-field search-field">
        <span>Pilote ou propriétaire</span>

        <input
            v-model="duelsStore.search"
            type="search"
            placeholder="Nom ou adresse e-mail"
            autocomplete="off"
        >
      </label>

      <label class="filter-field">
        <span>Vainqueur</span>

        <select v-model="duelsStore.winnerSide">
          <option value="">
            Tous
          </option>

          <option value="attacker">
            Attaquant
          </option>

          <option value="defender">
            Défenseur
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Version moteur</span>

        <input
            v-model="duelsStore.engineVersion"
            type="text"
            placeholder="1.0"
        >
      </label>

      <button
          type="submit"
          class="primary-button"
          :disabled="duelsStore.isLoading"
      >
        Filtrer
      </button>

      <button
          type="button"
          class="secondary-button"
          :disabled="duelsStore.isLoading"
          @click="duelsStore.clearFilters"
      >
        Effacer
      </button>
    </form>

    <div
        v-if="duelsStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>{{ duelsStore.errorMessage }}</p>

      <button
          type="button"
          @click="duelsStore.loadDuels(1)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        duelsStore.isLoading
        && duelsStore.duels.length === 0
      "
        class="loading-panel"
    >
      Chargement des duels…
    </div>

    <section v-else>
      <div class="section-summary">
        <p>
          <strong>
            {{ duelsStore.pagination.totalItems }}
          </strong>
          duel(s)
        </p>

        <p>
          Page
          {{ duelsStore.pagination.page }}
          sur
          {{ duelsStore.pagination.totalPages }}
        </p>
      </div>

      <div
          v-if="duelsStore.duels.length === 0"
          class="empty-panel"
      >
        Aucun duel ne correspond aux filtres.
      </div>

      <div v-else class="duels-grid">
        <article
            v-for="duel in duelsStore.duels"
            :key="duel.id"
            class="duel-card"
        >
          <header class="duel-card-header">
            <div>
              <strong>Duel #{{ duel.id }}</strong>

              <small>
                {{ formatDate(duel.createdAt) }}
              </small>
            </div>

            <span class="engine-badge">
              Moteur {{ duel.engineVersion }}
            </span>
          </header>

          <div class="fighters">
            <section
                class="fighter"
                :class="{
                winner:
                  duel.winnerSide === 'attacker',
              }"
            >
              <span class="side-label">
                Attaquant
              </span>

              <RouterLink
                  :to="{
                  name: 'admin-car-detail',
                  params: {
                    id: duel.attacker.id,
                  },
                }"
                  class="fighter-name"
              >
                {{ duel.attacker.pilotName }}
              </RouterLink>

              <small>
                {{ duel.attacker.ownerEmail ?? 'Sans compte' }}
              </small>

              <div class="rewards">
                <span>
                  +{{ duel.attackerXpReward }} XP
                </span>

                <span>
                  +{{ duel.attackerMoneyReward }} $
                </span>

                <span>
                  Elo
                  {{
                    formatDelta(
                        duel.attackerRatingDelta,
                    )
                  }}
                </span>
              </div>
            </section>

            <div class="versus">
              VS
            </div>

            <section
                class="fighter"
                :class="{
                winner:
                  duel.winnerSide === 'defender',
              }"
            >
              <span class="side-label">
                Défenseur
              </span>

              <RouterLink
                  :to="{
                  name: 'admin-car-detail',
                  params: {
                    id: duel.defender.id,
                  },
                }"
                  class="fighter-name"
              >
                {{ duel.defender.pilotName }}
              </RouterLink>

              <small>
                {{ duel.defender.ownerEmail ?? 'Sans compte' }}
              </small>

              <div class="rewards">
                <span>
                  +{{ duel.defenderXpReward }} XP
                </span>

                <span>
                  +{{ duel.defenderMoneyReward }} $
                </span>

                <span>
                  Elo
                  {{
                    formatDelta(
                        duel.defenderRatingDelta,
                    )
                  }}
                </span>
              </div>
            </section>
          </div>

          <footer class="duel-card-footer">
            <span>
              Écart final :
              <strong>{{ duel.finalGap }}</strong>
            </span>

            <span>
              Vainqueur :
              <strong>
                {{
                  duel.winnerSide === 'attacker'
                      ? duel.attacker.pilotName
                      : duel.defender.pilotName
                }}
              </strong>
            </span>
          </footer>
        </article>
      </div>

      <footer class="pagination">
        <button
            type="button"
            :disabled="
            duelsStore.isLoading
            || duelsStore.pagination.page <= 1
          "
            @click="duelsStore.previousPage"
        >
          Page précédente
        </button>

        <span>
          {{ duelsStore.pagination.page }}
          /
          {{ duelsStore.pagination.totalPages }}
        </span>

        <button
            type="button"
            :disabled="
            duelsStore.isLoading
            || duelsStore.pagination.page
              >= duelsStore.pagination.totalPages
          "
            @click="duelsStore.nextPage"
        >
          Page suivante
        </button>
      </footer>
    </section>
  </main>
</template>

<style scoped>
.admin-duels {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.admin-eyebrow {
  margin: 0 0 4px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1 {
  margin: 0;
  font-size: clamp(2rem, 5vw, 3rem);
}

.admin-description {
  margin: 8px 0 0;
  opacity: 0.7;
}

.refresh-button,
.primary-button,
.secondary-button,
.pagination button,
.alert button {
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.1);
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.filters {
  display: grid;
  grid-template-columns:
    minmax(260px, 1fr)
    minmax(150px, 180px)
    minmax(150px, 180px)
    auto
    auto;
  align-items: end;
  gap: 10px;
  margin-bottom: 24px;
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.06);
}

.filter-field {
  display: grid;
  gap: 7px;
}

.filter-field span {
  font-size: 0.82rem;
  font-weight: 700;
  opacity: 0.68;
}

.filter-field input,
.filter-field select {
  min-height: 42px;
  padding: 0 12px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
}

.section-summary {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 12px;
}

.section-summary p {
  margin: 0;
  opacity: 0.7;
}

.duels-grid {
  display: grid;
  gap: 14px;
}

.duel-card {
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.duel-card-header,
.duel-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.duel-card-header div {
  display: grid;
}

.duel-card small {
  opacity: 0.62;
}

.engine-badge {
  padding: 5px 9px;
  border-radius: 999px;
  background: rgba(127, 127, 127, 0.14);
  font-size: 0.76rem;
  font-weight: 750;
}

.fighters {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: stretch;
  gap: 12px;
  margin: 17px 0;
}

.fighter {
  display: grid;
  gap: 5px;
  padding: 15px;
  border: 1px solid rgba(127, 127, 127, 0.18);
  border-radius: 11px;
}

.fighter.winner {
  border-color: rgba(40, 160, 90, 0.45);
  background: rgba(40, 160, 90, 0.08);
}

.side-label {
  font-size: 0.72rem;
  font-weight: 750;
  text-transform: uppercase;
  opacity: 0.62;
}

.fighter-name {
  color: inherit;
  font-size: 1.05rem;
  font-weight: 800;
  text-decoration: none;
}

.fighter-name:hover {
  text-decoration: underline;
}

.rewards {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.rewards span {
  padding: 5px 7px;
  border-radius: 7px;
  background: rgba(127, 127, 127, 0.1);
  font-size: 0.73rem;
  font-weight: 700;
}

.versus {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 900;
  opacity: 0.5;
}

.duel-card-footer {
  padding-top: 13px;
  border-top: 1px solid rgba(127, 127, 127, 0.15);
  font-size: 0.85rem;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin-top: 22px;
}

.loading-panel,
.empty-panel,
.alert {
  padding: 36px;
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.5);
  background: rgba(190, 50, 50, 0.1);
}

.alert p {
  margin: 8px 0 14px;
}

@media (max-width: 850px) {
  .filters {
    grid-template-columns: 1fr 1fr;
  }

  .search-field {
    grid-column: 1 / -1;
  }
}

@media (max-width: 650px) {
  .admin-duels {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .section-summary,
  .duel-card-header,
  .duel-card-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .refresh-button {
    width: 100%;
  }

  .filters,
  .fighters {
    grid-template-columns: 1fr;
  }

  .search-field {
    grid-column: auto;
  }

  .versus {
    min-height: 30px;
  }
}
</style>