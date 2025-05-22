<template>
  <div>
    <label for="str_day" class="str_label">Day</label>
    <select id="str_day" name="str_day" v-model="value.day" @change="$emit('changed', value)">
      <option v-for="day in days" :value="day.value">{{ day.label }}</option>
    </select>
  </div>
</template>

<script>
export default {
  name: "day_of_week",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      selectedDay: 1,
      days: [
        {value: 1, label: 'Monday'},
        {value: 2, label: 'Tuesday'},
        {value: 3, label: 'Wednesday'},
        {value: 4, label: 'Thursday'},
        {value: 5, label: 'Friday'},
        {value: 6, label: 'Saturday'},
        {value: 7, label: 'Sunday'},
      ]
    }
  },
  created() {
    if (!this.value.day) {
      this.value.day = 1
    }
  }
}
</script>

<style scoped>
select {
  width: 100% !important;
}
</style>
