import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const client = axios.create({
  baseURL: apiBaseUrl,
  timeout: 30000
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('car_tracker_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export default client
