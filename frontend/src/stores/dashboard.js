import { defineStore } from 'pinia'
import api from '../api/client'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: {
      documents_total: 0,
      documents_owned: 0,
      documents_shared_with_me: 0,
      documents_archived: 0,
      documents_in_trash: 0,
      folders_total: 0
    },
    recentDocuments: [],
    recentActivity: [],
    loading: false
  }),
  actions: {
    async fetchSummary() {
      this.loading = true
      try {
        const { data } = await api.get('/dashboard/summary')
        this.stats = data.stats
        this.recentDocuments = data.recent_documents
        this.recentActivity = data.recent_activity
      } finally {
        this.loading = false
      }
    }
  }
})
