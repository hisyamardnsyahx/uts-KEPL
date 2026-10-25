import axios from 'axios'

// Di Docker, base URL relatif '/api' sehingga browser hanya memanggil
// origin frontend sendiri; Nginx frontend yang meneruskan /api ke service
// backend (nama service Docker, bukan IP laptop) di jaringan compose.
// Untuk dev lokal, isi VITE_API_BASE_URL=http://localhost:8000/api bila perlu.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('lab_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export default api
