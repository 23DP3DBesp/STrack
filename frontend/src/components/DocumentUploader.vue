<template>
  <v-form class="upload-form" @submit.prevent="submit">
    <v-text-field v-model="title" :label="t('documents.titleLabel')" required />
    <v-textarea v-model="description" :label="t('documents.description')" rows="3" />
    <v-select
      v-model="folderId"
      :label="t('documents.folder')"
      :items="folders"
      item-title="name"
      item-value="id"
      clearable
    />

    <div
      class="upload-drop mb-3"
      :class="{ 'is-dragover': dragOver }"
      @dragenter.prevent="dragOver = true"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
    >
      <v-file-input
        v-model="file"
        :label="t('documents.fileLabel')"
        required
        show-size
        prepend-icon="mdi-file-upload-outline"
        hide-details
      />
      <div class="upload-drop-hint">{{ t('documents.supported') }}</div>
      <v-progress-linear v-if="progress > 0 && progress < 100" :model-value="progress" color="primary" class="mt-2" />
      <div v-if="selectedFileName" class="upload-selected">
        <span>{{ selectedFileName }} · {{ selectedFileSize }}</span>
        <v-btn icon="mdi-close" variant="text" size="small" @click="clearSelectedFile" />
      </div>
    </div>

    <div class="d-flex justify-end">
      <v-btn type="submit" :loading="loading" class="ui-btn-primary upload-btn">{{ t('documents.uploadDocument') }}</v-btn>
    </div>
  </v-form>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from '../i18n'

const emit = defineEmits(['submit'])
const { t } = useI18n()

const { loading, folders } = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  folders: {
    type: Array,
    default: () => []
  },
  progress: {
    type: Number,
    default: 0
  }
})

const title = ref('')
const description = ref('')
const file = ref(null)
const folderId = ref(null)
const dragOver = ref(false)

const selectedFile = computed(() => (Array.isArray(file.value) ? file.value[0] : file.value))
const selectedFileName = computed(() => selectedFile.value?.name || '')
const selectedFileSize = computed(() => {
  const value = selectedFile.value?.size
  if (!value) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let size = value
  let unitIndex = 0
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex += 1
  }
  return `${size.toFixed(1)} ${units[unitIndex]}`
})

const submit = () => {
  if (!file.value) return
  const selected = selectedFile.value
  emit('submit', {
    title: title.value,
    description: description.value,
    folder_id: folderId.value,
    file: selected
  })
  title.value = ''
  description.value = ''
  file.value = null
  folderId.value = null
}

const clearSelectedFile = () => {
  file.value = null
}

const onDrop = (event) => {
  dragOver.value = false
  const dropped = event?.dataTransfer?.files?.[0]
  if (!dropped) return
  file.value = dropped
}
</script>
