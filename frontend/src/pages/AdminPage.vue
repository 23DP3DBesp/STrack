<template>
  <MainLayout>
    <section class="work-hero-card compact">
      <div class="work-kicker">Administration</div>
      <h1 class="work-title">{{ t('admin.title') }}</h1>
      <p class="work-subtitle">{{ t('admin.subtitle') }}</p>
    </section>

    <section class="work-grid-4 mt-4">
      <article class="work-panel">
        <div class="work-panel-label">{{ t('admin.users') }}</div>
        <div class="work-panel-value">{{ admin.summary.users_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('admin.admins') }}</div>
        <div class="work-panel-value">{{ admin.summary.admins_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('admin.developers') }}</div>
        <div class="work-panel-value">{{ admin.summary.developers_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('admin.docs') }}</div>
        <div class="work-panel-value">{{ admin.summary.documents_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">{{ t('admin.trash') }}</div>
        <div class="work-panel-value">{{ admin.summary.documents_in_trash }}</div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('admin.usersRoles') }}</h3>
          <button class="work-plain-btn" type="button" @click="reloadUsers">Refresh</button>
        </div>
        <v-text-field v-model="userQuery" :label="t('admin.searchUsers')" @keyup.enter="reloadUsers" />
        <div class="work-list">
          <div class="work-list-item static" v-for="user in admin.users" :key="user.id">
            <div>
              <div class="work-item-title">{{ user.name }}</div>
              <div class="work-item-sub">{{ user.email }}</div>
            </div>
            <v-select
              :model-value="user.role"
              :items="roles"
              style="max-width: 140px"
              hide-details
              @update:model-value="(value) => changeRole(user.id, value)"
            />
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('admin.globalTrash') }}</h3>
          <button class="work-plain-btn" type="button" @click="reloadTrash">Refresh</button>
        </div>
        <v-text-field v-model="trashQuery" :label="t('admin.searchTrash')" @keyup.enter="reloadTrash" />
        <div class="work-list">
          <div class="work-list-item static" v-for="doc in admin.trashItems" :key="doc.id">
            <div>
              <div class="work-item-title">{{ doc.title }}</div>
              <div class="work-item-sub">{{ doc.owner?.name || 'Unknown owner' }}</div>
            </div>
            <div class="d-flex ga-2">
              <v-btn class="ui-btn-secondary compact" variant="text" @click="restore(doc.id)">{{ t('admin.restore') }}</v-btn>
              <v-btn class="ui-btn-primary compact" @click="purge(doc.id)">{{ t('admin.purge') }}</v-btn>
            </div>
          </div>
        </div>
      </article>
    </section>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import MainLayout from '../layouts/MainLayout.vue'
import { useAdminStore } from '../stores/admin'
import { useI18n } from '../i18n'

const admin = useAdminStore()
const { t } = useI18n()
const roles = ['user', 'manager', 'developer', 'admin']
const userQuery = ref('')
const trashQuery = ref('')

onMounted(async () => {
  await Promise.all([
    admin.fetchSummary(),
    admin.fetchUsers(1, userQuery.value),
    admin.fetchTrash(1, trashQuery.value)
  ])
})

const reloadUsers = async () => {
  await admin.fetchUsers(1, userQuery.value.trim())
}

const reloadTrash = async () => {
  await admin.fetchTrash(1, trashQuery.value.trim())
}

const changeRole = async (userId, role) => {
  await admin.updateUserRole(userId, role)
}

const restore = async (documentId) => {
  await admin.restoreDocument(documentId)
}

const purge = async (documentId) => {
  const confirmed = window.confirm('Purge document permanently?')
  if (!confirmed) return
  await admin.purgeDocument(documentId)
}
</script>
