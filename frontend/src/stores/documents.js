import { defineStore } from 'pinia'
import api from '../api/client'

export const useDocumentsStore = defineStore('documents', {
  state: () => ({
    items: [],
    pagination: null,
    folders: [],
    filters: {
      q: '',
      folder_id: null,
      is_archived: null,
      is_starred: null,
      ownership: 'all',
      size_from_kb: null,
      sort_by: 'updated_at',
      sort_dir: 'desc'
    },
    activeDocument: null,
    activeContent: '',
    contentEditable: false,
    versions: [],
    shares: [],
    auditLogs: [],
    comments: [],
    trashItems: [],
    trashPagination: null,
    trashQuery: '',
    loading: false
  }),
  actions: {
    async fetchDocuments(page = 1) {
      this.loading = true
      try {
        const params = { page }
        if (this.filters.q) params.q = this.filters.q
        if (this.filters.folder_id) params.folder_id = this.filters.folder_id
        if (this.filters.is_archived !== null) params.is_archived = this.filters.is_archived ? '1' : '0'
        if (this.filters.is_starred !== null) params.is_starred = this.filters.is_starred ? '1' : '0'
        if (this.filters.ownership) params.ownership = this.filters.ownership
        if (this.filters.size_from_kb) params.size_from_kb = this.filters.size_from_kb
        if (this.filters.sort_by) params.sort_by = this.filters.sort_by
        if (this.filters.sort_dir) params.sort_dir = this.filters.sort_dir
        const { data } = await api.get('/documents', { params })
        this.items = data.data
        this.pagination = data
      } finally {
        this.loading = false
      }
    },
    async createDocument(payload, options = {}) {
      const fd = new FormData()
      fd.append('title', payload.title)
      fd.append('description', payload.description || '')
      if (payload.folder_id) fd.append('folder_id', payload.folder_id)
      fd.append('file', payload.file)
      await api.post('/documents', fd, {
        onUploadProgress: options.onUploadProgress
      })
      await this.fetchDocuments(1)
    },
    async updateDocument(id, payload) {
      const fd = new FormData()
      if (payload.title !== undefined) fd.append('title', payload.title)
      if (payload.description !== undefined) fd.append('description', payload.description)
      if (payload.folder_id !== undefined) fd.append('folder_id', payload.folder_id || '')
      if (payload.file) fd.append('file', payload.file)
      if (payload.is_archived !== undefined) fd.append('is_archived', payload.is_archived ? '1' : '0')
      fd.append('_method', 'PUT')
      await api.post(`/documents/${id}`, fd)
      await this.fetchDocument(id)
      await this.fetchDocuments(this.pagination?.current_page || 1)
    },
    async setStar(id, isStarred) {
      await api.post(`/documents/${id}/star`, { is_starred: isStarred })
      await Promise.all([
        this.fetchDocuments(this.pagination?.current_page || 1),
        this.activeDocument?.id === id ? this.fetchDocument(id) : Promise.resolve()
      ])
    },
    async duplicateDocument(id, payload = {}) {
      await api.post(`/documents/${id}/duplicate`, payload)
      await this.fetchDocuments(this.pagination?.current_page || 1)
    },
    async bulkAction(action, ids, extra = {}) {
      await api.post('/documents/bulk', {
        action,
        document_ids: ids,
        ...extra
      })
      await Promise.all([
        this.fetchDocuments(this.pagination?.current_page || 1),
        this.fetchTrash(this.trashPagination?.current_page || 1)
      ])
    },
    async moveDocumentToFolder(id, folderId = null) {
      await this.bulkAction('move', [id], { folder_id: folderId })
    },
    async deleteDocument(id) {
      await api.delete(`/documents/${id}`)
      await this.fetchDocuments(this.pagination?.current_page || 1)
    },
    async fetchTrash(page = 1, q = null) {
      this.loading = true
      try {
        if (q !== null) {
          this.trashQuery = q
        }
        const { data } = await api.get('/documents/trash', {
          params: {
            page,
            q: this.trashQuery || undefined
          }
        })
        this.trashItems = data.data
        this.trashPagination = data
      } finally {
        this.loading = false
      }
    },
    async restoreDocument(id) {
      await api.post(`/documents/${id}/restore`)
      await Promise.all([
        this.fetchTrash(this.trashPagination?.current_page || 1, this.trashQuery),
        this.fetchDocuments(this.pagination?.current_page || 1)
      ])
    },
    async purgeDocument(id) {
      await api.delete(`/documents/${id}/purge`)
      await Promise.all([
        this.fetchTrash(this.trashPagination?.current_page || 1, this.trashQuery),
        this.fetchDocuments(this.pagination?.current_page || 1)
      ])
    },
    async fetchDocument(id) {
      const { data } = await api.get(`/documents/${id}`)
      this.activeDocument = data
    },
    async fetchDocumentContent(id) {
      const { data } = await api.get(`/documents/${id}/content`)
      this.activeContent = data.content
      this.contentEditable = Boolean(data.editable)
    },
    async updateDocumentContent(id, content) {
      await api.put(`/documents/${id}/content`, { content })
      await Promise.all([
        this.fetchDocument(id),
        this.fetchVersions(id),
        this.fetchAuditLogs(id)
      ])
      this.activeContent = content
    },
    async fetchFolders() {
      const { data } = await api.get('/folders')
      this.folders = data
    },
    async createFolder(name) {
      await api.post('/folders', { name })
      await this.fetchFolders()
    },
    async updateFolder(id, name) {
      await api.put(`/folders/${id}`, { name })
      await this.fetchFolders()
    },
    async duplicateFolder(id) {
      await api.post(`/folders/${id}/duplicate`)
      await this.fetchFolders()
    },
    async bulkFolderAction(action, ids) {
      await api.post('/folders/bulk', {
        action,
        folder_ids: ids
      })
      await Promise.all([
        this.fetchFolders(),
        this.fetchDocuments(this.pagination?.current_page || 1)
      ])
    },
    async deleteFolder(id) {
      await api.delete(`/folders/${id}`)
      await Promise.all([this.fetchFolders(), this.fetchDocuments(1)])
    },
    setFilters(payload) {
      this.filters = {
        ...this.filters,
        ...payload
      }
    },
    async fetchVersions(id) {
      const { data } = await api.get(`/documents/${id}/versions`)
      this.versions = data
    },
    async restoreVersion(documentId, versionId) {
      await api.post(`/documents/${documentId}/versions/${versionId}/restore`)
      await Promise.all([this.fetchDocument(documentId), this.fetchVersions(documentId)])
    },
    async fetchShares(documentId) {
      try {
        const { data } = await api.get(`/documents/${documentId}/shares`)
        this.shares = data
      } catch (_) {
        this.shares = []
      }
    },
    async addShare(documentId, payload) {
      await api.post(`/documents/${documentId}/shares`, payload)
      await this.fetchShares(documentId)
    },
    async updateShare(documentId, userId, permission) {
      await api.put(`/documents/${documentId}/shares/${userId}`, { permission })
      await this.fetchShares(documentId)
    },
    async removeShare(documentId, userId) {
      await api.delete(`/documents/${documentId}/shares/${userId}`)
      await this.fetchShares(documentId)
    },
    async fetchAuditLogs(documentId) {
      const { data } = await api.get(`/documents/${documentId}/audit-logs`)
      this.auditLogs = data
    },
    async fetchComments(documentId) {
      const { data } = await api.get(`/documents/${documentId}/comments`)
      this.comments = data
    },
    async addComment(documentId, content) {
      await api.post(`/documents/${documentId}/comments`, { content })
      await this.fetchComments(documentId)
    },
    async deleteComment(documentId, commentId) {
      await api.delete(`/documents/${documentId}/comments/${commentId}`)
      await this.fetchComments(documentId)
    }
  }
})
