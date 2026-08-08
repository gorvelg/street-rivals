<script setup lang="ts">
import {
  computed,
} from 'vue'

import {
  RouterLink,
  RouterView,
  useRoute,
  useRouter,
} from 'vue-router'

import {
  useAuthStore,
} from './stores/auth'

const authStore =
    useAuthStore()

const router =
    useRouter()

const route =
    useRoute()

const showHeader =
    computed(
        () =>
            authStore.isAuthenticated
            && route.meta.immersive
            !== true,
    )

function logout():
    void {
  authStore.logout()

  void router.push({
    name: 'login',
  })
}
</script>

<template>
  <div
      class="app-shell"
      :class="{
        'app-shell-immersive':
          route.meta.immersive
          === true,
      }"
  >
    <!-- =====================================
         HEADER
    ====================================== -->

    <header
        v-if="
          showHeader
        "
        class="main-header"
    >
      <RouterLink
          :to="{
            name: 'garage',
          }"
          class="brand"
      >
        <span class="brand-main">
          STREET
        </span>

        <span class="brand-accent">
          RIVALS
        </span>
      </RouterLink>

      <div class="header-actions">
        <RouterLink
            v-if="
              authStore.isAdmin
            "
            :to="{
              name:
                'admin-dashboard',
            }"
            class="
              header-admin-link
            "
        >
          Administration
        </RouterLink>

        <span
            class="
              header-user-email
            "
        >
          {{
            authStore.user
                ?.email
          }}
        </span>

        <button
            type="button"
            class="
              button
              button-secondary
              logout-button
            "
            @click="
              logout
            "
        >
          <span
              class="
                logout-label
              "
          >
            Déconnexion
          </span>

          <span
              class="
                logout-short-label
              "
          >
            Quitter
          </span>
        </button>
      </div>
    </header>

    <!-- =====================================
         ROUTES
    ====================================== -->

    <main
        class="main-content"
        :class="{
          'main-content-immersive':
            route.meta.immersive
            === true,
        }"
    >
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100dvh;
}

/*
 * =====================================
 * HEADER
 * =====================================
 */

.main-header {
  display: flex;

  min-height: 66px;

  align-items: center;

  justify-content:
      space-between;

  gap: 24px;

  padding:
      10px
      max(
          18px,
          calc(
              (
              100vw
              - 1280px
              )
              / 2
          )
      );

  border-bottom:
      1px solid
      rgba(
          255,
          255,
          255,
          0.07
      );

  background:
      rgba(
          15,
          17,
          20,
          0.75
      );

  backdrop-filter:
      blur(14px);
}

.brand {
  display: inline-flex;

  align-items: center;

  gap: 5px;

  color: inherit;

  font-size: 1rem;
  font-weight: 950;

  letter-spacing: 0.08em;

  text-decoration: none;
}

.brand-accent {
  opacity: 0.48;
}

.header-actions {
  display: flex;

  min-width: 0;

  align-items: center;

  gap: 12px;
}

.header-admin-link {
  padding:
      7px
      10px;

  border-radius: 9px;

  color: inherit;

  font-size: 0.75rem;
  font-weight: 700;

  text-decoration: none;

  opacity: 0.65;
}

.header-admin-link:hover {
  background:
      rgba(
          255,
          255,
          255,
          0.06
      );

  opacity: 1;
}

.header-user-email {
  overflow: hidden;

  max-width: 220px;

  font-size: 0.75rem;

  opacity: 0.5;

  text-overflow:
      ellipsis;

  white-space: nowrap;
}

.logout-short-label {
  display: none;
}

/*
 * =====================================
 * CONTENU
 * =====================================
 */

.main-content {
  min-height:
      calc(
          100dvh
          - 66px
      );
}

.main-content-immersive {
  min-height: 100dvh;
}

/*
 * =====================================
 * MOBILE
 * =====================================
 */

@media (
max-width: 700px
) {
  .main-header {
    min-height: 56px;

    gap: 10px;

    padding:
        8px
        12px;
  }

  .brand {
    font-size: 0.82rem;
  }

  .header-actions {
    gap: 7px;
  }

  .header-user-email {
    display: none;
  }

  .header-admin-link {
    padding:
        6px
        8px;

    font-size: 0.68rem;
  }

  .logout-button {
    padding:
        7px
        9px;
  }

  .logout-label {
    display: none;
  }

  .logout-short-label {
    display: inline;
  }

  .main-content {
    min-height:
        calc(
            100dvh
            - 56px
        );
  }
}

@media (
max-width: 400px
) {
  .header-admin-link {
    font-size: 0;

    padding:
        7px
        8px;
  }

  .header-admin-link::after {
    content: 'Admin';

    font-size: 0.65rem;
  }
}
</style>