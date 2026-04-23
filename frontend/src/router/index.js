import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

import HomePage from '../pages/HomePage.vue'
import LoginPage from '../pages/auth/LoginPage.vue'
import RegisterPage from '../pages/auth/RegisterPage.vue'
import VerifyEmailPage from '../pages/auth/VerifyEmailPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import ProfilePage from '../pages/ProfilePage.vue'


const routes = [
  { path: '/', name: 'home', component: HomePage },
  { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
  { path: '/register', name: 'register', component: RegisterPage, meta: { guest: true } },
  { path: '/verify-email', name: 'verify-email', component: VerifyEmailPage, meta: { guest: true } },

  { path: '/app', name: 'dashboard', component: DashboardPage, meta: { auth: true } },
  { path: '/profile', name: 'profile', component: ProfilePage, meta: { auth: true } }

]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch (_) {
      await auth.logout()
    }
  }

  if (to.meta.auth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.name === 'home' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
