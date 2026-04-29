<template>
  <v-app class="workspace-root">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goDashboard">Car Tracker</button>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goDashboard">
            {{ t('nav.dashboard') }}
          </button>

          <button class="work-link" type="button" @click="goProfile">
            {{ t('nav.profile') }}
          </button>
        </nav>

        <div class="work-auth">
          <button
            class="work-lang-btn"
            :title="`${t('nav.language')}: ${currentLocale.toUpperCase()}`"
            type="button"
            @click="toggleLanguage"
          >
            {{ currentLocale.toUpperCase() }}
          </button>

          <div class="work-user-chip">
            {{ auth.user?.login || 'User' }}
          </div>

          <button class="work-auth-btn" type="button" @click="onLogout">
            {{ t('nav.logout') }}
          </button>
        </div>
      </div>
    </header>

    <v-main>
      <div class="work-bg">
        <div class="work-page-shell">
          <slot />
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import { setAppLocale } from '../i18n'

const router = useRouter()
const auth = useAuthStore()
const { t, locale } = useI18n()

const localeOptions = [
  { title: 'EN', value: 'en' },
  { title: 'LV', value: 'lv' }
]

const currentLocale = computed(() => locale.value)

const goDashboard = () => {
  router.push({ name: 'dashboard' })
}

const goProfile = () => {
  router.push({ name: 'profile' })
}

const toggleLanguage = async () => {
  const nextLocale = locale.value === 'en' ? 'lv' : 'en'
  await changeLocale(nextLocale)
}

const changeLocale = async (nextLocale) => {
  setAppLocale(nextLocale)

  if (auth.user) {
    try {
      await auth.updateProfile({
        login: auth.user.login,
        display_name: auth.user.display_name,
        locale: nextLocale,
        theme: auth.user.theme || 'light',
        currency: auth.user.currency || 'EUR',
        distance_unit: auth.user.distance_unit || 'km'
      })
    } catch (_) {}
  }
}

const onLogout = async () => {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>
