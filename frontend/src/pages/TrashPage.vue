<template>
  <MainLayout>
    <section class="work-hero-card compact">
      <div class="work-kicker">Recovery</div>
      <h1 class="work-title">{{ t('trash.title') }}</h1>
      <p class="work-subtitle">{{ t('trash.subtitle') }}</p>
    </section>

    <section class="work-panel mt-4">
        <div class="work-panel-head">
        <h3 class="work-panel-title">{{ t('trash.deletedDocs') }}</h3>
        <button class="work-plain-btn" type="button" @click="reload">{{ t('trash.refresh') }}</button>
        </div>

      <v-progress-linear v-if="documents.loading" indeterminate class="mb-3" />

      <div class="work-list">
        <div class="work-list-item static" v-for="doc in documents.trashItems" :key="doc.id">
          <div>
            <div class="work-item-title">{{ doc.title }}</div>
            <div class="work-item-sub">
              {{ doc.owner?.name || 'Unknown' }} ·
              {{ formatDate(doc.deleted_at) }}
            </div>
          </div>
            <div class="d-flex ga-2">
            <v-btn class="ui-btn-secondary compact" variant="text" @click="restore(doc.id)">{{ t('trash.restore') }}</v-btn>
            <v-btn class="ui-btn-primary compact" @click="purge(doc.id)">{{ t('trash.deleteForever') }}</v-btn>
            </div>
          </div>
        </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import { useDocumentsStore } from '../stores/documents'
import { useI18n } from '../i18n'

const documents = useDocumentsStore()
const { t } = useI18n()

onMounted(async () => {
  await documents.fetchTrash()
})

const reload = async () => {
  await documents.fetchTrash(documents.trashPagination?.current_page || 1)
}

const restore = async (id) => {
  await documents.restoreDocument(id)
}

const purge = async (id) => {
  const confirmed = window.confirm('Delete document permanently? This action cannot be undone.')
  if (!confirmed) return
  await documents.purgeDocument(id)
}

const formatDate = (value) => {
  if (!value) return '-'
  const parsed = new Date(value)
  return parsed.toLocaleString()
}
</script>
