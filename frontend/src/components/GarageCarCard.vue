<script setup lang="ts">
import CarVisual
  from './CarVisual.vue'

import type {
  Car,
} from '../types/api'

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
        'garage-car-selected':
          selected,
      }"
      @click="
        $emit(
          'select',
          car.id,
        )
      "
  >
    <div class="garage-car-visual">
      <CarVisual
          :color="car.color"
          :body-style="
            car.bodyStyle
          "
          :wheel-style="
            car.wheelStyle
          "
          :pilot-name="
            car.pilotName
          "
      />
    </div>

    <div class="garage-car-content">
      <div class="garage-car-header">
        <strong>
          {{ car.pilotName }}
        </strong>

        <span class="level-badge">
          Niveau {{ car.level }}
        </span>
      </div>

      <div class="garage-car-details">
        <span>
          {{ car.money }} $
        </span>

        <span>
          {{ car.rating ?? 1000 }}
          Elo
        </span>

        <span>
          {{ car.wins ?? 0 }} V /
          {{ car.losses ?? 0 }} D
        </span>
      </div>
    </div>
  </button>
</template>

<style scoped>
.garage-car {
  width: 100%;

  overflow: hidden;

  text-align: left;
}

.garage-car-visual {
  width: 100%;
  max-width: 260px;

  margin:
      0
      auto
      8px;

  transition:
      transform
      160ms
      ease;
}

.garage-car:hover
.garage-car-visual {
  transform:
      translateY(-2px)
      scale(1.02);
}

.garage-car-selected
.garage-car-visual {
  transform:
      scale(1.03);
}

.garage-car-content {
  display: grid;
  gap: 8px;
}

.garage-car-header {
  display: flex;
  align-items: center;
  justify-content:
      space-between;

  gap: 10px;
}

.garage-car-details {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 12px;

  font-size: 0.8rem;

  opacity: 0.72;
}
</style>