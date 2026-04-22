<template>
  <MainLayout>
    <section class="work-hero-card dashboard-hero">
      <div class="work-kicker">{{ t('dashboard.heroKicker') }}</div>
      <h1 class="work-title">{{ t('dashboard.welcome') }}, {{ auth.user?.login }}</h1>
      <p class="work-subtitle">
        {{ t('dashboard.subtitle') }}
      </p>

      <div class="work-hero-actions">
        <button class="work-solid-btn" type="button" @click="openCarDialog()">
          {{ t('dashboard.addCar') }}
        </button>
        <button class="work-outline-btn" type="button" @click="reloadAll">
          {{ t('dashboard.refresh') }}
        </button>
      </div>
    </section>

    <section class="work-grid-4">
      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.cars') }}</div>
        <div class="work-panel-value">{{ garage.summary.stats.cars_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.fuelLogs') }}</div>
        <div class="work-panel-value">{{ garage.summary.stats.fuel_logs_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.repairs') }}</div>
        <div class="work-panel-value">{{ garage.summary.stats.repairs_total }}</div>
      </article>

      <article class="work-panel metric-panel">
        <div class="work-panel-label">{{ t('dashboard.totalSpend') }}</div>
        <div class="work-panel-value">{{ formatCurrency(garage.summary.stats.total_spent) }}</div>
      </article>
    </section>

    <section class="work-grid-4 mt-4">
      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.selectedPeriod') }}</div>
        <div class="work-panel-value small-value">{{ garage.selectedPeriodLabel }}</div>
      </article>

      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.costPerKm') }}</div>
        <div class="work-panel-value small-value">
          {{ garage.costPerKm ? formatCurrency(garage.costPerKm) : '—' }}
        </div>
      </article>

      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.distanceTracked') }}</div>
        <div class="work-panel-value small-value">
          {{ garage.totalDistanceTracked ? `${garage.totalDistanceTracked} km` : '—' }}
        </div>
      </article>

      <article class="work-panel metric-panel compact-metric">
        <div class="work-panel-label">{{ t('dashboard.resaleScore') }}</div>
        <div class="work-panel-value small-value">
          {{ selectedCar ? `${garage.resaleScore}/100` : '—' }}
        </div>
      </article>
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
          :class="{ active: garage.selectedPeriod === option.value }"
          @click="setPeriod(option.value)"
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
              {{ selectedCar ? `${selectedCar.brand} ${selectedCar.model}` : t('dashboard.selectCarFuelTrend') }}
            </div>
          </div>
        </div>

        <ChartWrapper
          v-if="fuelConsumptionChart.length"
          :data="fuelConsumptionData"
          :options="chartOptions"
        />

        <div v-else class="empty-card">
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

        <ChartWrapper
          v-if="monthlyExpenseChart.length"
          :data="monthlyExpenseData"
          :options="stackedChartOptions"
        />

        <div v-else class="empty-card">
          {{ t('dashboard.needExpensesForBreakdown') }}
        </div>
      </article>
    </section>

    <section class="work-grid-dashboard mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <div>
            <div class="work-panel-title">{{ t('dashboard.carsSectionTitle') }}</div>
            <div class="work-item-sub">{{ t('dashboard.searchByBrandModel') }}</div>
          </div>

          <v-btn class="ui-btn-primary compact" @click="openCarDialog()">
            {{ t('dashboard.add') }}
          </v-btn>
        </div>

        <div class="toolbar-line">
          <v-text-field
            v-model="carSearch"
            :label="t('dashboard.searchCars')"
            density="comfortable"
            hide-details
            clearable
            @update:model-value="onCarSearch"
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
              @click="garage.selectCar(car.id)"
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
                @click.stop="openCarDialog(car)"
              >
                {{ t('dashboard.edit') }}
              </v-btn>

              <v-btn
                class="ui-btn-secondary compact"
                size="small"
                color="error"
                variant="outlined"
                @click.stop="removeCar(car)"
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
            <v-btn class="ui-btn-secondary compact" variant="outlined" @click="openCarDialog(selectedCar)">
              {{ t('dashboard.edit') }}
            </v-btn>
            <v-btn class="ui-btn-secondary compact" color="error" variant="outlined" @click="removeCar(selectedCar)">
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
                  <v-btn class="ui-btn-secondary compact" variant="outlined" @click="applyFuelFilters">
                    {{ t('dashboard.apply') }}
                  </v-btn>
                  <v-btn class="ui-btn-secondary compact" variant="text" @click="resetFuelFilters">
                    {{ t('dashboard.reset') }}
                  </v-btn>
                  <v-btn class="ui-btn-primary compact" @click="openFuelDialog">
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
                        <button class="table-action" type="button" @click="openFuelDialog(item)">{{ t('dashboard.edit') }}</button>
                        <button class="table-action danger" type="button" @click="removeFuelLog(item)">{{ t('dashboard.delete') }}</button>
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
                  <v-btn class="ui-btn-secondary compact" variant="outlined" @click="applyRepairFilters">
                    {{ t('dashboard.apply') }}
                  </v-btn>
                  <v-btn class="ui-btn-secondary compact" variant="text" @click="resetRepairFilters">
                    {{ t('dashboard.reset') }}
                  </v-btn>
                  <v-btn class="ui-btn-primary compact" @click="openRepairDialog">
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
                        <button class="table-action" type="button" @click="openRepairDialog(item)">{{ t('dashboard.edit') }}</button>
                        <button class="table-action danger" type="button" @click="removeRepair(item)">{{ t('dashboard.delete') }}</button>
                      </td>
                    </tr>
                  </tbody>
                </v-table>
              </div>
            </v-window-item>

            <v-window-item value="mods">
              <div class="toolbar-line filters">
                <div class="toolbar-spacer"></div>
                <v-btn class="ui-btn-primary compact" @click="openModDialog">
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
                        <button class="table-action" type="button" @click="openModDialog(item)">{{ t('dashboard.edit') }}</button>
                        <button class="table-action danger" type="button" @click="removeMod(item)">{{ t('dashboard.delete') }}</button>
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

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.resaleSnapshot') }}</h3>

          <div class="filter-actions">
            <v-btn class="ui-btn-secondary compact" variant="outlined" @click="copyResaleSummary">
              {{ t('dashboard.copySummary') }}
            </v-btn>
            <v-btn class="ui-btn-primary compact" @click="exportResalePdf">
              {{ t('dashboard.exportPdf') }}
            </v-btn>
          </div>
        </div>

        <div v-if="!selectedCar" class="empty-card">{{ t('dashboard.selectCarToManage') }}</div>

        <div v-else class="snapshot-list">
          <div class="snapshot-item">
            <span>{{ t('dashboard.vehicle') }}</span>
            <strong>{{ selectedCar.brand }} {{ selectedCar.model }} ({{ selectedCar.year }})</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.plate') }}</span>
            <strong>{{ selectedCar.license_plate }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.insurance') }}</span>
            <strong>{{ selectedCar.insurance_until ? formatDate(selectedCar.insurance_until) : t('dashboard.notSet') }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.technicalInspection') }}</span>
            <strong>{{ selectedCar.inspection_until ? formatDate(selectedCar.inspection_until) : t('dashboard.notSet') }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.totalFuelSpend') }}</span>
            <strong>{{ formatCurrency(totalFuelSpend) }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.serviceAndMods') }}</span>
            <strong>{{ formatCurrency(selectedCarServiceSpend) }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.trackedOwnershipTotal') }}</span>
            <strong>{{ formatCurrency(selectedCarTotalSpend) }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.costPerKm') }}</span>
            <strong>{{ garage.costPerKm ? formatCurrency(garage.costPerKm) : '—' }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.resaleScore') }}</span>
            <strong>{{ garage.resaleScore }}/100</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.records') }}</span>
            <strong>{{ garage.fuelLogs.length }} fuel · {{ garage.repairs.length }} repairs · {{ garage.mods.length }} mods</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.latestRepair') }}</span>
            <strong>{{ latestRepair ? `${latestRepair.type} on ${formatDate(latestRepair.date)}` : t('dashboard.noRepairLogged') }}</strong>
          </div>

          <div class="snapshot-item">
            <span>{{ t('dashboard.latestMod') }}</span>
            <strong>{{ latestMod ? `${latestMod.name} on ${formatDate(latestMod.date_installed)}` : t('dashboard.noModsLogged') }}</strong>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.smartReminders') }}</h3>
        </div>

        <div v-if="!smartReminders.length" class="empty-card">
          {{ t('dashboard.selectedCarUpToDate') }}
        </div>

        <div v-else class="reminder-list">
          <div v-for="item in smartReminders" :key="item.title" class="reminder-item">
            <div class="reminder-title">{{ item.title }}</div>
            <div class="work-item-sub">{{ item.message }}</div>
          </div>
        </div>
      </article>
    </section>

    <section class="work-grid-2 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.upcomingExpenses') }}</h3>
        </div>

        <div v-if="!garage.upcomingExpenses.length" class="empty-card">
          {{ t('dashboard.noUpcomingExpenses') }}
        </div>

        <div v-else class="reminder-list">
          <div v-for="item in garage.upcomingExpenses" :key="item.title" class="reminder-item">
            <div class="reminder-title">{{ item.title }}</div>
            <div class="work-item-sub">{{ item.message }}</div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.assistantInsights') }}</h3>
        </div>

        <div v-if="!garage.assistantInsights.length" class="empty-card">
          {{ t('dashboard.noAssistantInsights') }}
        </div>

        <div v-else class="reminder-list">
          <div v-for="item in garage.assistantInsights" :key="item.title" class="reminder-item">
            <div class="reminder-title">{{ item.title }}</div>
            <div class="work-item-sub">{{ item.message }}</div>
          </div>
        </div>
      </article>
    </section>

    <section class="work-grid-3 mt-4">
      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.whatChangedThisMonth') }}</h3>
        </div>

        <div class="snapshot-list">
          <div class="snapshot-item">
            <span>{{ t('dashboard.fuelLogsAdded') }}</span>
            <strong>{{ garage.currentMonthChanges.fuelLogsAdded }}</strong>
          </div>
          <div class="snapshot-item">
            <span>{{ t('dashboard.repairsAdded') }}</span>
            <strong>{{ garage.currentMonthChanges.repairsAdded }}</strong>
          </div>
          <div class="snapshot-item">
            <span>{{ t('dashboard.modsAdded') }}</span>
            <strong>{{ garage.currentMonthChanges.modsAdded }}</strong>
          </div>
          <div class="snapshot-item">
            <span>{{ t('dashboard.spentThisMonth') }}</span>
            <strong>{{ formatCurrency(garage.currentMonthChanges.spentThisMonth) }}</strong>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.compareCars') }}</h3>
        </div>

        <div v-if="!garage.compareCars.length" class="empty-card">
          {{ t('dashboard.addMoreCarsToCompare') }}
        </div>

        <div v-else class="compare-list">
          <div
            v-for="item in garage.compareCars.slice(0, 5)"
            :key="item.id"
            class="compare-item"
            :class="{ active: item.isSelected }"
          >
            <div>
              <div class="work-item-title">{{ item.name }}</div>
              <div class="work-item-sub">
                {{ item.year }} · {{ item.fuelLogsCount }} fuel · {{ item.repairsCount }} repairs · {{ item.modsCount }} mods
              </div>
            </div>
            <div class="compare-total">{{ formatCurrency(item.total) }}</div>
          </div>
        </div>
      </article>

      <article class="work-panel">
        <div class="work-panel-head">
          <h3 class="work-panel-title">{{ t('dashboard.recentActivity') }}</h3>
        </div>

        <div class="work-list">
          <div v-for="item in garage.summary.recent_fuel_logs" :key="`fuel-${item.id}`" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.car?.brand }} {{ item.car?.model }}</div>
              <div class="work-item-sub">{{ formatDate(item.date) }} · Fuel · {{ formatCurrency(item.total_price) }}</div>
            </div>
          </div>

          <div v-for="item in garage.summary.recent_repairs" :key="`repair-${item.id}`" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.type }}</div>
              <div class="work-item-sub">{{ formatDate(item.date) }} · Repair · {{ formatCurrency(item.cost) }}</div>
            </div>
          </div>

          <div v-for="item in garage.summary.recent_mods" :key="`mod-${item.id}`" class="work-list-item static">
            <div>
              <div class="work-item-title">{{ item.name }}</div>
              <div class="work-item-sub">{{ formatDate(item.date_installed) }} · Mod · {{ formatCurrency(item.cost) }}</div>
            </div>
          </div>
        </div>
      </article>
    </section>

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
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend } from 'chart.js'
import DateInput from '../components/DateInput.vue'
import ChartWrapper from '../components/ChartWrapper.vue'
import MainLayout from '../layouts/MainLayout.vue'
import { useAuthStore } from '../stores/auth'
import { useGarageStore } from '../stores/garage'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend)

const auth = useAuthStore()
const garage = useGarageStore()
const { t } = useI18n()

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
const latestRepair = computed(() => garage.latestRepair)
const latestMod = computed(() => garage.latestMod)
const totalFuelSpend = computed(() => garage.totalFuelSpend)
const selectedCarTotalSpend = computed(() => garage.selectedCarTotalSpend)
const selectedCarServiceSpend = computed(() => garage.selectedCarServiceSpend)

const periodOptions = computed(() => [
  { value: 'all', label: t('dashboard.allTime') },
  { value: '3m', label: t('dashboard.months3') },
  { value: '6m', label: t('dashboard.months6') },
  { value: '12m', label: t('dashboard.months12') }
])

const fuelConsumptionChart = computed(() => {
  const grouped = garage.filteredFuelLogs.reduce((acc, item) => {
    if (!item.fuel_consumption) return acc

    const monthKey = String(item.date).slice(0, 7)

    if (!acc[monthKey]) {
      acc[monthKey] = { total: 0, count: 0 }
    }

    acc[monthKey].total += Number(item.fuel_consumption)
    acc[monthKey].count += 1

    return acc
  }, {})

  return Object.entries(grouped)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([label, value]) => ({
      label,
      value: value.total / value.count
    }))
})

const monthlyExpenseChart = computed(() => garage.monthlyExpenseBreakdown)

const fuelConsumptionData = computed(() => ({
  labels: fuelConsumptionChart.value.map((item) => item.label),
  datasets: [
    {
      label: 'L/100km',
      data: fuelConsumptionChart.value.map((item) => item.value),
      borderColor: '#c81e1e',
      backgroundColor: 'rgba(227, 0, 0, 0.08)',
      tension: 0.35,
      fill: true
    }
  ]
}))

const monthlyExpenseData = computed(() => ({
  labels: monthlyExpenseChart.value.map((item) => item.month),
  datasets: [
    {
      type: 'bar',
      label: t('dashboard.fuelLogs'),
      data: monthlyExpenseChart.value.map((item) => item.fuel),
      backgroundColor: 'rgba(227, 0, 0, 0.75)'
    },
    {
      type: 'bar',
      label: t('dashboard.repairs'),
      data: monthlyExpenseChart.value.map((item) => item.repairs),
      backgroundColor: 'rgba(17, 24, 39, 0.70)'
    },
    {
      type: 'bar',
      label: t('dashboard.mods'),
      data: monthlyExpenseChart.value.map((item) => item.mods),
      backgroundColor: 'rgba(107, 114, 128, 0.65)'
    }
  ]
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback(value) {
          if (this.chart?.data?.datasets?.[0]?.label === 'L/100km') {
            return `${value} L/100km`
          }

          return formatCurrency(value)
        }
      }
    }
  }
}))

const stackedChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: true }
  },
  scales: {
    x: {
      stacked: true
    },
    y: {
      stacked: true,
      beginAtZero: true,
      ticks: {
        callback(value) {
          return formatCurrency(value)
        }
      }
    }
  }
}))

const smartReminders = computed(() => {
  if (!selectedCar.value) return []

  const reminders = []
  const latestFuelLog = garage.latestFuelLog
  const latestRepairItem = garage.latestRepair
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
  required: (v) => !!v || t('dashboard.required'),
  requiredDate: (v) => !!v || t('dashboard.required'),
  gt(value) {
    return (v) => (!!v && Number(v) > value) || `Must be greater than ${value}`
  },
  gte(value) {
    return (v) => (v !== '' && v !== null && v !== undefined && Number(v) >= value) || `Must be at least ${value}`
  },
  integerMin(value) {
    return (v) => (!!v && Number.isInteger(Number(v)) && Number(v) >= value) || `Must be integer >= ${value}`
  },
  year: (v) => (!!v && Number(v) >= 1900 && Number(v) <= 2100) || 'Year between 1900-2100'
}

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

const setPeriod = (period) => {
  garage.setSelectedPeriod(period)
}

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
        year: new Date().getFullYear()
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

const copyResaleSummary = async () => {
  if (!selectedCar.value || !navigator?.clipboard) return

  const lines = [
    `${selectedCar.value.brand} ${selectedCar.value.model} (${selectedCar.value.year})`,
    `Engine: ${selectedCar.value.engine_volume}L`,
    `Plate: ${selectedCar.value.license_plate}`,
    `Fuel spend: ${formatCurrency(totalFuelSpend.value)}`,
    `Service + mods: ${formatCurrency(selectedCarServiceSpend.value)}`,
    `Tracked ownership total: ${formatCurrency(selectedCarTotalSpend.value)}`,
    `Cost per km: ${garage.costPerKm ? formatCurrency(garage.costPerKm) : '—'}`,
    `Resale score: ${garage.resaleScore}/100`,
    `Fuel logs: ${garage.fuelLogs.length}, repairs: ${garage.repairs.length}, mods: ${garage.mods.length}`,
    `Latest repair: ${latestRepair.value ? `${latestRepair.value.type} on ${formatDate(latestRepair.value.date)}` : t('dashboard.noRepairLogged')}`,
    `Latest mod: ${latestMod.value ? `${latestMod.value.name} on ${formatDate(latestMod.value.date_installed)}` : t('dashboard.noModsLogged')}`
  ]

  await navigator.clipboard.writeText(lines.join('\n'))
}

const exportResalePdf = () => {
  if (!selectedCar.value) return

  const summaryRows = [
    [t('dashboard.vehicle'), `${selectedCar.value.brand} ${selectedCar.value.model} (${selectedCar.value.year})`],
    [t('dashboard.engineVolume'), `${selectedCar.value.engine_volume}L`],
    [t('dashboard.plate'), selectedCar.value.license_plate],
    [t('dashboard.totalFuelSpend'), formatCurrency(totalFuelSpend.value)],
    [t('dashboard.serviceAndMods'), formatCurrency(selectedCarServiceSpend.value)],
    [t('dashboard.trackedOwnershipTotal'), formatCurrency(selectedCarTotalSpend.value)],
    [t('dashboard.costPerKm'), garage.costPerKm ? formatCurrency(garage.costPerKm) : '—'],
    [t('dashboard.resaleScore'), `${garage.resaleScore}/100`],
    [t('dashboard.fuelLogs'), String(garage.fuelLogs.length)],
    [t('dashboard.repairs'), String(garage.repairs.length)],
    [t('dashboard.mods'), String(garage.mods.length)],
    [t('dashboard.latestRepair'), latestRepair.value ? `${latestRepair.value.type} on ${formatDate(latestRepair.value.date)}` : t('dashboard.noRepairLogged')],
    [t('dashboard.latestMod'), latestMod.value ? `${latestMod.value.name} on ${formatDate(latestMod.value.date_installed)}` : t('dashboard.noModsLogged')]
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
    ? smartReminders.value
        .map((item) => `<li><strong>${escapeHtml(item.title)}:</strong> ${escapeHtml(item.message)}</li>`)
        .join('')
    : '<li>No urgent reminders at the moment.</li>'

  const insightRows = garage.assistantInsights.length
    ? garage.assistantInsights
        .map((item) => `<li><strong>${escapeHtml(item.title)}:</strong> ${escapeHtml(item.message)}</li>`)
        .join('')
    : '<li>No assistant insights yet.</li>'

  const popup = window.open('', '_blank', 'width=980,height=900')
  if (!popup) return

  popup.document.write(`
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8" />
        <title>Resale Summary - ${escapeHtml(selectedCar.value.brand)} ${escapeHtml(selectedCar.value.model)}</title>
        <style>
          body {
            margin: 0;
            padding: 32px;
            font-family: Arial, sans-serif;
            color: #111827;
            background: #ffffff;
          }

          .sheet {
            max-width: 900px;
            margin: 0 auto;
          }

          .kicker {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 8px;
          }

          h1 {
            margin: 0 0 8px;
            font-size: 30px;
          }

          .subtitle {
            margin: 0 0 24px;
            color: #4b5563;
          }

          .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
          }

          .meta-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
          }

          .meta-card span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 6px;
          }

          .meta-card strong {
            font-size: 16px;
          }

          .section {
            margin-top: 18px;
          }

          .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
          }

          table {
            width: 100%;
            border-collapse: collapse;
          }

          td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
            vertical-align: top;
          }

          td:first-child {
            width: 240px;
            color: #6b7280;
          }

          ul {
            margin: 0;
            padding-left: 18px;
          }

          li {
            margin-bottom: 8px;
          }

          .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #6b7280;
          }
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
              <strong>${escapeHtml(formatCurrency(selectedCarTotalSpend.value))}</strong>
            </article>
          </section>

          <section class="section">
            <div class="section-title">Ownership Snapshot</div>
            <table>
              <tbody>${tableRows}</tbody>
            </table>
          </section>

          <section class="section">
            <div class="section-title">Smart Reminders</div>
            <ul>${reminderRows}</ul>
          </section>

          <section class="section">
            <div class="section-title">Assistant Insights</div>
            <ul>${insightRows}</ul>
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
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')

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