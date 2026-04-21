<template>
  <MainLayout>
    <section class="work-hero-card">
      <div class="work-kicker">Garage dashboard</div>
      <h1 class="work-title">Welcome back, {{ auth.user?.login }}</h1>
      <p class="work-subtitle">
        Manage your garage, monitor costs, and keep a complete tuning history for every car.
      </p>
      <div class="work-hero-actions">
        <button class="work-solid-btn" type="button" @click="openCarDialog()">Add car</button>
        <button class="work-outline-btn" type="button" @click="reloadAll">Refresh</button>
      </div>
    </section>

    <section class="work-grid-4">
      <article class="work-panel">
        <div class="work-panel-label">Cars</div>
        <div class="work-panel-value">{{ garage.summary.stats.cars_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Fuel Logs</div>
        <div class="work-panel-value">{{ garage.summary.stats.fuel_logs_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Repairs</div>
        <div class="work-panel-value">{{ garage.summary.stats.repairs_total }}</div>
      </article>
      <article class="work-panel">
        <div class="work-panel-label">Total Spend</div>
        <div class="work-panel-value">{{ formatCurrency(garage.summary.stats.total_spent) }}</div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <div>
            <h3 class="work-panel-title">Fuel consumption by month</h3>
            <div class="work-item-sub">
              {{ selectedCar ? `${selectedCar.brand} ${selectedCar.model}` : 'Select a car to see fuel trends' }}
            </div>
          </div>
        </div>


        <ChartWrapper
          v-if="fuelConsumptionChart.length"
          :data="fuelConsumptionData"
          :options="chartOptions"
        />
        <div v-else class="empty-card">
          Add at least two fuel logs for one car to build the monthly consumption chart.
        </div>


      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <div>
            <h3 class="work-panel-title">Cost of ownership timeline</h3>
            <div class="work-item-sub">Monthly fuel, repair, and mod costs for the selected car</div>
          </div>
        </div>


        <ChartWrapper
          v-if="costTimelineChart.length"
          :data="costTimelineData"
          :options="chartOptions"
        />
        <div v-else class="empty-card">
          Add fuel logs, repairs, or mods to build a monthly ownership timeline.
        </div>


      </article>
    </section>

    <section class="work-grid-dashboard mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <div>
            <div class="work-panel-title">Cars</div>
            <div class="work-item-sub">Search by brand or model</div>
          </div>
          <v-btn class="ui-btn-primary compact" @click="openCarDialog()">Add</v-btn>
        </div>

        <div class="toolbar-line">
          <v-text-field
            v-model="carSearch"
            label="Search cars"
            density="comfortable"
            hide-details
            clearable
            @update:model-value="onCarSearch"
          />
        </div>

        <v-progress-linear v-if="garage.carsLoading" indeterminate class="mb-3" />

        <div v-if="!garage.cars.length" class="empty-card">
          Add your first car to start tracking fuel, repairs, and mods.
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
              @click="garage.selectCar(car.id)"
            >
              <div>
                <div class="work-item-title">{{ car.brand }} {{ car.model }}</div>
                <div class="work-item-sub">{{ car.year }} · {{ car.engine_volume }}L · {{ car.license_plate }}</div>
              </div>
              <div class="car-item-metrics">
                <span>{{ car.fuel_logs_count }} fuel</span>
                <span>{{ car.repairs_count }} repairs</span>
                <span>{{ car.mods_count }} mods</span>
              </div>
            </button>
            <div class="garage-car-item-actions">
              <v-btn class="ui-btn-secondary compact" size="small" variant="outlined" @click.stop="openCarDialog(car)">Edit</v-btn>
              <v-btn class="ui-btn-secondary compact" size="small" color="error" variant="outlined" @click.stop="removeCar(car)">Delete</v-btn>
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
            <v-btn class="ui-btn-secondary compact" variant="outlined" @click="openCarDialog(selectedCar)">Edit</v-btn>
            <v-btn class="ui-btn-secondary compact" color="error" variant="outlined" @click="removeCar(selectedCar)">Delete</v-btn>
          </div>
        </div>

        <div v-else class="empty-card">Select a car to manage its journal.</div>

        <template v-if="selectedCar">
          <v-tabs v-model="activeTab" class="garage-tabs">
            <v-tab value="fuel">Fuel Logs</v-tab>
            <v-tab value="repairs">Repairs</v-tab>
            <v-tab value="mods">Mods</v-tab>
          </v-tabs>

          <v-window v-model="activeTab" class="mt-4">
            <v-window-item value="fuel">
              <div class="toolbar-line filters">
                <DateInput v-model="fuelFilters.date" label="Fueling date" placeholder="Pick a fueling date" />
                <div class="filter-actions">
                  <v-btn class="ui-btn-secondary compact" variant="outlined" @click="applyFuelFilters">Apply</v-btn>
                  <v-btn class="ui-btn-secondary compact" variant="text" @click="resetFuelFilters">Reset</v-btn>
                  <v-btn class="ui-btn-primary compact" @click="openFuelDialog">Add</v-btn>
                </div>
              </div>

              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Liters</th>
                    <th>Total</th>
                    <th>Price/L</th>
                    <th>Mileage</th>
                    <th>Consumption</th>
                    <th class="actions-col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in garage.fuelLogs" :key="item.id" class="hover-row">
                    <td>{{ formatDate(item.date) }}</td>
                    <td>{{ item.liters }}</td>
                    <td>{{ formatCurrency(item.total_price) }}</td>
                    <td>{{ item.price_per_liter }}</td>
                    <td>{{ item.mileage }} km</td>
                    <td>{{ item.fuel_consumption ? `${item.fuel_consumption} L/100km` : 'Pending next log' }}</td>
                    <td class="actions-col">
                      <button class="table-action" type="button" @click="openFuelDialog(item)">Edit</button>
                      <button class="table-action danger" type="button" @click="removeFuelLog(item)">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-window-item>

            <v-window-item value="repairs">
              <div class="toolbar-line filters">
                <DateInput v-model="repairFilters.date_from" label="Repair date" placeholder="Pick repair date" />
                <div class="filter-actions">
                  <v-btn class="ui-btn-secondary compact" variant="outlined" @click="applyRepairFilters">Apply</v-btn>
                  <v-btn class="ui-btn-primary compact" @click="openRepairDialog">Add</v-btn>
                </div>
              </div>

              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Cost</th>
                    <th>Mileage</th>
                    <th>Description</th>
                    <th class="actions-col">Actions</th>
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
                      <button class="table-action" type="button" @click="openRepairDialog(item)">Edit</button>
                      <button class="table-action danger" type="button" @click="removeRepair(item)">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-window-item>

            <v-window-item value="mods">
              <div class="toolbar-line filters">
                <div class="toolbar-spacer"></div>
                <v-btn class="ui-btn-primary compact" @click="openModDialog">Add</v-btn>
              </div>

              <v-table class="journal-table">
                <thead>
                  <tr>
                    <th>Date Installed</th>
                    <th>Name</th>
                    <th>Cost</th>
                    <th>Performance Impact</th>
                    <th class="actions-col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in garage.mods" :key="item.id" class="hover-row">
                    <td>{{ formatDate(item.date_installed) }}</td>
                    <td>{{ item.name }}</td>
                    <td>{{ formatCurrency(item.cost) }}</td>
                    <td>{{ item.performance_impact }}</td>
                    <td class="actions-col">
                      <button class="table-action" type="button" @click="openModDialog(item)">Edit</button>
                      <button class="table-action danger" type="button" @click="removeMod(item)">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-window-item>
          </v-window>
        </template>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Resale snapshot</h3>
          <div class="filter-actions">
            <v-btn class="ui-btn-secondary compact" variant="outlined" @click="copyResaleSummary">Copy summary</v-btn>
            <v-btn class="ui-btn-primary compact" @click="exportResalePdf">Export PDF</v-btn>
          </div>
        </div>
        <div v-if="!selectedCar" class="empty-card">Select a car to prepare a clean resale-ready summary.</div>
        <div v-else class="snapshot-list">
          <div class="snapshot-item">
            <span>Vehicle</span>
            <strong>{{ selectedCar.brand }} {{ selectedCar.model }} ({{ selectedCar.year }})</strong>
          </div>
          <div class="snapshot-item">
            <span>Plate</span>
            <strong>{{ selectedCar.license_plate }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Insurance</span>
            <strong>{{ selectedCar.insurance_until ? formatDate(selectedCar.insurance_until) : 'Not set' }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Technical inspection</span>
            <strong>{{ selectedCar.inspection_until ? formatDate(selectedCar.inspection_until) : 'Not set' }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Total fuel spend</span>
            <strong>{{ formatCurrency(selectedCar.fuel_logs_total_spent || 0) }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Service + mods</span>
            <strong>{{ formatCurrency(selectedCarServiceSpend) }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Records</span>
            <strong>{{ garage.fuelLogs.length }} fuel · {{ garage.repairs.length }} repairs · {{ garage.mods.length }} mods</strong>
          </div>
          <div class="snapshot-item">
            <span>Latest repair</span>
            <strong>{{ latestRepair ? `${latestRepair.type} on ${formatDate(latestRepair.date)}` : 'No repair logged' }}</strong>
          </div>
          <div class="snapshot-item">
            <span>Latest mod</span>
            <strong>{{ latestMod ? `${latestMod.name} on ${formatDate(latestMod.date_installed)}` : 'No mods logged' }}</strong>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Smart reminders</h3>
        </div>
        <div v-if="!smartReminders.length" class="empty-card">
          Your selected car looks up to date. Keep adding records to get smarter reminders.
        </div>
        <div v-else class="reminder-list">
          <div v-for="item in smartReminders" :key="item.title" class="reminder-item">
            <div class="reminder-title">{{ item.title }}</div>
            <div class="work-item-sub">{{ item.message }}</div>
          </div>
        </div>
      </article>
    </section>

    <section class="work-grid-3 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Recent fuel activity</h3>
        </div>
        <div class="work-list">
          <div v-for="item in garage.summary.recent_fuel_logs" :key="item.id" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.car?.brand }} {{ item.car?.model }}</div>
              <div class="work-item-sub">{{ formatDate(item.date) }} · {{ item.liters }}L · {{ formatCurrency(item.total_price) }}</div>
            </div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Recent repairs</h3>
        </div>
        <div class="work-list">
          <div v-for="item in garage.summary.recent_repairs" :key="item.id" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.type }}</div>
              <div class="work-item-sub">{{ formatDate(item.date) }} · {{ item.car?.brand }} {{ item.car?.model }} · {{ formatCurrency(item.cost) }}</div>
            </div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">Recent mods</h3>
        </div>
        <div class="work-list">
          <div v-for="item in garage.summary.recent_mods" :key="item.id" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.name }}</div>
              <div class="work-item-sub">{{ formatDate(item.date_installed) }} · {{ item.car?.brand }} {{ item.car?.model }} · {{ formatCurrency(item.cost) }}</div>
            </div>
          </div>
        </div>
      </article>
    </section>

    <!-- Car Dialog -->
    <v-dialog v-model="carDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ carForm.id ? 'Edit car' : 'Add car' }}</h3>
        <v-form id="carForm" ref="carFormRef" @submit.prevent="submitCar">
          <div class="dialog-grid">
            <v-text-field v-model="carForm.brand" label="Brand" :rules="[rules.required]" />
            <v-text-field v-model="carForm.model" label="Model" :rules="[rules.required]" />
            <v-text-field v-model="carForm.year" label="Year" type="number" :rules="[rules.required, rules.year]" />
            <v-text-field v-model="carForm.engine_volume" label="Engine volume" type="number" step="0.1" :rules="[rules.required, rules.gt(0)]" />
            <v-text-field v-model="carForm.license_plate" label="License plate" @update:model-value="v => carForm.license_plate = v.toUpperCase()" :rules="[rules.required]" />
            <DateInput v-model="carForm.insurance_until" label="Insurance until" placeholder="Select insurance expiry" />
            <DateInput v-model="carForm.inspection_until" label="Inspection until" placeholder="Select inspection expiry" />
          </div>
        </v-form>
        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="carDialog = false">Cancel</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingCar" type="submit" form="carForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Fuel Dialog -->
    <v-dialog v-model="fuelDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ fuelForm.id ? 'Edit fuel log' : 'Add fuel log' }}</h3>
        <v-form id="fuelForm" ref="fuelFormRef" @submit.prevent="submitFuelLog">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>
          <div class="dialog-grid">
            <DateInput v-model="fuelForm.date" label="Date" placeholder="Select fueling date" :rules="[rules.requiredDate]" />
            <v-text-field v-model="fuelForm.liters" label="Liters" type="number" step="0.01" :rules="[rules.required, rules.gt(0)]" />
            <v-text-field v-model="fuelForm.total_price" label="Total price" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-text-field :model-value="fuelPricePerLiter" label="Price/L" readonly suffix="€/L" />
            <v-text-field v-model="fuelForm.mileage" label="Mileage" type="number" :rules="[rules.required, rules.integerMin(0)]" />
          </div>
          <div class="dialog-note">Fuel consumption is calculated from the previous log for the same car.</div>
        </v-form>
        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="fuelDialog = false">Cancel</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingFuel" type="submit" form="fuelForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Repair Dialog -->
    <v-dialog v-model="repairDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ repairForm.id ? 'Edit repair' : 'Add repair' }}</h3>
        <v-form id="repairForm" ref="repairFormRef" @submit.prevent="submitRepair">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>
          <div class="dialog-grid">
            <DateInput v-model="repairForm.date" label="Date" placeholder="Select repair date" :rules="[rules.requiredDate]" />
            <v-text-field v-model="repairForm.type" label="Type" :rules="[rules.required]" />
            <v-text-field v-model="repairForm.cost" label="Cost" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-text-field v-model="repairForm.mileage" label="Mileage" type="number" :rules="[rules.required, rules.integerMin(0)]" />
            <v-textarea v-model="repairForm.description" label="Description" rows="3" class="span-2" />
          </div>
        </v-form>
        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="repairDialog = false">Cancel</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingRepair" type="submit" form="repairForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="450">
      <v-card class="dialog-card">
        <v-card-title>Confirm delete</v-card-title>
        <v-card-text>
          {{
            deleteType === 'car' 
              ? `Delete ${deleteItem?.brand} ${deleteItem?.model}? This removes all fuel logs, repairs, and mods.`
              : `Delete this ${deleteType}?`
          }}
        </v-card-text>
        <v-card-actions class="dialog-actions">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="confirmDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Mod Dialog -->
    <v-dialog v-model="modDialog" max-width="620">
      <v-card class="dialog-card">
        <h3 class="work-panel-title mb-4">{{ modForm.id ? 'Edit mod' : 'Add mod' }}</h3>
        <v-form id="modForm" ref="modFormRef" @submit.prevent="submitMod">
          <div v-if="saveError" class="form-error">{{ saveError }}</div>
          <div class="dialog-grid">
            <DateInput v-model="modForm.date_installed" label="Date installed" placeholder="Select install date" :rules="[rules.requiredDate]" />
            <v-text-field v-model="modForm.name" label="Name" :rules="[rules.required]" />
            <v-text-field v-model="modForm.cost" label="Cost" type="number" step="0.01" :rules="[rules.required, rules.gte(0)]" />
            <v-textarea v-model="modForm.performance_impact" label="Performance impact" rows="3" class="span-2" :rules="[rules.required]" />
          </div>
        </v-form>
        <div class="dialog-actions">
          <v-btn class="ui-btn-secondary" variant="text" @click="modDialog = false">Cancel</v-btn>
          <v-btn class="ui-btn-primary" :loading="savingMod" type="submit" form="modForm">Save</v-btn>
        </div>
      </v-card>
    </v-dialog>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref, nextTick } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js'
import DateInput from '../components/DateInput.vue'
import ChartWrapper from '../components/ChartWrapper.vue'
import MainLayout from '../layouts/MainLayout.vue'
import { useAuthStore } from '../stores/auth'
import { useGarageStore } from '../stores/garage'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const auth = useAuthStore()
const garage = useGarageStore()

const activeTab = ref('fuel')
const carSearch = ref('')
const fuelFilters = ref({ date: '' })
const repairFilters = ref({ date_from: '', date_to: '' })

const carDialog = ref(false)
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
const latestRepair = computed(() => garage.repairs[0] || null)
const latestMod = computed(() => garage.mods[0] || null)
const selectedCarServiceSpend = computed(() =>
  Number(selectedCar.value?.repairs_total_spent || 0) + Number(selectedCar.value?.mods_total_spent || 0)
)

const fuelConsumptionChart = computed(() => {
  const grouped = garage.fuelLogs.reduce((acc, item) => {
    if (!item.fuel_consumption) return acc
    const monthKey = String(item.date).slice(0, 7)
    if (!acc[monthKey]) {
      acc[monthKey] = { total: 0, count: 0 }
    }
    acc[monthKey].total += Number(item.fuel_consumption)
    acc[monthKey].count += 1
    return acc
  }, {})

  const items = Object.entries(grouped)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([label, value]) => ({
      label,
      value: value.total / value.count
    }))

  return items
})

const fuelConsumptionData = computed(() => ({
  labels: fuelConsumptionChart.value.map(item => item.label),
  datasets: [{
    label: 'L/100km',
    data: fuelConsumptionChart.value.map(item => item.value),
    borderColor: '#3b82f6',
    backgroundColor: 'rgba(59, 130, 246, 0.1)',
    tension: 0.4,
    fill: true
  }]
}))

const costTimelineChart = computed(() => {
  const grouped = {}

  garage.fuelLogs.forEach((item) => {
    const key = String(item.date).slice(0, 7)
    grouped[key] = (grouped[key] || 0) + Number(item.total_price || 0)
  })

  garage.repairs.forEach((item) => {
    const key = String(item.date).slice(0, 7)
    grouped[key] = (grouped[key] || 0) + Number(item.cost || 0)
  })

  garage.mods.forEach((item) => {
    const key = String(item.date_installed).slice(0, 7)
    grouped[key] = (grouped[key] || 0) + Number(item.cost || 0)
  })

  const items = Object.entries(grouped)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([label, value]) => ({ label, value }))

  return items
})

const costTimelineData = computed(() => ({
  labels: costTimelineChart.value.map(item => item.label),
  datasets: [{
    label: '€/month',
    data: costTimelineChart.value.map(item => item.value),
    borderColor: '#10b981',
    backgroundColor: 'rgba(16, 185, 129, 0.1)',
    tension: 0.4,
    fill: true
  }]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value) {
          if (this.chart?.data?.datasets[0]?.label === 'L/100km') {
            return value + ' L/100km'
          }
          return new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(value)
        }
      }
    }
  }
}

const smartReminders = computed(() => {
  if (!selectedCar.value) return []

  const reminders = []
  const latestFuelLog = garage.fuelLogs[0] || null
  const latestRepairItem = garage.repairs[0] || null
  const oilService = garage.repairs.find((item) => item.type?.toLowerCase().includes('oil'))
  const insuranceUntil = selectedCar.value.insurance_until ? new Date(selectedCar.value.insurance_until) : null
  const inspectionUntil = selectedCar.value.inspection_until ? new Date(selectedCar.value.inspection_until) : null
  const now = new Date()

  if (garage.fuelLogs.length < 2) {
    reminders.push({
      title: 'Add one more fuel log',
      message: 'Two or more fueling records unlock much better consumption trends.'
    })
  }

  if (!oilService) {
    reminders.push({
      title: 'No oil service on record',
      message: 'Logging oil changes makes your maintenance history much stronger for resale.'
    })
  } else if (latestFuelLog) {
    const kilometersSinceOil = Number(latestFuelLog.mileage) - Number(oilService.mileage)
    const kilometersUntilOil = 10000 - kilometersSinceOil

    if (kilometersUntilOil <= 0) {
      reminders.push({
        title: 'Oil service overdue',
        message: `You are ${Math.abs(kilometersUntilOil)} km past the 10 000 km oil interval.`
      })
    } else if (kilometersUntilOil <= 1000) {
      reminders.push({
        title: 'Oil service coming soon',
        message: `${kilometersUntilOil} km left until the next oil service at the 10 000 km interval.`
      })
    }
  }

  if (!latestRepairItem) {
    reminders.push({
      title: 'No repairs logged yet',
      message: 'Even routine service helps build trust when you later sell the car.'
    })
  } else {
    const daysSinceRepair = Math.floor((Date.now() - new Date(latestRepairItem.date).getTime()) / 86400000)
    if (daysSinceRepair > 180) {
      reminders.push({
        title: 'Maintenance history is getting stale',
        message: `${daysSinceRepair} days passed since the last recorded repair or service entry.`
      })
    }
  }

  if (!garage.mods.length) {
    reminders.push({
      title: 'No mods tracked',
      message: 'If the car is stock, that is still useful information for a clean resale summary.'
    })
  }

  if (!insuranceUntil) {
    reminders.push({
      title: 'Insurance date missing',
      message: 'Add insurance expiry to get automatic reminders before renewal.'
    })
  } else {
    const insuranceDays = Math.ceil((insuranceUntil.getTime() - now.getTime()) / 86400000)
    if (insuranceDays < 0) {
      reminders.push({
        title: 'Insurance expired',
        message: `Insurance expired ${Math.abs(insuranceDays)} days ago.`
      })
    } else if (insuranceDays <= 30) {
      reminders.push({
        title: 'Insurance renewal soon',
        message: `Insurance expires in ${insuranceDays} days on ${formatDate(selectedCar.value.insurance_until)}.`
      })
    }
  }

  if (!inspectionUntil) {
    reminders.push({
      title: 'Inspection date missing',
      message: 'Add technical inspection expiry to automate your legal compliance reminders.'
    })
  } else {
    const inspectionDays = Math.ceil((inspectionUntil.getTime() - now.getTime()) / 86400000)
    if (inspectionDays < 0) {
      reminders.push({
        title: 'Technical inspection expired',
        message: `Inspection expired ${Math.abs(inspectionDays)} days ago.`
      })
    } else if (inspectionDays <= 30) {
      reminders.push({
        title: 'Technical inspection soon',
        message: `Inspection expires in ${inspectionDays} days on ${formatDate(selectedCar.value.inspection_until)}.`
      })
    }
  }

  return reminders.slice(0, 6)
})

const rules = {
  required: v => !!v || 'Required',
  requiredDate: v => !!v || 'Date is required',
  gt(value) {
    return v => !!v && Number(v) > value || `Must be greater than ${value}`
  },
  gte(value) {
    return v => !!v && Number(v) >= value || `Must be at least ${value}`
  },
  integerMin(value) {
    return v => !!v && Number.isInteger(Number(v)) && Number(v) >= value || `Must be integer >= ${value}`
  },
  year: v => !!v && Number(v) >= 1900 && Number(v) <= 2100 || 'Year between 1900-2100'
}

// ... all computed fuelConsumptionChart, costTimelineChart, smartReminders remain the same ...

const emptyCarForm = () => ({
  id: null,
  brand: '',
  model: '',
  year: '',
  engine_volume: '',
  license_plate: '',
  insurance_until: '',
  inspection_until: ''
})

const emptyFuelForm = () => ({
  id: null,
  date: '',
  liters: '',
  total_price: '',
  mileage: ''
})

const emptyRepairForm = () => ({
  id: null,
  type: '',
  cost: '',
  date: '',
  mileage: '',
  description: ''
})

const emptyModForm = () => ({
  id: null,
  name: '',
  cost: '',
  date_installed: '',
  performance_impact: ''
})

const carForm = ref(emptyCarForm())
const fuelForm = ref(emptyFuelForm())
const fuelPricePerLiter = computed(() => {
  const liters = Number(fuelForm.value.liters || 0)
  const price = Number(fuelForm.value.total_price || 0)
  return liters > 0 ? (price / liters).toFixed(2) : ''
})
const repairForm = ref(emptyRepairForm())
const modForm = ref(emptyModForm())

onMounted(async () => {
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
  carForm.value = car ? { ...car } : emptyCarForm()
  if (!car) {
    carForm.value.year = new Date().getFullYear()
  }
  carDialog.value = true
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
    showError(error.message || 'Save failed')
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
    showError(error.response?.data?.message || 'Save failed')
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
    showError(error.response?.data?.message || 'Save failed')
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
    showError(error.response?.data?.message || 'Save failed')
  } finally {
    savingMod.value = false
  }
}

// ... rest of functions remain the same: remove*, applyFilters, copyResaleSummary, exportResalePdf, escapeHtml, formatCurrency, formatDate ...

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
  } catch (e) {
    showError('Delete failed')
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
  await garage.fetchFuelLogs()
}

const applyRepairFilters = async () => {
  await garage.fetchRepairs(repairFilters.value)
}

const copyResaleSummary = async () => {
  if (!selectedCar.value || !navigator?.clipboard) return

  const lines = [
    `${selectedCar.value.brand} ${selectedCar.value.model} (${selectedCar.value.year})`,
    `Engine: ${selectedCar.value.engine_volume}L`,
    `Plate: ${selectedCar.value.license_plate}`,
    `Fuel spend: ${formatCurrency(selectedCar.value.fuel_logs_total_spent || 0)}`,
    `Service + mods: ${formatCurrency(selectedCarServiceSpend.value)}`,
    `Fuel logs: ${garage.fuelLogs.length}, repairs: ${garage.repairs.length}, mods: ${garage.mods.length}`,
    `Latest repair: ${latestRepair.value ? `${latestRepair.value.type} on ${formatDate(latestRepair.value.date)}` : 'No repair logged'}`,
    `Latest mod: ${latestMod.value ? `${latestMod.value.name} on ${formatDate(latestMod.value.date_installed)}` : 'No mods logged'}`
  ]

  await navigator.clipboard.writeText(lines.join('\n'))
}

const exportResalePdf = () => {
  if (!selectedCar.value) return

  const summaryRows = [
    ['Vehicle', `${selectedCar.value.brand} ${selectedCar.value.model} (${selectedCar.value.year})`],
    ['Engine', `${selectedCar.value.engine_volume}L`],
    ['Plate', selectedCar.value.license_plate],
    ['Fuel spend', formatCurrency(selectedCar.value.fuel_logs_total_spent || 0)],
    ['Service + mods', formatCurrency(selectedCarServiceSpend.value)],
    ['Fuel logs', String(garage.fuelLogs.length)],
    ['Repairs', String(garage.repairs.length)],
    ['Mods', String(garage.mods.length)],
    ['Latest repair', latestRepair.value ? `${latestRepair.value.type} on ${formatDate(latestRepair.value.date)}` : 'No repair logged'],
    ['Latest mod', latestMod.value ? `${latestMod.value.name} on ${formatDate(latestMod.value.date_installed)}` : 'No mods logged']
  ]

  const tableRows = summaryRows
    .map(
      ([label, value]) => `
        <tr>
          <td>${escapeHtml(label)}</td>
          <td>${escapeHtml(value)}</td>
        </tr>
      `
    )
    .join('')

  const reminderRows = smartReminders.value.length
    ? smartReminders.value.map((item) => `<li><strong>${escapeHtml(item.title)}:</strong> ${escapeHtml(item.message)}</li>`).join('')
    : '<li>No urgent reminders at the moment.</li>'

  const popup = window.open('', '_blank', 'width=980,height=900')
  if (!popup) return

  popup.document.write(`
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8" />
        <title>Resale Summary - ${escapeHtml(selectedCar.value.brand)} ${escapeHtml(selectedCar.value.model)}</title>
        <style>
          /* ... PDF styles unchanged ... */
        </style>
      </head>
      <body>
        <main class="sheet">
          <div class="kicker">Car Tracker Resale Summary</div>
          <h1>${escapeHtml(selectedCar.value.brand)} ${escapeHtml(selectedCar.value.model)}</h1>
          <p class="subtitle">Prepared from your fuel logs, repairs, and tuning records.</p>

          <section class="meta">
            <article class="meta-card">
              <span>Registration</span>
              <strong>${escapeHtml(selectedCar.value.license_plate)}</strong>
            </article>
            <article class="meta-card">
              <span>Engine</span>
              <strong>${escapeHtml(String(selectedCar.value.engine_volume))}L</strong>
            </article>
            <article class="meta-card">
              <span>Tracked Spend</span>
              <strong>${escapeHtml(formatCurrency((selectedCar.value.fuel_logs_total_spent || 0) + selectedCarServiceSpend.value))}</strong>
            </article>
          </section>

          <section class="section">
            <div class="section-title">Ownership Snapshot</div>
            <table>
              <tbody>${tableRows}</tbody>
            </table>
          </section>

          <section class="section" style="margin-top: 18px;">
            <div class="section-title">Smart Reminders</div>
            <ul>${reminderRows}</ul>
          </section>

          <p class="footer">
            Generated on ${escapeHtml(new Date().toLocaleDateString('en-GB'))} from Car Tracker & Tuning Journal.
          </p>
        </main>
      </body>
    </html>
  `)

  popup.document.close()
  popup.focus()
  popup.print()
}

const escapeHtml = (value) =>
  String(value ?? '')
    .replaceAll('&', '&amp;')
.replaceAll('<', '<')
    .replaceAll('>', '>')
    .replaceAll('"', '"')

const formatCurrency = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-IE', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const formatDate = (value) => {
  if (!value) return '—'
  return String(value).slice(0, 10)
}
</script>
