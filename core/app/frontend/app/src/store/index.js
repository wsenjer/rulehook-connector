import Vue from 'vue'
import Vuex from 'vuex'
import api from '../services/api'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    product_categories: [],
    product_tags: [],
    product_attributes: [],
    product_attributes_terms: [],
    product_stock_status: [],
    shipping_classes: [],
    payment_methods: [],
    coupons: [],
    user_roles: [],
    selected_rows: [],
    rules: [],
  },
  mutations: {
    update_rules(state, rules) {
      state.rules = rules
    },
    add_to_rules(state, rule) {
      state.rules.push(rule)
    },
    new_rule(state) {
      state.rules.push({
        type: 'cart_total',
        price: '',
        value: {},
        actions: [],
      })
    },
    delete_rule(state, index) {
      state.rules.splice(index, 1)
    },
    update_rule_key(state, payload) {
        state.rules[payload.index][payload.key] = payload.value

    },
    update_user_roles(state, val) {
      state.user_roles = val
    },
    update_product_categories(state, val) {
      state.product_categories = val
    },
    update_product_tags(state, val) {
      state.product_tags = val
    },
    update_product_attributes(state, val) {
      state.product_attributes = val
    },
    update_product_attributes_terms(state, val) {
      state.product_attributes_terms = val
    },
    update_product_stock_status(state, val) {
      state.product_stock_status = val
    },
    update_shipping_classes(state, val) {
      state.shipping_classes = val
    },
    update_payment_methods(state, val) {
      state.payment_methods = val
    },
    update_coupons(state, val) {
      state.coupons = val
    },
    update_selected_rows(state, val) {
      state.selected_rows = val
    },
    reset_selected_rows(state) {
      state.selected_rows = []
    },
    add_to_selected_rows(state, index) {
      state.selected_rows.push(index)
    },
    remove_from_selected_rows(state, index) {
      state.selected_rows.splice(index, 1)
    },
  },
  actions: {
    find_product(ctx, search) {
      return api.post('', {
        search,
        action: "str_find_product",
      }).catch();
    },
    load_app() {
      return api.post('', {
        action: "str_load_app_data",
      }).then((response) => {
        if (response.data.product_categories) {
          this.commit('update_product_categories', response.data.product_categories)
        }
        if (response.data.product_tags) {
          this.commit('update_product_tags', response.data.product_tags)
        }
        if (response.data.product_attributes) {
          this.commit('update_product_attributes', response.data.product_attributes)
        }
        if (response.data.product_attributes_terms) {
          this.commit('update_product_attributes_terms', response.data.product_attributes_terms)
        }
        if (response.data.product_stock_status) {
          this.commit('update_product_stock_status', response.data.product_stock_status)
        }
        if (response.data.shipping_classes) {
          this.commit('update_shipping_classes', response.data.shipping_classes)
        }
        if (response.data.payment_methods) {
          this.commit('update_payment_methods', response.data.payment_methods)
        }
        if (response.data.user_roles) {
          this.commit('update_user_roles', response.data.user_roles)
        }
        if (response.data.coupons) {
          this.commit('update_coupons', response.data.coupons)
        }
      }).catch();
    },
  },
  getters: {
    product_categories: state => {
      return state.product_categories
    },
    product_attributes: state => {
      return state.product_attributes
    },
    product_attributes_terms: state => {
      return state.product_attributes_terms
    },
    product_tags: state => {
      return state.product_tags
    },
    product_stock_status: state => {
      return state.product_stock_status
    },
    shipping_classes: state => {
      return state.shipping_classes
    },
    payment_methods: state => {
      return state.payment_methods
    },
    coupons: state => {
      return state.coupons
    },
    user_roles: state => {
      return state.user_roles
    },
    selected_rows: state => {
      return state.selected_rows
    },
    rules: state => {
      return state.rules
    },
  }
})
