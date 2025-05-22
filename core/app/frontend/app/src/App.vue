<template>
  <div>
    <table id="str_rules_table" class="striped">
      <thead>
      <tr>
        <th class="str_cb"><input @click="selectAllRules"
                                  :checked="rules.length !==0 && selected_rows.length === rules.length"
                                  type="checkbox"/></th>
        <th class="str_condition">Condition</th>
        <th class="str_range"></th>
        <th class="str_price">Shipping</th>
        <th class="str_actions">Actions</th>
        <th class="str_buttons"></th>
      </tr>
      </thead>
      <tr v-if="app_loading" class="alternate">
        <td colspan="6">
          <div style="text-align: center">
            <span class="spinner is-active" style="float:revert;"></span>
          </div>
        </td>
      </tr>
      <draggable v-if="rules.length > 0 && !app_loading" v-model="rules" tag="tbody" handle=".handle">
        <Row v-for="(rule, i) in rules" :index="i" @ruleDeleted="deleteRule" @ruleDuplicated="duplicateRule"/>
      </draggable>
      <tr v-if="rules.length === 0">
        <td colspan="6">
          <div style="text-align: center">No rules has been created yet. <a href="javascript:void(0)" @click="newRule">Add
            one now</a>.
          </div>
        </td>
      </tr>
      <tfoot>
      <tr>
        <th colspan="3">
          <span style="margin: 4px 5px 0 0; display: inline-block;">Bulk Actions:</span>
          <button class="button-secondary" :disabled="selected_rows.length === 0" type="button" @click="bulkDuplicate">
            Duplicate
          </button>
          <button class="button-secondary" :disabled="selected_rows.length === 0" type="button" @click="bulkDelete">
            Delete
          </button>
          <button class="button-secondary" :disabled="selected_rows.length === 0" type="button" @click="bulkExport">
            Export
          </button>
        </th>
        <th colspan="3">
          <button id="btn-add-new-rule" class="button-primary" type="button" @click="newRule">Add New Rule</button>
          <ButtonImport />
        </th>
      </tr>
      </tfoot>
    </table>
    <br>

    <!-- rule inputs -->

    <div v-for="(rule, i) in rules">
      <input :name="`str_rule[${i}][index]`" :value="i" type="hidden"/>
      <input :name="`str_rule[${i}][type]`" :value="rule.type" type="hidden"/>
      <input :name="`str_rule[${i}][price]`" :value="rule.price" type="hidden"/>
      <input :name="`str_rule[${i}][actions]`" :value="JSON.stringify(rule.actions)" type="hidden"/>
      <input v-for="(key, j) in Object.keys(rule.value)" :name="`str_rule[${i}][value][${key}]`"
             :value="(is_object(rule.value[key]))? JSON.stringify(rule.value[key]): rule.value[key]" type="hidden"/>


    </div>

  </div>

</template>
<script>

import Row from "@/components/Row";
import {mapActions, mapGetters, mapMutations} from "vuex";
import {is_object} from "@/services/helpers";
import draggable from 'vuedraggable'
import ButtonImport from "@/components/buttons/ButtonImport";


export default {
  name: 'App',
  components: {
    ButtonImport,
    draggable,
    Row
  },
  data() {
    return {
      str_rules,
      app_loading: false,
    }
  },
  computed: {
    ...mapGetters(['selected_rows']),
    rules: {
      get() {
        return this.$store.getters.rules
      },
      set(value) {
        this.$store.commit('update_rules', value)
      },
    }
  },
  methods: {
    ...mapActions(['load_app']),
    ...mapMutations(['reset_selected_rows', 'delete_rule', 'add_to_rules', 'new_rule', 'update_rules', 'update_selected_rows']),
    is_object,
    selectAllRules(e) {
      if (e.target.checked) {
        this.update_selected_rows(this.rules.map((rule, i) => i))
      } else {
        this.reset_selected_rows()
      }
    },
    newRule() {
      this.new_rule()
    },
    deleteRule(index) {
      this.delete_rule(index)
    },
    duplicateRule(index) {
      const rules = this.rules.filter((rule, i) => index === i)
      rules.forEach((rule) => this.add_to_rules(rule))
    },
    bulkDelete() {
      this.update_rules(this.rules.filter((rule, i) => this.selected_rows.indexOf(i) === -1))
      this.reset_selected_rows()
    },
    bulkExport() {
      if (this.selected_rows.length === 0) return
      const rules = this.rules.filter((rule, index) => this.selected_rows.indexOf(index) !== -1)
      const data = JSON.stringify(rules)
      const blob = new Blob([data], {type: 'text/plain'})
      const e = document.createEvent('MouseEvents'), a = document.createElement('a');
      a.download = "export.json";
      a.href = window.URL.createObjectURL(blob);
      a.dataset.downloadurl = ['text/json', a.download, a.href].join(':');
      e.initEvent('click', true, false, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
      a.dispatchEvent(e);
    },
    bulkDuplicate() {
      if (this.selected_rows.length === 0) return
      const rules = this.rules.filter((rule, index) => this.selected_rows.indexOf(index) !== -1)
      rules.forEach((rule) => this.add_to_rules(rule))
      this.reset_selected_rows()
    }
  },
  mounted() {
    this.app_loading = true
    this.load_app().then(() => this.app_loading = false)
    this.update_rules(this.str_rules)
  }

};
</script>

<style scoped>
#str_rules_table {
  table-layout: fixed;
  background: #fff;
  border-radius: 4px;
  box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
}

#str_rules_table th {
  padding: 15px 10px;
}

.button-secondary {
  margin-right: 5px;
}

#btn-add-new-rule {
  float: right;
}

table th.str_cb {
  width: 15px;
}

table th.str_condition {
  min-width: 150px;
  width: 150px;
}

table th.str_range {
  min-width: 200px;
  width: 200px;
}

table th.str_price {
  min-width: 100px;
  width: 100px;
}

table th.str_actions {
  min-width: 90px;
  width: 90px;
}

table th.str_buttons {
  min-width: 150px;
  width: 150px;
}

</style>
