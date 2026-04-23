export const buildFuelConsumptionChart = (filteredFuelLogs) => {
  const grouped = filteredFuelLogs.reduce((acc, item) => {
    if (!item.fuel_consumption) return acc

    const monthKey = String(item.date).slice(0, 7)

    if (!acc[monthKey]) {
      acc[monthKey] = { total: 0, count: 0 }
    }

    acc[monthKey].total += Number(item.fuel_consumption)
    acc[monthKey].count += 1

    return acc
  }, {})

  return Object.entries(grouped)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([label, value]) => ({
      label,
      value: value.total / value.count
    }))
}

export const buildFuelConsumptionData = (fuelConsumptionChart) => ({
  labels: fuelConsumptionChart.map((item) => item.label),
  datasets: [
    {
      label: 'L/100km',
      data: fuelConsumptionChart.map((item) => item.value),
      borderColor: '#c81e1e',
      backgroundColor: 'rgba(227, 0, 0, 0.08)',
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
      backgroundColor: 'rgba(227, 0, 0, 0.75)'
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
