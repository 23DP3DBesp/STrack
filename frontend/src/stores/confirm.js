import { defineStore } from 'pinia'

export const useConfirmStore = defineStore('confirm', {
  state: () => ({
    open: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    _resolve: null
  }),
  actions: {
    ask(options = {}) {
      this.title = options.title || 'Please confirm'
      this.message = options.message || ''
      this.confirmText = options.confirmText || 'Confirm'
      this.cancelText = options.cancelText || 'Cancel'
      this.open = true

      return new Promise((resolve) => {
        this._resolve = resolve
      })
    },
    confirm() {
      this.open = false
      if (this._resolve) this._resolve(true)
      this._resolve = null
    },
    cancel() {
      this.open = false
      if (this._resolve) this._resolve(false)
      this._resolve = null
    }
  }
})
