<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <nav class="admin-navigation">
    <RouterLink
        :to="{ name: 'admin-dashboard' }"
        class="admin-navigation-link"
    >
      Dashboard
    </RouterLink>

    <RouterLink
        :to="{ name: 'admin-users' }"
        class="admin-navigation-link"
    >
      Utilisateurs
    </RouterLink>

    <span class="admin-navigation-link disabled">
      Voitures
    </span>

    <span class="admin-navigation-link disabled">
      Duels
    </span>

    <span class="admin-navigation-link disabled">
      Événements
    </span>

    <span class="admin-navigation-link disabled">
      Cartes
    </span>
  </nav>
</template>

<style scoped>
.admin-navigation {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  margin-bottom: 32px;
  padding-bottom: 4px;
}

.admin-navigation-link {
  flex: 0 0 auto;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(127, 127, 127, 0.12);
  color: inherit;
  font-size: 0.9rem;
  font-weight: 700;
  text-decoration: none;
}

.admin-navigation-link:hover {
  background: rgba(127, 127, 127, 0.2);
}

.admin-navigation-link.router-link-active {
  background: currentColor;
  color: white;
}

.admin-navigation-link.disabled {
  cursor: not-allowed;
  opacity: 0.4;
}
</style>