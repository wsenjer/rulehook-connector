<template>
  <div>
    <label for="str_stock_status" class="str_label">Stock Status</label>
    <select id="str_stock_status" name="stock_status" v-model="value.stock_status" @change="$emit('changed', value)">
      <option v-for="status in product_stock_status" :value="status.key">{{ status.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "product_stock_status",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['product_stock_status'])
  },
  created() {
    if (!this.value.stock_status && this.product_stock_status.length > 0) {
      this.value.stock_status = this.product_stock_status[0].key
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
