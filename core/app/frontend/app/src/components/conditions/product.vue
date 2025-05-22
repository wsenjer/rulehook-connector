<template>
  <div>
    Products
    <v-select class="advanced-select" v-model="value" @input="$emit('changed', value)" :filterable="false" multiple :options="products" @search="fetchProducts">
      <template v-slot:no-options="{ search, searching }">
        <template v-if="searching">
          No products found for <em>{{ search }}</em
        >.
        </template>
        <em v-else style="opacity: 0.5">Start typing to search for a product.</em>
      </template>
    </v-select>
  </div>
</template>

<script>
import {mapActions} from "vuex";
import { debounce } from "debounce";

export default {
  name: "product",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      products: [],
    }
  },
  created() {
    if (Object.keys(this.value).length === 0) {
      this.value = []
    }

    if (!Array.isArray(this.value)) {
      this.value = []
    }
  },
  mounted() {
    if (Object.keys(this.value).length === 0) {
      this.value = []
    }

    if (!Array.isArray(this.value)) {
      this.value = []
    }
  },
  methods: {
    ...mapActions(['find_product']),
    fetchProducts(search, loading) {
      if(search.length) {
        loading(true);
        this.search(loading, search, this);
      }
    },
    search: debounce ((loading, search, vm) => {
      vm.find_product(search).then((response) => {
          vm.products = response.data.products
          loading(false)
        })
    })
  }
}
</script>

<style>
.advanced-select .vs__search {
  border: none !important;
}
.advanced-select .vs__dropdown-toggle {
  border: 1px solid #8c8f94;
}
.advanced-select .vs__search {
  border-color: #ffffff !important;

}

.advanced-select .vs__search:focus {
  border-color: #ffffff !important;
  box-shadow: none !important;
}
</style>
