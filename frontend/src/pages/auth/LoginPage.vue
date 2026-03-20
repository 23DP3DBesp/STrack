<template>
  <div class="auth-page modern-auth">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <div class="work-brand" @click="goHome">DocBox</div>
        <nav class="work-links">
          <button class="work-link" type="button" @click="goHome">{{ t('nav.home') }}</button>
          <button class="work-link" type="button" @click="goRegister">{{ t('nav.register') }}</button>
        </nav>
        <div class="work-auth"></div>
      </div>
    </header>

    <div class="auth-modern-shell">
      <section class="work-panel auth-modern-panel">
        <div class="work-kicker">Secure workspace</div>
        <h1 class="work-title auth-modern-title">{{ t('auth.loginTitle') }}</h1>
        <p class="work-subtitle auth-modern-subtitle">{{ t('auth.continue') }}</p>

        <v-alert v-if="auth.error" type="error" variant="tonal" class="mt-4 mb-2">{{ auth.error }}</v-alert>

        <v-form class="mt-4" @submit.prevent="submit">
          <v-text-field v-model="email" :label="t('auth.email')" type="email" required />
          <v-text-field v-model="password" :label="t('auth.password')" type="password" required />
          <div class="d-flex ga-2 mt-2">
            <v-btn type="submit" class="ui-btn-primary flex-grow-1" :loading="auth.loading">{{ t('auth.signIn') }}</v-btn>
            <v-btn class="ui-btn-secondary" variant="text" @click="goRegister">{{ t('nav.register') }}</v-btn>
          </div>
        </v-form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useI18n } from '../../i18n'

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

const email = ref('')
const password = ref('')

onMounted(() => {
  auth.clearError()
})

const goHome = () => router.push({ name: 'home' })
const goRegister = () => router.push({ name: 'register' })

const submit = async () => {
  try {
    await auth.login({
      email: email.value,
      password: password.value,
      device_name: 'web-client'
    })
    router.push({ name: 'dashboard' })
  } catch (_) {
  }
}
</script>
