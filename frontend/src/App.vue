<script setup lang="ts">
import {
  RouterLink,
  RouterView,
  useRouter,
} from 'vue-router'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
const router = useRouter()

function logout(): void {
  authStore.logout()

  void router.push({
    name: 'login',
  })
}
</script>

<template>
  <div class="app-shell">
    <header
        v-if="authStore.isAuthenticated"
        class="main-header"
    >
      <RouterLink
          :to="{ name: 'garage' }"
          class="brand"
      >
        Street Rivals
      </RouterLink>

      <nav class="main-navigation">
        <RouterLink
            :to="{ name: 'garage' }"
            class="navigation-link"
        >
          Garage
        </RouterLink>

        <RouterLink
            v-if="authStore.isAdmin"
            :to="{ name: 'admin-dashboard' }"
            class="navigation-link"
        >
          Administration
        </RouterLink>
      </nav>

      <div class="header-user">
        <span class="header-user-email">
          {{ authStore.user?.email }}
        </span>

        <button
            type="button"
            class="button button-secondary"
            @click="logout"
        >
          Déconnexion
        </button>
      </div>
    </header>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>