import { defineStore } from 'pinia'
import api from '../api/client'

const emptyStats = () => ({
  cars_total: 0,
  fuel_logs_total: 0,
  repairs_total: 0,
  mods_total: 0,
  total_spent: 0,
  distance_tracked: 0,
  cost_per_km: 0
})

const toNumber = (value) => Number(value || 0)

const monthKeyFromDate = (value) => String(value || '').slice(0, 7)

const isMissingResourceError = (error) => error?.response?.status === 404

export const parseLocalDate = (value) => {
  if (!value) return null

  if (value instanceof Date) {
    const date = new Date(value)
    date.setHours(0, 0, 0, 0)
    return Number.isNaN(date.getTime()) ? null : date
  }

  const dateString = String(value).slice(0, 10)
  const isoDateMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/)

  if (isoDateMatch) {
    const [, year, month, day] = isoDateMatch
    return new Date(Number(year), Number(month) - 1, Number(day))
  }

  const date = new Date(value)
  date.setHours(0, 0, 0, 0)

  return Number.isNaN(date.getTime()) ? null : date
}

export const periodStartDate = (period, referenceDate = new Date()) => {
  if (period === 'all') return null

  const monthsBack = Number(String(period).replace('m', ''))

  if (!Number.isFinite(monthsBack) || monthsBack <= 0) return null

  const reference = parseLocalDate(referenceDate)
  if (!reference) return null

  const targetMonth = reference.getMonth() - monthsBack
  const lastTargetMonthDay = new Date(
    reference.getFullYear(),
    targetMonth + 1,
    0
  ).getDate()

  return new Date(
    reference.getFullYear(),
    targetMonth,
    Math.min(reference.getDate(), lastTargetMonthDay)
  )
}

const filterBySelectedPeriod = (items, period, dateField) => {
  const threshold = periodStartDate(period)

  if (!threshold) return items

  const today = parseLocalDate(new Date())

  return items.filter((item) => {
    const itemDate = parseLocalDate(item?.[dateField])

    return itemDate ? itemDate >= threshold && itemDate <= today : false
  })
}

const fuelLogsWithEffectiveDistance = (items) => {
  let previousMileage = null

  return [...items]
    .sort((a, b) => {
      const dateCompare = String(a.date || '').localeCompare(String(b.date || ''))
      return dateCompare || toNumber(a.id) - toNumber(b.id)
    })
    .map((item) => {
      const mileage = toNumber(item.mileage)
      let distance = toNumber(item.distance_since_previous)

      if (!distance && previousMileage !== null && mileage > previousMileage) {
        distance = mileage - previousMileage
      }

      if (mileage > 0) {
        previousMileage = mileage
      }

      return {
        ...item,
        effectiveDistance: distance
      }
    })
}

export const useGarageStore = defineStore('garage', {
  state: () => ({
    summary: {
      stats: emptyStats(),
      recent_fuel_logs: [],
      recent_repairs: [],
      recent_mods: [],
      fleet_monthly_breakdown: [],
      fleet_cost_by_category: {
        fuel: 0,
        repairs: 0,
        mods: 0
      },
      current_month: {
        fuel_spend: 0,
        repair_spend: 0,
        mod_spend: 0,
        total_spend: 0
      }
    },

    cars: [],
    selectedCarId: null,

    fuelLogs: [],
    repairs: [],
    mods: [],

    fuelFilters: {
      date_from: '',
      date_to: ''
    },

    repairFilters: {
      date_from: '',
      date_to: ''
    },

    selectedPeriod: 'all',

    carsLoading: false,
    recordsLoading: false,
    summaryLoading: false,

    error: null,
    search: ''
  }),

  getters: {
    selectedCar: (state) => state.cars.find((car) => car.id === state.selectedCarId) || null,

    latestFuelLog: (state) => state.fuelLogs[0] || null,
    latestRepair: (state) => state.repairs[0] || null,
    latestMod: (state) => state.mods[0] || null,

    selectedPeriodLabel: (state) => {
      const options = {
        all: 'All time',
        '3m': 'Last 3 months',
        '6m': 'Last 6 months',
        '12m': 'Last 12 months'
      }
      return options[state.selectedPeriod] || 'All time'
    },

    filteredFuelLogs(state) {
      return filterBySelectedPeriod(state.fuelLogs, state.selectedPeriod, 'date')
    },

    filteredRepairs(state) {
      return filterBySelectedPeriod(state.repairs, state.selectedPeriod, 'date')
    },

    filteredMods(state) {
      return filterBySelectedPeriod(state.mods, state.selectedPeriod, 'date_installed')
    },

    totalFuelSpend() {
      return this.filteredFuelLogs.reduce((sum, item) => sum + toNumber(item.total_price), 0)
    },

    totalRepairSpend() {
      return this.filteredRepairs.reduce((sum, item) => sum + toNumber(item.cost), 0)
    },

    totalModSpend() {
      return this.filteredMods.reduce((sum, item) => sum + toNumber(item.cost), 0)
    },

    selectedCarTotalSpend() {
      return this.totalFuelSpend + this.totalRepairSpend + this.totalModSpend
    },

    selectedCarServiceSpend() {
      return this.totalRepairSpend + this.totalModSpend
    },

    totalDistanceTracked() {
      if (this.filteredFuelLogs.length < 2) return 0

      const recordedDistance = this.filteredFuelLogs.reduce(
        (sum, item) => sum + toNumber(item.distance_since_previous),
        0
      )

      const effectiveDistance = fuelLogsWithEffectiveDistance(this.filteredFuelLogs).reduce(
        (sum, item) => sum + toNumber(item.effectiveDistance),
        0
      )

      const mileages = this.filteredFuelLogs
        .map((item) => toNumber(item.mileage))
        .filter((value) => value > 0)
        .sort((a, b) => a - b)

      const mileageRange =
        mileages.length >= 2 ? Math.max(0, mileages[mileages.length - 1] - mileages[0]) : 0

      return Math.max(recordedDistance, effectiveDistance, mileageRange)
    },

    costPerKm() {
      const distance = this.totalDistanceTracked
      if (!distance) return 0
      return this.selectedCarTotalSpend / distance
    },

    averageFuelConsumption() {
      const totals = fuelLogsWithEffectiveDistance(this.filteredFuelLogs).reduce(
        (acc, item) => {
          const distance = toNumber(item.effectiveDistance)
          const liters = toNumber(item.liters)

          if (distance > 0 && liters > 0) {
            acc.distance += distance
            acc.liters += liters
          }

          return acc
        },
        { distance: 0, liters: 0 }
      )

      if (totals.distance > 0) {
        return (totals.liters / totals.distance) * 100
      }

      const entries = this.filteredFuelLogs.filter((item) => item.fuel_consumption != null)
      if (!entries.length) return 0

      const total = entries.reduce((sum, item) => sum + toNumber(item.fuel_consumption), 0)
      return total / entries.length
    },

    monthlyExpenseBreakdown() {
      const grouped = {}

      this.filteredFuelLogs.forEach((item) => {
        const key = monthKeyFromDate(item.date)
        if (!grouped[key]) {
          grouped[key] = { fuel: 0, repairs: 0, mods: 0, total: 0 }
        }
        grouped[key].fuel += toNumber(item.total_price)
        grouped[key].total += toNumber(item.total_price)
      })

      this.filteredRepairs.forEach((item) => {
        const key = monthKeyFromDate(item.date)
        if (!grouped[key]) {
          grouped[key] = { fuel: 0, repairs: 0, mods: 0, total: 0 }
        }
        grouped[key].repairs += toNumber(item.cost)
        grouped[key].total += toNumber(item.cost)
      })

      this.filteredMods.forEach((item) => {
        const key = monthKeyFromDate(item.date_installed)
        if (!grouped[key]) {
          grouped[key] = { fuel: 0, repairs: 0, mods: 0, total: 0 }
        }
        grouped[key].mods += toNumber(item.cost)
        grouped[key].total += toNumber(item.cost)
      })

      return Object.entries(grouped)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, values]) => ({
          month,
          ...values
        }))
    },

    filteredStats() {
      return {
        cars_total: this.summary.stats.cars_total,
        fuel_logs_total: this.filteredFuelLogs.length,
        repairs_total: this.filteredRepairs.length,
        mods_total: this.filteredMods.length,
        total_spent: this.totalFuelSpend + this.totalRepairSpend + this.totalModSpend
      }
    },

    selectedCarContext() {
      if (!this.selectedCar) return null

      return {
        car: this.selectedCar,
        latest: {
          fuelLog: this.latestFuelLog,
          repair: this.latestRepair,
          mod: this.latestMod
        },
        stats: {
          fuelLogsCount: this.fuelLogs.length,
          repairsCount: this.repairs.length,
          modsCount: this.mods.length,
          totalFuelSpend: this.totalFuelSpend,
          totalRepairSpend: this.totalRepairSpend,
          totalModSpend: this.totalModSpend,
          totalSpend: this.selectedCarTotalSpend,
          averageFuelConsumption: this.averageFuelConsumption,
          totalDistanceTracked: this.totalDistanceTracked,
          costPerKm: this.costPerKm
        }
      }
    }
  },

  actions: {
    clearError() {
      this.error = null
    },

    setError(error, fallbackMessage = 'Something went wrong') {
      this.error = error?.response?.data?.message || error?.message || fallbackMessage
    },

    async setSelectedPeriod(period) {
      this.selectedPeriod = period
      await this.fetchSummary()
    },

    resetSelectedCarState() {
      this.selectedCarId = null
      this.fuelLogs = []
      this.repairs = []
      this.mods = []
      this.fuelFilters = { date_from: '', date_to: '' }
      this.repairFilters = { date_from: '', date_to: '' }
    },

    ensureSelectedCarExists() {
      if (!this.selectedCarId) return false

      if (this.cars.length && !this.selectedCar) {
        this.selectedCarId = this.cars[0].id
      }

      return Boolean(this.selectedCarId)
    },

    async recoverMissingSelectedCar(error) {
      if (!isMissingResourceError(error)) return false

      await this.fetchCars(this.search, { reloadSelected: false })

      if (!this.cars.length) {
        this.resetSelectedCarState()
      }

      return true
    },

    async bootstrap() {
      this.clearError()
      await Promise.all([this.fetchSummary(), this.fetchCars()])
    },

    async fetchSummary(period = this.selectedPeriod) {
      this.summaryLoading = true
      this.clearError()

      try {
        const { data } = await api.get('/dashboard/summary', {
          params: { period }
        })

        this.summary = {
          stats: { ...emptyStats(), ...(data?.stats || {}) },
          recent_fuel_logs: data?.recent_fuel_logs || [],
          recent_repairs: data?.recent_repairs || [],
          recent_mods: data?.recent_mods || [],
          fleet_monthly_breakdown: data?.fleet_monthly_breakdown || [],
          fleet_cost_by_category: data?.fleet_cost_by_category || {
            fuel: 0,
            repairs: 0,
            mods: 0
          },
          current_month: data?.current_month || {
            fuel_spend: 0,
            repair_spend: 0,
            mod_spend: 0,
            total_spend: 0
          }
        }
      } catch (error) {
        this.setError(error, 'Failed to load dashboard summary')
        throw error
      } finally {
        this.summaryLoading = false
      }
    },

    async fetchCars(search = this.search, options = {}) {
      const { reloadSelected = true } = options

      this.search = search
      this.carsLoading = true
      this.clearError()

      try {
        const { data } = await api.get('/cars', {
          params: { search }
        })

        this.cars = Array.isArray(data) ? data : []

        if (!this.cars.length) {
          this.resetSelectedCarState()
          return
        }

        const selectedExists = this.cars.some((car) => car.id === this.selectedCarId)

        if (!selectedExists) {
          this.selectedCarId = this.cars[0].id
        }

        if (reloadSelected) {
          await this.fetchSelectedCarData()
        }
      } catch (error) {
        this.setError(error, 'Failed to load cars')
        throw error
      } finally {
        this.carsLoading = false
      }
    },

    async selectCar(carId) {
      if (this.selectedCarId === carId) return
      this.selectedCarId = carId
      await this.fetchSelectedCarData()
    },

    async fetchSelectedCarData(filters = {}) {
      if (!this.ensureSelectedCarExists()) return

      this.recordsLoading = true
      this.clearError()

      try {
        await Promise.all([
          this.fetchFuelLogs(filters.fuel ?? this.fuelFilters),
          this.fetchRepairs(filters.repairs ?? this.repairFilters),
          this.fetchMods()
        ])
      } catch (error) {
        this.setError(error, 'Failed to load selected car data')
        throw error
      } finally {
        this.recordsLoading = false
      }
    },

    async saveCar(payload) {
      this.clearError()

      try {
        if (payload?.id) {
          await api.put(`/cars/${payload.id}`, payload)
        } else {
          await api.post('/cars', payload)
        }

        await Promise.all([this.fetchCars(), this.fetchSummary()])
      } catch (error) {
        this.setError(error, 'Failed to save car')
        throw error
      }
    },

    async deleteCar(id) {
      this.clearError()

      try {
        await api.delete(`/cars/${id}`)
        await Promise.all([this.fetchCars(), this.fetchSummary()])
      } catch (error) {
        this.setError(error, 'Failed to delete car')
        throw error
      }
    },

    async fetchFuelLogs(filters = this.fuelFilters) {
      if (!this.ensureSelectedCarExists()) return

      this.fuelFilters = {
        date_from: filters?.date_from || '',
        date_to: filters?.date_to || ''
      }

      try {
        const { data } = await api.get(`/cars/${this.selectedCarId}/fuel-logs`, {
          params: this.fuelFilters
        })

        this.fuelLogs = Array.isArray(data) ? data : []
      } catch (error) {
        if (await this.recoverMissingSelectedCar(error)) return

        this.setError(error, 'Failed to load fuel logs')
        throw error
      }
    },

    async saveFuelLog(payload) {
      if (!this.selectedCarId) return

      this.clearError()

      try {
        if (payload?.id) {
          await api.put(`/fuel-logs/${payload.id}`, payload)
        } else {
          await api.post(`/cars/${this.selectedCarId}/fuel-logs`, payload)
        }

        await Promise.all([
          this.fetchFuelLogs(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to save fuel log')
        throw error
      }
    },

    async deleteFuelLog(id) {
      this.clearError()

      try {
        await api.delete(`/fuel-logs/${id}`)

        await Promise.all([
          this.fetchFuelLogs(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to delete fuel log')
        throw error
      }
    },

    async fetchRepairs(filters = this.repairFilters) {
      if (!this.ensureSelectedCarExists()) return

      this.repairFilters = {
        date_from: filters?.date_from || '',
        date_to: filters?.date_to || ''
      }

      try {
        const { data } = await api.get(`/cars/${this.selectedCarId}/repairs`, {
          params: this.repairFilters
        })

        this.repairs = Array.isArray(data) ? data : []
      } catch (error) {
        if (await this.recoverMissingSelectedCar(error)) return

        this.setError(error, 'Failed to load repairs')
        throw error
      }
    },

    async saveRepair(payload) {
      if (!this.selectedCarId) return

      this.clearError()

      try {
        if (payload?.id) {
          await api.put(`/repairs/${payload.id}`, payload)
        } else {
          await api.post(`/cars/${this.selectedCarId}/repairs`, payload)
        }

        await Promise.all([
          this.fetchRepairs(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to save repair')
        throw error
      }
    },

    async deleteRepair(id) {
      this.clearError()

      try {
        await api.delete(`/repairs/${id}`)

        await Promise.all([
          this.fetchRepairs(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to delete repair')
        throw error
      }
    },

    async fetchMods() {
      if (!this.ensureSelectedCarExists()) return

      try {
        const { data } = await api.get(`/cars/${this.selectedCarId}/mods`)
        this.mods = Array.isArray(data) ? data : []
      } catch (error) {
        if (await this.recoverMissingSelectedCar(error)) return

        this.setError(error, 'Failed to load mods')
        throw error
      }
    },

    async saveMod(payload) {
      if (!this.selectedCarId) return

      this.clearError()

      try {
        if (payload?.id) {
          await api.put(`/mods/${payload.id}`, payload)
        } else {
          await api.post(`/cars/${this.selectedCarId}/mods`, payload)
        }

        await Promise.all([
          this.fetchMods(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to save mod')
        throw error
      }
    },

    async deleteMod(id) {
      this.clearError()

      try {
        await api.delete(`/mods/${id}`)

        await Promise.all([
          this.fetchMods(),
          this.fetchSummary(),
          this.fetchCars(this.search, { reloadSelected: false })
        ])
      } catch (error) {
        this.setError(error, 'Failed to delete mod')
        throw error
      }
    }
  }
})
