<script setup lang="ts">
import { onMounted } from 'vue'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminUsersStore }
  from '../../stores/adminUsers'

const usersStore = useAdminUsersStore()

function isAdmin(roles: string[]): boolean {
  return roles.includes('ROLE_ADMIN')
}

onMounted(async (): Promise<void> => {
  await usersStore.loadUsers()
})
</script>

<template>
  <main class="admin-users">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Utilisateurs</h1>

        <p class="admin-description">
          Comptes enregistrés pendant l’alpha.
        </p>
      </div>

      <button
          type="button"
          class="refresh-button"
          :disabled="usersStore.isLoading"
          @click="
          usersStore.loadUsers(
            usersStore.pagination.page,
          )
        "
      >
        {{
          usersStore.isLoading
              ? 'Actualisation...'
              : 'Actualiser'
        }}
      </button>
    </header>

    <AdminNavigation />

    <form
        class="search-form"
        @submit.prevent="usersStore.submitSearch"
    >
      <label class="search-field">
        <span>Rechercher par adresse e-mail</span>

        <input
            v-model="usersStore.search"
            type="search"
            placeholder="exemple@email.fr"
            autocomplete="off"
        >
      </label>

      <button
          type="submit"
          class="search-button"
          :disabled="usersStore.isLoading"
      >
        Rechercher
      </button>

      <button
          v-if="usersStore.search.trim() !== ''"
          type="button"
          class="clear-button"
          :disabled="usersStore.isLoading"
          @click="usersStore.clearSearch"
      >
        Effacer
      </button>
    </form>

    <div
        v-if="usersStore.errorMessage !== null"
        class="alert alert-error"
    >
      <strong>Erreur</strong>

      <p>
        {{ usersStore.errorMessage }}
      </p>

      <button
          type="button"
          @click="usersStore.loadUsers(1)"
      >
        Réessayer
      </button>
    </div>

    <div
        v-else-if="
        usersStore.isLoading
        && usersStore.users.length === 0
      "
        class="loading-panel"
    >
      Chargement des utilisateurs…
    </div>

    <section v-else class="users-section">
      <div class="section-summary">
        <p>
          <strong>
            {{ usersStore.pagination.totalItems }}
          </strong>
          utilisateur(s)
        </p>

        <p>
          Page
          {{ usersStore.pagination.page }}
          sur
          {{ usersStore.pagination.totalPages }}
        </p>
      </div>

      <div
          v-if="usersStore.users.length === 0"
          class="empty-panel"
      >
        Aucun utilisateur ne correspond à la recherche.
      </div>

      <div v-else class="table-container">
        <table class="users-table">
          <thead>
          <tr>
            <th>ID</th>
            <th>Adresse e-mail</th>
            <th>Type de compte</th>
            <th>Voitures</th>
          </tr>
          </thead>

          <tbody>
          <tr
              v-for="user in usersStore.users"
              :key="user.id"
          >
            <td class="identifier-cell">
              #{{ user.id }}
            </td>

            <td>
              {{ user.email }}
            </td>

            <td>
                <span
                    v-if="isAdmin(user.roles)"
                    class="role-badge role-admin"
                >
                  Administrateur
                </span>

              <span
                  v-else
                  class="role-badge"
              >
                  Joueur
                </span>
            </td>

            <td>
              {{ user.carCount }}
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <footer class="pagination">
        <button
            type="button"
            :disabled="
            usersStore.isLoading
            || usersStore.pagination.page <= 1
          "
            @click="usersStore.previousPage"
        >
          Page précédente
        </button>

        <span>
          {{
            usersStore.pagination.page
          }}
          /
          {{
            usersStore.pagination.totalPages
          }}
        </span>

        <button
            type="button"
            :disabled="
            usersStore.isLoading
            || usersStore.pagination.page
              >= usersStore.pagination.totalPages
          "
            @click="usersStore.nextPage"
        >
          Page suivante
        </button>
      </footer>
    </section>
  </main>
</template>

<style scoped>
.admin-users {
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
.search-button,
.clear-button,
.pagination button {
  min-height: 42px;
  padding: 0 16px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.1);
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.refresh-button:disabled,
.search-button:disabled,
.clear-button:disabled,
.pagination button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.search-form {
  display: flex;
  align-items: end;
  gap: 10px;
  margin-bottom: 24px;
  padding: 18px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.06);
}

.search-field {
  display: grid;
  flex: 1;
  gap: 7px;
}

.search-field span {
  font-size: 0.82rem;
  font-weight: 700;
  opacity: 0.68;
}

.search-field input {
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

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 15px 17px;
  border-bottom: 1px solid rgba(127, 127, 127, 0.16);
  text-align: left;
}

.users-table th {
  font-size: 0.78rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  opacity: 0.62;
}

.users-table tbody tr:last-child td {
  border-bottom: 0;
}

.identifier-cell {
  font-family: monospace;
  opacity: 0.65;
}

.role-badge {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 999px;
  background: rgba(127, 127, 127, 0.15);
  font-size: 0.78rem;
  font-weight: 750;
}

.role-admin {
  background: rgba(170, 110, 20, 0.18);
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
  border: 1px solid rgba(190, 40, 40, 0.5);
  background: rgba(190, 40, 40, 0.1);
}

.alert p {
  margin: 8px 0 14px;
}

@media (max-width: 700px) {
  .admin-users {
    width: min(100% - 20px, 1180px);
    padding-top: 20px;
  }

  .admin-header,
  .search-form {
    flex-direction: column;
    align-items: stretch;
  }

  .refresh-button {
    width: 100%;
  }

  .section-summary {
    display: block;
  }

  .section-summary p + p {
    margin-top: 5px;
  }
}
</style>