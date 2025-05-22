<template>
  <tr :class="{alternate : index % 2 === 0}">
    <td><input type="checkbox" :value="index" v-model="selected_rows" @click="selectRow" /></td>
    <td>
      <label for="conditions" class="str_label">Type</label>
      <select id="conditions" name="conditions" v-model="rule.type" @change="updateRuleValue($event, index)" style="width:100%;">
        <option v-for="condition in conditions" :value="condition.value">{{ condition.label }}</option>
      </select>
    </td>
    <td>
      <keep-alive>
        <component  v-bind:is="rule.type"  @changed="updateConditionValue" :value="rule.value" />
      </keep-alive>
    </td>
    <td>
      <label for="price" class="str_label">Cost</label>
      <div class="input-box">
        <span class="prefix">{{currency}}</span>
        <input id="price"  min="0" step="0.01" type="number" v-model="rule.price"/>
      </div>
    </td>
    <td>
      <Actions :rule="rule" />
    </td>
    <td>
     <div style="margin-top: 12px;">
       <button style="margin-right: 5px;" type="button" class="button-secondary" @click="$emit('ruleDeleted', index)" title="Delete">
         <span class="dashicons dashicons-trash"></span>
       </button>
       <button style="margin-right: 5px;" type="button" class="button-secondary" @click="$emit('ruleDuplicated', index)" title="Duplicate">
         <span class="dashicons dashicons-admin-page"></span>
       </button>
       <button style="cursor: move;" type="button" class="button-secondary handle"  title="Sort">
         <span class="dashicons dashicons-move"></span>
       </button>
     </div>
    </td>
  </tr>
</template>

<script>
import weight from "@/components/conditions/weight";
import cart_total from "@/components/conditions/cart_total";
import day_of_week from "@/components/conditions/day_of_week";
import product from "@/components/conditions/product";
import product_category from "@/components/conditions/product_category";
import product_tag from "@/components/conditions/product_tag";
import shipping_class from "@/components/conditions/shipping_class";
import time_of_day from "@/components/conditions/time_of_day";
import total_dimensions from "@/components/conditions/total_dimensions";
import quantity from "@/components/conditions/quantity";
import user_role from "@/components/conditions/user_role";
import volume from "@/components/conditions/volume";
import {mapGetters, mapMutations} from "vuex";
import coupon from "@/components/conditions/coupon";
import Actions from "@/components/actions/Actions";
import payment_method from "@/components/conditions/payment_method";
import product_stock_status from "@/components/conditions/product_stock_status";
import product_attribute from "@/components/conditions/product_attribute";

export default {
  name: "Row",
  components: {
    Actions,
    cart_total,
    day_of_week,
    product,
    product_category,
    product_tag,
    shipping_class,
    time_of_day,
    total_dimensions,
    quantity,
    user_role,
    volume,
    weight,
    coupon,
    payment_method,
    product_stock_status,
    product_attribute,
  },
  props: {
    index: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      selectedCondition: null,
      currency: str_app.store_currency_symbol,
      conditions: [
        {value: 'weight', label: 'Weight'},
        {value: 'cart_total', label: 'Cart Total'},
        {value: 'product', label: 'Product'},
        {value: 'quantity', label: 'Quantity'},
        {value: 'volume', label: 'Volume'},
        {value: 'total_dimensions', label: 'Total Dimensions'},
        {value: 'product_stock_status', label: 'Product Stock Status'},
        {value: 'product_category', label: 'Product Category'},
        {value: 'product_tag', label: 'Product Tag'},
        {value: 'product_attribute', label: 'Product Attribute'},
        {value: 'shipping_class', label: 'Shipping Class'},
        {value: 'coupon', label: 'Coupon'},
        {value: 'user_role', label: 'User Role'},
        {value: 'time_of_day', label: 'Time'},
        {value: 'day_of_week', label: 'Day'},
        {value: 'payment_method', label: 'Payment Method'},
      ]
    }
  },
  computed: {
    ...mapGetters(['selected_rows', 'rules']),
    rule() {
      return this.rules[this.index]
    }
  },
  methods: {
    ...mapMutations(['add_to_selected_rows', 'remove_from_selected_rows', 'update_rule_key']),
    updateRuleValue(e, index) {
      if (e.target.value === 'product') {
        this.update_rule_key({
          index,
          value: [],
          key: 'value',
        })
      }
    },
    selectRow(e) {
      if(e.target.checked) {
        this.add_to_selected_rows(this.index)
      } else {
        this.remove_from_selected_rows(this.index)
      }
    },
    updateConditionValue (val) {
      this.update_rule_key({
        index: this.index,
        value: val,
        key: 'value',
      })
    }
  }
}
</script>

<style scoped>
.button-secondary {
  line-height: normal !important;
}
</style>
