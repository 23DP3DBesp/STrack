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
      <section class="home-hero-panel">
        <div class="hero-copy home-hero-copy">
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

        <div class="home-preview-panel">
          <div class="preview-head">
            <div>
              <span class="hero-stat-label">{{ t('home.preview.label') }}</span>
              <strong>{{ t('home.preview.title') }}</strong>
            </div>
            <span class="preview-badge">{{ t('home.preview.status') }}</span>
          </div>

          <div class="preview-metrics">
            <article>
              <span>{{ t('dashboard.totalSpend') }}</span>
              <strong>€1 284</strong>
            </article>
            <article>
              <span>{{ t('dashboard.costPerKm') }}</span>
              <strong>€0.18</strong>
            </article>
            <article>
              <span>{{ t('dashboard.fuelLogs') }}</span>
              <strong>24</strong>
            </article>
          </div>

          <article class="preview-row">
            <span>{{ t('dashboard.insurance') }}</span>
            <strong>12.09.2026</strong>
          </article>

          <article class="preview-row warning">
            <span>{{ t('dashboard.technicalInspection') }}</span>
            <strong>{{ t('home.preview.soon') }}</strong>
          </article>

          <div class="preview-chart" aria-hidden="true">
            <span style="height: 36%"></span>
            <span style="height: 58%"></span>
            <span style="height: 44%"></span>
            <span style="height: 72%"></span>
            <span style="height: 64%"></span>
            <span style="height: 86%"></span>
          </div>
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
