<template>
  <div>
    <div class="str_block">
      <label for="volume_from" class="str_label">From</label>
      <div class="input-box">
        <span class="prefix">cm<sup>3</sup></span>
        <input id="volume_from" type="number" min="0" step="0.01" v-model="value.volume_from" @change="$emit('changed', value)" />
      </div>
    </div>

    <div class="str_block">
      <label for="volume_to" class="str_label">To</label>
      <div class="input-box">
        <span class="prefix">cm<sup>3</sup></span>
        <input id="volume_to" type="number" min="0" step="0.01" v-model="value.volume_to" @change="$emit('changed', value)" />
      </div>
    </div>
    <Tip v-if="parseInt(value.volume_from)  > parseInt(value.volume_to)" msg="This rule has no effect as the From volume is greater than the To volume." color="coral" />

  </div>
</template>

<script>
import Tip from "@/components/ui/Tip";

export default {
  name: "volume",
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
