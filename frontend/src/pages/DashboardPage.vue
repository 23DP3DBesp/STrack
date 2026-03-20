<template>
  <MainLayout>
    <section class="work-hero-card">
      <div class="work-kicker">Workspace overview</div>
      <h1 class="work-title">{{ t('dashboard.title') }}</h1>
      <p class="work-subtitle">Welcome back, {{ auth.user?.name }}. {{ t('dashboard.subtitle') }}</p>
      <div class="work-hero-actions">
        <button class="work-solid-btn" type="button" @click="goDocuments">{{ t('dashboard.openDocuments') }}</button>
        <button class="work-outline-btn" type="button" @click="reload">{{ t('dashboard.refresh') }}</button>
      </div>
    </section>

    <section class="work-grid-4">
      <article class="work-panel">
        <div class="work-panel-label">{{ t('dashboard.totalDocs') }}</div>
        <div class="work-panel-value">{{ dashboard.stats.documents_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('dashboard.ownedDocs') }}</div>
        <div class="work-panel-value">{{ dashboard.stats.documents_owned }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('dashboard.sharedDocs') }}</div>
        <div class="work-panel-value">{{ dashboard.stats.documents_shared_with_me }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('dashboard.folders') }}</div>
        <div class="work-panel-value">{{ dashboard.stats.folders_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('dashboard.inTrash') }}</div>
        <div class="work-panel-value">{{ dashboard.stats.documents_in_trash }}</div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.recentDocs') }}</h3>
          <button class="work-plain-btn" type="button" @click="goDocuments">{{ t('dashboard.viewAll') }}</button>
        </div>
        <v-progress-linear v-if="dashboard.loading" indeterminate class="mb-3" />
        <div class="work-list">
          <button class="work-list-item" v-for="doc in dashboard.recentDocuments" :key="doc.id" @click="openDoc(doc.id)">
            <div>
              <div class="work-item-title">{{ doc.title }}</div>
              <div class="work-item-sub">{{ doc.folder?.name || 'General' }} · {{ formatSize(doc.size) }}</div>
            </div>
            <div class="work-item-dot">›</div>
          </button>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.recentActivity') }}</h3>
          <button class="work-plain-btn" type="button" @click="reload">{{ t('dashboard.refresh') }}</button>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="item in dashboard.recentActivity" :key="item.id">
            <div>
              <div class="work-item-title">{{ item.action }}</div>
              <div class="work-item-sub">{{ item.document?.title || 'Document' }} · {{ item.user?.name || 'System' }}</div>
            </div>
          </div>
        </div>
      </article>
    </section>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import { useAuthStore } from '../stores/auth'
import { useDashboardStore } from '../stores/dashboard'
import { useI18n } from '../i18n'

const router = useRouter()
const auth = useAuthStore()
const dashboard = useDashboardStore()
const { t } = useI18n()

onMounted(async () => {
  await dashboard.fetchSummary()
})

const reload = async () => {
  await dashboard.fetchSummary()
}

const goDocuments = () => router.push({ name: 'documents' })
const openDoc = (id) => router.push({ name: 'document-details', params: { id } })

const formatSize = (value) => {
  if (!value) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let size = value
  let unitIndex = 0
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }
  return `${size.toFixed(1)} ${units[unitIndex]}`
}
</script>
