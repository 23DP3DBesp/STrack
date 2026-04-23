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
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
              @click:append-inner="showPassword = !showPassword"
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

            <div
              v-if="auth.error === t('auth.verifyFirst')"
              class="auth-inline-note"
            >
              <span>{{ t('auth.needVerificationHint') }}</span>
              <button type="button" class="auth-inline-link" @click="resendVerification">
                {{ t('auth.resendVerification') }}
              </button>
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
const showPassword = ref(false)

onMounted(() => {
  auth.clearError()
})

const goHome = () => router.push({ name: 'home' })
const goRegister = () => router.push({ name: 'register' })

const resendVerification = async () => {
  try {
    await auth.resendVerificationEmail({ login: login.value })

    router.push({
      name: 'verify-email',
      query: {
        status: 'resent',
        login: login.value
      }
    })
  } catch (_) {
  }
}

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
