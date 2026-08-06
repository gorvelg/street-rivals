<script setup lang="ts">
import {
  onMounted,
  onUnmounted,
} from 'vue'
import { RouterLink } from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminCarsStore }
  from '../../stores/adminCars'

const carsStore = useAdminCarsStore()

function formatDate(
    value: string | null,
): string {
  if (value === null) {
    return 'Aucun duel'
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

onMounted(async (): Promise<void> => {
  await carsStore.loadCars()
})

onUnmounted((): void => {
  carsStore.reset()
})
</script>

<template>
  <main class="admin-cars">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Voitures</h1>

        <p class="admin-description">
          Progression et activité des voitures du jeu.
        </p>
      </div>

      <button
          type="button"
          class="refresh-button"
          :disabled="carsStore.isLoading"
          @click="
          carsStore.loadCars(
            carsStore.pagination.page,
          )
        "
      >
        {{
          carsStore.isLoading
              ? 'Actualisation…'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <form
        class="filters"
        @submit.prevent="carsStore.submitFilters"
    >
      <label class="filter-field search-field">
        <span>Pilote ou propriétaire</span>

        <input
            v-model="carsStore.search"
            type="search"
            placeholder="Nom ou adresse e-mail"
            autocomplete="off"
        >
      </label>

      <label class="filter-field">
        <span>Niveau minimum</span>

        <input
            v-model.number="carsStore.minLevel"
            type="number"
            min="1"
            step="1"
            placeholder="1"
        >
      </label>

      <label class="filter-field">
        <span>Classement minimum</span>

        <input
            v-model.number="carsStore.minRating"
            type="number"
            min="0"
            step="1"
            placeholder="1000"
        >
      </label>

      <button
          type="submit"
          class="primary-button"
          :disabled="carsStore.isLoading"
      >
        Filtrer
      </button>

      <button
          type="button"
          class="secondary-button"
          :disabled="carsStore.isLoading"
          @click="carsStore.clearFilters"
      >
        Effacer
      </button>
    </form>

    <div
        v-if="carsStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ carsStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="carsStore.loadCars(1)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        carsStore.isLoading
        && carsStore.cars.length === 0
      "
        class="loading-panel"
    >
      Chargement des voitures…
    </div>

    <section v-else>
      <div class="section-summary">
        <p>
          <strong>
            {{ carsStore.pagination.totalItems }}
          </strong>
          voiture(s)
        </p>

        <p>
          Page
          {{ carsStore.pagination.page }}
          sur
          {{ carsStore.pagination.totalPages }}
        </p>
      </div>

      <div
          v-if="carsStore.cars.length === 0"
          class="empty-panel"
      >
        Aucune voiture ne correspond aux filtres.
      </div>

      <div
          v-else
          class="table-container"
      >
        <table class="cars-table">
          <thead>
          <tr>
            <th>Voiture</th>
            <th>Propriétaire</th>
            <th>Niveau</th>
            <th>XP</th>
            <th>Classement</th>
            <th>V / D</th>
            <th>Cartes</th>
            <th>Argent</th>
            <th>Dernier duel</th>
          </tr>
          </thead>

          <tbody>
          <tr
              v-for="car in carsStore.cars"
              :key="car.id"
          >
            <td>
              <div class="car-identity">
                  <span
                      class="car-color"
                      :style="{
                      backgroundColor: car.color,
                    }"
                  />

                <div>
                  <strong>
                    {{ car.pilotName }}
                  </strong>

                  <small>
                    #{{ car.id }}
                  </small>
                </div>
              </div>
            </td>

            <td>
              <RouterLink
                  :to="{
                    name: 'admin-user-detail',
                    params: {
                      id: car.owner.id,
                    },
                  }"
                  class="owner-link"
              >
                {{ car.owner.email }}
              </RouterLink>

              <span
                  v-if="!car.owner.isActive"
                  class="disabled-badge"
              >
                  Désactivé
                </span>
            </td>

            <td>
              {{ car.level }}
            </td>

            <td>
              {{ car.xp }}
            </td>

            <td>
              <strong>
                {{ car.rating }}
              </strong>
            </td>

            <td>
              {{ car.wins }} / {{ car.losses }}
            </td>

            <td>
              {{ car.cardCount }}
            </td>

            <td>
              {{ car.money }}
            </td>

            <td>
              {{ formatDate(car.lastDuelAt) }}
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <footer class="pagination">
        <button
            type="button"
            :disabled="
            carsStore.isLoading
            || carsStore.pagination.page <= 1
          "
            @click="carsStore.previousPage"
        >
          Page précédente
        </button>

        <span>
          {{ carsStore.pagination.page }}
          /
          {{ carsStore.pagination.totalPages }}
        </span>

        <button
            type="button"
            :disabled="
            carsStore.isLoading
            || carsStore.pagination.page
              >= carsStore.pagination.totalPages
          "
            @click="carsStore.nextPage"
        >
          Page suivante
        </button>
      </footer>
    </section>
  </main>
</template>

<style scoped>
.admin-cars {
  width: min(1280px, calc(100% - 32px));
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
.pagination button {
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
    minmax(240px, 1fr)
    minmax(140px, 180px)
    minmax(160px, 200px)
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

.filter-field input {
  width: 100%;
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

.table-container {
  overflow-x: auto;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
}

.cars-table {
  width: 100%;
  min-width: 1050px;
  border-collapse: collapse;
}

.cars-table th,
.cars-table td {
  padding: 14px 15px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.16);
  text-align: left;
  white-space: nowrap;
}

.cars-table th {
  font-size: 0.76rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  opacity: 0.62;
}

.cars-table tbody tr:last-child td {
  border-bottom: 0;
}

.car-identity {
  display: flex;
  align-items: center;
  gap: 10px;
}

.car-identity div {
  display: grid;
}

.car-identity small {
  opacity: 0.6;
}

.car-color {
  width: 32px;
  height: 32px;
  border: 2px solid rgba(127, 127, 127, 0.3);
  border-radius: 50%;
}

.owner-link {
  color: inherit;
  font-weight: 700;
  text-decoration: none;
}

.owner-link:hover {
  text-decoration: underline;
}

.disabled-badge {
  display: inline-flex;
  margin-left: 7px;
  padding: 4px 7px;
  border-radius: 999px;
  background: rgba(190, 50, 50, 0.15);
  font-size: 0.7rem;
  font-weight: 750;
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

@media (max-width: 900px) {
  .filters {
    grid-template-columns: 1fr 1fr;
  }

  .search-field {
    grid-column: 1 / -1;
  }
}

@media (max-width: 600px) {
  .admin-cars {
    width: min(100% - 20px, 1280px);
    padding-top: 20px;
  }

  .admin-header,
  .section-summary {
    flex-direction: column;
  }

  .refresh-button {
    width: 100%;
  }

  .filters {
    grid-template-columns: 1fr;
  }

  .search-field {
    grid-column: auto;
  }
}
</style>