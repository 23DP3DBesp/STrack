<template>
  <MainLayout>
    <section class="work-hero-card compact">
      <div class="work-kicker">Developer workspace</div>
      <h1 class="work-title">{{ t('developer.title') }}</h1>
      <p class="work-subtitle">{{ t('developer.subtitle') }}</p>
    </section>

    <section class="work-grid-4 mt-4" v-if="overview">
      <article class="work-panel">
        <div class="work-panel-label">Users</div>
        <div class="work-panel-value">{{ overview.users.total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Admins / Developers</div>
        <div class="work-panel-value">{{ overview.users.admins }} / {{ overview.users.developers }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Documents / In trash</div>
        <div class="work-panel-value">{{ overview.documents.total }} / {{ overview.documents.in_trash }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Failed jobs</div>
        <div class="work-panel-value">{{ overview.system.failed_jobs }}</div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">User control center</h3>
          <button class="work-plain-btn" type="button" @click="reloadUsers">Refresh</button>
        </div>
        <v-text-field v-model="query" label="Search users" @keyup.enter="reloadUsers" />
        <div v-if="tempPasswordMessage" class="work-item-sub mb-2">{{ tempPasswordMessage }}</div>
        <div class="work-list">
          <div class="work-list-item static" v-for="user in admin.developerUsers" :key="user.id">
            <div class="flex-grow-1">
              <div class="work-item-title">{{ user.name }}</div>
              <div class="work-item-sub">{{ user.email }} - folders: {{ user.folders_count }} - shares: {{ user.shared_documents_count }}</div>
            </div>
            <div class="d-flex ga-1 flex-wrap justify-end">
              <v-btn class="ui-btn-secondary compact" variant="text" @click="inspectUserDocs(user)">Docs</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="prefillNotify(user)">Notify</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="impersonate(user)">Impersonate</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="resetPassword(user)">Reset password</v-btn>
            </div>
            <v-select
              :model-value="user.role"
              :items="roles"
              style="max-width: 160px"
              hide-details
              @update:model-value="(value) => updateRole(user.id, value)"
            />
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">System actions</h3>
        </div>
        <v-select
          v-model="notify.userId"
          :items="admin.developerUsers"
          item-title="email"
          item-value="id"
          label="Target user"
          clearable
        />
        <v-text-field v-model="notify.title" label="Notification title" />
        <v-textarea v-model="notify.message" label="Notification message" rows="3" auto-grow />
        <div class="d-flex ga-2 justify-end mb-3">
          <v-btn class="ui-btn-secondary" variant="text" :loading="broadcastLoading" @click="broadcastToAll">Broadcast all</v-btn>
          <v-btn class="ui-btn-primary" :loading="notifyLoading" @click="sendNotification">Send to one</v-btn>
        </div>

        <div class="work-divider"></div>
        <v-text-field v-model="cleanupDays" type="number" label="Cleanup trash older than days" />
        <div class="d-flex justify-end mb-3">
          <v-btn class="ui-btn-primary" :loading="cleanupLoading" @click="cleanupTrash">Run cleanup</v-btn>
        </div>
        <div v-if="systemMessage" class="work-item-sub">{{ systemMessage }}</div>

        <div class="work-divider"></div>
        <div class="work-panel-head">
          <h3 class="work-panel-title">Storage top users</h3>
          <button class="work-plain-btn" type="button" @click="reloadStorageTop">Refresh</button>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="item in admin.developerStorageTopUsers" :key="item.owner_id">
            <div>
              <div class="work-item-title">{{ item.owner?.name || 'Unknown user' }}</div>
              <div class="work-item-sub">{{ formatBytes(item.total_bytes) }} - {{ item.documents_total }} docs</div>
            </div>
          </div>
        </div>
      </article>
    </section>

    <section class="mt-4" v-if="admin.developerUserDocuments.length">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Selected user documents</h3>
          <button class="work-plain-btn" type="button" @click="reloadSelectedUserDocs">Refresh</button>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="doc in admin.developerUserDocuments" :key="doc.id">
            <div>
              <div class="work-item-title">{{ doc.title }}</div>
              <div class="work-item-sub">ID {{ doc.id }} - {{ doc.deleted_at ? 'trashed' : 'active' }}</div>
            </div>
            <v-btn class="ui-btn-secondary compact" variant="text" @click="inspectDocument(doc.id)">Inspect</v-btn>
          </div>
        </div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Global documents explorer</h3>
          <button class="work-plain-btn" type="button" @click="reloadDocuments">Refresh</button>
        </div>
        <v-text-field v-model="docFilters.q" label="Search documents" @keyup.enter="reloadDocuments" />
        <div class="d-flex ga-2 mb-3 flex-wrap">
          <v-switch v-model="docFilters.onlyTrashed" label="Only trashed" hide-details />
          <v-select
            v-model="docFilters.archived"
            :items="archiveOptions"
            item-title="label"
            item-value="value"
            label="Archived"
            style="max-width: 180px"
            hide-details
          />
          <v-text-field
            v-model="docFilters.ownerId"
            type="number"
            label="Owner ID"
            style="max-width: 140px"
            hide-details
          />
          <v-btn class="ui-btn-primary compact" @click="reloadDocuments">Apply</v-btn>
        </div>
        <div v-if="selectedDocIds.length" class="d-flex ga-2 mb-3 flex-wrap">
          <v-chip>Selected: {{ selectedDocIds.length }}</v-chip>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulkDocs('archive')">Archive</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulkDocs('unarchive')">Unarchive</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulkDocs('star')">Star</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulkDocs('unstar')">Unstar</v-btn>
          <v-btn class="ui-btn-secondary compact" variant="text" @click="runBulkDocs('restore')">Restore</v-btn>
          <v-btn class="ui-btn-primary compact" @click="runBulkDocs('purge')">Purge</v-btn>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="doc in admin.developerDocuments" :key="doc.id">
            <div class="d-flex align-center ga-2">
              <v-checkbox-btn :model-value="selectedDocIds.includes(doc.id)" @update:model-value="toggleSelectedDoc(doc.id)" />
            </div>
            <div class="flex-grow-1">
              <div class="work-item-title">{{ doc.title }}</div>
              <div class="work-item-sub">
                ID {{ doc.id }} - owner {{ doc.owner?.email || 'unknown' }} - {{ doc.deleted_at ? 'trashed' : 'active' }}
              </div>
            </div>
            <div class="d-flex ga-1 flex-wrap justify-end">
              <v-btn class="ui-btn-secondary compact" variant="text" @click="openDoc(doc.id)">Open</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="inspectDocument(doc.id)">Inspect</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="toggleArchive(doc)">{{ doc.is_archived ? 'Unarchive' : 'Archive' }}</v-btn>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="toggleStar(doc)">{{ doc.is_starred ? 'Unstar' : 'Star' }}</v-btn>
              <v-btn v-if="doc.deleted_at" class="ui-btn-secondary compact" variant="text" @click="restoreDoc(doc.id)">Restore</v-btn>
              <v-btn class="ui-btn-primary compact" @click="purgeDoc(doc.id)">Purge</v-btn>
            </div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Document inspector</h3>
          <button class="work-plain-btn" type="button" @click="refreshInspector" :disabled="!admin.developerDocumentDetails">Refresh</button>
        </div>

        <div v-if="!admin.developerDocumentDetails" class="work-item-sub">Select document from explorer</div>
        <template v-else>
          <div class="work-list mb-3">
            <div class="work-list-item static">
              <div>
                <div class="work-item-title">{{ admin.developerDocumentDetails.title }}</div>
                <div class="work-item-sub">Owner: {{ admin.developerDocumentDetails.owner?.email || 'unknown' }}</div>
                <div class="work-item-sub">Folder: {{ admin.developerDocumentDetails.folder?.name || 'General' }}</div>
              </div>
            </div>
          </div>
          <div class="d-flex ga-2 align-center mb-3">
            <v-select
              v-model="reassignOwnerId"
              :items="admin.developerUsers"
              item-title="email"
              item-value="id"
              label="Reassign owner"
              style="max-width: 280px"
              hide-details
            />
            <v-btn class="ui-btn-primary compact" @click="reassignOwner">Apply</v-btn>
          </div>

          <div class="work-divider"></div>
          <h4 class="work-panel-title mb-2">Shares</h4>
          <div class="work-list mb-3">
            <div class="work-list-item static" v-for="share in admin.developerDocumentShares" :key="share.id">
              <div>
                <div class="work-item-title">{{ share.email }}</div>
                <div class="work-item-sub">Permission: {{ share.permission }}</div>
              </div>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="removeShare(share.id)">Remove</v-btn>
            </div>
          </div>

          <div class="work-divider"></div>
          <h4 class="work-panel-title mb-2">Comments</h4>
          <div class="work-list mb-3">
            <div class="work-list-item static" v-for="comment in admin.developerDocumentComments" :key="comment.id">
              <div>
                <div class="work-item-title">{{ comment.user?.name || 'Unknown user' }}</div>
                <div class="work-item-sub">{{ comment.created_at }}</div>
                <div class="mt-1">{{ comment.content }}</div>
              </div>
              <v-btn class="ui-btn-secondary compact" variant="text" @click="deleteComment(comment.id)">Delete</v-btn>
            </div>
          </div>

          <div class="work-divider"></div>
          <h4 class="work-panel-title mb-2">Audit</h4>
          <div class="work-list">
            <div class="work-list-item static" v-for="event in admin.developerDocumentAudit.slice(0, 20)" :key="event.id">
              <div>
                <div class="work-item-title">{{ event.action }}</div>
                <div class="work-item-sub">{{ event.user?.name || 'System' }} - {{ event.created_at }}</div>
              </div>
            </div>
          </div>
        </template>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Realtime activity stream</h3>
          <button class="work-plain-btn" type="button" @click="reloadActivity">Refresh</button>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="item in admin.developerActivity" :key="item.id">
            <div>
              <div class="work-item-title">{{ item.action }}</div>
              <div class="work-item-sub">{{ item.user?.name || 'System' }} - {{ item.document?.title || 'No document' }} - {{ item.created_at }}</div>
            </div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Global notifications stream</h3>
          <button class="work-plain-btn" type="button" @click="reloadNotifications">Refresh</button>
        </div>
        <div class="work-list">
          <div class="work-list-item static" v-for="notification in admin.developerNotifications" :key="notification.id">
            <div>
              <div class="work-item-title">{{ notification.title }}</div>
              <div class="work-item-sub">{{ notification.user?.email || 'User' }} - {{ notification.type }} - {{ notification.created_at }}</div>
              <div class="mt-1">{{ notification.message }}</div>
            </div>
          </div>
        </div>
      </article>
    </section>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import { useAdminStore } from '../stores/admin'
import { useConfirmStore } from '../stores/confirm'
import { useAuthStore } from '../stores/auth'
import { useI18n } from '../i18n'

const router = useRouter()
const admin = useAdminStore()
const confirm = useConfirmStore()
const auth = useAuthStore()
const { t } = useI18n()

const overview = computed(() => admin.developerOverview)
const roles = ['user', 'manager', 'developer', 'admin']
const query = ref('')
const reassignOwnerId = ref(null)
const selectedInspectorDocumentId = ref(null)
const selectedUserId = ref(null)
const selectedDocIds = ref([])
const notifyLoading = ref(false)
const broadcastLoading = ref(false)
const cleanupLoading = ref(false)
const systemMessage = ref('')
const tempPasswordMessage = ref('')
const cleanupDays = ref(30)

const notify = ref({
  userId: null,
  title: '',
  message: ''
})

const docFilters = ref({
  q: '',
  onlyTrashed: false,
  archived: null,
  ownerId: null
})

const archiveOptions = [
  { label: 'All', value: null },
  { label: 'Archived only', value: true },
  { label: 'Non-archived', value: false }
]

onMounted(async () => {
  await Promise.all([
    admin.fetchDeveloperOverview(),
    admin.fetchDeveloperUsers(1, query.value),
    admin.fetchDeveloperActivity(1),
    admin.fetchDeveloperNotifications(1),
    admin.fetchDeveloperDocuments(),
    admin.fetchDeveloperStorageTopUsers()
  ])
})

const reloadUsers = async () => {
  await admin.fetchDeveloperUsers(1, query.value.trim())
}

const reloadDocuments = async () => {
  selectedDocIds.value = []
  await admin.fetchDeveloperDocuments({
    q: docFilters.value.q || '',
    only_trashed: docFilters.value.onlyTrashed,
    owner_id: docFilters.value.ownerId || null,
    is_archived: docFilters.value.archived
  })
}

const reloadActivity = async () => {
  await admin.fetchDeveloperActivity(1)
}

const reloadNotifications = async () => {
  await admin.fetchDeveloperNotifications(1)
}

const reloadStorageTop = async () => {
  await admin.fetchDeveloperStorageTopUsers()
}

const updateRole = async (id, role) => {
  await admin.updateDeveloperUserRole(id, role)
  await admin.fetchDeveloperOverview()
}

const inspectUserDocs = async (user) => {
  notify.value.userId = user.id
  selectedUserId.value = user.id
  await admin.fetchDeveloperUserDocuments(user.id)
}

const reloadSelectedUserDocs = async () => {
  if (!selectedUserId.value) return
  await admin.fetchDeveloperUserDocuments(selectedUserId.value)
}

const prefillNotify = (user) => {
  notify.value.userId = user.id
  if (!notify.value.title) notify.value.title = 'Developer message'
}

const impersonate = async (user) => {
  const ok = await confirm.ask({
    title: t('developer.confirmImpersonateTitle'),
    message: t('developer.confirmImpersonateMessage').replace('{email}', user.email),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!ok) return
  const data = await admin.impersonateUser(user.id)
  auth.startImpersonation(data.user, data.token, data.impersonator || null)
  await router.push({ name: 'dashboard' })
}

const resetPassword = async (user) => {
  const ok = await confirm.ask({
    title: t('developer.confirmResetPasswordTitle'),
    message: t('developer.confirmResetPasswordMessage').replace('{email}', user.email),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!ok) return
  const data = await admin.resetUserPassword(user.id)
  tempPasswordMessage.value = `Temporary password for ${user.email}: ${data.temporary_password}`
}

const sendNotification = async () => {
  if (!notify.value.userId || !notify.value.title.trim() || !notify.value.message.trim()) return
  notifyLoading.value = true
  try {
    await admin.sendDeveloperNotification(notify.value.userId, {
      title: notify.value.title.trim(),
      message: notify.value.message.trim(),
      type: 'developer.manual'
    })
    notify.value.title = ''
    notify.value.message = ''
    systemMessage.value = 'Notification sent'
    await reloadNotifications()
  } finally {
    notifyLoading.value = false
  }
}

const broadcastToAll = async () => {
  if (!notify.value.title.trim() || !notify.value.message.trim()) return
  const ok = await confirm.ask({
    title: t('developer.confirmBroadcastTitle'),
    message: t('developer.confirmBroadcastMessage'),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!ok) return
  broadcastLoading.value = true
  try {
    const result = await admin.broadcastDeveloperNotification({
      title: notify.value.title.trim(),
      message: notify.value.message.trim(),
      type: 'developer.broadcast'
    })
    systemMessage.value = `Broadcast delivered to ${result.recipients} users`
  } finally {
    broadcastLoading.value = false
  }
}

const cleanupTrash = async () => {
  const days = Number(cleanupDays.value || 30)
  if (!Number.isFinite(days) || days < 1) return
  const ok = await confirm.ask({
    title: t('developer.confirmCleanupTitle'),
    message: t('developer.confirmCleanupMessage').replace('{days}', String(days)),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!ok) return
  cleanupLoading.value = true
  try {
    const result = await admin.cleanupDeveloperTrash(days)
    systemMessage.value = `Cleanup complete. Purged ${result.purged} documents`
    await Promise.all([reloadDocuments(), admin.fetchDeveloperOverview()])
  } finally {
    cleanupLoading.value = false
  }
}

const openDoc = (id) => {
  router.push({ name: 'document-details', params: { id } })
}

const inspectDocument = async (id) => {
  selectedInspectorDocumentId.value = id
  await admin.fetchDeveloperDocumentInspector(id)
  reassignOwnerId.value = admin.developerDocumentDetails?.owner_id || null
}

const refreshInspector = async () => {
  if (!selectedInspectorDocumentId.value) return
  await inspectDocument(selectedInspectorDocumentId.value)
}

const toggleArchive = async (doc) => {
  await admin.setDeveloperDocumentArchive(doc.id, !doc.is_archived)
  await reloadDocuments()
  if (selectedInspectorDocumentId.value === doc.id) await refreshInspector()
}

const toggleStar = async (doc) => {
  await admin.setDeveloperDocumentStar(doc.id, !doc.is_starred)
  await reloadDocuments()
}

const restoreDoc = async (id) => {
  await admin.restoreDeveloperDocument(id)
  await reloadDocuments()
  if (selectedInspectorDocumentId.value === id) await refreshInspector()
}

const purgeDoc = async (id) => {
  const ok = await confirm.ask({
    title: t('developer.confirmPurgeTitle'),
    message: t('developer.confirmPurgeMessage'),
    confirmText: t('common.yes'),
    cancelText: t('common.no')
  })
  if (!ok) return
  await admin.purgeDeveloperDocument(id)
  await reloadDocuments()
  if (selectedInspectorDocumentId.value === id) {
    selectedInspectorDocumentId.value = null
    admin.clearDeveloperInspector()
  }
}

const reassignOwner = async () => {
  if (!selectedInspectorDocumentId.value || !reassignOwnerId.value) return
  await admin.reassignDeveloperDocumentOwner(selectedInspectorDocumentId.value, reassignOwnerId.value)
  await Promise.all([refreshInspector(), reloadDocuments()])
}

const removeShare = async (userId) => {
  if (!selectedInspectorDocumentId.value) return
  await admin.removeDeveloperDocumentShare(selectedInspectorDocumentId.value, userId)
  await refreshInspector()
}

const deleteComment = async (commentId) => {
  if (!selectedInspectorDocumentId.value) return
  await admin.deleteDeveloperDocumentComment(selectedInspectorDocumentId.value, commentId)
  await refreshInspector()
}

const toggleSelectedDoc = (id) => {
  if (selectedDocIds.value.includes(id)) {
    selectedDocIds.value = selectedDocIds.value.filter((item) => item !== id)
    return
  }
  selectedDocIds.value = [...selectedDocIds.value, id]
}

const runBulkDocs = async (action) => {
  if (!selectedDocIds.value.length) return
  if (action === 'purge') {
    const ok = await confirm.ask({
      title: t('developer.confirmBulkPurgeTitle'),
      message: t('developer.confirmBulkPurgeMessage'),
      confirmText: t('common.yes'),
      cancelText: t('common.no')
    })
    if (!ok) return
  }
  await admin.bulkDeveloperDocumentsAction(action, selectedDocIds.value)
  selectedDocIds.value = []
  await reloadDocuments()
}

const formatBytes = (value) => {
  if (!value) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = Number(value)
  let i = 0
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024
    i += 1
  }
  return `${size.toFixed(1)} ${units[i]}`
}
</script>
