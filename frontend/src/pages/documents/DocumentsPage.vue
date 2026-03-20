<template>
  <MainLayout>
    <section class="work-hero-card compact">
      <div class="work-kicker">Document management</div>
      <h1 class="work-title">{{ t('documents.title') }}</h1>
      <p class="work-subtitle">{{ t('documents.subtitle') }}</p>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('documents.upload') }}</h3>
        </div>
        <DocumentUploader :loading="documents.loading" :folders="documents.folders" :progress="uploadProgress" @submit="onCreate" />

        <div class="work-divider"></div>

        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('documents.folders') }}</h3>
        </div>
        <div class="d-flex ga-2 mb-3">
          <v-text-field v-model="newFolderName" :label="t('documents.newFolder')" hide-details density="compact" />
          <v-btn class="ui-btn-primary compact" @click="createFolder">{{ t('documents.add') }}</v-btn>
        </div>
        <div v-if="selectedFolderIds.length" class="d-flex ga-2 mb-3 flex-wrap">
          <v-chip>{{ t('documents.bulk.selected') }}: {{ selectedFolderIds.length }}</v-chip>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runFolderBulk('duplicate')">{{ t('documents.duplicateFolder') }}</v-btn>
          <v-btn class="ui-btn-primary compact" @click="runFolderBulk('delete')">{{ t('documents.trash') }}</v-btn>
        </div>
        <div class="work-list">
          <button
            class="work-list-item"
            :class="{ active: documents.filters.folder_id === folder.id, 'folder-drop-target': hoveredFolderId === folder.id }"
            v-for="folder in documents.folders"
            :key="folder.id"
            @dragover.prevent="onFolderDragOver(folder.id)"
            @dragleave="onFolderDragLeave(folder.id)"
            @drop.prevent.stop="onFolderDrop(folder.id)"
            @click="toggleFolder(folder.id)"
          >
            <div class="d-flex align-center ga-2">
              <v-checkbox-btn :model-value="selectedFolderIds.includes(folder.id)" @click.stop @update:model-value="toggleFolderSelected(folder.id)" />
            </div>
            <div class="flex-grow-1">
              <div class="work-item-title">{{ folder.name }}</div>
              <div class="work-item-sub">{{ folder.documents_count }} files</div>
            </div>
            <v-menu>
              <template #activator="{ props }">
                <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" @click.stop />
              </template>
              <v-list density="compact">
                <v-list-item @click="startRenameFolder(folder)">
                  <v-list-item-title>{{ t('documents.renameFolder') }}</v-list-item-title>
                </v-list-item>
                <v-list-item @click="duplicateFolder(folder.id)">
                  <v-list-item-title>{{ t('documents.duplicateFolder') }}</v-list-item-title>
                </v-list-item>
                <v-list-item @click="removeFolder(folder.id)">
                  <v-list-item-title>{{ t('documents.trash') }}</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </button>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('documents.library') }}</h3>
          <div class="d-flex ga-2">
            <button class="work-plain-btn" type="button" @click="reload">{{ t('documents.refresh') }}</button>
          </div>
        </div>

        <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-3">{{ errorMessage }}</v-alert>

        <v-row class="mb-2">
          <v-col cols="12" md="8">
            <v-text-field ref="searchField" v-model="query" :label="t('documents.search')" hide-details @keyup.enter="applyFilters" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="archivedFilter"
              :items="archiveItems"
              item-title="label"
              item-value="value"
              :label="t('documents.archive')"
              hide-details
            />
          </v-col>
        </v-row>

        <div class="d-flex ga-2 mb-3 flex-wrap">
          <v-chip :variant="smartView === 'all' ? 'flat' : 'outlined'" @click="applySmartView('all')">{{ t('documents.viewAll') }}</v-chip>
          <v-chip :variant="smartView === 'owned' ? 'flat' : 'outlined'" @click="applySmartView('owned')">{{ t('documents.viewOwned') }}</v-chip>
          <v-chip :variant="smartView === 'shared' ? 'flat' : 'outlined'" @click="applySmartView('shared')">{{ t('documents.viewShared') }}</v-chip>
          <v-chip :variant="smartView === 'starred' ? 'flat' : 'outlined'" @click="applySmartView('starred')">{{ t('documents.viewStarred') }}</v-chip>
          <v-chip :variant="smartView === 'recent' ? 'flat' : 'outlined'" @click="applySmartView('recent')">{{ t('documents.viewRecent') }}</v-chip>
          <v-chip :variant="smartView === 'large' ? 'flat' : 'outlined'" @click="applySmartView('large')">{{ t('documents.viewLarge') }}</v-chip>
        </div>

        <div v-if="selectedIds.length" class="d-flex ga-2 mb-3 flex-wrap">
          <v-chip>{{ t('documents.bulk.selected') }}: {{ selectedIds.length }}</v-chip>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulk('archive')">{{ t('documents.bulk.archive') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulk('unarchive')">{{ t('documents.bulk.unarchive') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulk('star')">{{ t('documents.bulk.star') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulk('unstar')">{{ t('documents.bulk.unstar') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulk('move')">{{ t('documents.moveToFolder') }}</v-btn>
          <v-btn class="ui-btn-primary compact" @click="runBulk('trash')">{{ t('documents.bulk.delete') }}</v-btn>
        </div>

        <div class="d-flex ga-2 mb-3">
          <v-btn class="ui-btn-primary compact" @click="applyFilters">{{ t('documents.apply') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="resetFilters">{{ t('documents.reset') }}</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="saveCurrentView">{{ t('documents.saveCurrentView') }}</v-btn>
        </div>

        <div v-if="savedViews.length" class="d-flex ga-2 mb-3 flex-wrap">
          <v-chip
            v-for="view in sortedViews"
            :key="view.id"
            class="cursor-pointer"
            draggable="true"
            @dragstart="startViewDrag(view.id)"
            @dragover.prevent
            @drop.prevent="dropView(view.id)"
            @click="applySavedView(view.id)"
          >
            <v-icon size="14" class="mr-1">{{ view.pinned ? 'mdi-pin' : 'mdi-filter-variant' }}</v-icon>
            {{ view.name }}
            <v-btn size="x-small" variant="text" :icon="view.pinned ? 'mdi-pin-off' : 'mdi-pin'" @click.stop="togglePinView(view.id)" />
            <v-btn size="x-small" variant="text" icon="mdi-arrow-left" @click.stop="moveView(view.id, -1)" />
            <v-btn size="x-small" variant="text" icon="mdi-arrow-right" @click.stop="moveView(view.id, 1)" />
            <v-btn size="x-small" variant="text" icon="mdi-close" @click.stop="removeSavedView(view.id)" />
          </v-chip>
        </div>

        <v-progress-linear v-if="documents.loading" indeterminate class="mb-3" />

        <div class="work-list">
          <button
            class="work-list-item"
            v-for="doc in documents.items"
            :key="doc.id"
            draggable="true"
            @dragstart="onDocumentDragStart($event, doc.id)"
            @click="openDoc(doc.id)"
            @contextmenu.prevent="openContextMenu($event, doc)"
          >
            <div class="d-flex align-center ga-2">
              <v-checkbox-btn :model-value="selectedIds.includes(doc.id)" @click.stop @update:model-value="toggleSelected(doc.id)" />
              <v-btn
                :icon="doc.is_starred ? 'mdi-star' : 'mdi-star-outline'"
                variant="text"
                size="small"
                @click.stop="toggleStar(doc)"
              />
            </div>
            <div class="flex-grow-1">
              <div class="work-item-title">{{ doc.title }}</div>
              <div class="work-item-sub">{{ doc.folder?.name || 'General' }} · {{ doc.owner?.name }} · {{ formatSize(doc.size) }}</div>
            </div>
            <div class="d-flex ga-2">
              <v-btn icon="mdi-eye-outline" variant="text" @click.stop="openQuickPreview(doc)" />
              <v-menu>
                <template #activator="{ props }">
                  <v-btn icon="mdi-dots-vertical" variant="text" v-bind="props" @click.stop />
                </template>
                <v-list density="compact">
                  <v-list-item @click="openDoc(doc.id)">
                    <v-list-item-title>{{ t('documents.open') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="toggleStar(doc)">
                    <v-list-item-title>{{ doc.is_starred ? t('documents.unstar') : t('documents.star') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="duplicateDocument(doc.id)">
                    <v-list-item-title>{{ t('documents.duplicate') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="openCopyDialog(doc)">
                    <v-list-item-title>{{ t('documents.copyToFolder') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="openMoveDialog(doc)">
                    <v-list-item-title>{{ t('documents.moveToFolder') }}</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="moveToTrash(doc.id)">
                    <v-list-item-title>{{ t('documents.trash') }}</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
              <div class="work-item-dot">{{ t('documents.open') }}</div>
            </div>
          </button>
        </div>
      </article>
    </section>

    <v-dialog v-model="previewDialog" fullscreen>
      <v-card class="workspace-card pa-4">
        <div class="work-panel-head mb-2">
          <div class="d-flex align-center ga-2">
            <v-btn icon="mdi-chevron-left" variant="text" @click="previewPrev" />
            <v-btn icon="mdi-chevron-right" variant="text" @click="previewNext" />
            <h3 class="work-panel-title">{{ previewDocument?.title || t('details.preview') }}</h3>
          </div>
          <div class="d-flex ga-1">
            <v-btn icon="mdi-magnify-minus-outline" variant="text" @click="zoomOut" />
            <v-btn icon="mdi-magnify-plus-outline" variant="text" @click="zoomIn" />
            <v-btn icon="mdi-rotate-right" variant="text" @click="rotateRight" />
            <v-btn icon="mdi-close" variant="text" @click="closePreview" />
          </div>
        </div>
        <div class="image-preview-shell">
          <img
            v-if="previewType === 'image' && previewBlobUrl"
            :src="previewBlobUrl"
            alt="Preview"
            class="image-preview"
            :style="{ transform: `scale(${previewZoom}) rotate(${previewRotate}deg)` }"
          />
          <iframe v-else-if="previewType === 'pdf' && previewBlobUrl" :src="previewBlobUrl" class="file-frame-preview" />
          <pre v-else-if="previewType === 'text'" class="text-file-preview" :style="{ fontSize: `${previewTextSize}px` }">{{ previewText }}</pre>
          <div v-else class="pa-4">Preview is not available for this file type.</div>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="copyDialog" width="560">
      <v-card class="workspace-card pa-4">
        <h3 class="work-panel-title mb-3">{{ t('documents.copyToFolder') }}</h3>
        <v-text-field v-model="copyTitle" :label="t('documents.titleLabel')" />
        <v-select v-model="copyFolderId" :items="documents.folders" item-title="name" item-value="id" :label="t('documents.folder')" clearable />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn class="ui-btn-secondary" variant="text" @click="copyDialog = false">{{ t('documents.reset') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="copyLoading" @click="submitCopy">{{ t('documents.duplicate') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="moveDialog" width="560">
      <v-card class="workspace-card pa-4">
        <h3 class="work-panel-title mb-3">{{ t('documents.moveToFolder') }}</h3>
        <v-select v-model="moveFolderId" :items="documents.folders" item-title="name" item-value="id" :label="t('documents.folder')" clearable />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn class="ui-btn-secondary" variant="text" @click="moveDialog = false">{{ t('documents.reset') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="moveLoading" @click="submitMove">{{ t('documents.moveToFolder') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="renameFolderDialog" width="520">
      <v-card class="workspace-card pa-4">
        <h3 class="work-panel-title mb-3">{{ t('documents.renameFolder') }}</h3>
        <v-text-field v-model="renameFolderName" :label="t('documents.newFolder')" />
        <div class="d-flex justify-end ga-2 mt-2">
          <v-btn class="ui-btn-secondary" variant="text" @click="renameFolderDialog = false">{{ t('documents.reset') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="renameFolderLoading" @click="submitRenameFolder">{{ t('documents.apply') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <div v-if="contextMenu.show" class="doc-context-menu" :style="{ top: `${contextMenu.y}px`, left: `${contextMenu.x}px` }">
      <button type="button" class="doc-context-item" @click="contextOpen">{{ t('documents.open') }}</button>
      <button type="button" class="doc-context-item" @click="contextToggleStar">{{ contextMenu.document?.is_starred ? t('documents.unstar') : t('documents.star') }}</button>
      <button type="button" class="doc-context-item" @click="contextDuplicate">{{ t('documents.duplicate') }}</button>
      <button type="button" class="doc-context-item" @click="contextCopy">{{ t('documents.copyToFolder') }}</button>
      <button type="button" class="doc-context-item" @click="contextMove">{{ t('documents.moveToFolder') }}</button>
      <button type="button" class="doc-context-item danger" @click="contextTrash">{{ t('documents.trash') }}</button>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../../layouts/MainLayout.vue'
import DocumentUploader from '../../components/DocumentUploader.vue'
import { useDocumentsStore } from '../../stores/documents'
import { useI18n } from '../../i18n'
import api from '../../api/client'

const router = useRouter()
const documents = useDocumentsStore()
const { t } = useI18n()

const newFolderName = ref('')
const query = ref('')
const archivedFilter = ref(null)
const errorMessage = ref('')
const selectedIds = ref([])
const selectedFolderIds = ref([])
const uploadProgress = ref(0)
const previewDialog = ref(false)
const previewDocument = ref(null)
const previewBlobUrl = ref('')
const previewText = ref('')
const previewType = ref(null)
const previewIndex = ref(-1)
const previewZoom = ref(1)
const previewRotate = ref(0)
const previewTextSize = ref(13)
const copyDialog = ref(false)
const moveDialog = ref(false)
const copyLoading = ref(false)
const moveLoading = ref(false)
const selectedActionDocument = ref(null)
const copyFolderId = ref(null)
const moveFolderId = ref(null)
const copyTitle = ref('')
const savedViews = ref([])
const moveMode = ref('single')
const smartView = ref('all')
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  document: null
})
const searchField = ref(null)
const SAVED_VIEWS_KEY = 'docbox_saved_views'
const renameFolderDialog = ref(false)
const renameFolderLoading = ref(false)
const renameFolderId = ref(null)
const renameFolderName = ref('')
const draggedViewId = ref(null)
const draggedDocumentId = ref(null)
const hoveredFolderId = ref(null)

const archiveItems = computed(() => ([
  { label: t('documents.all'), value: null },
  { label: t('documents.active'), value: false },
  { label: t('documents.archived'), value: true }
]))
const sortedViews = computed(() => {
  const pinned = savedViews.value.filter((v) => v.pinned)
  const regular = savedViews.value.filter((v) => !v.pinned)
  return [...pinned, ...regular]
})

onMounted(async () => {
  await Promise.all([documents.fetchFolders(), documents.fetchDocuments()])
  loadSavedViews()
  window.addEventListener('click', closeContextMenu, true)
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closeContextMenu, true)
  window.removeEventListener('keydown', onKeydown)
})

const onCreate = async (payload) => {
  uploadProgress.value = 0
  await documents.createDocument(payload, {
    onUploadProgress: (event) => {
      const total = event.total || 0
      if (!total) return
      uploadProgress.value = Math.round((event.loaded / total) * 100)
    }
  })
  uploadProgress.value = 0
}

const reload = async () => {
  await documents.fetchDocuments()
}

const createFolder = async () => {
  if (!newFolderName.value.trim()) return
  await documents.createFolder(newFolderName.value.trim())
  newFolderName.value = ''
}

const removeFolder = async (id) => {
  await documents.deleteFolder(id)
}

const duplicateFolder = async (id) => {
  await documents.duplicateFolder(id)
}

const toggleFolderSelected = (id) => {
  if (selectedFolderIds.value.includes(id)) {
    selectedFolderIds.value = selectedFolderIds.value.filter((item) => item !== id)
    return
  }
  selectedFolderIds.value = [...selectedFolderIds.value, id]
}

const runFolderBulk = async (action) => {
  if (!selectedFolderIds.value.length) return
  await documents.bulkFolderAction(action, selectedFolderIds.value)
  selectedFolderIds.value = []
}

const startRenameFolder = (folder) => {
  renameFolderId.value = folder.id
  renameFolderName.value = folder.name
  renameFolderDialog.value = true
}

const submitRenameFolder = async () => {
  if (!renameFolderId.value || !renameFolderName.value.trim()) return
  renameFolderLoading.value = true
  try {
    await documents.updateFolder(renameFolderId.value, renameFolderName.value.trim())
    renameFolderDialog.value = false
  } finally {
    renameFolderLoading.value = false
  }
}

const toggleFolder = async (folderId) => {
  const current = documents.filters.folder_id
  documents.setFilters({ folder_id: current === folderId ? null : folderId })
  await documents.fetchDocuments(1)
}

const applyFilters = async () => {
  documents.setFilters({
    q: query.value.trim(),
    is_archived: archivedFilter.value
  })
  await documents.fetchDocuments(1)
}

const applySmartView = async (view) => {
  smartView.value = view
  if (view === 'owned') {
    documents.setFilters({ ownership: 'owned', is_starred: null, size_from_kb: null, sort_by: 'updated_at', sort_dir: 'desc' })
  } else if (view === 'shared') {
    documents.setFilters({ ownership: 'shared', is_starred: null, size_from_kb: null, sort_by: 'updated_at', sort_dir: 'desc' })
  } else if (view === 'starred') {
    documents.setFilters({ ownership: 'all', is_starred: true, size_from_kb: null, sort_by: 'updated_at', sort_dir: 'desc' })
  } else if (view === 'recent') {
    documents.setFilters({ ownership: 'all', is_starred: null, size_from_kb: null, sort_by: 'created_at', sort_dir: 'desc' })
  } else if (view === 'large') {
    documents.setFilters({ ownership: 'all', is_starred: null, size_from_kb: 5120, sort_by: 'size', sort_dir: 'desc' })
  } else {
    documents.setFilters({ ownership: 'all', is_starred: null, size_from_kb: null, sort_by: 'updated_at', sort_dir: 'desc' })
  }
  await documents.fetchDocuments(1)
}

const resetFilters = async () => {
  query.value = ''
  archivedFilter.value = null
  smartView.value = 'all'
  documents.setFilters({ q: '', folder_id: null, is_archived: null, is_starred: null, ownership: 'all', size_from_kb: null, sort_by: 'updated_at', sort_dir: 'desc' })
  await documents.fetchDocuments(1)
}

const openDoc = (id) => router.push({ name: 'document-details', params: { id } })

const moveToTrash = async (id) => {
  errorMessage.value = ''
  try {
    await documents.deleteDocument(id)
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Cannot move document to trash.'
  }
}

const toggleSelected = (id) => {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((item) => item !== id)
    return
  }
  selectedIds.value = [...selectedIds.value, id]
}

const runBulk = async (action) => {
  if (!selectedIds.value.length) return
  if (action === 'move') {
    moveMode.value = 'bulk'
    moveFolderId.value = null
    moveDialog.value = true
  } else {
    await documents.bulkAction(action, selectedIds.value)
    selectedIds.value = []
  }
}

const toggleStar = async (doc) => {
  await documents.setStar(doc.id, !doc.is_starred)
}

const duplicateDocument = async (id) => {
  await documents.duplicateDocument(id)
}

const openCopyDialog = (doc) => {
  closeContextMenu()
  selectedActionDocument.value = doc
  copyFolderId.value = doc.folder_id || null
  copyTitle.value = `${doc.title} (Copy)`
  copyDialog.value = true
}

const submitCopy = async () => {
  if (!selectedActionDocument.value) return
  copyLoading.value = true
  try {
    await documents.duplicateDocument(selectedActionDocument.value.id, {
      folder_id: copyFolderId.value,
      title: copyTitle.value
    })
    copyDialog.value = false
  } finally {
    copyLoading.value = false
  }
}

const openMoveDialog = (doc) => {
  closeContextMenu()
  moveMode.value = 'single'
  selectedActionDocument.value = doc
  moveFolderId.value = doc.folder_id || null
  moveDialog.value = true
}

const submitMove = async () => {
  moveLoading.value = true
  try {
    if (moveMode.value === 'bulk') {
      if (!selectedIds.value.length) return
      await documents.bulkAction('move', selectedIds.value, { folder_id: moveFolderId.value })
      selectedIds.value = []
    } else {
      if (!selectedActionDocument.value) return
      await documents.bulkAction('move', [selectedActionDocument.value.id], { folder_id: moveFolderId.value })
    }
    moveDialog.value = false
  } finally {
    moveLoading.value = false
  }
}

const loadSavedViews = () => {
  try {
    const parsed = JSON.parse(localStorage.getItem(SAVED_VIEWS_KEY) || '[]')
    savedViews.value = Array.isArray(parsed) ? parsed : []
  } catch (_) {
    savedViews.value = []
  }
}

const persistSavedViews = () => {
  localStorage.setItem(SAVED_VIEWS_KEY, JSON.stringify(savedViews.value))
}

const saveCurrentView = () => {
  const name = window.prompt(t('documents.viewName'))
  if (!name || !name.trim()) return
  savedViews.value = [
    ...savedViews.value,
    {
      id: Date.now(),
      name: name.trim(),
      pinned: false,
      filters: { ...documents.filters }
    }
  ]
  persistSavedViews()
}

const applySavedView = async (id) => {
  const view = savedViews.value.find((item) => item.id === id)
  if (!view) return
  query.value = view.filters.q || ''
  archivedFilter.value = view.filters.is_archived ?? null
  smartView.value = 'all'
  documents.setFilters({ ...view.filters })
  await documents.fetchDocuments(1)
}

const removeSavedView = (id) => {
  savedViews.value = savedViews.value.filter((item) => item.id !== id)
  persistSavedViews()
}

const togglePinView = (id) => {
  savedViews.value = savedViews.value.map((item) => (
    item.id === id ? { ...item, pinned: !item.pinned } : item
  ))
  persistSavedViews()
}

const moveView = (id, direction) => {
  const index = savedViews.value.findIndex((item) => item.id === id)
  if (index < 0) return
  const next = index + direction
  if (next < 0 || next >= savedViews.value.length) return
  const copy = [...savedViews.value]
  const [item] = copy.splice(index, 1)
  copy.splice(next, 0, item)
  savedViews.value = copy
  persistSavedViews()
}

const startViewDrag = (id) => {
  draggedViewId.value = id
}

const dropView = (targetId) => {
  if (!draggedViewId.value || draggedViewId.value === targetId) return
  const currentIndex = savedViews.value.findIndex((item) => item.id === draggedViewId.value)
  const targetIndex = savedViews.value.findIndex((item) => item.id === targetId)
  if (currentIndex < 0 || targetIndex < 0) return
  const copy = [...savedViews.value]
  const [item] = copy.splice(currentIndex, 1)
  copy.splice(targetIndex, 0, item)
  savedViews.value = copy
  draggedViewId.value = null
  persistSavedViews()
}

const clearPreview = () => {
  if (previewBlobUrl.value) {
    URL.revokeObjectURL(previewBlobUrl.value)
    previewBlobUrl.value = ''
  }
  previewText.value = ''
  previewType.value = null
  previewZoom.value = 1
  previewRotate.value = 0
  previewTextSize.value = 13
}

const closePreview = () => {
  previewDialog.value = false
  previewIndex.value = -1
  clearPreview()
}

const loadPreviewForDocument = async (doc) => {
  closeContextMenu()
  clearPreview()
  previewDocument.value = doc
  const mime = doc?.mime_type || ''

  if (mime.startsWith('image/')) {
    const { data } = await api.get(`/documents/${doc.id}/preview`, { responseType: 'blob' })
    previewBlobUrl.value = URL.createObjectURL(data)
    previewType.value = 'image'
  } else if (mime === 'application/pdf') {
    const { data } = await api.get(`/documents/${doc.id}/preview`, { responseType: 'blob' })
    previewBlobUrl.value = URL.createObjectURL(data)
    previewType.value = 'pdf'
  } else if (mime.startsWith('text/') || ['application/json', 'application/xml', 'text/xml'].includes(mime)) {
    const { data } = await api.get(`/documents/${doc.id}/content`)
    previewText.value = data?.content || ''
    previewType.value = 'text'
  } else {
    previewType.value = null
  }

  previewDialog.value = true
}

const openQuickPreview = async (doc) => {
  previewIndex.value = documents.items.findIndex((item) => item.id === doc.id)
  await loadPreviewForDocument(doc)
}

const previewPrev = async () => {
  if (!documents.items.length) return
  const next = previewIndex.value <= 0 ? documents.items.length - 1 : previewIndex.value - 1
  previewIndex.value = next
  await loadPreviewForDocument(documents.items[next])
}

const previewNext = async () => {
  if (!documents.items.length) return
  const next = previewIndex.value >= documents.items.length - 1 ? 0 : previewIndex.value + 1
  previewIndex.value = next
  await loadPreviewForDocument(documents.items[next])
}

const zoomIn = () => {
  if (previewType.value === 'text') {
    previewTextSize.value = Math.min(26, previewTextSize.value + 1)
    return
  }
  previewZoom.value = Math.min(4, Number((previewZoom.value + 0.1).toFixed(2)))
}

const zoomOut = () => {
  if (previewType.value === 'text') {
    previewTextSize.value = Math.max(11, previewTextSize.value - 1)
    return
  }
  previewZoom.value = Math.max(0.4, Number((previewZoom.value - 0.1).toFixed(2)))
}

const rotateRight = () => {
  previewRotate.value = (previewRotate.value + 90) % 360
}

const onDocumentDragStart = (event, id) => {
  event.dataTransfer.effectAllowed = 'move'
  draggedDocumentId.value = id
}

const onFolderDragOver = (id) => {
  hoveredFolderId.value = id
}

const onFolderDragLeave = (id) => {
  if (hoveredFolderId.value === id) {
    hoveredFolderId.value = null
  }
}

const onFolderDrop = async (folderId) => {
  hoveredFolderId.value = null
  if (!draggedDocumentId.value) return
  await documents.moveDocumentToFolder(draggedDocumentId.value, folderId)
  draggedDocumentId.value = null
}

const openContextMenu = (event, doc) => {
  contextMenu.value = {
    show: true,
    x: event.clientX,
    y: event.clientY,
    document: doc
  }
}

const closeContextMenu = () => {
  contextMenu.value.show = false
}

const contextOpen = () => {
  if (!contextMenu.value.document) return
  openDoc(contextMenu.value.document.id)
  closeContextMenu()
}

const contextToggleStar = async () => {
  if (!contextMenu.value.document) return
  await toggleStar(contextMenu.value.document)
  closeContextMenu()
}

const contextDuplicate = async () => {
  if (!contextMenu.value.document) return
  await duplicateDocument(contextMenu.value.document.id)
  closeContextMenu()
}

const contextCopy = () => {
  if (!contextMenu.value.document) return
  openCopyDialog(contextMenu.value.document)
}

const contextMove = () => {
  if (!contextMenu.value.document) return
  openMoveDialog(contextMenu.value.document)
}

const contextTrash = async () => {
  if (!contextMenu.value.document) return
  await moveToTrash(contextMenu.value.document.id)
  closeContextMenu()
}

const focusSearch = () => {
  const el = searchField.value?.$el?.querySelector?.('input')
  if (el) el.focus()
}

const onKeydown = async (event) => {
  if (previewDialog.value) {
    if (event.key === 'Escape') {
      closePreview()
      return
    }
    if (event.key === 'ArrowLeft') {
      event.preventDefault()
      await previewPrev()
      return
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault()
      await previewNext()
      return
    }
    if (event.key === '+' || event.key === '=') {
      zoomIn()
      return
    }
    if (event.key === '-') {
      zoomOut()
      return
    }
    if (event.key.toLowerCase() === 'r') {
      rotateRight()
      return
    }
  }

  const tag = window.document.activeElement?.tagName?.toLowerCase?.()
  if (tag === 'input' || tag === 'textarea') {
    return
  }

  const isCtrl = event.ctrlKey || event.metaKey
  if (isCtrl && event.key.toLowerCase() === 'f') {
    event.preventDefault()
    focusSearch()
    return
  }

  if (isCtrl && event.key.toLowerCase() === 'd') {
    event.preventDefault()
    if (selectedIds.value.length) {
      await documents.duplicateDocument(selectedIds.value[0])
      return
    }
    if (contextMenu.value.document) {
      await documents.duplicateDocument(contextMenu.value.document.id)
    }
    return
  }

  if (event.key === 'Delete') {
    if (selectedIds.value.length) {
      await documents.bulkAction('trash', selectedIds.value)
      selectedIds.value = []
      return
    }
    if (contextMenu.value.document) {
      await moveToTrash(contextMenu.value.document.id)
    }
  }
}

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
