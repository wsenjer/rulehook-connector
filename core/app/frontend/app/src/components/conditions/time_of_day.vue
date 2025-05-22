<template>
  <div>
    <div class="str_block">
      <label for="time_from" class="str_label">From</label>
      <input id="time_from" type="time" v-model="value.time_from" @change="$emit('changed', value)"/>
    </div>

    <div class="str_block">
      <label for="time_to" class="str_label">To</label>
      <input id="time_to" type="time" v-model="value.time_to" @change="$emit('changed', value)" />
    </div>

  </div>
</template>

<script>
export default {
  name: "time_of_day",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
}
</script>

<style scoped>
  input {
    width: 88px !important;
  }
</style>
