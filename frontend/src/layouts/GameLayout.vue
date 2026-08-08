<script setup lang="ts">
import {
  RouterView,
} from 'vue-router'

import AppBottomNavigation
  from '../components/navigation/AppBottomNavigation.vue'
</script>

<template>
  <div class="game-layout">
    <div class="game-layout-content">
      <RouterView />
    </div>

    <AppBottomNavigation />
  </div>
</template>

<style scoped>
.game-layout {
  min-height: 100%;
}

/*
 * Espace réservé à la barre fixe.
 *
 * env(safe-area-inset-bottom) permet
 * notamment de gérer proprement les iPhone.
 */
.game-layout-content {
  padding-bottom:
      calc(
          108px
          + env(safe-area-inset-bottom)
      );
}

@media (
min-width: 900px
) {
  .game-layout-content {
    padding-bottom: 118px;
  }
}
</style>