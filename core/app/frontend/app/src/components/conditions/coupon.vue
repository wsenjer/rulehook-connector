<template>
  <div>
    <label for="str_coupon" class="str_label">Coupon</label>
    <select id="str_coupon" name="day" v-model="value.coupon" @change="$emit('changed', value)">
      <option v-for="coupon in coupons" :value="coupon.name">{{ coupon.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "coupon",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['coupons'])
  },
  created() {
    if (!this.value.coupon && this.coupons.length > 0) {
      this.value.coupon = this.coupons[0].name
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
