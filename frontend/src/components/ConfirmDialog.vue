<template>
  <v-dialog v-model="confirm.open" width="480" persistent>
    <v-card class="workspace-card pa-4">
      <h3 class="work-panel-title mb-2">{{ confirm.title }}</h3>
      <p class="work-item-sub mb-4">{{ confirm.message }}</p>
      <div class="d-flex justify-end ga-2">
        <v-btn class="ui-btn-secondary" variant="text" @click="confirm.cancel">{{ confirm.cancelText }}</v-btn>
        <v-btn class="ui-btn-primary" @click="confirm.confirm">{{ confirm.confirmText }}</v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useConfirmStore } from '../stores/confirm'

const confirm = useConfirmStore()
</script>
