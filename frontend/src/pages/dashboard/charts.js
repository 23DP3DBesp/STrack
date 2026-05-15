const toNumber = (value) => Number(value || 0)

const fuelLogsWithEffectiveDistance = (items) => {
  let previousMileage = null

  return [...items]
    .sort((a, b) => {
      const dateCompare = String(a.date || '').localeCompare(String(b.date || ''))
      return dateCompare || toNumber(a.id) - toNumber(b.id)
    })
    .map((item) => {
      const mileage = toNumber(item.mileage)
      let distance = toNumber(item.distance_since_previous)

      if (!distance && previousMileage !== null && mileage > previousMileage) {
        distance = mileage - previousMileage
      }

      if (mileage > 0) {
        previousMileage = mileage
      }

      return {
        ...item,
        effectiveDistance: distance
      }
    })
}

export const buildFuelConsumptionChart = (filteredFuelLogs) => {
  const grouped = fuelLogsWithEffectiveDistance(filteredFuelLogs).reduce((acc, item) => {
    const distance = toNumber(item.effectiveDistance)
    const liters = toNumber(item.liters)
    const fallbackConsumption = toNumber(item.fuel_consumption)

    if ((!distance || !liters) && !fallbackConsumption) return acc

    const monthKey = String(item.date).slice(0, 7)

    if (!acc[monthKey]) {
      acc[monthKey] = { liters: 0, distance: 0, fallbackTotal: 0, fallbackCount: 0 }
    }

    if (distance > 0 && liters > 0) {
      acc[monthKey].liters += liters
      acc[monthKey].distance += distance
    } else {
      acc[monthKey].fallbackTotal += fallbackConsumption
      acc[monthKey].fallbackCount += 1
    }

    return acc
  }, {})

  return Object.entries(grouped)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([label, value]) => ({
      label,
      value:
        value.distance > 0
          ? (value.liters / value.distance) * 100
          : value.fallbackTotal / value.fallbackCount
    }))
}

export const buildFuelConsumptionData = (fuelConsumptionChart) => ({
  labels: fuelConsumptionChart.map((item) => item.label),
  datasets: [
    {
      label: 'L/100km',
      data: fuelConsumptionChart.map((item) => item.value),
      borderColor: '#9f1d2d',
      backgroundColor: 'rgba(159, 29, 45, 0.08)',
      tension: 0.35,
      fill: true
    }
  ]
})

export const buildMonthlyExpenseData = (monthlyExpenseChart, t) => ({
  labels: monthlyExpenseChart.map((item) => item.month),
  datasets: [
    {
      type: 'bar',
      label: t('dashboard.fuelLogs'),
      data: monthlyExpenseChart.map((item) => item.fuel),
      backgroundColor: 'rgba(159, 29, 45, 0.75)'
    },
    {
      type: 'bar',
      label: t('dashboard.repairs'),
      data: monthlyExpenseChart.map((item) => item.repairs),
      backgroundColor: 'rgba(17, 24, 39, 0.70)'
    },
    {
      type: 'bar',
      label: t('dashboard.mods'),
      data: monthlyExpenseChart.map((item) => item.mods),
      backgroundColor: 'rgba(107, 114, 128, 0.65)'
    }
  ]
})

export const buildChartOptions = (formatCurrency) => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback(value) {
          if (this.chart?.data?.datasets?.[0]?.label === 'L/100km') {
            return `${value} L/100km`
          }

          return formatCurrency(value)
        }
      }
    }
  }
})

export const buildStackedChartOptions = (formatCurrency) => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: true }
  },
  scales: {
    x: {
      stacked: true
    },
    y: {
      stacked: true,
      beginAtZero: true,
      ticks: {
        callback(value) {
          return formatCurrency(value)
        }
      }
    }
  }
})
