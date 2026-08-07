<script setup lang="ts">
import {
  onMounted,
  onUnmounted,
} from 'vue'
import { RouterLink } from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminEventsStore }
  from '../../stores/adminEvents'

const eventsStore = useAdminEventsStore()

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date)
}

function formatJson(
    value: Record<string, unknown>,
): string {
  try {
    return JSON.stringify(value, null, 2)
  } catch {
    return 'Payload indisponible'
  }
}

function eventLabel(type: string): string {
  const labels: Record<string, string> = {
    user_registered: 'Inscription',
    login_succeeded: 'Connexion réussie',
    car_created: 'Voiture créée',
    level_up: 'Montée de niveau',
    card_selected: 'Carte sélectionnée',
    card_upgraded: 'Carte améliorée',
    duel_completed: 'Duel terminé',
    admin_cooldown_reset:
        'Cooldown réinitialisé',
    admin_card_updated:
        'Carte modifiée',
  }

  return labels[type] ?? type
}

onMounted(async (): Promise<void> => {
  await eventsStore.loadEvents()
})

onUnmounted((): void => {
  eventsStore.reset()
})
</script>

<template>
  <main class="admin-events">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Historique</h1>

        <p class="admin-description">
          Événements de jeu et actions
          administratives.
        </p>
      </div>

      <button
          type="button"
          class="refresh-button"
          :disabled="eventsStore.isLoading"
          @click="
          eventsStore.loadEvents(
            eventsStore.pagination.page,
          )
        "
      >
        {{
          eventsStore.isLoading
              ? 'Actualisation…'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <form
        class="filters"
        @submit.prevent="eventsStore.submitFilters"
    >
      <label class="filter-field search-field">
        <span>Utilisateur ou pilote</span>

        <input
            v-model="eventsStore.search"
            type="search"
            placeholder="E-mail ou nom du pilote"
            autocomplete="off"
        >
      </label>

      <label class="filter-field">
        <span>Type d’événement</span>

        <select v-model="eventsStore.type">
          <option value="">
            Tous
          </option>

          <option
              v-for="
              availableType in
                eventsStore.options.types
            "
              :key="availableType"
              :value="availableType"
          >
            {{ eventLabel(availableType) }}
          </option>
        </select>
      </label>

      <label class="filter-field">
        <span>Du</span>

        <input
            v-model="eventsStore.dateFrom"
            type="date"
        >
      </label>

      <label class="filter-field">
        <span>Au</span>

        <input
            v-model="eventsStore.dateTo"
            type="date"
        >
      </label>

      <button
          type="submit"
          :disabled="eventsStore.isLoading"
      >
        Filtrer
      </button>

      <button
          type="button"
          :disabled="eventsStore.isLoading"
          @click="eventsStore.clearFilters"
      >
        Effacer
      </button>
    </form>

    <div
        v-if="eventsStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>{{ eventsStore.errorMessage }}</p>

      <button
          type="button"
          @click="eventsStore.loadEvents(1)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        eventsStore.isLoading
        && eventsStore.events.length === 0
      "
        class="loading-panel"
    >
      Chargement de l’historique…
    </div>

    <section v-else>
      <div class="section-summary">
        <p>
          <strong>
            {{ eventsStore.pagination.totalItems }}
          </strong>
          événement(s)
        </p>

        <p>
          Page
          {{ eventsStore.pagination.page }}
          sur
          {{ eventsStore.pagination.totalPages }}
        </p>
      </div>

      <div
          v-if="eventsStore.events.length === 0"
          class="empty-panel"
      >
        Aucun événement ne correspond aux filtres.
      </div>

      <div v-else class="events-list">
        <article
            v-for="event in eventsStore.events"
            :key="event.id"
            class="event-card"
        >
          <header class="event-header">
            <div>
              <span class="event-type">
                {{ eventLabel(event.type) }}
              </span>

              <strong>
                Événement #{{ event.id }}
              </strong>

              <small>
                {{ formatDate(event.occurredAt) }}
              </small>
            </div>

            <span class="technical-type">
              {{ event.type }}
            </span>
          </header>

          <div class="event-relations">
            <RouterLink
                v-if="event.user !== null"
                :to="{
                name: 'admin-user-detail',
                params: {
                  id: event.user.id,
                },
              }"
            >
              Utilisateur :
              {{ event.user.email }}
            </RouterLink>

            <RouterLink
                v-if="event.car !== null"
                :to="{
                name: 'admin-car-detail',
                params: {
                  id: event.car.id,
                },
              }"
            >
              Voiture :
              {{ event.car.pilotName }}
            </RouterLink>

            <RouterLink
                v-if="event.duel !== null"
                :to="{
                name: 'admin-duel-detail',
                params: {
                  id: event.duel.id,
                },
              }"
            >
              Duel #{{ event.duel.id }}
            </RouterLink>

            <span
                v-if="
                event.user === null
                && event.car === null
                && event.duel === null
              "
            >
              Aucun objet directement associé
            </span>
          </div>

          <details class="payload-details">
            <summary>
              Afficher le payload JSON
            </summary>

            <pre>{{
                formatJson(event.payload)
              }}</pre>
          </details>
        </article>
      </div>

      <footer class="pagination">
        <button
            type="button"
            :disabled="
            eventsStore.isLoading
            || eventsStore.pagination.page <= 1
          "
            @click="eventsStore.previousPage"
        >
          Page précédente
        </button>

        <span>
          {{ eventsStore.pagination.page }}
          /
          {{ eventsStore.pagination.totalPages }}
        </span>

        <button
            type="button"
            :disabled="
            eventsStore.isLoading
            || eventsStore.pagination.page
              >= eventsStore.pagination.totalPages
          "
            @click="eventsStore.nextPage"
        >
          Page suivante
        </button>
      </footer>
    </section>
  </main>
</template>

<style scoped>
.admin-events {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header,
.event-header,
.section-summary {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.admin-header {
  align-items: flex-start;
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
.filters button,
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
    minmax(230px, 1fr)
    minmax(180px, 230px)
    minmax(145px, 170px)
    minmax(145px, 170px)
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
  font-size: 0.8rem;
  font-weight: 700;
  opacity: 0.68;
}

.filter-field input,
.filter-field select {
  width: 100%;
  min-height: 42px;
  padding: 0 11px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
  box-sizing: border-box;
}

.section-summary {
  margin-bottom: 12px;
}

.section-summary p {
  margin: 0;
  opacity: 0.7;
}

.events-list {
  display: grid;
  gap: 12px;
}

.event-card {
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.21);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.event-header div {
  display: grid;
  gap: 3px;
}

.event-type {
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  opacity: 0.68;
}

.event-header small {
  opacity: 0.62;
}

.technical-type {
  align-self: flex-start;
  padding: 5px 8px;
  border-radius: 999px;
  background: rgba(127, 127, 127, 0.13);
  font-family: monospace;
  font-size: 0.72rem;
}

.event-relations {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}

.event-relations a,
.event-relations span {
  padding: 7px 9px;
  border-radius: 8px;
  background: rgba(127, 127, 127, 0.08);
  color: inherit;
  font-size: 0.79rem;
  font-weight: 700;
  text-decoration: none;
}

.event-relations a:hover {
  text-decoration: underline;
}

.payload-details {
  margin-top: 15px;
}

.payload-details summary {
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 750;
}

.payload-details pre {
  max-height: 500px;
  overflow: auto;
  margin: 12px 0 0;
  padding: 14px;
  border-radius: 9px;
  background: rgba(18, 18, 18, 0.92);
  color: white;
  font-size: 0.76rem;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
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
  padding: 34px;
  border-radius: 13px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

.alert p {
  margin: 8px 0 14px;
}

@media (max-width: 950px) {
  .filters {
    grid-template-columns: 1fr 1fr;
  }

  .search-field {
    grid-column: 1 / -1;
  }
}

@media (max-width: 600px) {
  .admin-events {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .event-header,
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

  .technical-type {
    align-self: flex-start;
  }
}
</style>