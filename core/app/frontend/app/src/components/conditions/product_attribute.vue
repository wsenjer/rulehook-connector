<template>
  <div>
    <label for="str_attribute" class="str_label">Attribute</label>
    <select style="width: 48%" id="str_attribute" name="attribute" v-model="value.attribute" @change="attributeChanged">
      <option v-for="attribute in product_attributes" :value="attribute.name">{{ attribute.name }}</option>
    </select>
    <select style="width: 48%" id="str_term" name="term" v-model="value.term" @change="$emit('changed', value)">
      <option v-for="term in terms" :value="term.name">{{ term.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "product_attribute",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  data () {
    return {
      terms: []
    }
  },
  computed: {
    ...mapGetters(['product_attributes', 'product_attributes_terms'])
  },
  methods: {
    attributeChanged($event, val) {
      this.terms = this.product_attributes_terms[this.value.attribute]
      this.value.term = this.product_attributes_terms[this.value.attribute][0].name
      this.$emit('changed', this.value)
    }
  },
  created() {
    if (!this.value.attribute && this.product_attributes.length > 0) {
      this.value.attribute = this.product_attributes[0].name
      this.terms = this.product_attributes_terms[this.product_attributes[0].name]
    }
    if (!this.value.term && this.product_attributes_terms[this.value.attribute].length > 0) {
      this.value.term = this.product_attributes_terms[this.value.attribute][0].name
    }
    if (this.value.attribute) {
      this.terms = this.product_attributes_terms[this.value.attribute]
    }
  }
}
</script>

<style scoped>
select {
  margin-right: 2px !important;
}
</style>
