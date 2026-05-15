import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { parseLocalDate, periodStartDate, useGarageStore } from './garage'

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

describe('garage period helpers', () => {
  it('starts a 3 month period on the same calendar day', () => {
    const start = periodStartDate('3m', new Date(2026, 4, 12))

    expect(formatDate(start)).toBe('2026-02-12')
  })

  it('parses ISO dates as local calendar dates', () => {
    const date = parseLocalDate('2026-02-12')

    expect(formatDate(date)).toBe('2026-02-12')
  })

  it('clamps the day when the target month is shorter', () => {
    const start = periodStartDate('1m', new Date(2026, 2, 31))

    expect(formatDate(start)).toBe('2026-02-28')
  })
})

describe('garage period filtered metrics', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date(2026, 4, 12))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('updates overview metrics when the selected period changes', () => {
    setActivePinia(createPinia())

    const garage = useGarageStore()

    garage.summary.stats.cars_total = 1
    garage.fuelLogs = [
      { id: 1, date: '2026-05-01', total_price: '100.00', mileage: 2000 },
      { id: 2, date: '2026-02-20', total_price: '50.00', mileage: 1500 },
      { id: 3, date: '2026-01-10', total_price: '25.00', mileage: 1000 }
    ]
    garage.repairs = [
      { id: 1, date: '2026-04-10', cost: '200.00' },
      { id: 2, date: '2026-01-05', cost: '300.00' }
    ]
    garage.mods = [
      { id: 1, date_installed: '2026-03-15', cost: '400.00' },
      { id: 2, date_installed: '2025-12-15', cost: '500.00' }
    ]

    garage.selectedPeriod = 'all'

    expect(garage.filteredStats).toMatchObject({
      fuel_logs_total: 3,
      repairs_total: 2,
      mods_total: 2,
      total_spent: 1575
    })
    expect(garage.totalDistanceTracked).toBe(1000)
    expect(garage.costPerKm).toBe(1.575)

    garage.selectedPeriod = '3m'

    expect(garage.filteredStats).toMatchObject({
      fuel_logs_total: 2,
      repairs_total: 1,
      mods_total: 1,
      total_spent: 750
    })
    expect(garage.totalDistanceTracked).toBe(500)
    expect(garage.costPerKm).toBe(1.5)
  })

  it('excludes future records from selected period metrics', () => {
    setActivePinia(createPinia())

    const garage = useGarageStore()

    garage.fuelLogs = [
      { id: 1, date: '2026-05-01', total_price: '100.00', mileage: 2000 },
      { id: 2, date: '2026-08-05', total_price: '999.00', mileage: 5000 }
    ]

    garage.selectedPeriod = '3m'

    expect(garage.filteredStats).toMatchObject({
      fuel_logs_total: 1,
      total_spent: 100
    })
  })

  it('uses recorded distance for cost per km and weighted fuel consumption', () => {
    setActivePinia(createPinia())

    const garage = useGarageStore()

    garage.fuelLogs = [
      {
        id: 1,
        date: '2026-05-01',
        liters: '10.00',
        total_price: '20.00',
        mileage: 1000,
        distance_since_previous: null,
        fuel_consumption: null
      },
      {
        id: 2,
        date: '2026-05-08',
        liters: '20.00',
        total_price: '40.00',
        mileage: 1200,
        distance_since_previous: 200,
        fuel_consumption: '10.00'
      },
      {
        id: 3,
        date: '2026-05-15',
        liters: '20.00',
        total_price: '40.00',
        mileage: 1800,
        distance_since_previous: 600,
        fuel_consumption: '3.33'
      }
    ]

    garage.selectedPeriod = 'all'

    expect(garage.totalDistanceTracked).toBe(800)
    expect(garage.costPerKm).toBe(0.125)
    expect(garage.averageFuelConsumption).toBe(5)
  })
})
