import { defineStore } from 'pinia'
import api from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('car_tracker_token') || null,
    pendingVerificationEmail: localStorage.getItem('car_tracker_pending_verification_email') || '',
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
        this.setPendingVerificationEmail(data.email || payload.email || '')
        return data
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      if (!this.token) return

      try {
        const { data } = await api.get('/auth/me')
        this.user = data
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (_) {}

      this.user = null
      this.token = null
      this.error = null

      localStorage.removeItem('car_tracker_token')
      localStorage.removeItem('user')
    },

    async resendVerificationEmail(payload) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.post('/auth/email/verification-notification', payload)

        if (payload?.email) {
          this.setPendingVerificationEmail(payload.email)
        }

        return data
      } catch (error) {
        this.error = this.extractError(error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateProfile(payload) {
      const normalized = {
        login: payload.login,
        display_name: payload.display_name,
        locale: payload.locale,
        theme: payload.theme,
        currency: payload.currency,
        distance_unit: payload.distance_unit
      }

      const { data: updated } = await api.put('/profile', normalized)
      this.user = updated
      localStorage.setItem('user', JSON.stringify(updated))

      if (updated?.theme) {
        document.documentElement.setAttribute('data-theme', updated.theme)
      }

      return updated
    },

    async updatePassword(payload) {
      await api.put('/profile/password', payload)
    },

    async deleteAccount(payload) {
      await api.delete('/profile', { data: payload })
      await this.logout()
    },

    setSession(user, token) {
      this.user = user
      this.token = token
      this.setPendingVerificationEmail('')

      localStorage.setItem('car_tracker_token', token)
      localStorage.setItem('user', JSON.stringify(user))
    },

    clearError() {
      this.error = null
    },

    setPendingVerificationEmail(email) {
      this.pendingVerificationEmail = email || ''

      if (this.pendingVerificationEmail) {
        localStorage.setItem(
          'car_tracker_pending_verification_email',
          this.pendingVerificationEmail
        )
      } else {
        localStorage.removeItem('car_tracker_pending_verification_email')
      }
    },

    setUserLocale(locale) {
      if (window.i18n) {
        window.i18n.global.locale.value = locale
      }
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

      if (error?.response?.data?.code === 'email_not_verified') {
        return 'Please verify your email before logging in.'
      }

      return apiMessage || 'Request failed. Please try again.'
    }
  }
})
