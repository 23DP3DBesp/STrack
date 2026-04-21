import { defineStore } from 'pinia'
import api from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('car_tracker_token') || null,
    loading: false,
    error: null
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token)
  },
  actions: {
    async login(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/auth/login', payload)
        this.setSession(data.user, data.token)
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async register(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/auth/register', payload)
        this.setSession(data.user, data.token)
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchMe() {
      if (!this.token) return
      const { data } = await api.get('/auth/me')
      this.user = data
    },
    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (_) {
      }
      this.user = null
      this.token = null
      this.error = null
      localStorage.removeItem('car_tracker_token')
    },
    async updateProfile(data) {
      const { data: updated } = await api.put('/profile', data)
      this.user = updated
      localStorage.setItem('user', JSON.stringify(updated))
      // Apply theme immediately
      document.documentElement.setAttribute('data-theme', updated.theme)
      return updated
    },

    setUserLocale(locale) {
      // Sync with i18n
      if (window.i18n) {
        window.i18n.global.locale.value = locale
      }
    },
    async updatePassword(data) {
      await api.put('/profile/password', data)
    },
    async deleteAccount(data) {
      await api.delete('/profile', { data })
      this.logout()
    },
    setSession(user, token) {
      this.user = user
      this.token = token
      localStorage.setItem('car_tracker_token', token)
    },
    clearError() {
      this.error = null
    },
    extractError(error) {
      if (!error?.response) {
        return 'Cannot connect to API server. Check backend and database are running.'
      }

      const apiMessage = error?.response?.data?.message
      const errors = error?.response?.data?.errors
      if (errors && typeof errors === 'object') {
        const firstField = Object.keys(errors)[0]
        if (firstField && Array.isArray(errors[firstField]) && errors[firstField][0]) {
          return errors[firstField][0]
        }
      }

      if (error?.response?.status >= 500) {
        return 'Server error. Check Laravel logs and database connection.'
      }

      return apiMessage || 'Request failed. Please try again.'
    }
  }
})
