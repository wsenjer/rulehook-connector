import Vue from 'vue'
import App from './App.vue'
import store from './store'
import vSelect from 'vue-select'
import './assets/style.css'
import 'vue-select/dist/vue-select.css';

Vue.component('v-select', vSelect)

Vue.config.productionTip = false

new Vue({
  store,
  render: function (h) { return h(App) }
}).$mount('#str_rules')
