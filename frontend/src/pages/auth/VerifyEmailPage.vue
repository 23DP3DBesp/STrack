<template>
  <div class="auth-page modern-auth">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goHome">Car Tracker</button>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goHome">Home</button>
          <button class="work-link" type="button" @click="goLogin">{{ t('nav.login') }}</button>
        </nav>

        <div class="work-auth" />
      </div>
    </header>

    <main class="auth-page-main">
      <div class="auth-modern-shell">
        <section class="work-panel auth-modern-panel">
          <div class="work-kicker">Email confirmation</div>
          <h1 class="work-title auth-modern-title">{{ t('auth.verifyEmailTitle') }}</h1>
          <p class="work-subtitle auth-modern-subtitle">{{ statusMessage }}</p>

          <v-alert
            v-if="route.query.email || auth.pendingVerificationEmail"
            type="info"
            variant="tonal"
            class="mt-4 mb-3"
          >
            {{ activeEmail }}
          </v-alert>

          <v-alert v-if="showMailtrapFallback" type="warning" variant="tonal" class="mt-4 mb-3">
            {{ t('auth.verifyEmailSentFallback') }}
          </v-alert>

          <div class="auth-actions-row mt-4">
            <v-btn
              v-if="canResend"
              class="ui-btn-primary auth-submit-btn"
              :loading="auth.loading"
              @click="resendVerification"
            >
              {{ t('auth.sendAgain') }}
            </v-btn>

            <v-btn class="ui-btn-secondary" variant="text" @click="goLogin">
              {{ t('auth.goToLogin') }}
            </v-btn>

            <v-btn v-if="!activeEmail" class="ui-btn-secondary" variant="text" @click="goRegister">
              {{ t('auth.goToRegister') }}
            </v-btn>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

onMounted(() => {
  auth.clearError()

  if (route.query.email) {
    auth.setPendingVerificationEmail(route.query.email)
  }
})

const activeEmail = computed(() => route.query.email || auth.pendingVerificationEmail || '')
const status = computed(() => String(route.query.status || 'notice'))

const statusMessage = computed(() => {
  if (status.value === 'verified') return t('auth.verificationSuccess')
  if (status.value === 'already-verified') return t('auth.verificationAlreadyDone')
  if (status.value === 'invalid') return t('auth.verificationInvalid')
  if (status.value === 'resent') return t('auth.verificationResent')
  return t('auth.verifyEmailSubtitle')
})

const showMailtrapFallback = computed(
  () => status.value === 'notice' && route.query.emailSent === '0'
)
const canResend = computed(
  () =>
    status.value !== 'verified' && status.value !== 'already-verified' && Boolean(activeEmail.value)
)

const goHome = () => router.push({ name: 'home' })
const goLogin = () => router.push({ name: 'login' })
const goRegister = () => router.push({ name: 'register' })

const resendVerification = async () => {
  try {
    await auth.resendVerificationEmail({ email: activeEmail.value })

    router.replace({
      name: 'verify-email',
      query: {
        status: 'resent',
        email: activeEmail.value
      }
    })
  } catch (_) {}
}
</script>
