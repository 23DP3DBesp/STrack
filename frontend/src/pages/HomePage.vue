<template>
  <div class="landing-page">
    <header class="work-topbar landing-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goHome">Car Tracker</button>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goLogin">
            {{ t('nav.login') }}
          </button>
          <button class="work-link" type="button" @click="goRegister">
            {{ t('nav.register') }}
          </button>
        </nav>
      </div>
    </header>

    <main class="landing-shell">
      <section class="hero-panel home-hero-panel">
        <div class="hero-copy">
          <div class="work-kicker">{{ t('home.kicker') }}</div>
          <h1 class="hero-title">{{ t('home.title') }}</h1>
          <p class="hero-subtitle">
            {{ t('home.subtitle') }}
          </p>

          <div class="hero-actions">
            <v-btn class="ui-btn-primary" size="large" @click="goRegister">
              {{ t('home.start') }}
            </v-btn>

            <v-btn class="ui-btn-secondary" variant="outlined" size="large" @click="goLogin">
              {{ t('home.signIn') }}
            </v-btn>
          </div>
        </div>

        <div class="hero-stat-grid">
          <article class="hero-stat-card">
            <span class="hero-stat-label">{{ t('home.cards.cars.label') }}</span>
            <strong>{{ t('home.cards.cars.value') }}</strong>
          </article>

          <article class="hero-stat-card">
            <span class="hero-stat-label">{{ t('home.cards.fuel.label') }}</span>
            <strong>{{ t('home.cards.fuel.value') }}</strong>
          </article>

          <article class="hero-stat-card">
            <span class="hero-stat-label">{{ t('home.cards.repairs.label') }}</span>
            <strong>{{ t('home.cards.repairs.value') }}</strong>
          </article>

          <article class="hero-stat-card">
            <span class="hero-stat-label">{{ t('home.cards.mods.label') }}</span>
            <strong>{{ t('home.cards.mods.value') }}</strong>
          </article>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { setAppLocale } from '../i18n'

const router = useRouter()
const auth = useAuthStore()
const { t, locale } = useI18n()

onMounted(() => {
  if (!auth.isAuthenticated && locale.value !== 'lv') {
    setAppLocale('lv')
  }
})

const goHome = () => router.push({ name: 'home' })
const goLogin = () => router.push({ name: 'login' })
const goRegister = () => router.push({ name: 'register' })
</script>
