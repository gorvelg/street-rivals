<script setup lang="ts">
import {
  RouterLink,
} from 'vue-router'
</script>

<template>
  <nav
      class="bottom-navigation"
      aria-label="Navigation principale"
  >
    <!-- =====================================
         GARAGE
    ====================================== -->

    <RouterLink
        :to="{
          name: 'garage',
        }"
        class="navigation-item"
        active-class="navigation-item-active"
    >
      <span class="navigation-icon">
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
          <path
              d="
                M3 13
                L5 8
                C5.4 7 6.3 6.4 7.4 6.4
                H16.6
                C17.7 6.4 18.6 7 19 8
                L21 13
                V18
                H19
                V19
                C19 19.6 18.6 20 18 20
                H17
                C16.4 20 16 19.6 16 19
                V18
                H8
                V19
                C8 19.6 7.6 20 7 20
                H6
                C5.4 20 5 19.6 5 19
                V18
                H3
                Z

                M7 14
                A1.5 1.5 0 1 0 7 17
                A1.5 1.5 0 1 0 7 14

                M17 14
                A1.5 1.5 0 1 0 17 17
                A1.5 1.5 0 1 0 17 14

                M6 12
                H18
                L16.8 9
                C16.7 8.7 16.4 8.5 16 8.5
                H8
                C7.6 8.5 7.3 8.7 7.2 9
                Z
              "
          />
        </svg>
      </span>

      <span class="navigation-label">
        Garage
      </span>
    </RouterLink>

    <!-- =====================================
         DUELS
    ====================================== -->

    <RouterLink
        :to="{
          name: 'duels',
        }"
        class="navigation-item"
        active-class="navigation-item-active"
    >
      <span class="navigation-icon">
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
          <path
              d="
                M7.2 3
                L12 7.8
                L16.8 3
                L19 5.2
                L14.2 10
                L17 12.8
                L18.4 11.4
                L20 13
                L16 17
                L14.4 15.4
                L15.8 14
                L13 11.2
                L10.2 14
                L11.6 15.4
                L10 17
                L6 13
                L7.6 11.4
                L9 12.8
                L11.8 10
                L7 5.2
                Z
              "
          />
        </svg>
      </span>

      <span class="navigation-label">
        Duels
      </span>
    </RouterLink>

    <!-- =====================================
         TOURNOIS
    ====================================== -->

    <RouterLink
        :to="{
          name: 'tournaments',
        }"
        class="navigation-item"
        active-class="navigation-item-active"
    >
      <span class="navigation-icon">
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
          <path
              d="
                M8 3
                H16
                V5
                H20
                V8
                C20 11 18.2 13 15.5 13.7
                C15 15 14.1 16 13 16.5
                V19
                H17
                V21
                H7
                V19
                H11
                V16.5
                C9.9 16 9 15 8.5 13.7
                C5.8 13 4 11 4 8
                V5
                H8
                Z

                M6 7
                V8
                C6 9.6 6.8 10.7 8.1 11.3
                C8 10.9 8 10.5 8 10
                V7
                Z

                M16 7
                V10
                C16 10.5 16 10.9 15.9 11.3
                C17.2 10.7 18 9.6 18 8
                V7
                Z
              "
          />
        </svg>
      </span>

      <span class="navigation-label">
        Tournois
      </span>
    </RouterLink>

    <!-- =====================================
         CLANS
    ====================================== -->

    <RouterLink
        :to="{
          name: 'clans',
        }"
        class="navigation-item"
        active-class="navigation-item-active"
    >
      <span class="navigation-icon">
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
          <path
              d="
                M8 4
                A3 3 0 1 1 8 10
                A3 3 0 1 1 8 4

                M16 5
                A2.5 2.5 0 1 1 16 10
                A2.5 2.5 0 1 1 16 5

                M3 18
                C3 14.7 5.2 12 8 12
                C10.8 12 13 14.7 13 18
                V20
                H3
                Z

                M14 19
                V18
                C14 15.9 13.3 14.2 12.2 13
                C13.3 12.3 14.5 12 16 12
                C18.8 12 21 14.4 21 17.5
                V19
                Z
              "
          />
        </svg>
      </span>

      <span class="navigation-label">
        Clans
      </span>
    </RouterLink>

    <!-- =====================================
         CHAT
    ====================================== -->

    <RouterLink
        :to="{
          name: 'chat',
        }"
        class="navigation-item"
        active-class="navigation-item-active"
    >
      <span class="navigation-icon">
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
          <path
              d="
                M4 4
                H20
                C21.1 4 22 4.9 22 6
                V16
                C22 17.1 21.1 18 20 18
                H9
                L4 22
                V18
                C2.9 18 2 17.1 2 16
                V6
                C2 4.9 2.9 4 4 4

                M7 9
                A1 1 0 1 0 7 11
                A1 1 0 1 0 7 9

                M12 9
                A1 1 0 1 0 12 11
                A1 1 0 1 0 12 9

                M17 9
                A1 1 0 1 0 17 11
                A1 1 0 1 0 17 9
              "
          />
        </svg>
      </span>

      <span class="navigation-label">
        Chat
      </span>
    </RouterLink>
  </nav>
</template>

<style scoped>
.bottom-navigation {
  position: fixed;

  left: 50%;
  bottom:
      calc(
          10px
          + env(safe-area-inset-bottom)
      );

  z-index: 100;

  display: grid;

  grid-template-columns:
      repeat(
          5,
          minmax(0, 1fr)
      );

  width:
      min(
          calc(100% - 20px),
          620px
      );

  padding:
      7px;

  border:
      1px solid
      rgba(
          255,
          255,
          255,
          0.1
      );

  border-radius: 22px;

  background:
      rgba(
          20,
          22,
          26,
          0.94
      );

  box-shadow:
      0
      14px
      45px
      rgba(
          0,
          0,
          0,
          0.42
      );

  backdrop-filter:
      blur(18px);

  transform:
      translateX(-50%);
}

.navigation-item {
  position: relative;

  display: flex;

  min-width: 0;
  min-height: 58px;

  flex-direction: column;

  align-items: center;
  justify-content: center;

  gap: 3px;

  border-radius: 16px;

  color: inherit;

  text-decoration: none;

  opacity: 0.52;

  transition:
      opacity
      150ms ease,
      background
      150ms ease,
      transform
      150ms ease;
}

.navigation-item:hover {
  opacity: 0.85;

  background:
      rgba(
          255,
          255,
          255,
          0.04
      );
}

.navigation-item-active {
  opacity: 1;

  background:
      rgba(
          255,
          255,
          255,
          0.08
      );
}

.navigation-item-active::after {
  content: '';

  position: absolute;

  bottom: 3px;
  left: 50%;

  width: 22px;
  height: 3px;

  border-radius: 999px;

  background: currentColor;

  transform:
      translateX(-50%);
}

.navigation-icon {
  display: flex;

  width: 24px;
  height: 24px;

  align-items: center;
  justify-content: center;
}

.navigation-icon svg {
  width: 100%;
  height: 100%;

  fill: currentColor;
}

.navigation-label {
  overflow: hidden;

  max-width: 100%;

  font-size: 0.68rem;
  font-weight: 700;

  text-overflow: ellipsis;

  white-space: nowrap;
}

@media (
max-width: 430px
) {
  .bottom-navigation {
    width:
        calc(
            100% - 14px
        );

    bottom:
        calc(
            7px
            + env(safe-area-inset-bottom)
        );

    padding: 5px;

    border-radius: 19px;
  }

  .navigation-item {
    min-height: 54px;

    border-radius: 14px;
  }

  .navigation-icon {
    width: 22px;
    height: 22px;
  }

  .navigation-label {
    font-size: 0.61rem;
  }
}

@media (
min-width: 900px
) {
  .bottom-navigation {
    bottom: 18px;
  }

  .navigation-item {
    min-height: 62px;
  }
}
</style>