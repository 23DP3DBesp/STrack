import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomePage from '../pages/HomePage.vue'
import LoginPage from '../pages/auth/LoginPage.vue'
import RegisterPage from '../pages/auth/RegisterPage.vue'
import DashboardPage from '../pages/DashboardPage.vue'
import DocumentsPage from '../pages/documents/DocumentsPage.vue'
import DocumentDetailsPage from '../pages/documents/DocumentDetailsPage.vue'
import TrashPage from '../pages/TrashPage.vue'
import AdminPage from '../pages/AdminPage.vue'
import DeveloperPage from '../pages/DeveloperPage.vue'

const routes = [
  { path: '/', name: 'home', component: HomePage },
  { path: '/login', name: 'login', component: LoginPage, meta: { guest: true } },
  { path: '/register', name: 'register', component: RegisterPage, meta: { guest: true } },
  { path: '/app', name: 'dashboard', component: DashboardPage, meta: { auth: true } },
  { path: '/documents', name: 'documents', component: DocumentsPage, meta: { auth: true } },
  { path: '/documents/:id', name: 'document-details', component: DocumentDetailsPage, meta: { auth: true } },
  { path: '/trash', name: 'trash', component: TrashPage, meta: { auth: true } },
  { path: '/admin', name: 'admin', component: AdminPage, meta: { auth: true, admin: true } },
  { path: '/developer', name: 'developer', component: DeveloperPage, meta: { auth: true, staff: true } }
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

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.admin && !auth.isAdmin) {
    return { name: 'dashboard' }
  }

  if (to.meta.staff && !auth.isStaff) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
