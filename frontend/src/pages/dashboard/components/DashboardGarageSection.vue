<template>
  <section class="work-grid-dashboard mt-4">
    <article class="work-panel">
      <div class="work-panel-head">
        <div>
          <div class="work-panel-title">{{ t('dashboard.carsSectionTitle') }}</div>
          <div class="work-item-sub">{{ t('dashboard.searchByBrandModel') }}</div>
        </div>

        <v-btn class="ui-btn-primary compact" @click="emit('open-car-dialog')">
          {{ t('dashboard.add') }}
        </v-btn>
      </div>

      <div class="toolbar-line">
        <v-text-field
          :model-value="carSearch"
          :label="t('dashboard.searchCars')"
          density="comfortable"
          hide-details
          clearable
          @update:model-value="updateCarSearch"
        />
      </div>

      <v-progress-linear v-if="garage.carsLoading" indeterminate class="mb-3" />

      <div v-if="!garage.cars.length" class="empty-card">
        {{ t('dashboard.addFirstCar') }}
      </div>

      <div v-else class="garage-car-list">
        <div
          v-for="car in garage.cars"
          :key="car.id"
          class="garage-car-item-wrap"
          :class="{ active: garage.selectedCarId === car.id }"
        >
          <button
            type="button"
            class="garage-car-item-main"
            @click="emit('select-car', car.id)"
          >
            <div class="garage-car-meta">
              <div class="work-item-title">{{ car.brand }} {{ car.model }}</div>
              <div class="work-item-sub">{{ car.year }} · {{ car.engine_volume }}L · {{ car.license_plate }}</div>
            </div>

            <div class="car-item-metrics">
              <span>{{ car.fuel_logs_count }} {{ t('dashboard.fuelTab').toLowerCase() }}</span>
              <span>{{ car.repairs_count }} {{ t('dashboard.repairsTab').toLowerCase() }}</span>
              <span>{{ car.mods_count }} {{ t('dashboard.modsTab').toLowerCase() }}</span>
            </div>
          </button>

          <div class="garage-car-item-actions">
            <v-btn
              class="ui-btn-secondary compact"
              size="small"
              variant="outlined"
              @click.stop="emit('edit-car', car)"
            >
              {{ t('dashboard.edit') }}
            </v-btn>

            <v-btn
              class="ui-btn-secondary compact"
              size="small"
              color="error"
              variant="outlined"
              @click.stop="emit('remove-car', car)"
            >
              {{ t('dashboard.delete') }}
            </v-btn>
          </div>
        </div>
      </div>
    </article>

    <article class="work-panel wide-panel">
      <div v-if="selectedCar" class="work-panel-head align-start">
        <div>
          <div class="work-panel-title">{{ selectedCar.brand }} {{ selectedCar.model }}</div>
          <div class="work-item-sub">{{ selectedCar.year }} · {{ selectedCar.engine_volume }}L · {{ selectedCar.license_plate }}</div>
        </div>

        <div class="toolbar-actions top-actions">
          <v-btn class="ui-btn-secondary compact" variant="outlined" @click="emit('edit-car', selectedCar)">
            {{ t('dashboard.edit') }}
          </v-btn>
          <v-btn class="ui-btn-secondary compact" color="error" variant="outlined" @click="emit('remove-car', selectedCar)">
            {{ t('dashboard.delete') }}
          </v-btn>
        </div>
      </div>

      <div v-else class="empty-card">{{ t('dashboard.selectCarToManage') }}</div>

      <template v-if="selectedCar">
        <v-tabs v-model="activeTab" class="garage-tabs">
          <v-tab value="fuel">{{ t('dashboard.fuelTab') }}</v-tab>
          <v-tab value="repairs">{{ t('dashboard.repairsTab') }}</v-tab>
          <v-tab value="mods">{{ t('dashboard.modsTab') }}</v-tab>
        </v-tabs>

        <v-window v-model="activeTab" class="mt-4">
          <v-window-item value="fuel">
            <div class="toolbar-line filters">
              <DateInput
                v-model="fuelFilters.date"
                :label="t('dashboard.fuelingDate')"
                :placeholder="t('dashboard.selectFuelingDate')"
              />

              <div class="filter-actions">
                <v-btn class="ui-btn-secondary compact" variant="outlined" @click="emit('apply-fuel-filters')">
                  {{ t('dashboard.apply') }}
                </v-btn>
                <v-btn class="ui-btn-secondary compact" variant="text" @click="emit('reset-fuel-filters')">
                  {{ t('dashboard.reset') }}
                </v-btn>
                <v-btn class="ui-btn-primary compact" @click="emit('open-fuel-dialog')">
                  {{ t('dashboard.add') }}
                </v-btn>
              </div>
            </div>

            <div class="journal-table-wrap">
              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>{{ t('dashboard.date') }}</th>
                    <th>{{ t('dashboard.liters') }}</th>
                    <th>{{ t('dashboard.total') }}</th>
                    <th>{{ t('dashboard.pricePerLiter') }}</th>
                    <th>{{ t('dashboard.mileage') }}</th>
                    <th>{{ t('dashboard.consumption') }}</th>
                    <th class="actions-col">{{ t('dashboard.actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in garage.fuelLogs" :key="item.id" class="hover-row">
                    <td>{{ formatDate(item.date) }}</td>
                    <td>{{ item.liters }}</td>
                    <td>{{ formatCurrency(item.total_price) }}</td>
                    <td>{{ item.price_per_liter }}</td>
                    <td>{{ item.mileage }} km</td>
                    <td>{{ item.fuel_consumption ? `${item.fuel_consumption} L/100km` : t('dashboard.pendingNextLog') }}</td>
                    <td class="actions-col">
                      <button class="table-action" type="button" @click="emit('open-fuel-dialog', item)">{{ t('dashboard.edit') }}</button>
                      <button class="table-action danger" type="button" @click="emit('remove-fuel-log', item)">{{ t('dashboard.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-window-item>

          <v-window-item value="repairs">
            <div class="toolbar-line filters">
              <DateInput
                v-model="repairFilters.date_from"
                :label="t('dashboard.repairDateFrom')"
                :placeholder="t('dashboard.selectRepairDate')"
              />

              <div class="filter-actions">
                <v-btn class="ui-btn-secondary compact" variant="outlined" @click="emit('apply-repair-filters')">
                  {{ t('dashboard.apply') }}
                </v-btn>
                <v-btn class="ui-btn-secondary compact" variant="text" @click="emit('reset-repair-filters')">
                  {{ t('dashboard.reset') }}
                </v-btn>
                <v-btn class="ui-btn-primary compact" @click="emit('open-repair-dialog')">
                  {{ t('dashboard.add') }}
                </v-btn>
              </div>
            </div>

            <div class="journal-table-wrap">
              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>{{ t('dashboard.date') }}</th>
                    <th>{{ t('dashboard.type') }}</th>
                    <th>{{ t('dashboard.cost') }}</th>
                    <th>{{ t('dashboard.mileage') }}</th>
                    <th>{{ t('dashboard.description') }}</th>
                    <th class="actions-col">{{ t('dashboard.actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in garage.repairs" :key="item.id" class="hover-row">
                    <td>{{ formatDate(item.date) }}</td>
                    <td>{{ item.type }}</td>
                    <td>{{ formatCurrency(item.cost) }}</td>
                    <td>{{ item.mileage }} km</td>
                    <td>{{ item.description || '—' }}</td>
                    <td class="actions-col">
                      <button class="table-action" type="button" @click="emit('open-repair-dialog', item)">{{ t('dashboard.edit') }}</button>
                      <button class="table-action danger" type="button" @click="emit('remove-repair', item)">{{ t('dashboard.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-window-item>

          <v-window-item value="mods">
            <div class="toolbar-line filters">
              <div class="toolbar-spacer"></div>
              <v-btn class="ui-btn-primary compact" @click="emit('open-mod-dialog')">
                {{ t('dashboard.add') }}
              </v-btn>
            </div>

            <div class="journal-table-wrap">
              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>{{ t('dashboard.dateInstalled') }}</th>
                    <th>{{ t('dashboard.name') }}</th>
                    <th>{{ t('dashboard.cost') }}</th>
                    <th>{{ t('dashboard.performanceImpact') }}</th>
                    <th class="actions-col">{{ t('dashboard.actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in garage.mods" :key="item.id" class="hover-row">
                    <td>{{ formatDate(item.date_installed) }}</td>
                    <td>{{ item.name }}</td>
                    <td>{{ formatCurrency(item.cost) }}</td>
                    <td>{{ item.performance_impact }}</td>
                    <td class="actions-col">
                      <button class="table-action" type="button" @click="emit('open-mod-dialog', item)">{{ t('dashboard.edit') }}</button>
                      <button class="table-action danger" type="button" @click="emit('remove-mod', item)">{{ t('dashboard.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-window-item>
        </v-window>
      </template>
    </article>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import DateInput from '../../../components/DateInput.vue'
import { formatCurrency, formatDate } from '../formatters'

const activeTab = defineModel('activeTab', { type: String, default: 'fuel' })
const carSearch = defineModel('carSearch', { type: String, default: '' })
const fuelFilters = defineModel('fuelFilters', {
  type: Object,
  default: () => ({ date: '' })
})
const repairFilters = defineModel('repairFilters', {
  type: Object,
  default: () => ({ date_from: '', date_to: '' })
})

defineProps({
  garage: {
    type: Object,
    required: true
  },
  selectedCar: {
    type: Object,
    default: null
  }
})

const emit = defineEmits([
  'select-car',
  'edit-car',
  'remove-car',
  'open-car-dialog',
  'open-fuel-dialog',
  'remove-fuel-log',
  'open-repair-dialog',
  'remove-repair',
  'open-mod-dialog',
  'remove-mod',
  'apply-fuel-filters',
  'reset-fuel-filters',
  'apply-repair-filters',
  'reset-repair-filters',
  'search-change'
])

const { t } = useI18n()

const updateCarSearch = (value) => {
  carSearch.value = String(value || '')
  emit('search-change')
}
</script>
