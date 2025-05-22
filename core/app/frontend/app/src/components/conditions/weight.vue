<template>
  <div>
    <div class="str_block">
      <label for="weight_from" class="str_label">From</label>
      <div class="input-box">
        <span class="prefix">kg</span>
        <input id="weight_from" min="0" v-model="value.weight_from" step="0.01" @change="$emit('changed', value)" type="number"/>
      </div>
    </div>

   <div class="str_block">
     <label for="weight_from" class="str_label">To</label>
     <div class="input-box">
       <span class="prefix">kg</span>
       <input id="weight_to" min="0" v-model="value.weight_to" step="0.01" @change="$emit('changed', value)" type="number"/>
     </div>
   </div>
    <Tip v-if="parseInt(value.weight_from)  > parseInt(value.weight_to)" msg="This rule has no effect as the From weight is greater than the To weight." color="coral" />

  </div>
</template>

<script>
import Tip from "@/components/ui/Tip";

export default {
  name: "weight",
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
