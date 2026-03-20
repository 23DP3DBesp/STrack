import { defineStore } from 'pinia'
import api from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('docbox_token') || null,
    impersonator: JSON.parse(localStorage.getItem('docbox_impersonator') || 'null'),
    backupSession: JSON.parse(localStorage.getItem('docbox_backup_session') || 'null'),
    loading: false,
    error: null
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    isAdmin: (state) => state.user?.role === 'admin',
    isDeveloper: (state) => state.user?.role === 'developer',
    isStaff: (state) => ['admin', 'developer'].includes(state.user?.role),
    isImpersonating: (state) => Boolean(state.impersonator)
  },
  actions: {
    async login(payload) {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.post('/auth/login', payload)
        this.setSession(data.user, data.token, null)
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
        this.setSession(data.user, data.token, null)
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
      this.impersonator = null
      this.backupSession = null
      this.error = null
      localStorage.removeItem('docbox_token')
      localStorage.removeItem('docbox_impersonator')
      localStorage.removeItem('docbox_backup_session')
    },
    setSession(user, token, impersonator = null) {
      this.user = user
      this.token = token
      this.impersonator = impersonator
      localStorage.setItem('docbox_token', token)
      if (impersonator) {
        localStorage.setItem('docbox_impersonator', JSON.stringify(impersonator))
      } else {
        localStorage.removeItem('docbox_impersonator')
      }
    },
    startImpersonation(user, token, impersonator) {
      this.backupSession = {
        token: this.token,
        user: this.user
      }
      localStorage.setItem('docbox_backup_session', JSON.stringify(this.backupSession))
      this.setSession(user, token, impersonator)
    },
    stopImpersonation() {
      if (!this.backupSession?.token || !this.backupSession?.user) return false
      this.setSession(this.backupSession.user, this.backupSession.token, null)
      this.backupSession = null
      localStorage.removeItem('docbox_backup_session')
      return true
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
