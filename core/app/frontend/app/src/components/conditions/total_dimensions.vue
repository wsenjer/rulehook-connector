<template>
  <div>

    <div class="str_block">
      <label for="dimensions_from" class="str_label">From</label>
      <div class="input-box">
        <span class="prefix">cm</span>
        <input id="dimensions_from" type="number" min="0" step="0.01" v-model="value.dimensions_from" @change="$emit('changed', value)" />
      </div>
    </div>

    <div class="str_block">
      <label for="dimensions_to" class="str_label">To</label>
      <div class="input-box">
        <span class="prefix">cm</span>
        <input id="dimensions_to" type="number" min="0" step="0.01" v-model="value.dimensions_to" @change="$emit('changed', value)" />
      </div>
    </div>

    <Tip v-if="parseInt(value.dimensions_from)  > parseInt(value.dimensions_to)" msg="This rule has no effect as the From dimensions is greater than the To dimensions." color="coral" />

  </div>
</template>

<script>
import Tip from "@/components/ui/Tip";

export default {
  name: "total_dimensions",
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
