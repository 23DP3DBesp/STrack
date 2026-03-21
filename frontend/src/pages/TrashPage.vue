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

      <v-text-field
        v-model="query"
        :label="t('trash.search')"
        class="mb-3"
        density="comfortable"
        @keyup.enter="applySearch"
      />
      <div class="d-flex ga-2 mb-3">
        <v-btn class="ui-btn-primary compact" @click="applySearch">{{ t('trash.apply') }}</v-btn>
        <v-btn class="ui-btn-secondary compact" variant="text" @click="resetSearch">{{ t('trash.reset') }}</v-btn>
      </div>

      <div v-if="selectedIds.length" class="d-flex ga-2 mb-3 flex-wrap">
        <v-chip>{{ t('trash.selected') }}: {{ selectedIds.length }}</v-chip>
        <v-btn class="ui-btn-secondary compact" variant="text" @click="restoreSelected">{{ t('trash.restore') }}</v-btn>
        <v-btn class="ui-btn-primary compact" @click="purgeSelected">{{ t('trash.deleteForever') }}</v-btn>
      </div>

      <v-progress-linear v-if="documents.loading" indeterminate class="mb-3" />

      <div class="work-list">
        <div class="work-list-item static" v-for="doc in documents.trashItems" :key="doc.id">
          <div class="d-flex align-center ga-2">
            <v-checkbox-btn :model-value="selectedIds.includes(doc.id)" @update:model-value="toggleSelected(doc.id)" />
          </div>
          <div>
            <div class="work-item-title">{{ doc.title }}</div>
            <div class="work-item-sub">
              {{ doc.owner?.name || t('trash.unknownOwner') }} ·
              {{ formatDate(doc.deleted_at) }}
            </div>
          </div>
          <div class="d-flex ga-2">
            <v-btn class="ui-btn-secondary compact" variant="text" @click="restore(doc.id)">{{ t('trash.restore') }}</v-btn>
            <v-btn class="ui-btn-primary compact" @click="purge(doc.id)">{{ t('trash.deleteForever') }}</v-btn>
          </div>
        </div>
      </div>

      <div class="d-flex justify-end mt-3" v-if="documents.trashPagination?.last_page > 1">
        <v-pagination
          :model-value="documents.trashPagination?.current_page || 1"
          :length="documents.trashPagination?.last_page || 1"
          @update:model-value="goToPage"
        />
      </div>
    </section>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import { useDocumentsStore } from '../stores/documents'
import { useConfirmStore } from '../stores/confirm'
import { useI18n } from '../i18n'

const documents = useDocumentsStore()
const confirm = useConfirmStore()
const { t } = useI18n()
const query = ref('')
const selectedIds = ref([])

onMounted(async () => {
  query.value = documents.trashQuery || ''
  await documents.fetchTrash(1, query.value)
})

const reload = async () => {
  selectedIds.value = []
  await documents.fetchTrash(documents.trashPagination?.current_page || 1, query.value.trim())
}

const applySearch = async () => {
  selectedIds.value = []
  await documents.fetchTrash(1, query.value.trim())
}

const resetSearch = async () => {
  query.value = ''
  selectedIds.value = []
  await documents.fetchTrash(1, '')
}

const restore = async (id) => {
  await documents.restoreDocument(id)
  selectedIds.value = selectedIds.value.filter((item) => item !== id)
}

const purge = async (id) => {
  const confirmed = await confirm.ask({
    title: t('trash.confirmPurgeTitle'),
    message: t('trash.confirmPurgeMessage'),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!confirmed) return
  await documents.purgeDocument(id)
  selectedIds.value = selectedIds.value.filter((item) => item !== id)
}

const toggleSelected = (id) => {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((item) => item !== id)
    return
  }
  selectedIds.value = [...selectedIds.value, id]
}

const restoreSelected = async () => {
  if (!selectedIds.value.length) return
  await documents.bulkAction('restore', selectedIds.value)
  selectedIds.value = []
}

const purgeSelected = async () => {
  if (!selectedIds.value.length) return
  const confirmed = await confirm.ask({
    title: t('trash.confirmPurgeTitle'),
    message: t('trash.confirmPurgeSelectedMessage'),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!confirmed) return
  await documents.bulkAction('purge', selectedIds.value)
  selectedIds.value = []
}

const goToPage = async (page) => {
  selectedIds.value = []
  await documents.fetchTrash(page, query.value.trim())
}

const formatDate = (value) => {
  if (!value) return '-'
  const parsed = new Date(value)
  return parsed.toLocaleString()
}
</script>
