<script setup lang="ts">
import {
  computed,
  onMounted,
} from 'vue'
import { RouterLink } from 'vue-router'
import { useAdminDashboardStore } from '../../stores/adminDashboard'
import AdminNavigation from '../../components/admin/AdminNavigation.vue'

const dashboardStore = useAdminDashboardStore()

const generatedAtLabel = computed<string>(() => {
  const generatedAt =
      dashboardStore.dashboard?.generatedAt

  if (generatedAt === undefined) {
    return ''
  }

  const date = new Date(generatedAt)

  if (Number.isNaN(date.getTime())) {
    return generatedAt
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'medium',
  }).format(date)
})

onMounted(async (): Promise<void> => {
  await dashboardStore.loadDashboard()
})
</script>

<template>
  <main class="admin-dashboard">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Administration</h1>

        <p class="admin-description">
          Activité générale de l’alpha privée.
        </p>
      </div>

      <button
          type="button"
          class="admin-refresh-button"
          :disabled="dashboardStore.isLoading"
          @click="dashboardStore.loadDashboard"
      >
        {{
          dashboardStore.isLoading
              ? 'Actualisation...'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <div
        v-if="dashboardStore.errorMessage !== null"
        class="admin-alert admin-alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ dashboardStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="dashboardStore.loadDashboard"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        dashboardStore.isLoading
        && dashboardStore.dashboard === null
      "
        class="admin-loading"
    >
      Chargement des statistiques…
    </div>

    <template
        v-else-if="dashboardStore.dashboard !== null"
    >
      <section class="admin-section">
        <div class="admin-section-heading">
          <div>
            <h2>Vue générale</h2>

            <p>
              Situation actuelle du jeu.
            </p>
          </div>

          <p class="admin-generation-date">
            Généré le {{ generatedAtLabel }}
            — {{ dashboardStore.dashboard.timezone }}
          </p>
        </div>

        <div class="admin-stat-grid">
          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Utilisateurs
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.users.total
              }}
            </strong>

            <p class="admin-stat-detail">
              {{
                dashboardStore.dashboard.users
                    .registeredToday
              }}
              inscription(s) aujourd’hui
            </p>
          </article>

          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Actifs aujourd’hui
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.users
                    .activeToday
              }}
            </strong>

            <p class="admin-stat-detail">
              Joueurs distincts connectés
            </p>
          </article>

          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Voitures
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.cars.total
              }}
            </strong>

            <p class="admin-stat-detail">
              {{
                dashboardStore.dashboard.cars
                    .createdToday
              }}
              créée(s) aujourd’hui
            </p>
          </article>

          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Duels
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.duels.total
              }}
            </strong>

            <p class="admin-stat-detail">
              {{
                dashboardStore.dashboard.duels.today
              }}
              terminé(s) aujourd’hui
            </p>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <div class="admin-section-heading">
          <div>
            <h2>Progression du jour</h2>

            <p>
              Choix et évolution des voitures.
            </p>
          </div>
        </div>

        <div class="admin-stat-grid">
          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Montées de niveau
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.progression
                    .levelUpsToday
              }}
            </strong>
          </article>

          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Cartes obtenues
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.progression
                    .cardsSelectedToday
              }}
            </strong>
          </article>

          <article class="admin-stat-card">
            <p class="admin-stat-label">
              Cartes améliorées
            </p>

            <strong class="admin-stat-value">
              {{
                dashboardStore.dashboard.progression
                    .cardsUpgradedToday
              }}
            </strong>
          </article>
        </div>
      </section>
    </template>
  </main>
</template>

<style scoped>
.admin-dashboard {
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
  line-height: 1.1;
}

.admin-description {
  margin: 8px 0 0;
  opacity: 0.72;
}

.admin-refresh-button {
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 10px;
  background: rgba(127, 127, 127, 0.08);
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
  transition:
      background 0.2s ease,
      opacity 0.2s ease;
}

.admin-refresh-button:hover:not(:disabled) {
  background: rgba(127, 127, 127, 0.16);
}

.admin-refresh-button:disabled {
  cursor: wait;
  opacity: 0.55;
}

.admin-section {
  margin-top: 34px;
}

.admin-section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 16px;
}

.admin-section-heading h2 {
  margin: 0;
  font-size: 1.35rem;
}

.admin-section-heading p {
  margin: 4px 0 0;
  opacity: 0.65;
}

.admin-generation-date {
  font-size: 0.82rem;
  text-align: right;
}

.admin-stat-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.admin-stat-card {
  position: relative;
  min-height: 150px;
  padding: 22px;
  overflow: hidden;
  border: 1px solid rgba(127, 127, 127, 0.24);
  border-radius: 16px;
  background:
      linear-gradient(
          145deg,
          rgba(127, 127, 127, 0.1),
          rgba(127, 127, 127, 0.04)
      );
  box-shadow:
      0 8px 24px rgba(0, 0, 0, 0.08);
  transition:
      transform 0.2s ease,
      box-shadow 0.2s ease;
}

.admin-stat-card::after {
  position: absolute;
  top: -45px;
  right: -45px;
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: rgba(127, 127, 127, 0.1);
  content: '';
}

.admin-stat-card:hover {
  transform: translateY(-2px);
  box-shadow:
      0 12px 28px rgba(0, 0, 0, 0.12);
}

.admin-stat-label {
  position: relative;
  z-index: 1;
  margin: 0;
  font-size: 0.86rem;
  font-weight: 750;
  opacity: 0.65;
}

.admin-stat-value {
  position: relative;
  z-index: 1;
  display: block;
  margin-top: 12px;
  font-size: 2.4rem;
  line-height: 1;
}

.admin-stat-detail {
  position: relative;
  z-index: 1;
  margin: 16px 0 0;
  font-size: 0.88rem;
  opacity: 0.68;
}

.admin-loading {
  padding: 48px;
  border: 1px solid rgba(127, 127, 127, 0.2);
  border-radius: 16px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.admin-alert {
  padding: 18px;
  border: 1px solid;
  border-radius: 12px;
}

.admin-alert p {
  margin: 6px 0 14px;
}

.admin-alert button {
  min-height: 38px;
  padding: 0 14px;
  border: 0;
  border-radius: 8px;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.admin-alert-error {
  border-color: rgba(190, 40, 40, 0.55);
  background: rgba(190, 40, 40, 0.1);
}

@media (max-width: 700px) {
  .admin-dashboard {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header {
    flex-direction: column;
  }

  .admin-refresh-button {
    width: 100%;
  }

  .admin-section-heading {
    display: block;
  }

  .admin-generation-date {
    margin-top: 10px !important;
    text-align: left;
  }

  .admin-stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>