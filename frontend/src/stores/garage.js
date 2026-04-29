import { defineStore } from 'pinia'
import api from '../api/client'

const emptyStats = () => ({
  cars_total: 0,
  fuel_logs_total: 0,
  repairs_total: 0,
  mods_total: 0,
  total_spent: 0
})

const toNumber = (value) => Number(value || 0)

const monthKeyFromDate = (value) => String(value || '').slice(0, 7)

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
      date: ''
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
      if (state.selectedPeriod === 'all') return state.fuelLogs

      const monthsBack = Number(String(state.selectedPeriod).replace('m', ''))
      const threshold = new Date()
      threshold.setMonth(threshold.getMonth() - monthsBack)

      return state.fuelLogs.filter((item) => new Date(item.date) >= threshold)
    },

    filteredRepairs(state) {
      if (state.selectedPeriod === 'all') return state.repairs

      const monthsBack = Number(String(state.selectedPeriod).replace('m', ''))
      const threshold = new Date()
      threshold.setMonth(threshold.getMonth() - monthsBack)

      return state.repairs.filter((item) => new Date(item.date) >= threshold)
    },

    filteredMods(state) {
      if (state.selectedPeriod === 'all') return state.mods

      const monthsBack = Number(String(state.selectedPeriod).replace('m', ''))
      const threshold = new Date()
      threshold.setMonth(threshold.getMonth() - monthsBack)

      return state.mods.filter((item) => new Date(item.date_installed) >= threshold)
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

      const mileages = this.filteredFuelLogs
        .map((item) => toNumber(item.mileage))
        .filter((value) => value > 0)
        .sort((a, b) => a - b)

      if (mileages.length < 2) return 0

      return Math.max(0, mileages[mileages.length - 1] - mileages[0])
    },

    costPerKm() {
      const distance = this.totalDistanceTracked
      if (!distance) return 0
      return this.selectedCarTotalSpend / distance
    },

    averageFuelConsumption() {
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

      const result = Object.entries(grouped)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([month, values]) => ({
          month,
          ...values
        }))

      console.log('📊 monthlyExpenseBreakdown computed:', {
        fuelLogs: this.filteredFuelLogs.length,
        repairs: this.filteredRepairs.length,
        mods: this.filteredMods.length,
        result: result.length,
        data: result
      })

      return result
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

    setSelectedPeriod(period) {
      this.selectedPeriod = period
    },

    resetSelectedCarState() {
      this.selectedCarId = null
      this.fuelLogs = []
      this.repairs = []
      this.mods = []
      this.fuelFilters = { date: '' }
      this.repairFilters = { date_from: '', date_to: '' }
    },

    async bootstrap() {
      this.clearError()
      await Promise.all([this.fetchSummary(), this.fetchCars()])
    },

    async fetchSummary() {
      this.summaryLoading = true
      this.clearError()

      try {
        const { data } = await api.get('/dashboard/summary')

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
      if (!this.selectedCarId) return

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
      if (!this.selectedCarId) return

      this.fuelFilters = {
        date: filters?.date || ''
      }

      try {
        const { data } = await api.get(`/cars/${this.selectedCarId}/fuel-logs`, {
          params: this.fuelFilters
        })

        this.fuelLogs = Array.isArray(data) ? data : []
      } catch (error) {
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
      if (!this.selectedCarId) return

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
      if (!this.selectedCarId) return

      try {
        const { data } = await api.get(`/cars/${this.selectedCarId}/mods`)
        this.mods = Array.isArray(data) ? data : []
      } catch (error) {
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
