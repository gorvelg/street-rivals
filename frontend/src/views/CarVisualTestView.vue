<script setup lang="ts">
import { ref } from 'vue'
import CarVisual from '../components/CarVisual.vue'
import type {
  CarWheelStyle,
} from '../types/api'

const color = ref('#d9343a')

const wheelStyle =
    ref<CarWheelStyle>('street_01')
</script>

<template>
  <main class="test-page">
    <h1>Test voiture</h1>

    <div class="car-container">
      <CarVisual
          :color="color"
          body-style="coupe_01"
          :wheel-style="wheelStyle"
          pilot-name="Raven"
      />
    </div>

    <div class="controls">
      <label>
        Couleur

        <input
            v-model="color"
            type="color"
        >
      </label>

      <label>
        Jantes

        <select v-model="wheelStyle">
          <option value="street_01">
            Street
          </option>

          <option value="five_spoke">
            5 branches
          </option>

          <option value="multi_spoke">
            Multi-branches
          </option>
        </select>
      </label>
    </div>
  </main>
</template>

<style scoped>
.test-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 40px;
}

.car-container {
  margin: 40px 0;
  padding: 30px;

  border-radius: 16px;
  background: #24272b;
}

.controls {
  display: flex;
  gap: 30px;
}

.controls label {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>