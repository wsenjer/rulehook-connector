<template>
  <div>
    <div class="str_block">
      <label for="price_from" class="str_label">From</label>
      <div class="input-box">
        <span class="prefix">{{ currency_symbol }}</span>
        <input id="price_from" type="number" min="0" step="0.01" v-model="value.price_from" @change="$emit('changed', value)"/>
      </div>
    </div>

    <div class="str_block">
      <label for="price_to" class="str_label">To</label>
      <div class="input-box">
        <span class="prefix">{{ currency_symbol }}</span>
        <input id="price_to" type="number" min="0" step="0.01" v-model="value.price_to" @change="$emit('changed', value)" />
      </div>
    </div>

    <Tip v-if="parseInt(value.price_from)  > parseInt(value.price_to)" msg="This rule has no effect as the From price is greater than the To price." color="coral" />

  </div>
</template>

<script>
import Tip from "@/components/ui/Tip";
export default {
  name: "cart_total",
  components: {Tip},
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      currency_symbol: str_app.store_currency_symbol
    }
  }
}
</script>

<style scoped>

</style>
