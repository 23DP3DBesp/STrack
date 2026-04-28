const daysUntil = (date) => Math.ceil((date.getTime() - Date.now()) / 86400000)

const pushReminder = (reminders, title, message) => {
  reminders.push({ title, message })
}

const addExpiryReminder = ({
  reminders,
  date,
  missingTitle,
  missingMessage,
  expiredTitle,
  expiredMessage,
  soonTitle,
  soonMessage
}) => {
  if (!date) {
    pushReminder(reminders, missingTitle, missingMessage)
    return
  }

  const remainingDays = daysUntil(date)

  if (remainingDays < 0) {
    pushReminder(reminders, expiredTitle, expiredMessage(Math.abs(remainingDays)))
    return
  }

  if (remainingDays <= 30) {
    pushReminder(reminders, soonTitle, soonMessage(remainingDays))
  }
}

export const buildSmartReminders = ({ selectedCar, garage, formatDate }) => {
  if (!selectedCar) return []

  const reminders = []
  const latestRepairItem = garage.latestRepair
  const insuranceUntil = selectedCar.insurance_until ? new Date(selectedCar.insurance_until) : null
  const inspectionUntil = selectedCar.inspection_until
    ? new Date(selectedCar.inspection_until)
    : null

  if (garage.fuelLogs.length < 2) {
    pushReminder(
      reminders,
      'Add one more fuel log',
      'Two or more fueling records unlock much better consumption trends.'
    )
  }

  if (!latestRepairItem) {
    pushReminder(
      reminders,
      'No repairs logged yet',
      'Even routine service entries improve your maintenance history.'
    )
  } else {
    const daysSinceRepair = Math.floor((Date.now() - new Date(latestRepairItem.date).getTime()) / 86400000)

    if (daysSinceRepair > 180) {
      pushReminder(
        reminders,
        'Maintenance history is getting stale',
        `${daysSinceRepair} days passed since the last recorded repair or service entry.`
      )
    }
  }

  if (!garage.mods.length) {
    pushReminder(
      reminders,
      'No mods tracked',
      'If the car is stock, track that too for a complete car history.'
    )
  }

  addExpiryReminder({
    reminders,
    date: insuranceUntil,
    missingTitle: 'Insurance date missing',
    missingMessage: 'Add insurance expiry to get automatic reminders before renewal.',
    expiredTitle: 'Insurance expired',
    expiredMessage: (days) => `Insurance expired ${days} days ago.`,
    soonTitle: 'Insurance renewal soon',
    soonMessage: (days) => `Insurance expires in ${days} days on ${formatDate(selectedCar.insurance_until)}.`
  })

  addExpiryReminder({
    reminders,
    date: inspectionUntil,
    missingTitle: 'Inspection date missing',
    missingMessage: 'Add technical inspection expiry to automate your legal compliance reminders.',
    expiredTitle: 'Technical inspection expired',
    expiredMessage: (days) => `Inspection expired ${days} days ago.`,
    soonTitle: 'Technical inspection soon',
    soonMessage: (days) =>
      `Inspection expires in ${days} days on ${formatDate(selectedCar.inspection_until)}.`
  })

  return reminders.slice(0, 6)
}
