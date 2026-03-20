<template>
  <v-dialog v-model="model" width="680">
    <v-card class="workspace-card pa-5">
      <div class="text-h6 mb-4">Manage Access</div>
      <v-row class="mb-3">
        <v-col cols="12" md="7">
          <v-text-field v-model="query" label="Find user" @update:model-value="searchUsers" />
          <v-list density="compact" class="border rounded-lg">
            <v-list-item
              v-for="u in users"
              :key="u.id"
              @click="selectedUser = u"
              :active="selectedUser?.id === u.id"
            >
              <v-list-item-title>{{ u.name }}</v-list-item-title>
              <v-list-item-subtitle>{{ u.email }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-col>
        <v-col cols="12" md="5">
          <v-select v-model="permission" :items="permissions" label="Permission" />
          <v-btn
            class="mt-2 ui-btn-primary"
            block
            :disabled="!selectedUser"
            :loading="loading"
            @click="grantAccess"
          >Grant access</v-btn>
        </v-col>
      </v-row>

      <v-alert v-if="error" type="error" variant="tonal" class="mb-3">{{ error }}</v-alert>

      <v-divider class="mb-3" />

      <v-list>
        <v-list-item v-for="share in shares" :key="share.id">
          <v-list-item-title>{{ share.name }}</v-list-item-title>
          <v-list-item-subtitle>{{ share.email }}</v-list-item-subtitle>
          <template #append>
            <v-select
              :model-value="share.permission"
              :items="permissions"
              style="max-width: 120px"
              hide-details
              density="compact"
              @update:model-value="(value) => updatePermission(share.id, value)"
            />
            <v-btn icon="mdi-delete-outline" variant="text" @click="removeAccess(share.id)" />
          </template>
        </v-list-item>
      </v-list>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../api/client'

const model = defineModel({ type: Boolean, default: false })

const props = defineProps({
  shares: {
    type: Array,
    default: () => []
  },
  documentId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['refresh'])

const query = ref('')
const users = ref([])
const selectedUser = ref(null)
const permission = ref('view')
const permissions = ['view', 'edit', 'admin']
const loading = ref(false)
const error = ref('')

watch(model, (isOpen) => {
  if (!isOpen) {
    query.value = ''
    users.value = []
    selectedUser.value = null
    error.value = ''
  }
})

const searchUsers = async () => {
  try {
    const { data } = await api.get('/users/search', { params: { q: query.value } })
    users.value = data
  } catch (_) {
    users.value = []
  }
}

const grantAccess = async () => {
  loading.value = true
  error.value = ''
  try {
    await api.post(`/documents/${props.documentId}/shares`, {
      user_id: selectedUser.value.id,
      permission: permission.value
    })
    await emit('refresh')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Cannot update sharing settings.'
  } finally {
    loading.value = false
  }
}

const updatePermission = async (userId, value) => {
  error.value = ''
  try {
    await api.put(`/documents/${props.documentId}/shares/${userId}`, { permission: value })
    await emit('refresh')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Cannot update permission.'
  }
}

const removeAccess = async (userId) => {
  error.value = ''
  try {
    await api.delete(`/documents/${props.documentId}/shares/${userId}`)
    await emit('refresh')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Cannot remove access.'
  }
}
</script>
