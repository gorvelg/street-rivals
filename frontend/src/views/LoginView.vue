<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ApiError } from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

const mode = ref<'login' | 'register'>('login')

const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errorMessage = ref('')

const title = computed(() =>
    mode.value === 'login'
        ? 'Connexion'
        : 'Créer un compte',
)

async function submit(): Promise<void> {
  errorMessage.value = ''

  if (
      mode.value === 'register' &&
      password.value !== passwordConfirmation.value
  ) {
    errorMessage.value =
        'Les deux mots de passe ne correspondent pas.'

    return
  }

  try {
    if (mode.value === 'login') {
      await authStore.login(email.value, password.value)
    } else {
      await authStore.register(email.value, password.value)
    }

    const redirect =
        typeof route.query.redirect === 'string'
            ? route.query.redirect
            : '/garage'

    await router.push(redirect)
  } catch (error) {
    if (error instanceof ApiError) {
      errorMessage.value = error.message
    } else {
      errorMessage.value =
          'Une erreur inattendue est survenue.'
    }
  }
}

function switchMode(): void {
  mode.value =
      mode.value === 'login'
          ? 'register'
          : 'login'

  errorMessage.value = ''
  password.value = ''
  passwordConfirmation.value = ''
}
</script>

<template>
  <section class="auth-page">
    <div class="auth-card">
      <p class="eyebrow">Street Rivals</p>

      <h1>{{ title }}</h1>

      <p class="muted">
        Prépare ta voiture et affronte les autres pilotes.
      </p>

      <form
          class="form-stack"
          @submit.prevent="submit"
      >
        <label>
          Adresse e-mail

          <input
              v-model.trim="email"
              type="email"
              autocomplete="email"
              required
          />
        </label>

        <label>
          Mot de passe

          <input
              v-model="password"
              type="password"
              :autocomplete="
              mode === 'login'
                ? 'current-password'
                : 'new-password'
            "
              minlength="8"
              required
          />
        </label>

        <label v-if="mode === 'register'">
          Confirmer le mot de passe

          <input
              v-model="passwordConfirmation"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
          />
        </label>

        <p
            v-if="errorMessage !== ''"
            class="alert alert-error"
        >
          {{ errorMessage }}
        </p>

        <button
            type="submit"
            class="button button-primary button-full"
            :disabled="authStore.loading"
        >
          {{
            authStore.loading
                ? 'Chargement...'
                : title
          }}
        </button>
      </form>

      <button
          type="button"
          class="button-link"
          @click="switchMode"
      >
        {{
          mode === 'login'
              ? 'Créer un nouveau compte'
              : 'J’ai déjà un compte'
        }}
      </button>
    </div>
  </section>
</template>