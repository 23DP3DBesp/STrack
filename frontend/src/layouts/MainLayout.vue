<template>
  <v-app class="workspace-root">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <button class="work-brand" type="button" @click="goDashboard">Car Tracker</button>


        <nav class="work-links">
          <button class="work-link" type="button" @click="goDashboard">Dashboard</button>
          <button class="work-link" type="button" @click="goProfile">Profile</button>
        </nav>



        <div class="work-auth">
          <!-- Theme toggle removed -->
          <div class="work-user-chip">{{ auth.user?.login }}</div>
          <button class="work-auth-btn" type="button" @click="onLogout">Logout</button>
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
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()


const goDashboard = () => router.push({ name: 'dashboard' })
const goProfile = () => router.push({ name: 'profile' })


const onLogout = async () => {
  await auth.logout()
  router.push({ name: 'home' })
}

const toggleTheme = async () => {
  const newTheme = auth.user.theme === 'dark' ? 'light' : 'dark'
  try {
    const updated = await auth.updateProfile({ ...auth.user, theme: newTheme })
  } catch (e) {
    console.error('Theme toggle failed', e)
  }
}


</script>
