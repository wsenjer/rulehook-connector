<template>
  <div>
    <label for="selectedTag" class="str_label">Tag</label>
    <select id="selectedTag" name="tag" v-model="value.tag" @change="$emit('changed', value)">
      <option v-for="tag in product_tags" :value="tag.id">{{ tag.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "product_tag",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['product_tags'])
  },
  created() {
    if (!this.value.tag && this.product_tags.length > 0) {
      this.value.tag = this.product_tags[0].id
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
