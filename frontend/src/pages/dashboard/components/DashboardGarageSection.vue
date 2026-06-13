<template>
  <section id="garage" class="work-grid-dashboard mt-4">
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
          <button type="button" class="garage-car-item-main" @click="emit('select-car', car.id)">
            <div class="garage-car-meta">
              <div class="work-item-title">{{ car.brand }} {{ car.model }}</div>
              <div class="work-item-sub">
                {{ car.year }} · {{ car.engine_volume }}L · {{ car.license_plate }}
              </div>
            </div>

            <div class="car-item-metrics">
              <span>{{ car.fuel_logs_count }} {{ t('dashboard.fuelTab').toLowerCase() }}</span>
              <span>{{ car.repairs_count }} {{ t('dashboard.repairsTab').toLowerCase() }}</span>
              <span>{{ car.mods_count }} {{ t('dashboard.modsTab').toLowerCase() }}</span>
              <span>{{ car.documents_count }} {{ t('dashboard.documentsTab').toLowerCase() }}</span>
              <span>{{ car.wishlist_items_count }} {{ t('dashboard.wishlistTab').toLowerCase() }}</span>
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
          <div class="work-item-sub">
            {{ selectedCar.year }} · {{ selectedCar.engine_volume }}L ·
            {{ selectedCar.license_plate }}
          </div>
        </div>

        <div class="toolbar-actions top-actions">
          <v-btn
            class="ui-btn-secondary compact"
            variant="outlined"
            @click="emit('edit-car', selectedCar)"
          >
            {{ t('dashboard.edit') }}
          </v-btn>
          <v-btn
            class="ui-btn-secondary compact"
            color="error"
            variant="outlined"
            @click="emit('remove-car', selectedCar)"
          >
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
          <v-tab value="ownership">{{ t('dashboard.ownershipTab') }}</v-tab>
          <v-tab value="media">{{ t('dashboard.mediaTab') }}</v-tab>
          <v-tab value="wishlist">{{ t('dashboard.wishlistTab') }}</v-tab>
        </v-tabs>

        <v-window v-model="activeTab" class="mt-4">
          <v-window-item value="fuel" id="fuel">
            <div class="toolbar-line filters">
              <DateInput
                v-model="fuelFilters.date_from"
                :label="t('dashboard.fuelingDateFrom')"
                :placeholder="t('dashboard.selectFuelingDate')"
              />

              <DateInput
                v-model="fuelFilters.date_to"
                :label="t('dashboard.fuelingDateTo')"
                :placeholder="t('dashboard.selectFuelingDate')"
              />

              <div class="filter-actions">
                <v-btn
                  class="ui-btn-secondary compact"
                  variant="outlined"
                  @click="emit('apply-fuel-filters')"
                >
                  {{ t('dashboard.apply') }}
                </v-btn>
                <v-btn
                  class="ui-btn-secondary compact"
                  variant="text"
                  @click="emit('reset-fuel-filters')"
                >
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
                    <td>
                      {{
                        item.fuel_consumption
                          ? `${item.fuel_consumption} L/100km`
                          : t('dashboard.pendingNextLog')
                      }}
                    </td>
                    <td class="actions-col">
                      <button
                        class="table-action"
                        type="button"
                        @click="emit('open-fuel-dialog', item)"
                      >
                        {{ t('dashboard.edit') }}
                      </button>
                      <button
                        class="table-action danger"
                        type="button"
                        @click="emit('remove-fuel-log', item)"
                      >
                        {{ t('dashboard.delete') }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-window-item>

          <v-window-item value="repairs" id="repairs">
            <div class="toolbar-line filters">
              <DateInput
                v-model="repairFilters.date_from"
                :label="t('dashboard.repairDateFrom')"
                :placeholder="t('dashboard.selectRepairDate')"
              />

              <DateInput
                v-model="repairFilters.date_to"
                :label="t('dashboard.repairDateTo')"
                :placeholder="t('dashboard.selectRepairDate')"
              />

              <div class="filter-actions">
                <v-btn
                  class="ui-btn-secondary compact"
                  variant="outlined"
                  @click="emit('apply-repair-filters')"
                >
                  {{ t('dashboard.apply') }}
                </v-btn>
                <v-btn
                  class="ui-btn-secondary compact"
                  variant="text"
                  @click="emit('reset-repair-filters')"
                >
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
                      <button
                        class="table-action"
                        type="button"
                        @click="emit('open-repair-dialog', item)"
                      >
                        {{ t('dashboard.edit') }}
                      </button>
                      <button
                        class="table-action danger"
                        type="button"
                        @click="emit('remove-repair', item)"
                      >
                        {{ t('dashboard.delete') }}
                      </button>
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
                      <button
                        class="table-action"
                        type="button"
                        @click="emit('open-mod-dialog', item)"
                      >
                        {{ t('dashboard.edit') }}
                      </button>
                      <button
                        class="table-action danger"
                        type="button"
                        @click="emit('remove-mod', item)"
                      >
                        {{ t('dashboard.delete') }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-window-item>

          <v-window-item value="ownership" id="ownership">
            <div class="work-grid-2">
              <article class="work-panel">
                <div class="work-panel-head">
                  <div>
                    <h3 class="work-panel-title">{{ t('dashboard.ownershipHistoryTitle') }}</h3>
                    <div class="work-item-sub">
                      {{ t('dashboard.ownershipHistorySubtitle') }}
                    </div>
                  </div>
                </div>

                <div class="journal-table-wrap">
                  <v-table class="journal-table">
                    <thead>
                      <tr>
                        <th>{{ t('dashboard.month') }}</th>
                        <th>{{ t('dashboard.fuelLogs') }}</th>
                        <th>{{ t('dashboard.repairs') }}</th>
                        <th>{{ t('dashboard.mods') }}</th>
                        <th>{{ t('dashboard.totalSpend') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in ownershipHistory" :key="item.month" class="hover-row">
                        <td>{{ formatMonth(item.month) }}</td>
                        <td>{{ formatCurrency(item.fuel) }}</td>
                        <td>{{ formatCurrency(item.repairs) }}</td>
                        <td>{{ formatCurrency(item.mods) }}</td>
                        <td>{{ formatCurrency(item.total) }}</td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div v-if="!ownershipHistory.length" class="empty-card">
                    {{ t('dashboard.noOwnershipHistory') }}
                  </div>
                </div>
              </article>

              <article class="work-panel">
                <div class="work-panel-head">
                  <div>
                    <h3 class="work-panel-title">{{ t('dashboard.recurringCostsTitle') }}</h3>
                    <div class="work-item-sub">
                      {{ t('dashboard.recurringCostsSubtitle') }}
                    </div>
                  </div>
                </div>

                <div class="journal-table-wrap">
                  <v-table class="journal-table">
                    <thead>
                      <tr>
                        <th>{{ t('dashboard.name') }}</th>
                        <th>{{ t('dashboard.cost') }}</th>
                        <th>{{ t('dashboard.interval') }}</th>
                        <th>{{ t('dashboard.nextDueDate') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in garage.recurringCosts" :key="item.id" class="hover-row">
                        <td>{{ item.name }}</td>
                        <td>{{ formatCurrency(item.amount) }}</td>
                        <td>{{ formatInterval(item.interval) }}</td>
                        <td>{{ formatDate(item.next_due_date) }}</td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div v-if="!garage.recurringCosts.length" class="empty-card">
                    {{ t('dashboard.noRecurringCosts') }}
                  </div>
                </div>
              </article>
            </div>
          </v-window-item>

          <v-window-item value="media" id="media">
            <div class="work-grid-2">
              <article class="work-panel">
                <div class="work-panel-head">
                  <div>
                    <h3 class="work-panel-title">{{ t('dashboard.photosTitle') }}</h3>
                    <div class="work-item-sub">{{ t('dashboard.photosSubtitle') }}</div>
                  </div>
                </div>

                <div class="journal-table-wrap">
                  <v-table class="journal-table">
                    <thead>
                      <tr>
                        <th>{{ t('dashboard.name') }}</th>
                        <th>{{ t('dashboard.type') }}</th>
                        <th>{{ t('dashboard.actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in photoDocuments" :key="item.id" class="hover-row">
                        <td>{{ item.title }}</td>
                        <td>{{ item.type }}</td>
                        <td>
                          <a
                            class="table-action"
                            :href="item.file_url"
                            target="_blank"
                            rel="noreferrer"
                          >
                            {{ t('dashboard.openLink') }}
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div v-if="!photoDocuments.length" class="empty-card">
                    {{ t('dashboard.noPhotos') }}
                  </div>
                </div>
              </article>

              <article class="work-panel">
                <div class="work-panel-head">
                  <div>
                    <h3 class="work-panel-title">{{ t('dashboard.documentsTitle') }}</h3>
                    <div class="work-item-sub">{{ t('dashboard.documentsSubtitle') }}</div>
                  </div>
                </div>

                <div class="journal-table-wrap">
                  <v-table class="journal-table">
                    <thead>
                      <tr>
                        <th>{{ t('dashboard.name') }}</th>
                        <th>{{ t('dashboard.type') }}</th>
                        <th>{{ t('dashboard.actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in regularDocuments" :key="item.id" class="hover-row">
                        <td>{{ item.title }}</td>
                        <td>{{ item.type }}</td>
                        <td>
                          <a
                            class="table-action"
                            :href="item.file_url"
                            target="_blank"
                            rel="noreferrer"
                          >
                            {{ t('dashboard.openLink') }}
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div v-if="!regularDocuments.length" class="empty-card">
                    {{ t('dashboard.noDocuments') }}
                  </div>
                </div>
              </article>
            </div>
          </v-window-item>

          <v-window-item value="wishlist" id="wishlist">
            <article class="work-panel">
              <div class="work-panel-head">
                <div>
                  <h3 class="work-panel-title">{{ t('dashboard.wishlistTitle') }}</h3>
                  <div class="work-item-sub">{{ t('dashboard.wishlistSubtitle') }}</div>
                </div>
              </div>

              <div class="journal-table-wrap">
                <v-table class="journal-table">
                  <thead>
                    <tr>
                      <th>{{ t('dashboard.priority') }}</th>
                      <th>{{ t('dashboard.name') }}</th>
                      <th>{{ t('dashboard.category') }}</th>
                      <th>{{ t('dashboard.cost') }}</th>
                      <th>{{ t('dashboard.status') }}</th>
                      <th>{{ t('dashboard.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in garage.wishlistItems" :key="item.id" class="hover-row">
                      <td>{{ priorityLabel(item.priority) }}</td>
                      <td>
                        <div>{{ item.name }}</div>
                        <div class="work-item-sub">{{ item.store || '—' }}</div>
                      </td>
                      <td>{{ item.category }}</td>
                      <td>{{ item.estimated_price ? formatCurrency(item.estimated_price) : '—' }}</td>
                      <td>{{ formatStatus(item.status) }}</td>
                      <td>
                        <a
                          v-if="item.url"
                          class="table-action"
                          :href="item.url"
                          target="_blank"
                          rel="noreferrer"
                        >
                          {{ t('dashboard.openLink') }}
                        </a>
                        <span v-else>—</span>
                      </td>
                    </tr>
                  </tbody>
                </v-table>

                <div v-if="!garage.wishlistItems.length" class="empty-card">
                  {{ t('dashboard.noWishlistItems') }}
                </div>
              </div>
            </article>
          </v-window-item>
        </v-window>
      </template>
    </article>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DateInput from '../../../components/DateInput.vue'
import { formatCurrency, formatDate } from '../formatters'

const activeTab = defineModel('activeTab', { type: String, default: 'fuel' })
const carSearch = defineModel('carSearch', { type: String, default: '' })
const fuelFilters = defineModel('fuelFilters', {
  type: Object,
  default: () => ({ date_from: '', date_to: '' })
})
const repairFilters = defineModel('repairFilters', {
  type: Object,
  default: () => ({ date_from: '', date_to: '' })
})

const props = defineProps({
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

const ownershipHistory = computed(() => props.garage.monthlyExpenseBreakdown || [])

const photoTypeKeywords = ['photo', 'photos', 'image', 'images', 'gallery', 'picture', 'pictures']

const isPhotoDocument = (item) => {
  const type = String(item?.type || '').toLowerCase()
  const title = String(item?.title || '').toLowerCase()

  return photoTypeKeywords.some((keyword) => type.includes(keyword) || title.includes(keyword))
}

const photoDocuments = computed(() => (props.garage.documents || []).filter(isPhotoDocument))

const regularDocuments = computed(() =>
  (props.garage.documents || []).filter((item) => !isPhotoDocument(item))
)

const formatMonth = (value) => {
  if (!value) return '—'

  const parsed = new Date(`${value}-01`)
  if (Number.isNaN(parsed.getTime())) return value

  return new Intl.DateTimeFormat(undefined, { month: 'short', year: 'numeric' }).format(parsed)
}

const formatInterval = (value) => {
  if (!value) return '—'
  return String(value).charAt(0).toUpperCase() + String(value).slice(1)
}

const priorityLabel = (value) => {
  const priority = Number(value || 0)
  return priority ? `P${priority}` : '—'
}

const formatStatus = (value) => {
  if (!value) return '—'
  return String(value).charAt(0).toUpperCase() + String(value).slice(1)
}

const updateCarSearch = (value) => {
  carSearch.value = String(value || '')
  emit('search-change')
}
</script>
