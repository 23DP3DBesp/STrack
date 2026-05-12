import { describe, expect, it } from 'vitest'
import { parseLocalDate, periodStartDate } from './garage'

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
