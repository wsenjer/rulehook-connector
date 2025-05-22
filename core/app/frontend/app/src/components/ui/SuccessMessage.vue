<template>
  <div class="success-msg">
    <span @click="$emit('closed')" class="dashicons dashicons-no-alt" style="float:right; cursor: pointer;"></span>
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: "SuccessMessage"
}
</script>

<style scoped>
.success-msg {
  margin: 10px 0;
  padding: 10px;
  border-radius: 4px 4px 4px 4px;
}

.success-msg {
  color: #270;
  background-color: #DFF2BF;
  border:#8bce85 1px solid;
  --border-opacity: 1;
}
</style>
