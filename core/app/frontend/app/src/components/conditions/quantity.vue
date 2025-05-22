<template>
  <div>
    <div class="str_block">
      <label for="quantity_from" class="str_label">From</label>
      <div class="input-box">
        <input id="quantity_from" type="number" min="0" v-model="value.quantity_from" @change="$emit('changed', value)" />
      </div>
    </div>

    <div class="str_block">
      <label for="quantity_to" class="str_label">To</label>
      <div class="input-box">
        <input id="quantity_to" type="number" min="0" v-model="value.quantity_to" @change="$emit('changed', value)"/>
      </div>
    </div>
    <Tip v-if="parseInt(value.quantity_from)  > parseInt(value.quantity_to)" msg="This rule has no effect as the From quantity is greater than the To quantity." color="coral" />

  </div>
</template>

<script>
import Tip from "@/components/ui/Tip";

export default {
  name: "quantity",
  components: {
    Tip
  },
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
}
</script>

<style scoped>

</style>
