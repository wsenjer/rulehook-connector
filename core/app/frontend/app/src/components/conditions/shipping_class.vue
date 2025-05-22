<template>
  <div>
    <label for="str_shipping_class" class="str_label">Shipping Class</label>
    <select id="str_shipping_class" name="day" v-model="value.shipping_class" @change="$emit('changed', value)">
      <option v-for="shipping_class in shipping_classes" :value="shipping_class.id">{{ shipping_class.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "shipping_class",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['shipping_classes'])
  },
  created() {
    if (!this.value.shipping_class && this.shipping_classes.length > 0) {
      this.value.shipping_class = this.shipping_classes[0].id
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
