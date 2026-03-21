<template>
  <v-app class="workspace-root">
    <header class="work-topbar">
      <div class="work-topbar-in">
        <div class="work-brand" @click="goHome">DocBox</div>

        <nav class="work-links">
          <button class="work-link" type="button" @click="goHero">{{ t('nav.home') }}</button>
          <button class="work-link" type="button" @click="goDashboard">{{ t('nav.dashboard') }}</button>
          <button class="work-link" type="button" @click="goDocuments">{{ t('nav.documents') }}</button>
          <button v-if="auth.isAdmin" class="work-link" type="button" @click="goAdmin">{{ t('nav.admin') }}</button>
          <button v-if="auth.isStaff" class="work-link" type="button" @click="goDeveloper">{{ t('nav.developer') }}</button>
        </nav>

        <div class="work-auth">
          <button v-if="auth.isImpersonating" class="work-auth-btn" type="button" @click="stopImpersonation">
            Back To {{ auth.impersonator?.email || 'My Account' }}
          </button>
          <v-menu location="bottom end">
            <template #activator="{ props }">
              <button class="work-auth-btn" type="button" v-bind="props">
                {{ t('nav.notifications') }}
                <span v-if="notifications.unread" class="work-badge">{{ notifications.unread }}</span>
              </button>
            </template>
            <div class="work-notifications-menu">
              <div class="work-notifications-head">
                <strong>{{ t('nav.notifications') }}</strong>
                <button class="work-plain-btn" type="button" @click="markAllRead">{{ t('nav.markAllRead') }}</button>
              </div>
              <div v-if="!notifications.items.length" class="work-item-sub">No notifications</div>
              <button
                v-for="item in notifications.items.slice(0, 8)"
                :key="item.id"
                class="work-notification-item"
                type="button"
                @click="openNotification(item)"
              >
                <div class="work-item-title">{{ item.title }}</div>
                <div class="work-item-sub">{{ item.message }}</div>
              </button>
            </div>
          </v-menu>
          <button class="work-auth-btn" type="button" @click="onLogout">{{ t('nav.logout') }}</button>
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
    <ConfirmDialog />
  </v-app>
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useAuthStore } from '../stores/auth'
import { useI18n } from '../i18n'
import { useNotificationsStore } from '../stores/notifications'
import { useRealtimeStore } from '../stores/realtime'

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()
const notifications = useNotificationsStore()
const realtime = useRealtimeStore()

onMounted(async () => {
  await notifications.fetchNotifications().catch(() => {})
  realtime.start()
})

onBeforeUnmount(() => {
  realtime.stop()
})

const goHome = () => router.push({ name: 'dashboard' })
const goHero = () => router.push({ name: 'home' })
const goDashboard = () => router.push({ name: 'dashboard' })
const goDocuments = () => router.push({ name: 'documents' })
const goAdmin = () => router.push({ name: 'admin' })
const goDeveloper = () => router.push({ name: 'developer' })

const openNotification = async (item) => {
  if (!item.read_at) {
    await notifications.markRead(item.id).catch(() => {})
  }
  const documentId = item?.data?.document_id
  if (documentId) {
    router.push({ name: 'document-details', params: { id: documentId } })
    return
  }
  router.push({ name: 'documents' })
}

const markAllRead = async () => {
  await notifications.markAllRead().catch(() => {})
}

const onLogout = async () => {
  realtime.stop()
  await auth.logout()
  router.push({ name: 'home' })
}

const stopImpersonation = async () => {
  const restored = auth.stopImpersonation()
  if (!restored) return
  await auth.fetchMe().catch(() => {})
  await router.push({ name: 'developer' })
}
</script>
