<script setup lang="ts">
import type { Car } from '../types/api'

defineProps<{
  car: Car
  selected: boolean
}>()

defineEmits<{
  select: [carId: number]
}>()
</script>

<template>
  <button
      type="button"
      class="garage-car"
      :class="{
      'garage-car-selected': selected,
    }"
      @click="$emit('select', car.id)"
  >
    <div
        class="car-color"
        :style="{
        backgroundColor: car.color,
      }"
    />

    <div class="garage-car-content">
      <div class="garage-car-header">
        <strong>{{ car.pilotName }}</strong>

        <span class="level-badge">
          Niveau {{ car.level }}
        </span>
      </div>

      <div class="garage-car-details">
        <span>{{ car.money }} $</span>
        <span>{{ car.rating ?? 1000 }} Elo</span>
        <span>
          {{ car.wins ?? 0 }} V /
          {{ car.losses ?? 0 }} D
        </span>
      </div>
    </div>
  </button>
</template>