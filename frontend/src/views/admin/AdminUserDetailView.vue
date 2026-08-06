<script setup lang="ts">
import {
  computed,
  onMounted,
  onUnmounted,
} from 'vue'
import {
  RouterLink,
  useRoute,
} from 'vue-router'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminUserDetailStore }
  from '../../stores/adminUserDetail'

const route = useRoute()
const detailStore = useAdminUserDetailStore()

const userId = computed<number | null>(() => {
  const parsedId = Number.parseInt(
      String(route.params.id),
      10,
  )

  return Number.isInteger(parsedId)
      ? parsedId
      : null
})

function formatDate(
    value: string | null,
): string {
  if (value === null) {
    return 'Jamais'
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

function eventLabel(
    type: string,
): string {
  const labels: Record<string, string> = {
    user_registered: 'Inscription',
    login_succeeded: 'Connexion',
    car_created: 'Voiture créée',
    level_up: 'Montée de niveau',
    card_selected: 'Carte obtenue',
    card_upgraded: 'Carte améliorée',
    duel_completed: 'Duel terminé',
  }

  return labels[type] ?? type
}

async function toggleStatus(): Promise<void> {
  const detail = detailStore.detail

  if (detail === null) {
    return
  }

  const action = detail.user.isActive
      ? 'désactiver'
      : 'réactiver'

  const confirmed = window.confirm(
      `Confirmer l’action suivante : ${action} le compte ${detail.user.email} ?`,
  )

  if (!confirmed) {
    return
  }

  try {
    await detailStore.updateStatus(
        !detail.user.isActive,
    )
  } catch {
    // Erreur déjà gérée par le store.
  }
}

onMounted(async (): Promise<void> => {
  if (userId.value === null) {
    return
  }

  await detailStore.loadUser(userId.value)
})

onUnmounted((): void => {
  detailStore.reset()
})
</script>

<template>
  <main class="admin-user-detail">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Fiche utilisateur</h1>

        <p class="admin-description">
          Activité et progression du joueur.
        </p>
      </div>

      <RouterLink
          :to="{ name: 'admin-users' }"
          class="back-link"
      >
        Retour aux utilisateurs
      </RouterLink>
    </header>

    <AdminNavigation />

    <div
        v-if="detailStore.errorMessage !== null"
        class="alert alert-error"
    >
      {{ detailStore.errorMessage }}
    </div>

    <div
        v-else-if="detailStore.isLoading"
        class="loading-panel"
    >
      Chargement de l’utilisateur…
    </div>

    <template v-else-if="detailStore.detail !== null">
      <section class="account-card">
        <div>
          <p class="account-id">
            Compte #{{ detailStore.detail.user.id }}
          </p>

          <h2>
            {{ detailStore.detail.user.email }}
          </h2>

          <div class="badges">
            <span
                class="status-badge"
                :class="{
                active:
                  detailStore.detail.user.isActive,
                disabled:
                  !detailStore.detail.user.isActive,
              }"
            >
              {{
                detailStore.detail.user.isActive
                    ? 'Actif'
                    : 'Désactivé'
              }}
            </span>

            <span
                v-for="role in detailStore.detail.user.roles"
                :key="role"
                class="role-badge"
            >
              {{ role }}
            </span>
          </div>
        </div>

        <button
            type="button"
            class="status-action"
            :class="{
            danger:
              detailStore.detail.user.isActive,
            success:
              !detailStore.detail.user.isActive,
          }"
            :disabled="detailStore.isUpdatingStatus"
            @click="toggleStatus"
        >
          {{
            detailStore.isUpdatingStatus
                ? 'Modification…'
                : detailStore.detail.user.isActive
                    ? 'Désactiver'
                    : 'Réactiver'
          }}
        </button>
      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <span>Voitures</span>
          <strong>
            {{ detailStore.detail.statistics.carCount }}
          </strong>
        </article>

        <article class="stat-card">
          <span>Duels</span>
          <strong>
            {{ detailStore.detail.statistics.duelCount }}
          </strong>
        </article>

        <article class="stat-card">
          <span>Victoires</span>
          <strong>
            {{ detailStore.detail.statistics.wins }}
          </strong>
        </article>

        <article class="stat-card">
          <span>Défaites</span>
          <strong>
            {{ detailStore.detail.statistics.losses }}
          </strong>
        </article>

        <article class="stat-card">
          <span>Dernière connexion</span>

          <strong class="date-value">
            {{
              formatDate(
                  detailStore.detail.statistics
                      .lastLoginAt,
              )
            }}
          </strong>
        </article>
      </section>

      <section class="admin-section">
        <h2>Voitures</h2>

        <div
            v-if="detailStore.detail.cars.length === 0"
            class="empty-panel"
        >
          Aucune voiture.
        </div>

        <div v-else class="cars-grid">
          <article
              v-for="car in detailStore.detail.cars"
              :key="car.id"
              class="car-card"
          >
            <header>
              <span
                  class="car-color"
                  :style="{ backgroundColor: car.color }"
              />

              <div>
                <strong>{{ car.pilotName }}</strong>
                <small>Voiture #{{ car.id }}</small>
              </div>
            </header>

            <dl>
              <div>
                <dt>Niveau</dt>
                <dd>{{ car.level }}</dd>
              </div>

              <div>
                <dt>XP</dt>
                <dd>{{ car.xp }}</dd>
              </div>

              <div>
                <dt>Argent</dt>
                <dd>{{ car.money }}</dd>
              </div>

              <div>
                <dt>Classement</dt>
                <dd>{{ car.rating }}</dd>
              </div>

              <div>
                <dt>Victoires</dt>
                <dd>{{ car.wins }}</dd>
              </div>

              <div>
                <dt>Défaites</dt>
                <dd>{{ car.losses }}</dd>
              </div>
            </dl>

            <div class="car-stats">
              <span>VIT {{ car.stats.speed }}</span>
              <span>ACC {{ car.stats.acceleration }}</span>
              <span>GRIP {{ car.stats.grip }}</span>
              <span>SOL {{ car.stats.solidity }}</span>
            </div>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <h2>Derniers duels</h2>

        <div
            v-if="
            detailStore.detail.recentDuels.length === 0
          "
            class="empty-panel"
        >
          Aucun duel.
        </div>

        <div v-else class="duel-list">
          <article
              v-for="duel in detailStore.detail.recentDuels"
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

            <div>
              <strong>
                {{ duel.userCar.pilotName }}
                contre
                {{ duel.opponentCar.pilotName }}
              </strong>

              <small>
                Duel #{{ duel.id }} —
                {{ formatDate(duel.createdAt) }}
              </small>
            </div>

            <span>
              Écart : {{ duel.finalGap }}
            </span>
          </article>
        </div>
      </section>

      <section class="admin-section">
        <h2>Activité récente</h2>

        <div
            v-if="
            detailStore.detail.recentEvents.length === 0
          "
            class="empty-panel"
        >
          Aucun événement.
        </div>

        <div v-else class="event-list">
          <article
              v-for="event in detailStore.detail.recentEvents"
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

            <span v-if="event.carId !== null">
              Voiture #{{ event.carId }}
            </span>

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
.admin-user-detail {
  width: min(1180px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header,
.account-card {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
}

.admin-header {
  margin-bottom: 24px;
}

.admin-eyebrow,
.account-id {
  margin: 0 0 5px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1,
.account-card h2 {
  margin: 0;
}

.admin-description {
  margin: 8px 0 0;
  opacity: 0.7;
}

.back-link,
.status-action {
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  color: inherit;
  font: inherit;
  font-weight: 700;
  text-decoration: none;
}

.back-link {
  display: inline-flex;
  align-items: center;
}

.account-card {
  align-items: center;
  padding: 24px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 16px;
  background: rgba(127, 127, 127, 0.06);
}

.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-top: 14px;
}

.status-badge,
.role-badge,
.result-badge {
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 750;
}

.status-badge.active,
.result-badge.won {
  background: rgba(40, 160, 90, 0.17);
}

.status-badge.disabled,
.result-badge.lost {
  background: rgba(190, 50, 50, 0.16);
}

.role-badge {
  background: rgba(127, 127, 127, 0.15);
}

.status-action {
  cursor: pointer;
}

.status-action.danger {
  background: rgba(190, 50, 50, 0.1);
}

.status-action.success {
  background: rgba(40, 160, 90, 0.1);
}

.status-action:disabled {
  cursor: wait;
  opacity: 0.5;
}

.stats-grid,
.cars-grid {
  display: grid;
  grid-template-columns:
    repeat(auto-fit, minmax(190px, 1fr));
  gap: 14px;
  margin-top: 24px;
}

.stat-card,
.car-card {
  padding: 20px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.06);
}

.stat-card span {
  display: block;
  font-size: 0.83rem;
  opacity: 0.67;
}

.stat-card strong {
  display: block;
  margin-top: 9px;
  font-size: 2rem;
}

.stat-card .date-value {
  font-size: 1rem;
  line-height: 1.4;
}

.admin-section {
  margin-top: 36px;
}

.car-card header {
  display: flex;
  align-items: center;
  gap: 11px;
}

.car-card header div {
  display: grid;
}

.car-card small,
.duel-row small,
.event-row small {
  opacity: 0.62;
}

.car-color {
  width: 34px;
  height: 34px;
  border: 2px solid rgba(127, 127, 127, 0.3);
  border-radius: 50%;
}

.car-card dl {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin: 18px 0;
}

.car-card dl div {
  padding: 9px;
  border-radius: 8px;
  background: rgba(127, 127, 127, 0.08);
}

.car-card dt {
  font-size: 0.72rem;
  opacity: 0.62;
}

.car-card dd {
  margin: 3px 0 0;
  font-weight: 750;
}

.car-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.car-stats span {
  padding: 5px 7px;
  border-radius: 7px;
  background: rgba(127, 127, 127, 0.12);
  font-size: 0.72rem;
  font-weight: 750;
}

.duel-list,
.event-list {
  display: grid;
  gap: 9px;
}

.duel-row,
.event-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px;
  border: 1px solid rgba(127, 127, 127, 0.18);
  border-radius: 11px;
}

.duel-row div,
.event-row div {
  display: grid;
  flex: 1;
}

.empty-panel,
.loading-panel,
.alert {
  padding: 32px;
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

.alert-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

@media (max-width: 700px) {
  .admin-user-detail {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .account-card {
    flex-direction: column;
  }

  .back-link,
  .status-action {
    width: 100%;
    justify-content: center;
  }

  .duel-row,
  .event-row {
    align-items: flex-start;
    flex-wrap: wrap;
  }
}
</style>