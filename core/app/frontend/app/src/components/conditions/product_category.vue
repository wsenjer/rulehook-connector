<template>
  <div>
    <label for="str_cat" class="str_label">Category</label>
    <select id="str_cat" name="day" v-model="value.category" @change="$emit('changed', value)">
      <option v-for="category in product_categories" :value="category.id">{{ category.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "product_category",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['product_categories'])
  },
  created() {
    if (!this.value.category && this.product_categories.length > 0) {
      this.value.category = this.product_categories[0].id
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
