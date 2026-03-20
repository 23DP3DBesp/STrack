import { defineStore } from 'pinia'
import api from '../api/client'
import { useNotificationsStore } from './notifications'

export const useRealtimeStore = defineStore('realtime', {
  state: () => ({
    events: [],
    latestAuditId: 0,
    latestNotificationId: 0,
    polling: false,
    timer: null,
    socket: null
  }),
  actions: {
    async pull() {
      const notifications = useNotificationsStore()
      const { data } = await api.get('/realtime/feed', {
        params: {
          since_audit_id: this.latestAuditId,
          since_notification_id: this.latestNotificationId
        }
      })

      if (Array.isArray(data.events) && data.events.length) {
        this.events = [...data.events.reverse(), ...this.events].slice(0, 80)
      }
      this.latestAuditId = data.latest_audit_id || this.latestAuditId
      this.latestNotificationId = data.latest_notification_id || this.latestNotificationId
      notifications.mergeIncoming(data.notifications || [], data.unread_notifications)
    },
    start() {
      if (this.timer || this.socket) return
      this.polling = true
      this.pull().catch(() => {})
      const wsUrl = import.meta.env.VITE_REALTIME_WS_URL
      if (wsUrl) {
        try {
          this.socket = new WebSocket(wsUrl)
          this.socket.onmessage = (event) => {
            const payload = JSON.parse(event.data || '{}')
            const notifications = useNotificationsStore()
            if (Array.isArray(payload.events) && payload.events.length) {
              this.events = [...payload.events.reverse(), ...this.events].slice(0, 80)
            }
            if (Array.isArray(payload.notifications) && payload.notifications.length) {
              notifications.mergeIncoming(payload.notifications, payload.unread_notifications)
            }
          }
          this.socket.onerror = () => {
            this.socket?.close()
            this.socket = null
          }
          this.socket.onclose = () => {
            this.socket = null
          }
        } catch (_) {
          this.socket = null
        }
      }
      this.timer = setInterval(() => {
        this.pull().catch(() => {})
      }, 8000)
    },
    stop() {
      this.polling = false
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
      if (this.socket) {
        this.socket.close()
        this.socket = null
      }
    }
  }
})
