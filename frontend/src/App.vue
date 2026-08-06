<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()
const router = useRouter()

function logout(): void {
  authStore.logout()
  router.push({
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
          to="/garage"
          class="brand"
      >
        Street Rivals
      </RouterLink>

      <div class="header-user">
        <span>{{ authStore.user?.email }}</span>

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