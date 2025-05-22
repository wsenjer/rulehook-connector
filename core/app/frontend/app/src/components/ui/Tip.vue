<template>
  <span :data-tip="msg" class="woocommerce-help-tip" :style="`color:${color};`"></span>
</template>

<script>
export default {
  name: "Tip",
  props: {
    msg: {
      required: true,
      type: String
    },
    color: {
      required: false,
      type: String,
      default: '#666'
    }
  }
}
</script>

<style scoped>
  .woocommerce-help-tip {
    margin:0 !important;
  }
</style>
