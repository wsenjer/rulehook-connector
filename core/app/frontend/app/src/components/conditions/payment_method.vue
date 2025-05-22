<template>
  <div>
    <label for="str_payment_method" class="str_label">Payment Method</label>
    <select id="str_payment_method" name="day" v-model="value.payment_method" @change="$emit('changed', value)">
      <option v-for="payment_method in payment_methods" :value="payment_method.id">{{ payment_method.title }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "payment_method",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['payment_methods'])
  },
  created() {
    if (!this.value.payment_method && Object.keys(this.payment_methods).length > 0) {
      this.value.payment_method = Object.values(this.payment_methods)[0].id
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
