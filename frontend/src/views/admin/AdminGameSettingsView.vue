<script setup lang="ts">
import {
  onMounted,
  onUnmounted,
} from 'vue'
import AdminNavigation
  from '../../components/admin/AdminNavigation.vue'
import { useAdminGameSettingsStore }
  from '../../stores/adminGameSettings'

const settingsStore =
    useAdminGameSettingsStore()

onMounted(async (): Promise<void> => {
  await settingsStore.loadSettings()
})

onUnmounted((): void => {
  settingsStore.reset()
})
</script>

<template>
  <main class="admin-settings">
    <header class="admin-header">
      <div>
        <p class="admin-eyebrow">
          Street Rivals
        </p>

        <h1>Paramètres du gameplay</h1>

        <p class="admin-description">
          Configuration des limites et délais
          appliqués aux duels.
        </p>
      </div>

      <button
          type="button"
          :disabled="settingsStore.isLoading"
          @click="settingsStore.loadSettings"
      >
        Actualiser
      </button>
    </header>

    <AdminNavigation />

    <div
        v-if="settingsStore.errorMessage !== null"
        class="message message-error"
    >
      {{ settingsStore.errorMessage }}
    </div>

    <div
        v-if="settingsStore.successMessage !== null"
        class="message message-success"
    >
      {{ settingsStore.successMessage }}
    </div>

    <div
        v-if="
        settingsStore.isLoading
        && settingsStore.settings.length === 0
      "
        class="loading-panel"
    >
      Chargement des paramètres…
    </div>

    <form
        v-else
        class="settings-form"
        @submit.prevent="
        settingsStore.saveSettings
      "
    >
      <article
          v-for="setting in settingsStore.settings"
          :key="setting.key"
          class="setting-card"
      >
        <div class="setting-description">
          <div>
            <h2>
              {{ setting.label }}
            </h2>

            <code>
              {{ setting.key }}
            </code>
          </div>

          <p>
            {{ setting.description }}
          </p>

          <small>
            Valeur par défaut :
            {{ setting.defaultValue }}
            {{ setting.unit }}
          </small>
        </div>

        <label class="setting-input">
          <span>Valeur actuelle</span>

          <div>
            <input
                v-model.number="
                settingsStore.draftValues[
                  setting.key
                ]
              "
                type="number"
                :min="setting.minimum"
                :max="setting.maximum"
                step="1"
                :disabled="settingsStore.isSaving"
            >

            <span>
              {{ setting.unit }}
            </span>
          </div>

          <small>
            Minimum {{ setting.minimum }},
            maximum {{ setting.maximum }}
          </small>
        </label>
      </article>

      <footer class="form-actions">
        <button
            type="button"
            :disabled="
            settingsStore.isSaving
            || !settingsStore.isDirty
          "
            @click="settingsStore.resetDraft"
        >
          Annuler les modifications
        </button>

        <button
            type="submit"
            class="save-button"
            :disabled="
            settingsStore.isSaving
            || !settingsStore.isDirty
          "
        >
          {{
            settingsStore.isSaving
                ? 'Enregistrement…'
                : 'Enregistrer'
          }}
        </button>
      </footer>
    </form>
  </main>
</template>

<style scoped>
.admin-settings {
  width: min(1000px, calc(100% - 32px));
  margin: 0 auto;
  padding: 32px 0 64px;
}

.admin-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 24px;
}

.admin-eyebrow {
  margin: 0 0 4px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  opacity: 0.65;
}

.admin-header h1 {
  margin: 0;
}

.admin-description {
  margin: 8px 0 0;
  opacity: 0.7;
}

button {
  min-height: 42px;
  padding: 0 15px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: rgba(127, 127, 127, 0.09);
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.48;
}

.settings-form {
  display: grid;
  gap: 14px;
}

.setting-card {
  display: grid;
  grid-template-columns:
    minmax(0, 1fr)
    minmax(220px, 300px);
  align-items: center;
  gap: 25px;
  padding: 20px;
  border: 1px solid rgba(127, 127, 127, 0.22);
  border-radius: 14px;
  background: rgba(127, 127, 127, 0.05);
}

.setting-description h2 {
  margin: 0;
  font-size: 1.08rem;
}

.setting-description code {
  display: inline-block;
  margin-top: 5px;
  opacity: 0.6;
}

.setting-description p {
  margin: 11px 0 7px;
  opacity: 0.75;
}

.setting-description small,
.setting-input small {
  opacity: 0.62;
}

.setting-input {
  display: grid;
  gap: 7px;
}

.setting-input > span {
  font-size: 0.78rem;
  font-weight: 750;
}

.setting-input div {
  display: flex;
  align-items: center;
  gap: 9px;
}

.setting-input input {
  width: 100%;
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid rgba(127, 127, 127, 0.35);
  border-radius: 9px;
  background: transparent;
  color: inherit;
  font: inherit;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 10px;
}

.save-button {
  border-color: rgba(40, 120, 210, 0.5);
  background: rgba(40, 120, 210, 0.15);
}

.message,
.loading-panel {
  margin-bottom: 16px;
  padding: 16px;
  border-radius: 10px;
}

.message-error {
  border: 1px solid rgba(190, 50, 50, 0.45);
  background: rgba(190, 50, 50, 0.1);
}

.message-success {
  border: 1px solid rgba(40, 160, 90, 0.4);
  background: rgba(40, 160, 90, 0.1);
}

.loading-panel {
  background: rgba(127, 127, 127, 0.08);
  text-align: center;
}

@media (max-width: 650px) {
  .admin-settings {
    width: min(100% - 20px, 1000px);
  }

  .admin-header,
  .setting-card {
    grid-template-columns: 1fr;
    flex-direction: column;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions button {
    width: 100%;
  }
}
</style>