<template>
  <div class="auth-page modern-auth">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goHome">
          Car Tracker
        </button>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goHome">Home</button>
          <button class="work-link" type="button" @click="goRegister">Register</button>
        </nav>

        <div class="work-auth" />
      </div>
    </header>

    <main class="auth-page-main">
      <div class="auth-modern-shell">
        <section class="work-panel auth-modern-panel">
          <div class="work-kicker">Garage access</div>
          <h1 class="work-title auth-modern-title">{{ t('auth.loginTitle') }}</h1>
          <p class="work-subtitle auth-modern-subtitle">{{ t('auth.continue') }}</p>

          <v-alert
            v-if="auth.error"
            type="error"
            variant="tonal"
            class="mt-4 mb-3"
          >
            {{ auth.error }}
          </v-alert>

          <v-form class="mt-4" @submit.prevent="submit">
            <v-text-field
              v-model="login"
              :label="t('auth.login')"
              autocomplete="username"
              required
            />

            <v-text-field
              v-model="password"
              :label="t('auth.password')"
              type="password"
              autocomplete="current-password"
              required
            />

            <div class="auth-actions-row">
              <v-btn
                type="submit"
                class="ui-btn-primary auth-submit-btn"
                :loading="auth.loading"
              >
                {{ t('auth.signIn') }}
              </v-btn>

              <v-btn
                class="ui-btn-secondary"
                variant="text"
                @click="goRegister"
              >
                Register
              </v-btn>
            </div>
          </v-form>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

const login = ref('')
const password = ref('')

onMounted(() => {
  auth.clearError()
})

const goHome = () => router.push({ name: 'home' })
const goRegister = () => router.push({ name: 'register' })

const submit = async () => {
  try {
    await auth.login({
      login: login.value,
      password: password.value,
      device_name: 'web-client'
    })

    router.push({ name: 'dashboard' })
  } catch (_) {
  }
}
</script>