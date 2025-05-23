import axios from 'axios'

const api = axios.create({
  baseURL: window.ajaxurl,
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  // clear previous errors
  // include the WP nonce
  const nonce = window.rulehook.nonces[config.data.action]
  config.baseURL += '?_ajax_nonce=' + nonce + '&action=' + config.data.action
  delete config.data.action
  return config
})

export default api
