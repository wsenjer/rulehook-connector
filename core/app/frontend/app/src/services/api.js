import axios from "axios"
import index from '../store/index'

const api = axios.create({
  baseURL: window.ajaxurl,
  headers: {
    'Accept': 'application/json',
  }
})

api.interceptors.request.use(config => {
  // clear previous errors
  index.commit('add_errors', []);
  // include the WP nonce
  const nonce = window.str_app.nonces[config.data.action]
  config.baseURL += '?_ajax_nonce=' +  nonce + '&action=' + config.data.action
  delete config.data.action;
  return config;
});

api.interceptors.response.use(response => {
  if (!response.data || !response.data.errors){
    // clear previous errors
    index.commit('add_errors', []);
    return response;
  }

  index.commit('add_errors', response.data.errors);
  return response;
});

export default api
