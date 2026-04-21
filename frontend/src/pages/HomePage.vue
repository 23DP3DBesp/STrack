<template>
  <div class="landing-page">
    <header class="work-topbar landing-topbar">
      <div class="work-topbar-in">
        <div class="work-brand">Car Tracker</div>
        <nav class="work-links">
          <button class="work-link" type="button" @click="goLogin">Login</button>
          <button class="work-link" type="button" @click="goRegister">Register</button>
        </nav>
      </div>
    </header>

    <main class="landing-shell">
      <section class="hero-panel">
        <div class="hero-copy">
          <div class="work-kicker">Car Tracker & Tuning Journal</div>
          <h1 class="hero-title">Track every refill, repair, and upgrade in one clean garage workspace.</h1>
          <p class="hero-subtitle">
            Keep a searchable garage, monitor fuel costs, log maintenance, and build a full tuning history for each car.
          </p>
          <div class="hero-actions">
            <v-btn class="ui-btn-primary" size="large" @click="goRegister">Start Tracking</v-btn>
            <v-btn class="ui-btn-secondary" variant="outlined" size="large" @click="goLogin">Sign In</v-btn>
          </div>
        </div>

        <div class="hero-stat-grid">
          <article class="hero-stat-card">
            <span class="hero-stat-label">Cars</span>
            <strong>Garage inventory with instant search</strong>
          </article>
          <article class="hero-stat-card">
            <span class="hero-stat-label">Fuel Logs</span>
            <strong>Auto-calculated price per liter and consumption</strong>
          </article>
          <article class="hero-stat-card">
            <span class="hero-stat-label">Repairs</span>
            <strong>Date filtering for service history</strong>
          </article>
          <article class="hero-stat-card">
            <span class="hero-stat-label">Mods</span>
            <strong>Tuning journal with cost and impact notes</strong>
          </article>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goLogin = () => router.push({ name: 'login' })
const goRegister = () => router.push({ name: 'register' })
</script>
