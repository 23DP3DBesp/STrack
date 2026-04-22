<template>
  <v-menu
    v-model="open"
    :close-on-content-click="false"
    location="bottom start"
  >
    <template #activator="{ props }">
      <v-text-field
        v-bind="props"
        :label="label"
        :model-value="displayValue"
        :placeholder="placeholder"
        :rules="rules"
        readonly
        variant="outlined"
        density="comfortable"
        hide-details="auto"
        class="date-text-field"
      >
        <template #prepend-inner>
          <v-icon icon="mdi-calendar-month-outline" size="20" />
        </template>

        <template #append-inner>
          <button
            v-if="clearable && modelValue"
            type="button"
            class="date-clear-btn"
            @click.stop="clearValue"
          >
            Clear
          </button>
        </template>
      </v-text-field>
    </template>

    <v-card class="date-menu-card">
      <v-date-picker
        :model-value="pickerValue"
        color="primary"
        hide-header
        @update:model-value="onPick"
      />

      <div class="date-menu-actions">
        <v-btn
          class="ui-btn-secondary compact"
          variant="text"
          @click="clearValue"
        >
          Clear
        </v-btn>

        <v-btn
          class="ui-btn-primary compact"
          @click="open = false"
        >
          Done
        </v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Array, Object, Date, Number],
    default: ''
  },
  label: {
    type: String,
    default: 'Date'
  },
  placeholder: {
    type: String,
    default: 'Select date'
  },
  clearable: {
    type: Boolean,
    default: true
  },
  rules: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)

const normalizeToIsoDate = (value) => {
  if (!value) return ''

  if (Array.isArray(value)) {
    return value.length ? normalizeToIsoDate(value[value.length - 1]) : ''
  }

  if (value instanceof Date) {
    const year = value.getFullYear()
    const month = String(value.getMonth() + 1).padStart(2, '0')
    const day = String(value.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  if (typeof value === 'object') {
    if (value.date) return normalizeToIsoDate(value.date)
    if (value.value) return normalizeToIsoDate(value.value)
  }

  const str = String(value)

  if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
    const [year, month, day] = str.split('-')
    const localDate = new Date(Number(year), Number(month) - 1, Number(day), 12, 0, 0)

    const isoYear = localDate.getFullYear()
    const isoMonth = String(localDate.getMonth() + 1).padStart(2, '0')
    const isoDay = String(localDate.getDate()).padStart(2, '0')

    return `${isoYear}-${isoMonth}-${isoDay}`
  }

  const fromDate = new Date(str)

  if (!Number.isNaN(fromDate.getTime())) {
    const year = fromDate.getFullYear()
    const month = String(fromDate.getMonth() + 1).padStart(2, '0')
    const day = String(fromDate.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  return str.slice(0, 10)
}

const pickerValue = computed(() => normalizeToIsoDate(props.modelValue) || null)

const displayValue = computed(() => {
  const iso = normalizeToIsoDate(props.modelValue)
  if (!iso) return ''

  const [year, month, day] = iso.split('-')
  if (!year || !month || !day) return iso

  return `${day}.${month}.${year}`
})

const onPick = (value) => {
  const iso = normalizeToIsoDate(value)
  if (!iso) return

  emit('update:modelValue', iso)
  open.value = false
}

const clearValue = () => {
  emit('update:modelValue', '')
  open.value = false
}
</script>