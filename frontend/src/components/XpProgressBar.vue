<script setup lang="ts">
import {
  computed,
} from 'vue'

const props =
    defineProps<{
      currentXp: number
      requiredXp: number
    }>()

const progress =
    computed(() => {
      if (
          props.requiredXp <= 0
      ) {
        return 0
      }

      return Math.min(
          100,
          Math.max(
              0,
              (
                  props.currentXp
                  / props.requiredXp
              )
              * 100,
          ),
      )
    })
</script>

<template>
  <div class="xp-progress">
    <div class="xp-progress-header">
      <span>
        XP
      </span>

      <strong>
        {{ currentXp }}
        /
        {{ requiredXp }}
      </strong>
    </div>

    <div
        class="xp-progress-track"
        role="progressbar"
        aria-label="Progression XP"
        :aria-valuenow="currentXp"
        aria-valuemin="0"
        :aria-valuemax="requiredXp"
    >
      <div
          class="xp-progress-fill"
          :style="{
            width:
              `${progress}%`,
          }"
      />
    </div>
  </div>
</template>

<style scoped>
.xp-progress {
  display: grid;

  width: 100%;

  gap: 6px;
}

.xp-progress-header {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 10px;

  font-size: 0.62rem;
}

.xp-progress-header span {
  font-weight: 850;

  letter-spacing: 0.08em;

  text-transform: uppercase;

  opacity: 0.5;
}

.xp-progress-header strong {
  font-size: 0.62rem;

  font-variant-numeric:
      tabular-nums;
}

.xp-progress-track {
  position: relative;

  width: 100%;
  height: 8px;

  overflow: hidden;

  border-radius: 999px;

  background:
      rgba(
          255,
          255,
          255,
          0.08
      );
}

.xp-progress-fill {
  height: 100%;

  border-radius: inherit;

  background:
      linear-gradient(
          90deg,
          rgba(
              100,
              145,
              245,
              0.9
          ),
          rgba(
              145,
              110,
              245,
              1
          )
      );

  transition:
      width
      450ms
      ease;
}

@media (
prefers-reduced-motion:
    reduce
) {
  .xp-progress-fill {
    transition: none;
  }
}
</style>