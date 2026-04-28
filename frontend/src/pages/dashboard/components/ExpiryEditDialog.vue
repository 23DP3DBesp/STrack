<template>
  <v-dialog v-model="open" max-width="520">
    <v-card class="dialog-card">
      <h3 class="work-panel-title mb-4">{{ t('dashboard.updateExpiryDates') }}</h3>

      <div class="expiry-edit-grid">
        <div class="expiry-edit-car-info">
          <v-icon icon="mdi-car" size="32" />
          <div>
            <div class="car-name">{{ carLabel }}</div>
            <div class="car-plate">{{ selectedCar?.license_plate || '—' }}</div>
          </div>
        </div>
      </div>

      <v-form id="expiryForm" ref="expiryFormRef" @submit.prevent="submitForm">
        <div class="dialog-grid">
          <DateInput
            v-model="form.insurance_until"
            :label="t('dashboard.insurance')"
            :placeholder="t('dashboard.selectInsuranceExpiry')"
          />
          <DateInput
            v-model="form.inspection_until"
            :label="t('dashboard.technicalInspection')"
            :placeholder="t('dashboard.selectInspectionExpiry')"
          />
        </div>
      </v-form>

      <div class="dialog-actions">
        <v-btn class="ui-btn-secondary" variant="text" @click="open = false">
          {{ t('dashboard.cancel') }}
        </v-btn>
        <v-btn class="ui-btn-primary" :loading="saving" type="submit" form="expiryForm">
          {{ t('dashboard.save') }}
        </v-btn>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DateInput from '../../../components/DateInput.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  selectedCar: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'save'])

const { t } = useI18n()
const expiryFormRef = ref(null)
const saving = ref(false)

const open = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const carLabel = computed(() => {
  if (!props.selectedCar) return '—'
  return `${props.selectedCar.brand} ${props.selectedCar.model} (${props.selectedCar.year})`
})

const form = ref({
  insurance_until: '',
  inspection_until: ''
})

watch(
  () => props.selectedCar,
  (newCar) => {
    if (newCar) {
      form.value = {
        insurance_until: newCar.insurance_until || '',
        inspection_until: newCar.inspection_until || ''
      }
    }
  },
  { immediate: true, deep: true }
)

const submitForm = async () => {
  const validation = await expiryFormRef.value?.validate()
  if (!validation?.valid) return

  saving.value = true
  try {
    await emit('save', {
      car_id: props.selectedCar.id,
      insurance_until: form.value.insurance_until,
      inspection_until: form.value.inspection_until
    })
    open.value = false
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.expiry-edit-grid {
  display: grid;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
}

.expiry-edit-car-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--color-bg-secondary, #f5f5f5);
  border-radius: 8px;
}

.car-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0;
}

.car-plate {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 4px;
}

.dialog-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

@media (max-width: 480px) {
  .dialog-grid {
    grid-template-columns: 1fr;
  }
}
</style>
