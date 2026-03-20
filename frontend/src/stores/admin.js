import { defineStore } from 'pinia'
import api from '../api/client'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    summary: {
      users_total: 0,
      admins_total: 0,
      developers_total: 0,
      documents_total: 0,
      documents_in_trash: 0,
      storage_total_bytes: 0
    },
    users: [],
    usersPagination: null,
    trashItems: [],
    trashPagination: null,
    developerOverview: null,
    developerUsers: [],
    developerUsersPagination: null,
    developerActivity: [],
    developerActivityPagination: null,
    developerNotifications: [],
    developerNotificationsPagination: null,
    developerDocuments: [],
    developerDocumentsPagination: null,
    developerDocumentDetails: null,
    developerDocumentShares: [],
    developerDocumentComments: [],
    developerDocumentAudit: [],
    developerUserDocuments: [],
    developerStorageTopUsers: [],
    loading: false
  }),
  actions: {
    async fetchSummary() {
      const { data } = await api.get('/admin/summary')
      this.summary = data
    },
    async fetchUsers(page = 1, q = '') {
      this.loading = true
      try {
        const { data } = await api.get('/admin/users', { params: { page, q } })
        this.users = data.data
        this.usersPagination = data
      } finally {
        this.loading = false
      }
    },
    async updateUserRole(userId, role) {
      await api.put(`/admin/users/${userId}/role`, { role })
      await this.fetchUsers(this.usersPagination?.current_page || 1)
      await this.fetchSummary()
    },
    async fetchTrash(page = 1, q = '') {
      this.loading = true
      try {
        const { data } = await api.get('/admin/trash', { params: { page, q } })
        this.trashItems = data.data
        this.trashPagination = data
      } finally {
        this.loading = false
      }
    },
    async restoreDocument(documentId) {
      await api.post(`/admin/documents/${documentId}/restore`)
      await Promise.all([
        this.fetchTrash(this.trashPagination?.current_page || 1),
        this.fetchSummary()
      ])
    },
    async purgeDocument(documentId) {
      await api.delete(`/admin/documents/${documentId}/purge`)
      await Promise.all([
        this.fetchTrash(this.trashPagination?.current_page || 1),
        this.fetchSummary()
      ])
    },
    async fetchDeveloperOverview() {
      const { data } = await api.get('/developer/overview')
      this.developerOverview = data
    },
    async fetchDeveloperUsers(page = 1, q = '') {
      this.loading = true
      try {
        const { data } = await api.get('/developer/users', { params: { page, q } })
        this.developerUsers = data.data
        this.developerUsersPagination = data
      } finally {
        this.loading = false
      }
    },
    async updateDeveloperUserRole(userId, role) {
      await api.put(`/developer/users/${userId}/role`, { role })
      await this.fetchDeveloperUsers(this.developerUsersPagination?.current_page || 1)
    },
    async fetchDeveloperActivity(page = 1) {
      const { data } = await api.get('/developer/activity', { params: { page, per_page: 30 } })
      this.developerActivity = data.data
      this.developerActivityPagination = data
    },
    async fetchDeveloperNotifications(page = 1) {
      const { data } = await api.get('/developer/notifications', { params: { page, per_page: 30 } })
      this.developerNotifications = data.data
      this.developerNotificationsPagination = data
    },
    async fetchDeveloperDocuments(params = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/developer/documents', { params: { per_page: 25, ...params } })
        this.developerDocuments = data.data
        this.developerDocumentsPagination = data
      } finally {
        this.loading = false
      }
    },
    async fetchDeveloperDocumentInspector(documentId) {
      const [details, shares, comments, audit] = await Promise.all([
        api.get(`/developer/documents/${documentId}`),
        api.get(`/developer/documents/${documentId}/shares`),
        api.get(`/developer/documents/${documentId}/comments`),
        api.get(`/developer/documents/${documentId}/audit`)
      ])
      this.developerDocumentDetails = details.data
      this.developerDocumentShares = shares.data
      this.developerDocumentComments = comments.data
      this.developerDocumentAudit = audit.data
    },
    clearDeveloperInspector() {
      this.developerDocumentDetails = null
      this.developerDocumentShares = []
      this.developerDocumentComments = []
      this.developerDocumentAudit = []
    },
    async restoreDeveloperDocument(documentId) {
      await api.post(`/developer/documents/${documentId}/restore`)
    },
    async purgeDeveloperDocument(documentId) {
      await api.delete(`/developer/documents/${documentId}/purge`)
    },
    async setDeveloperDocumentArchive(documentId, isArchived) {
      await api.post(`/developer/documents/${documentId}/archive`, { is_archived: isArchived })
    },
    async setDeveloperDocumentStar(documentId, isStarred) {
      await api.post(`/developer/documents/${documentId}/star`, { is_starred: isStarred })
    },
    async reassignDeveloperDocumentOwner(documentId, ownerId) {
      await api.post(`/developer/documents/${documentId}/reassign-owner`, { owner_id: ownerId })
    },
    async fetchDeveloperUserDocuments(userId) {
      const { data } = await api.get(`/developer/users/${userId}/documents`)
      this.developerUserDocuments = data
    },
    async sendDeveloperNotification(userId, payload) {
      await api.post(`/developer/users/${userId}/notifications`, payload)
    },
    async fetchDeveloperStorageTopUsers() {
      const { data } = await api.get('/developer/storage/top-users')
      this.developerStorageTopUsers = data
    },
    async impersonateUser(userId) {
      const { data } = await api.post(`/developer/users/${userId}/impersonate`)
      return data
    },
    async resetUserPassword(userId) {
      const { data } = await api.post(`/developer/users/${userId}/reset-password`)
      return data
    },
    async removeDeveloperDocumentShare(documentId, userId) {
      await api.delete(`/developer/documents/${documentId}/shares/${userId}`)
    },
    async deleteDeveloperDocumentComment(documentId, commentId) {
      await api.delete(`/developer/documents/${documentId}/comments/${commentId}`)
    },
    async broadcastDeveloperNotification(payload) {
      const { data } = await api.post('/developer/system/broadcast', payload)
      return data
    },
    async cleanupDeveloperTrash(days = 30) {
      const { data } = await api.post('/developer/system/cleanup-trash', { days })
      return data
    },
    async bulkDeveloperDocumentsAction(action, ids) {
      const { data } = await api.post('/developer/documents/bulk', {
        action,
        document_ids: ids
      })
      return data
    }
  }
})
