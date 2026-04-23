export const buildSmartReminders = ({ selectedCar, garage, formatDate }) => {
  if (!selectedCar) return []

  const reminders = []
  const latestRepairItem = garage.latestRepair
  const insuranceUntil = selectedCar.insurance_until ? new Date(selectedCar.insurance_until) : null
  const inspectionUntil = selectedCar.inspection_until ? new Date(selectedCar.inspection_until) : null
  const now = new Date()

  if (garage.fuelLogs.length < 2) {
    reminders.push({
      title: 'Add one more fuel log',
      message: 'Two or more fueling records unlock much better consumption trends.'
    })
  }

  if (!latestRepairItem) {
    reminders.push({
      title: 'No repairs logged yet',
      message: 'Even routine service entries improve your maintenance history.'
    })
  } else {
    const daysSinceRepair = Math.floor((Date.now() - new Date(latestRepairItem.date).getTime()) / 86400000)

    if (daysSinceRepair > 180) {
      reminders.push({
        title: 'Maintenance history is getting stale',
        message: `${daysSinceRepair} days passed since the last recorded repair or service entry.`
      })
    }
  }

  if (!garage.mods.length) {
    reminders.push({
      title: 'No mods tracked',
      message: 'If the car is stock, track that too for a complete car history.'
    })
  }

  if (!insuranceUntil) {
    reminders.push({
      title: 'Insurance date missing',
      message: 'Add insurance expiry to get automatic reminders before renewal.'
    })
  } else {
    const insuranceDays = Math.ceil((insuranceUntil.getTime() - now.getTime()) / 86400000)

    if (insuranceDays < 0) {
      reminders.push({
        title: 'Insurance expired',
        message: `Insurance expired ${Math.abs(insuranceDays)} days ago.`
      })
    } else if (insuranceDays <= 30) {
      reminders.push({
        title: 'Insurance renewal soon',
        message: `Insurance expires in ${insuranceDays} days on ${formatDate(selectedCar.insurance_until)}.`
      })
    }
  }

  if (!inspectionUntil) {
    reminders.push({
      title: 'Inspection date missing',
      message: 'Add technical inspection expiry to automate your legal compliance reminders.'
    })
  } else {
    const inspectionDays = Math.ceil((inspectionUntil.getTime() - now.getTime()) / 86400000)

    if (inspectionDays < 0) {
      reminders.push({
        title: 'Technical inspection expired',
        message: `Inspection expired ${Math.abs(inspectionDays)} days ago.`
      })
    } else if (inspectionDays <= 30) {
      reminders.push({
        title: 'Technical inspection soon',
        message: `Inspection expires in ${inspectionDays} days on ${formatDate(selectedCar.inspection_until)}.`
      })
    }
  }

  return reminders.slice(0, 6)
}
