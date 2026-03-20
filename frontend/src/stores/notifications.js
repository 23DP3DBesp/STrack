import { defineStore } from 'pinia'
import api from '../api/client'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],
    unread: 0,
    loading: false
  }),
  actions: {
    async fetchNotifications(page = 1) {
      this.loading = true
      try {
        const { data } = await api.get('/notifications', { params: { page } })
        this.items = data.items?.data || []
        this.unread = data.unread || 0
      } finally {
        this.loading = false
      }
    },
    async markRead(id) {
      await api.post(`/notifications/${id}/read`)
      this.items = this.items.map((item) => (item.id === id ? { ...item, read_at: new Date().toISOString() } : item))
      this.unread = Math.max(0, this.unread - 1)
    },
    async markAllRead() {
      await api.post('/notifications/read-all')
      const now = new Date().toISOString()
      this.items = this.items.map((item) => ({ ...item, read_at: item.read_at || now }))
      this.unread = 0
    },
    mergeIncoming(items, unreadCount = null) {
      if (Array.isArray(items) && items.length) {
        const currentIds = new Set(this.items.map((item) => item.id))
        const merged = [...items.filter((item) => !currentIds.has(item.id)), ...this.items]
        this.items = merged.slice(0, 40)
      }
      if (typeof unreadCount === 'number') {
        this.unread = unreadCount
      }
    }
  }
})
