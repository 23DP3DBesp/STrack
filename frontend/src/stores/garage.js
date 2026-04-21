import { defineStore } from 'pinia'
import api from '../api/client'

const emptyStats = () => ({
  cars_total: 0,
  fuel_logs_total: 0,
  repairs_total: 0,
  mods_total: 0,
  total_spent: 0
})

export const useGarageStore = defineStore('garage', {
  state: () => ({
    summary: {
      stats: emptyStats(),
      recent_fuel_logs: [],
      recent_repairs: [],
      recent_mods: []
    },
    cars: [],
    selectedCarId: null,
    fuelLogs: [],
    repairs: [],
    mods: [],
    carsLoading: false,
    recordsLoading: false,
    summaryLoading: false,
    search: ''
  }),
  getters: {
    selectedCar: (state) => state.cars.find((car) => car.id === state.selectedCarId) || null
  },
  actions: {
    async bootstrap() {
      await Promise.all([this.fetchSummary(), this.fetchCars()])
    },
    async fetchSummary() {
      this.summaryLoading = true
      try {
        const { data } = await api.get('/dashboard/summary')
        this.summary = {
          stats: { ...emptyStats(), ...(data.stats || {}) },
          recent_fuel_logs: data.recent_fuel_logs || [],
          recent_repairs: data.recent_repairs || [],
          recent_mods: data.recent_mods || []
        }
      } finally {
        this.summaryLoading = false
      }
    },
    async fetchCars(search = this.search) {
      this.search = search
      this.carsLoading = true
      try {
        const { data } = await api.get('/cars', { params: { search } })
        this.cars = data

        if (!this.cars.length) {
          this.selectedCarId = null
          this.fuelLogs = []
          this.repairs = []
          this.mods = []
          return
        }

        if (!this.cars.some((car) => car.id === this.selectedCarId)) {
          this.selectedCarId = this.cars[0].id
        }

        await this.fetchSelectedCarData()
      } finally {
        this.carsLoading = false
      }
    },
    async selectCar(carId) {
      this.selectedCarId = carId
      await this.fetchSelectedCarData()
    },
    async fetchSelectedCarData(filters = {}) {
      if (!this.selectedCarId) return

      this.recordsLoading = true
      try {
        await Promise.all([
          this.fetchFuelLogs(filters.fuel || {}),
          this.fetchRepairs(filters.repairs || {}),
          this.fetchMods()
        ])
      } finally {
        this.recordsLoading = false
      }
    },
    async saveCar(payload) {
      if (payload.id) {
        await api.put(`/cars/${payload.id}`, payload)
      } else {
        await api.post('/cars', payload)
      }

      await Promise.all([this.fetchCars(), this.fetchSummary()])
    },
    async deleteCar(id) {
      await api.delete(`/cars/${id}`)
      await Promise.all([this.fetchCars(), this.fetchSummary()])
    },
    async fetchFuelLogs(filters = {}) {
      if (!this.selectedCarId) return
      const { data } = await api.get(`/cars/${this.selectedCarId}/fuel-logs`, { params: filters })
      this.fuelLogs = data
    },
    async saveFuelLog(payload) {
      if (!this.selectedCarId) return
      if (payload.id) {
        await api.put(`/fuel-logs/${payload.id}`, payload)
      } else {
        await api.post(`/cars/${this.selectedCarId}/fuel-logs`, payload)
      }

      await Promise.all([this.fetchFuelLogs(), this.fetchSummary(), this.fetchCars()])
    },
    async deleteFuelLog(id) {
      await api.delete(`/fuel-logs/${id}`)
      await Promise.all([this.fetchFuelLogs(), this.fetchSummary(), this.fetchCars()])
    },
    async fetchRepairs(filters = {}) {
      if (!this.selectedCarId) return
      const { data } = await api.get(`/cars/${this.selectedCarId}/repairs`, { params: filters })
      this.repairs = data
    },
    async saveRepair(payload) {
      if (!this.selectedCarId) return
      if (payload.id) {
        await api.put(`/repairs/${payload.id}`, payload)
      } else {
        await api.post(`/cars/${this.selectedCarId}/repairs`, payload)
      }

      await Promise.all([this.fetchRepairs(), this.fetchSummary(), this.fetchCars()])
    },
    async deleteRepair(id) {
      await api.delete(`/repairs/${id}`)
      await Promise.all([this.fetchRepairs(), this.fetchSummary(), this.fetchCars()])
    },
    async fetchMods() {
      if (!this.selectedCarId) return
      const { data } = await api.get(`/cars/${this.selectedCarId}/mods`)
      this.mods = data
    },
    async saveMod(payload) {
      if (!this.selectedCarId) return
      if (payload.id) {
        await api.put(`/mods/${payload.id}`, payload)
      } else {
        await api.post(`/cars/${this.selectedCarId}/mods`, payload)
      }

      await Promise.all([this.fetchMods(), this.fetchSummary(), this.fetchCars()])
    },
    async deleteMod(id) {
      await api.delete(`/mods/${id}`)
      await Promise.all([this.fetchMods(), this.fetchSummary(), this.fetchCars()])
    }
  }
})
