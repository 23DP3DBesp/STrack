<template>
  <article class="work-panel expiry-card" :class="`expiry-${status}`">
    <div class="expiry-card-header">
      <v-icon :icon="icon" size="24" class="expiry-icon" />
      <div class="expiry-title-area">
        <h4 class="expiry-title">{{ title }}</h4>
        <div v-if="daysLeft !== null" class="expiry-days">{{ daysText }}</div>
      </div>
    </div>
    <div class="expiry-date">{{ dateDisplay }}</div>
    <div class="expiry-message">{{ message }}</div>
    <v-btn class="expiry-edit-btn" size="small" variant="text" @click="emit('edit-expiry')">
      {{ t('dashboard.edit') }}
    </v-btn>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getExpiryStatus, getExpiryMessage, formatDateForDisplay } from '../expiryHelper'

const props = defineProps({
  type: {
    type: String,
    enum: ['insurance', 'inspection'],
    required: true
  },
  expiryDate: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['edit-expiry'])

const { t } = useI18n()

const title = computed(() => {
  return props.type === 'insurance' ? t('dashboard.insurance') : t('dashboard.technicalInspection')
})

const icon = computed(() => {
  return props.type === 'insurance' ? 'mdi-shield-check' : 'mdi-car-door'
})

const status = computed(() => getExpiryStatus(props.expiryDate))

const dateDisplay = computed(() => formatDateForDisplay(props.expiryDate))

const daysLeft = computed(() => {
  if (!props.expiryDate) return null
  const now = new Date()
  const expiry = new Date(props.expiryDate)
  return Math.ceil((expiry.getTime() - now.getTime()) / 86400000)
})

const daysText = computed(() => {
  if (daysLeft.value === null) return ''
  if (daysLeft.value < 0) return `Expired ${Math.abs(daysLeft.value)}d ago`
  if (daysLeft.value === 0) return 'Expires today'
  if (daysLeft.value === 1) return 'Expires tomorrow'
  return `${daysLeft.value} days left`
})

const message = computed(() => {
  return getExpiryMessage(props.expiryDate, title.value)
})
</script>

<style scoped>
.expiry-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: relative;
  padding: 16px;
  border-left: 4px solid var(--color-border);
  transition: all 0.2s ease;
}

.expiry-valid {
  border-left-color: var(--color-success, #4caf50);
  background: rgba(76, 175, 80, 0.04);
}

.expiry-warning {
  border-left-color: var(--color-warning, #ff9800);
  background: rgba(255, 152, 0, 0.04);
}

.expiry-critical {
  border-left-color: var(--color-error, #f44336);
  background: rgba(244, 67, 54, 0.08);
}

.expiry-expired {
  border-left-color: var(--color-error, #f44336);
  background: rgba(244, 67, 54, 0.12);
}

.expiry-missing {
  border-left-color: var(--color-disabled, #bdbdbd);
  background: rgba(189, 189, 189, 0.04);
}

.expiry-card-header {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.expiry-icon {
  margin-top: 2px;
  flex-shrink: 0;
}

.expiry-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.expiry-title-area {
  flex: 1;
  min-width: 0;
}

.expiry-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  color: var(--color-text-primary);
}

.expiry-days {
  font-size: 12px;
  font-weight: 600;
  margin-top: 4px;
  color: var(--color-text-secondary);
}

.expiry-date {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-primary);
}

.expiry-message {
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.expiry-edit-btn {
  align-self: flex-start;
  margin-top: 4px;
}

@media (max-width: 768px) {
  .expiry-card {
    padding: 12px;
  }

  .expiry-title {
    font-size: 13px;
  }
}
</style>
