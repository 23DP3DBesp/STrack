<template>
  <section class="dashboard-overview">
    <section class="work-hero-card dashboard-hero">
      <div class="work-kicker">{{ t('dashboard.heroKicker') }}</div>
      <h1 class="work-title">{{ t('dashboard.welcome') }}, {{ login || '—' }}</h1>
      <p class="work-subtitle">
        {{ t('dashboard.subtitle') }}
      </p>

      <div class="work-hero-actions">
        <button class="work-solid-btn" type="button" @click="emit('add-car')">
          {{ t('dashboard.addCar') }}
        </button>
        <button class="work-outline-btn" type="button" @click="emit('refresh')">
          {{ t('dashboard.refresh') }}
        </button>
      </div>
    </section>

    <section class="work-grid-4">
      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.cars') }}</div>
        <div class="work-panel-value">{{ stats.cars_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.fuelLogs') }}</div>
        <div class="work-panel-value">{{ stats.fuel_logs_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.repairs') }}</div>
        <div class="work-panel-value">{{ stats.repairs_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.totalSpend') }}</div>
        <div class="work-panel-value">{{ formatCurrency(stats.total_spent) }}</div>
      </article>
    </section>

    <section class="work-grid-3 mt-4">
      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.selectedPeriod') }}</div>
        <div class="work-panel-value small-value">{{ selectedPeriodLabel }}</div>
      </article>

      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.costPerKm') }}</div>
        <div class="work-panel-value small-value">
          {{ costPerKm ? formatCurrency(costPerKm) : '—' }}
        </div>
      </article>

      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.distanceTracked') }}</div>
        <div class="work-panel-value small-value">
          {{ totalDistanceTracked ? `${totalDistanceTracked} km` : '—' }}
        </div>
      </article>
    </section>

    <section v-if="selectedCar" class="work-grid-2 mt-4">
      <ExpiryCard
        type="insurance"
        :expiry-date="selectedCar.insurance_until"
        @edit-expiry="emit('edit-expiry')"
      />
      <ExpiryCard
        type="inspection"
        :expiry-date="selectedCar.inspection_until"
        @edit-expiry="emit('edit-expiry')"
      />
    </section>

    <section class="work-panel mt-4">
      <div class="work-panel-head">
        <div>
          <h3 class="work-panel-title">{{ t('dashboard.periodFilter') }}</h3>
          <div class="work-item-sub">{{ t('dashboard.periodFilterSubtitle') }}</div>
        </div>
      </div>

      <div class="period-chip-row">
        <button
          v-for="option in periodOptions"
          :key="option.value"
          type="button"
          class="period-chip"
          :class="{ active: selectedPeriod === option.value }"
          @click="selectedPeriod = option.value"
        >
          {{ option.label }}
        </button>
      </div>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel chart-panel">
        <div class="work-panel-head">
          <div>
            <h3 class="work-panel-title">{{ t('dashboard.fuelConsumptionByMonth') }}</h3>
            <div class="work-item-sub">
              {{
                selectedCar
                  ? `${selectedCar.brand} ${selectedCar.model}`
                  : t('dashboard.selectCarFuelTrend')
              }}
            </div>
          </div>
        </div>

        <ChartWrapper
          v-if="fuelConsumptionChart.length"
          type="line"
          :data="fuelConsumptionData"
          :options="chartOptions"
        />

        <div v-else class="chart-empty-state">
          {{ t('dashboard.needFuelLogsForChart') }}
        </div>
      </article>

      <article class="work-panel chart-panel">
        <div class="work-panel-head">
          <div>
            <h3 class="work-panel-title">{{ t('dashboard.monthlyExpenseBreakdown') }}</h3>
            <div class="work-item-sub">{{ t('dashboard.expenseBreakdownSubtitle') }}</div>
          </div>
        </div>

        <div v-if="monthlyExpenseChart && monthlyExpenseChart.length > 0">
          <ChartWrapper
            type="bar"
            :data="monthlyExpenseData"
            :options="stackedChartOptions"
          />
        </div>

        <div v-else class="chart-empty-state">
          {{ t('dashboard.needExpensesForBreakdown') }}
        </div>
      </article>
    </section>
  </section>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import ChartWrapper from '../../../components/ChartWrapper.vue'
import ExpiryCard from './ExpiryCard.vue'
import { formatCurrency } from '../formatters'

const selectedPeriod = defineModel('selectedPeriod', { type: String, default: 'all' })

defineProps({
  login: {
    type: String,
    default: ''
  },
  selectedCar: {
    type: Object,
    default: null
  },
  stats: {
    type: Object,
    default: () => ({
      cars_total: 0,
      fuel_logs_total: 0,
      repairs_total: 0,
      total_spent: 0
    })
  },
  selectedPeriodLabel: {
    type: String,
    default: ''
  },
  costPerKm: {
    type: [Number, String],
    default: 0
  },
  totalDistanceTracked: {
    type: [Number, String],
    default: 0
  },
  fuelConsumptionChart: {
    type: Array,
    default: () => []
  },
  fuelConsumptionData: {
    type: Object,
    default: () => ({})
  },
  monthlyExpenseChart: {
    type: Array,
    default: () => []
  },
  monthlyExpenseData: {
    type: Object,
    default: () => ({})
  },
  chartOptions: {
    type: Object,
    default: () => ({})
  },
  stackedChartOptions: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['add-car', 'refresh', 'edit-expiry'])
const { t } = useI18n()

// Debug logging
watch(selectedPeriod, (newVal, oldVal) => {
  console.log('🔘 selectedPeriod prop changed:', oldVal, '->', newVal)
})

watch(() => monthlyExpenseChart, (newVal) => {
  console.log('monthlyExpenseChart changed:', newVal?.length, newVal)
}, { deep: true })

watch(() => monthlyExpenseData, (newVal) => {
  console.log('monthlyExpenseData changed:', newVal?.datasets?.length, newVal)
}, { deep: true })

const periodOptions = computed(() => [
  { value: 'all', label: t('dashboard.allTime') },
  { value: '3m', label: t('dashboard.months3') },
  { value: '6m', label: t('dashboard.months6') },
  { value: '12m', label: t('dashboard.months12') }
])
</script>
