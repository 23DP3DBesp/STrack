/**
 * Helper for insurance and inspection expiry management
 */

export const getExpiryStatus = (expiryDate) => {
  if (!expiryDate) return 'missing'

  const now = new Date()
  const expiry = new Date(expiryDate)
  const daysUntil = Math.ceil((expiry.getTime() - now.getTime()) / 86400000)

  if (daysUntil < 0) return 'expired'
  if (daysUntil <= 7) return 'critical'
  if (daysUntil <= 30) return 'warning'
  return 'valid'
}

export const getExpiryMessage = (expiryDate, label = 'Item') => {
  if (!expiryDate) return `${label} date not set`

  const now = new Date()
  const expiry = new Date(expiryDate)
  const daysUntil = Math.ceil((expiry.getTime() - now.getTime()) / 86400000)
  const year = expiry.getFullYear()
  const month = String(expiry.getMonth() + 1).padStart(2, '0')
  const day = String(expiry.getDate()).padStart(2, '0')
  const dateStr = `${day}.${month}.${year}`

  if (daysUntil < 0) return `${label} expired ${Math.abs(daysUntil)} days ago`
  if (daysUntil === 0) return `${label} expires today!`
  if (daysUntil === 1) return `${label} expires tomorrow`
  if (daysUntil <= 30) return `${label} expires in ${daysUntil} days (${dateStr})`
  return `${label} valid until ${dateStr}`
}

export const getDefaultExpiryDate = (daysFromNow = 365) => {
  const date = new Date()
  date.setDate(date.getDate() + daysFromNow)
  return date.toISOString().split('T')[0]
}

export const formatDateForDisplay = (dateString) => {
  if (!dateString) return '—'
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${day}.${month}.${year}`
}
