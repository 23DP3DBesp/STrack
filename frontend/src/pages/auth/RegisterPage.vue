<template>
  <div class="auth-page modern-auth">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goHome">
          Car Tracker
        </button>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goHome">Home</button>
          <button class="work-link" type="button" @click="goLogin">Login</button>
        </nav>

        <div class="work-auth" />
      </div>
    </header>

    <main class="auth-page-main">
      <div class="auth-modern-shell">
        <section class="work-panel auth-modern-panel">
          <div class="work-kicker">Create driver profile</div>
          <h1 class="work-title auth-modern-title">{{ t('auth.registerTitle') }}</h1>
          <p class="work-subtitle auth-modern-subtitle">{{ t('auth.create') }}</p>

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
              v-model="email"
              :label="t('auth.email')"
              type="email"
              autocomplete="email"
              required
            />

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
              autocomplete="new-password"
              :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
              @click:append-inner="showPassword = !showPassword"
              required
            />

            <v-text-field
              v-model="passwordConfirmation"
              :label="t('auth.passwordConfirm')"
              :type="showPasswordConfirmation ? 'text' : 'password'"
              autocomplete="new-password"
              :append-inner-icon="showPasswordConfirmation ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
              @click:append-inner="showPasswordConfirmation = !showPasswordConfirmation"
              required
            />

            <div class="auth-actions-row">
              <v-btn
                type="submit"
                class="ui-btn-primary auth-submit-btn"
                :loading="auth.loading"
              >
                {{ t('auth.signUp') }}
              </v-btn>

              <v-btn
                class="ui-btn-secondary"
                variant="text"
                @click="goLogin"
              >
                Login
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

const email = ref('')
const login = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

onMounted(() => {
  auth.clearError()
})

const goHome = () => router.push({ name: 'home' })
const goLogin = () => router.push({ name: 'login' })

const submit = async () => {
  try {
    const response = await auth.register({
      email: email.value,
      login: login.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })

    router.push({
      name: 'verify-email',
      query: {
        status: 'notice',
        email: email.value,
        emailSent: response?.verification_email_sent ? '1' : '0'
      }
    })
  } catch (_) {
  }
}
</script>
