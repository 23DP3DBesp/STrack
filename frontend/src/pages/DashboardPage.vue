<template>
  <MainLayout>
    <DashboardOverviewSection
      v-model:selected-period="selectedPeriodProxy"
      :login="auth.user?.login"
      :selected-car="selectedCar"
      :stats="garage.summary.stats"
      :selected-period-label="garage.selectedPeriodLabel"
      :cost-per-km="garage.costPerKm"
      :total-distance-tracked="garage.totalDistanceTracked"
      :fuel-consumption-chart="fuelConsumptionChart"
      :fuel-consumption-data="fuelConsumptionData"
      :monthly-expense-chart="monthlyExpenseChart"
      :monthly-expense-data="monthlyExpenseData"
      :chart-options="chartOptions"
      :stacked-chart-options="stackedChartOptions"
      @add-car="openCarDialog"
      @refresh="reloadAll"
      @edit-expiry="openExpiryDialog"
    />

    <DashboardGarageSection
      v-model:active-tab="activeTab"
      v-model:car-search="carSearch"
      v-model:fuel-filters="fuelFilters"
      v-model:repair-filters="repairFilters"
      :garage="garage"
      :selected-car="selectedCar"
      @select-car="garage.selectCar"
      @open-car-dialog="openCarDialog"
      @edit-car="openCarDialog"
      @remove-car="removeCar"
      @search-change="onCarSearch"
      @open-fuel-dialog="openFuelDialog"
      @remove-fuel-log="removeFuelLog"
      @open-repair-dialog="openRepairDialog"
      @remove-repair="removeRepair"
      @open-mod-dialog="openModDialog"
      @remove-mod="removeMod"
      @apply-fuel-filters="applyFuelFilters"
      @reset-fuel-filters="resetFuelFilters"
      @apply-repair-filters="applyRepairFilters"
      @reset-repair-filters="resetRepairFilters"
    />

    <v-dialog v-model="carDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ carForm.id ? t('dashboard.editCar') : t('dashboard.addCar') }}</h3>

        <v-form id="carForm" ref="carFormRef" @submit.prevent="submitCar">
          <div class="dialog-grid">
            <v-text-field v-model="carForm.brand" :label="t('dashboard.brand')" :rules="[rules.required]" />
            <v-text-field v-model="carForm.model" :label="t('dashboard.model')" :rules="[rules.required]" />
            <v-text-field v-model="carForm.year" :label="t('dashboard.year')" type="number" :rules="[rules.required, rules.year]" />
            <v-text-field v-model="carForm.engine_volume" :label="t('dashboard.engineVolume')" type="number" step="0.1" :rules="[rules.required, rules.gt(0)]" />
            <v-text-field
              v-model="carForm.license_plate"
              :label="t('dashboard.licensePlate')"
              :rules="[rules.required]"
              @update:model-value="(v) => carForm.license_plate = String(v || '').toUpperCase()"
            />
            <DateInput v-model="carForm.insurance_until" :label="t('dashboard.insuranceUntil')" :placeholder="t('dashboard.selectInsuranceExpiry')" />
            <DateInput v-model="carForm.inspection_until" :label="t('dashboard.inspectionUntil')" :placeholder="t('dashboard.selectInspectionExpiry')" />
          </div>
        </v-form>

        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="carDialog = false">{{ t('dashboard.cancel') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingCar" type="submit" form="carForm">{{ t('dashboard.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="fuelDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ fuelForm.id ? t('dashboard.editFuelLog') : t('dashboard.addFuelLog') }}</h3>

        <v-form id="fuelForm" ref="fuelFormRef" @submit.prevent="submitFuelLog">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>

          <div class="dialog-grid">
            <DateInput v-model="fuelForm.date" :label="t('dashboard.date')" :placeholder="t('dashboard.selectFuelingDate')" :rules="[rules.requiredDate]" />
            <v-text-field v-model="fuelForm.liters" :label="t('dashboard.liters')" type="number" step="0.01" :rules="[rules.required, rules.gt(0)]" />
            <v-text-field v-model="fuelForm.total_price" :label="t('dashboard.total')" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-text-field :model-value="fuelPricePerLiter" :label="t('dashboard.pricePerLiter')" readonly suffix="€/L" />
            <v-text-field v-model="fuelForm.mileage" :label="t('dashboard.mileage')" type="number" :rules="[rules.required, rules.integerMin(0)]" />
          </div>

          <div class="dialog-note">{{ t('dashboard.fuelConsumptionCalculated') }}</div>
        </v-form>

        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="fuelDialog = false">{{ t('dashboard.cancel') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingFuel" type="submit" form="fuelForm">{{ t('dashboard.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="repairDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ repairForm.id ? t('dashboard.editRepair') : t('dashboard.addRepair') }}</h3>

        <v-form id="repairForm" ref="repairFormRef" @submit.prevent="submitRepair">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>

          <div class="dialog-grid">
            <DateInput v-model="repairForm.date" :label="t('dashboard.date')" :placeholder="t('dashboard.selectRepairDate')" :rules="[rules.requiredDate]" />
            <v-text-field v-model="repairForm.type" :label="t('dashboard.type')" :rules="[rules.required]" />
            <v-text-field v-model="repairForm.cost" :label="t('dashboard.cost')" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-text-field v-model="repairForm.mileage" :label="t('dashboard.mileage')" type="number" :rules="[rules.required, rules.integerMin(0)]" />
            <v-textarea v-model="repairForm.description" :label="t('dashboard.description')" rows="3" class="span-2" />
          </div>
        </v-form>

        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="repairDialog = false">{{ t('dashboard.cancel') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingRepair" type="submit" form="repairForm">{{ t('dashboard.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="modDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ modForm.id ? t('dashboard.editMod') : t('dashboard.addMod') }}</h3>

        <v-form id="modForm" ref="modFormRef" @submit.prevent="submitMod">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>

          <div class="dialog-grid">
            <DateInput v-model="modForm.date_installed" :label="t('dashboard.dateInstall')" :placeholder="t('dashboard.selectInstallDate')" :rules="[rules.requiredDate]" />
            <v-text-field v-model="modForm.name" :label="t('dashboard.name')" :rules="[rules.required]" />
            <v-text-field v-model="modForm.cost" :label="t('dashboard.cost')" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-textarea v-model="modForm.performance_impact" :label="t('dashboard.performanceImpact')" rows="3" class="span-2" :rules="[rules.required]" />
          </div>
        </v-form>

        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="modDialog = false">{{ t('dashboard.cancel') }}</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingMod" type="submit" form="modForm">{{ t('dashboard.save') }}</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog" max-width="450">
      <v-card class="dialog-card">
        <v-card-title>{{ t('dashboard.confirmDelete') }}</v-card-title>

        <v-card-text>
          {{
            deleteType === 'car'
              ? `${deleteItem?.brand} ${deleteItem?.model}? ${t('dashboard.deleteCarConfirm')}`
              : `${t('dashboard.delete')} ${deleteType}?`
          }}
        </v-card-text>

        <v-card-actions class="dialog-actions">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">{{ t('dashboard.cancel') }}</v-btn>
          <v-btn color="error" @click="confirmDelete">{{ t('dashboard.delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ExpiryEditDialog
      v-model="expiryDialog"
      :selected-car="selectedCar"
      @save="onSaveExpiry"
    />
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend } from 'chart.js'
import MainLayout from '../layouts/MainLayout.vue'
import DateInput from '../components/DateInput.vue'
import { useAuthStore } from '../stores/auth'
import { useGarageStore } from '../stores/garage'
import DashboardOverviewSection from './dashboard/components/DashboardOverviewSection.vue'
import DashboardGarageSection from './dashboard/components/DashboardGarageSection.vue'
import ExpiryEditDialog from './dashboard/components/ExpiryEditDialog.vue'
import {
  buildChartOptions,
  buildFuelConsumptionChart,
  buildFuelConsumptionData,
  buildMonthlyExpenseData,
  buildStackedChartOptions
} from './dashboard/charts'
import { createDashboardRules, emptyCarForm, emptyFuelForm, emptyRepairForm, emptyModForm } from './dashboard/forms'
import { formatCurrency } from './dashboard/formatters'
import { getDefaultExpiryDate } from './dashboard/expiryHelper'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend)

const auth = useAuthStore()
const garage = useGarageStore()
const { t } = useI18n()

const activeTab = ref('fuel')
const carSearch = ref('')
const fuelFilters = ref({ date: '' })
const repairFilters = ref({ date_from: '', date_to: '' })

const carDialog = ref(false)
const expiryDialog = ref(false)
const fuelDialog = ref(false)
const repairDialog = ref(false)
const modDialog = ref(false)
const deleteDialog = ref(false)
const deleteType = ref('')
const deleteItem = ref(null)

const carFormRef = ref(null)
const fuelFormRef = ref(null)
const repairFormRef = ref(null)
const modFormRef = ref(null)

const savingCar = ref(false)
const savingFuel = ref(false)
const savingRepair = ref(false)
const savingMod = ref(false)

const saveError = ref('')

const selectedCar = computed(() => garage.selectedCar)
const selectedPeriodProxy = computed({
  get: () => garage.selectedPeriod,
  set: (value) => garage.setSelectedPeriod(value)
})

const fuelConsumptionChart = computed(() => buildFuelConsumptionChart(garage.filteredFuelLogs))

const monthlyExpenseChart = computed(() => garage.monthlyExpenseBreakdown)

const fuelConsumptionData = computed(() => buildFuelConsumptionData(fuelConsumptionChart.value))

const monthlyExpenseData = computed(() => buildMonthlyExpenseData(monthlyExpenseChart.value, t))

const chartOptions = computed(() => buildChartOptions(formatCurrency))

const stackedChartOptions = computed(() => buildStackedChartOptions(formatCurrency))

const rules = createDashboardRules(t)

const carForm = ref(emptyCarForm())
const fuelForm = ref(emptyFuelForm())
const repairForm = ref(emptyRepairForm())
const modForm = ref(emptyModForm())

const fuelPricePerLiter = computed(() => {
  const liters = Number(fuelForm.value.liters || 0)
  const price = Number(fuelForm.value.total_price || 0)
  return liters > 0 ? (price / liters).toFixed(2) : ''
})

onMounted(async () => {
  carSearch.value = garage.search || ''
  fuelFilters.value = { date: garage.fuelFilters?.date || '' }
  repairFilters.value = {
    date_from: garage.repairFilters?.date_from || '',
    date_to: garage.repairFilters?.date_to || ''
  }

  await garage.bootstrap()
})

const onCarSearch = async () => {
  await garage.fetchCars(carSearch.value || '')
}

const reloadAll = async () => {
  await garage.bootstrap()
}

const showError = (msg) => {
  saveError.value = msg

  nextTick(() => {
    setTimeout(() => {
      saveError.value = ''
    }, 5000)
  })
}

const openCarDialog = (car = null) => {
  carForm.value = car
    ? { ...car }
    : {
        ...emptyCarForm(),
        year: new Date().getFullYear(),
        insurance_until: getDefaultExpiryDate(365),
        inspection_until: getDefaultExpiryDate(365)
      }

  carDialog.value = true
}

const openExpiryDialog = () => {
  expiryDialog.value = true
}

const onSaveExpiry = async (data) => {
  try {
    await garage.saveCar({
      id: data.car_id,
      insurance_until: data.insurance_until,
      inspection_until: data.inspection_until
    })
    expiryDialog.value = false
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Update failed')
  }
}

const openFuelDialog = async (item = null) => {
  if (!selectedCar.value) {
    showError('Select a car first')
    return
  }

  fuelForm.value = item ? { ...item } : emptyFuelForm()
  fuelDialog.value = true

  await nextTick()
  fuelFormRef.value?.resetValidation()
}

const openRepairDialog = async (item = null) => {
  if (!selectedCar.value) {
    showError('Select a car first')
    return
  }

  repairForm.value = item ? { ...item } : emptyRepairForm()
  repairDialog.value = true

  await nextTick()
  repairFormRef.value?.resetValidation()
}

const openModDialog = async (item = null) => {
  if (!selectedCar.value) {
    showError('Select a car first')
    return
  }

  modForm.value = item ? { ...item } : emptyModForm()
  modDialog.value = true

  await nextTick()
  modFormRef.value?.resetValidation()
}

const submitCar = async () => {
  const validation = await carFormRef.value?.validate()
  if (!validation?.valid) return

  savingCar.value = true
  saveError.value = ''

  try {
    await garage.saveCar(carForm.value)
    carDialog.value = false
    carForm.value = emptyCarForm()
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Save failed')
  } finally {
    savingCar.value = false
  }
}

const submitFuelLog = async () => {
  const validation = await fuelFormRef.value?.validate()
  if (!validation?.valid) return

  savingFuel.value = true
  saveError.value = ''

  try {
    await garage.saveFuelLog(fuelForm.value)
    await garage.fetchFuelLogs(fuelFilters.value)
    fuelDialog.value = false
    fuelForm.value = emptyFuelForm()
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Save failed')
  } finally {
    savingFuel.value = false
  }
}

const submitRepair = async () => {
  const validation = await repairFormRef.value?.validate()
  if (!validation?.valid) return

  savingRepair.value = true
  saveError.value = ''

  try {
    await garage.saveRepair(repairForm.value)
    await garage.fetchRepairs(repairFilters.value)
    repairDialog.value = false
    repairForm.value = emptyRepairForm()
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Save failed')
  } finally {
    savingRepair.value = false
  }
}

const submitMod = async () => {
  const validation = await modFormRef.value?.validate()
  if (!validation?.valid) return

  savingMod.value = true
  saveError.value = ''

  try {
    await garage.saveMod(modForm.value)
    modDialog.value = false
    modForm.value = emptyModForm()
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Save failed')
  } finally {
    savingMod.value = false
  }
}

const showDeleteDialog = (type, item) => {
  deleteType.value = type
  deleteItem.value = item
  deleteDialog.value = true
}

const confirmDelete = async () => {
  deleteDialog.value = false

  try {
    if (deleteType.value === 'car') {
      await garage.deleteCar(deleteItem.value.id)
    } else if (deleteType.value === 'fuel') {
      await garage.deleteFuelLog(deleteItem.value.id)
      await garage.fetchFuelLogs(fuelFilters.value)
    } else if (deleteType.value === 'repair') {
      await garage.deleteRepair(deleteItem.value.id)
      await garage.fetchRepairs(repairFilters.value)
    } else if (deleteType.value === 'mod') {
      await garage.deleteMod(deleteItem.value.id)
    }
  } catch (error) {
    showError(error?.response?.data?.message || error?.message || 'Delete failed')
  }
}

const removeCar = (car) => showDeleteDialog('car', car)
const removeFuelLog = (item) => showDeleteDialog('fuel', item)
const removeRepair = (item) => showDeleteDialog('repair', item)
const removeMod = (item) => showDeleteDialog('mod', item)

const applyFuelFilters = async () => {
  await garage.fetchFuelLogs(fuelFilters.value)
}

const resetFuelFilters = async () => {
  fuelFilters.value = { date: '' }
  await garage.fetchFuelLogs(fuelFilters.value)
}

const applyRepairFilters = async () => {
  await garage.fetchRepairs(repairFilters.value)
}

const resetRepairFilters = async () => {
  repairFilters.value = { date_from: '', date_to: '' }
  await garage.fetchRepairs(repairFilters.value)
}

</script>