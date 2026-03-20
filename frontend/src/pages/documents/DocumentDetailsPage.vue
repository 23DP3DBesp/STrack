<template>
  <MainLayout>
    <section v-if="document" class="work-grid-2 details-layout">
      <article class="work-panel">
        <div class="work-panel-head">
          <div>
            <h2 class="work-panel-title big">{{ document.title }}</h2>
            <div class="work-item-sub">{{ document.description || t('common.noDescription') }}</div>
          </div>
          <div class="d-flex ga-2">
            <v-btn class="ui-btn-secondary" variant="text" :loading="downloading" @click="downloadDocument">{{ t('details.download') }}</v-btn>
            <v-btn v-if="canShare" class="ui-btn-primary" @click="shareDialog = true">{{ t('details.share') }}</v-btn>
            <v-btn class="ui-btn-secondary" variant="text" @click="moveToTrash">{{ t('details.delete') }}</v-btn>
          </div>
        </div>

        <div class="work-divider"></div>

        <div v-if="canPreviewFile" class="mb-4">
          <div class="work-panel-head mb-2">
            <h3 class="work-panel-title">{{ t('details.preview') }}</h3>
          </div>
          <div class="image-preview-shell">
            <img v-if="previewType === 'image' && previewBlobUrl" :src="previewBlobUrl" alt="Document preview" class="image-preview" />
            <iframe v-else-if="previewType === 'pdf' && previewBlobUrl" :src="previewBlobUrl" class="file-frame-preview" />
            <pre v-else-if="previewType === 'text'" class="text-file-preview">{{ textPreview }}</pre>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="7">
            <v-select
              v-model="selectedFolderId"
              :items="documents.folders"
              item-title="name"
              item-value="id"
              :label="t('documents.folder')"
              clearable
            />
          </v-col>
          <v-col cols="12" md="5" class="d-flex align-center">
            <v-switch v-model="archived" color="primary" label="Archived" @update:model-value="saveMeta" />
          </v-col>
        </v-row>

        <div class="d-flex mb-3">
          <v-btn class="ui-btn-primary" @click="saveMeta">{{ t('details.saveMeta') }}</v-btn>
        </div>

        <v-form @submit.prevent="uploadNewVersion">
          <v-file-input v-model="file" :label="t('details.uploadVersion')" />
          <v-btn type="submit" class="ui-btn-primary">{{ t('details.createVersion') }}</v-btn>
        </v-form>

        <div v-if="editorEnabled" class="work-divider"></div>

        <div v-if="editorEnabled">
          <div class="work-panel-head mb-2">
            <h3 class="work-panel-title">{{ t('details.inlineEditor') }}</h3>
            <div class="work-item-sub">{{ saveStatus }}</div>
          </div>
          <v-textarea
            v-model="editorContent"
            rows="14"
            auto-grow
            label="Document content"
            @update:model-value="queueAutoSave"
          />
          <div class="d-flex ga-2 mt-2">
            <v-btn class="ui-btn-primary" :loading="savingContent" @click="saveContent">{{ t('details.saveAsVersion') }}</v-btn>
            <v-btn class="ui-btn-secondary" variant="text" @click="reloadContent">{{ t('details.reload') }}</v-btn>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('details.owner') }}</h3>
        </div>
        <div class="work-item-title">{{ document.owner?.name }}</div>
        <div class="work-item-sub">{{ document.owner?.email }}</div>

        <div class="work-divider"></div>

        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('details.currentVersion') }}</h3>
          <div class="work-item-sub">v{{ document.current_version?.version_number || '-' }}</div>
        </div>

        <div class="work-divider"></div>

        <div class="work-panel-head"><h3 class="work-panel-title">{{ t('details.versionHistory') }}</h3></div>
        <div class="work-list">
          <div class="work-list-item static" v-for="version in versions" :key="version.id">
            <div>
              <div class="work-item-title">v{{ version.version_number }} · {{ formatSize(version.size) }}</div>
              <div class="work-item-sub">{{ version.created_at }}</div>
            </div>
            <v-btn class="ui-btn-secondary compact" variant="text" @click="restore(version.id)">{{ t('details.restore') }}</v-btn>
          </div>
        </div>

        <div class="work-divider"></div>

        <div class="work-panel-head"><h3 class="work-panel-title">{{ t('details.activity') }}</h3></div>
        <div class="work-list">
          <div class="work-list-item static" v-for="log in documents.auditLogs" :key="log.id">
            <div>
              <div class="work-item-title">{{ log.action }}</div>
              <div class="work-item-sub">{{ log.user?.name || 'System' }} · {{ log.created_at }}</div>
            </div>
          </div>
        </div>

        <div class="work-divider"></div>

        <div class="work-panel-head"><h3 class="work-panel-title">{{ t('details.comments') }}</h3></div>
        <div class="d-flex ga-2 mb-3">
          <v-textarea
            v-model="commentText"
            :label="t('details.writeComment')"
            rows="2"
            auto-grow
            hide-details
          />
          <v-btn class="ui-btn-primary" @click="postComment">{{ t('details.postComment') }}</v-btn>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="comment in documents.comments" :key="comment.id">
            <div>
              <div class="work-item-title">{{ comment.user?.name || 'Unknown user' }}</div>
              <div class="work-item-sub">{{ comment.created_at }}</div>
              <div class="mt-1">{{ comment.content }}</div>
            </div>
            <v-btn
              v-if="canDeleteComment(comment)"
              class="ui-btn-secondary compact"
              variant="text"
              @click="deleteComment(comment.id)"
            >
              {{ t('details.delete') }}
            </v-btn>
          </div>
        </div>
      </article>
    </section>

    <DocumentShareDialog
      v-model="shareDialog"
      :document-id="Number(route.params.id)"
      :shares="shares"
      @refresh="loadShares"
    />
  </MainLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MainLayout from '../../layouts/MainLayout.vue'
import DocumentShareDialog from '../../components/DocumentShareDialog.vue'
import { useDocumentsStore } from '../../stores/documents'
import { useAuthStore } from '../../stores/auth'
import api from '../../api/client'
import { useI18n } from '../../i18n'

const route = useRoute()
const router = useRouter()
const documents = useDocumentsStore()
const auth = useAuthStore()
const { t } = useI18n()

const file = ref(null)
const shareDialog = ref(false)
const selectedFolderId = ref(null)
const archived = ref(false)
const editorEnabled = ref(false)
const editorContent = ref('')
const savingContent = ref(false)
const saveStatus = ref('Ready')
const previewBlobUrl = ref('')
const textPreview = ref('')
const downloading = ref(false)
const loadingPreview = ref(false)
const commentText = ref('')
let autoSaveTimer = null

const document = computed(() => documents.activeDocument)
const versions = computed(() => documents.versions)
const shares = computed(() => documents.shares)
const mimeType = computed(() => document.value?.mime_type || '')
const previewType = computed(() => {
  if (!mimeType.value) return null
  if (mimeType.value.startsWith('image/')) return 'image'
  if (mimeType.value === 'application/pdf') return 'pdf'
  if (mimeType.value.startsWith('text/') || ['application/json', 'application/xml', 'text/xml'].includes(mimeType.value)) return 'text'
  return null
})
const canPreviewFile = computed(() => Boolean(previewType.value))
const canShare = computed(() => {
  if (!document.value || !auth.user) return false
  if (auth.user.role === 'admin' || document.value.owner_id === auth.user.id) return true
  const me = (document.value.collaborators || []).find((item) => item.id === auth.user.id)
  return me?.pivot?.permission === 'admin'
})

watch(document, (value) => {
  if (!value) return
  selectedFolderId.value = value.folder_id || null
  archived.value = Boolean(value.is_archived)
})

onMounted(async () => {
  const id = Number(route.params.id)
  await Promise.allSettled([
    documents.fetchFolders(),
    documents.fetchDocument(id),
    documents.fetchVersions(id),
    documents.fetchShares(id),
    documents.fetchAuditLogs(id),
    documents.fetchComments(id)
  ])
  await tryLoadContent()
  await loadImagePreview()
})

onBeforeUnmount(() => {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  clearImagePreview()
})

const tryLoadContent = async () => {
  const id = Number(route.params.id)
  try {
    await documents.fetchDocumentContent(id)
    editorContent.value = documents.activeContent
    editorEnabled.value = true
  } catch (_) {
    editorEnabled.value = false
  }
}

const queueAutoSave = () => {
  if (!editorEnabled.value) return
  saveStatus.value = 'Unsaved changes'
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(async () => {
    await saveContent()
  }, 1500)
}

const saveContent = async () => {
  if (!editorEnabled.value) return
  savingContent.value = true
  saveStatus.value = 'Saving...'
  try {
    await documents.updateDocumentContent(Number(route.params.id), editorContent.value)
    saveStatus.value = 'Saved'
  } finally {
    savingContent.value = false
  }
}

const reloadContent = async () => {
  await tryLoadContent()
  saveStatus.value = 'Reloaded'
}

const clearImagePreview = () => {
  if (previewBlobUrl.value) {
    URL.revokeObjectURL(previewBlobUrl.value)
    previewBlobUrl.value = ''
  }
  textPreview.value = ''
}

const loadImagePreview = async () => {
  clearImagePreview()
  if (!canPreviewFile.value) return
  loadingPreview.value = true
  try {
    if (previewType.value === 'text') {
      await documents.fetchDocumentContent(Number(route.params.id))
      textPreview.value = documents.activeContent || ''
      return
    }
    const { data } = await api.get(`/documents/${route.params.id}/preview`, { responseType: 'blob' })
    previewBlobUrl.value = URL.createObjectURL(data)
  } finally {
    loadingPreview.value = false
  }
}

const uploadNewVersion = async () => {
  const selected = Array.isArray(file.value) ? file.value[0] : file.value
  if (!selected) return
  await documents.updateDocument(Number(route.params.id), { file: selected })
  await Promise.all([
    documents.fetchVersions(Number(route.params.id)),
    documents.fetchAuditLogs(Number(route.params.id))
  ])
  file.value = null
  await tryLoadContent()
  await loadImagePreview()
}

const restore = async (versionId) => {
  await documents.restoreVersion(Number(route.params.id), versionId)
  await documents.fetchAuditLogs(Number(route.params.id))
  await tryLoadContent()
  await loadImagePreview()
}

const loadShares = async () => {
  await Promise.allSettled([
    documents.fetchShares(Number(route.params.id)),
    documents.fetchAuditLogs(Number(route.params.id))
  ])
}

const postComment = async () => {
  if (!commentText.value.trim()) return
  await documents.addComment(Number(route.params.id), commentText.value.trim())
  commentText.value = ''
}

const canDeleteComment = (comment) => {
  if (!auth.user) return false
  return auth.isStaff || comment.user_id === auth.user.id || document.value?.owner_id === auth.user.id
}

const deleteComment = async (commentId) => {
  await documents.deleteComment(Number(route.params.id), commentId)
}

const moveToTrash = async () => {
  const confirmed = window.confirm('Move document to trash?')
  if (!confirmed) return
  await documents.deleteDocument(Number(route.params.id))
  router.push({ name: 'documents' })
}

const downloadDocument = async () => {
  downloading.value = true
  try {
    const { data } = await api.get(`/documents/${route.params.id}/download`, { responseType: 'blob' })
    const blobUrl = URL.createObjectURL(data)
    const link = window.document.createElement('a')
    link.href = blobUrl
    link.download = document.value?.title || `document-${route.params.id}`
    window.document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(blobUrl)
  } finally {
    downloading.value = false
  }
}

const saveMeta = async () => {
  await documents.updateDocument(Number(route.params.id), {
    folder_id: selectedFolderId.value,
    is_archived: archived.value
  })
  await documents.fetchAuditLogs(Number(route.params.id))
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
