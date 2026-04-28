export const createDashboardRules = (t) => ({
  required: (v) => !!v || t('dashboard.required'),
  requiredDate: (v) => !!v || t('dashboard.required'),
  gt(value) {
    return (v) => (!!v && Number(v) > value) || `Must be greater than ${value}`
  },
  gte(value) {
    return (v) =>
      (v !== '' && v !== null && v !== undefined && Number(v) >= value) ||
      `Must be at least ${value}`
  },
  integerMin(value) {
    return (v) =>
      (!!v && Number.isInteger(Number(v)) && Number(v) >= value) || `Must be integer >= ${value}`
  },
  year: (v) => (!!v && Number(v) >= 1900 && Number(v) <= 2100) || 'Year between 1900-2100'
})

export const emptyCarForm = () => ({
  id: null,
  brand: '',
  model: '',
  year: '',
  engine_volume: '',
  license_plate: '',
  insurance_until: '',
  inspection_until: ''
})

export const emptyFuelForm = () => ({
  id: null,
  date: '',
  liters: '',
  total_price: '',
  mileage: ''
})

export const emptyRepairForm = () => ({
  id: null,
  type: '',
  cost: '',
  date: '',
  mileage: '',
  description: ''
})

export const emptyModForm = () => ({
  id: null,
  name: '',
  cost: '',
  date_installed: '',
  performance_impact: ''
})
