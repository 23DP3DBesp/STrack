import { describe, expect, it } from 'vitest'
import { buildFuelConsumptionChart } from './charts'

describe('dashboard chart helpers', () => {
  it('builds monthly fuel consumption using weighted distance', () => {
    const chart = buildFuelConsumptionChart([
      {
        date: '2026-05-01',
        liters: '20.00',
        distance_since_previous: 200,
        fuel_consumption: '10.00'
      },
      {
        date: '2026-05-14',
        liters: '20.00',
        distance_since_previous: 600,
        fuel_consumption: '3.33'
      }
    ])

    expect(chart).toEqual([{ label: '2026-05', value: 5 }])
  })

  it('derives distance from mileage when stored distance is missing', () => {
    const chart = buildFuelConsumptionChart([
      {
        id: 1,
        date: '2026-05-01',
        mileage: 1000,
        liters: '10.00',
        distance_since_previous: null,
        fuel_consumption: null
      },
      {
        id: 2,
        date: '2026-05-08',
        mileage: 1500,
        liters: '30.00',
        distance_since_previous: null,
        fuel_consumption: null
      }
    ])

    expect(chart).toEqual([{ label: '2026-05', value: 6 }])
  })
})
