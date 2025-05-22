<template>
  <div>
    <Modal v-if="open_modal" @closed="open_modal = false" no-confirm no-close>
      <h3 slot="header">Manage Actions</h3>
      <div slot="body">
        <table class="wp-list-table widefat fixed striped table-view-list">
          <thead>
          <tr>
            <th style="width: 200px;">Type</th>
            <th style="width: 300px;">Content</th>
            <th style="width: 70px;"></th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(action, i) in rule.actions">
            <td>
              <select name="actions" id="actions" v-model="action.action" @change="actionChanged($event, i)">
                <option value="-1">Select an action</option>
                <option value="cancel">Cancel calculations</option>
                <option value="stop">Stop calculations</option>
                <option value="show_notice">Show customer message</option>
                <option value="rename_method">Rename shipping title</option>
                <option value="subtitle">Add subtitle</option>
                <option value="hide_other_methods">Hide other methods</option>
              </select>
            </td>
            <td >
              <div v-if="action.action === 'cancel'">
                <span>This action will cancel all calculations if the rule matched. No shipping rate will be provided.</span>
              </div>
              <div v-if="action.action === 'stop'">
                <span>This action will stop the calculations at this point, previously matched rates will be provided.</span>
              </div>
              <div v-if="action.action === 'hide_other_methods'">
                <span>This action will hide other shipping methods if this rule is matched.</span>
              </div>
              <div v-if="action.action === 'show_notice'">
                <div class="str_block">
                  <select name="actions" id="actions" v-model="action.value.type" style="margin-right: 5px;">
                    <option value="notice">Notice</option>
                    <option value="success">Success</option>
                    <option value="error">Error</option>
                  </select>
                </div>
                <div class="str_block" style="width: 100%; margin-top: 5px;">
                  <textarea rows="3" style="width: 100%;" v-model="action.value.message" placeholder="Message"></textarea>
                </div>
              </div>
              <div v-if="action.action === 'rename_method'">
                <input type="text" style="width: 100%;" v-model="action.value.title" placeholder="Shipping Title"/>
              </div>
              <div v-if="action.action === 'subtitle'">
                <input type="text" style="width: 100%;" v-model="action.value.subtitle" placeholder="Subtitle"/>
              </div>
            </td>
            <td>
              <a href="javascript:void(0)" class="delete-action-icon" @click="deleteAction(i)">
                <span class="dashicons dashicons-trash"></span>
              </a>
            </td>
          </tr>
          <tr v-if="rule.actions && rule.actions.length === 0">
            <td colspan="3">
              <div style="text-align: center">No actions has been added.
              </div>
            </td>
          </tr>
          </tbody>
          <tfoot>
            <tr>
              <th><button type="button" class="button-primary" @click="addAction" title="Add Action">
                <span class="dashicons dashicons-plus-alt"></span> Add Action
              </button></th>
              <th></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </Modal>
    <div style="margin-top: 5px;">
      <button style="margin-top: 5px;" type="button" class="button-secondary" @click="open_modal = true" title="Manage Actions">
        <span class="dashicons dashicons-list-view"></span> Manage Actions ({{rule.actions.length}})
      </button>
    </div>
  </div>
</template>

<script>
import Modal from "@/components/ui/Modal";

export default {
  name: "Actions",
  components: { Modal},
  data() {
    return {
      open_modal: false,
    }
  },
  props: {
    rule: {
      required: true,
      type: Object,
    }
  },
  methods: {
    deleteAction(index) {
      this.rule.actions = this.rule.actions.filter((action, i) => i !== index)
    },
    addAction() {
      this.rule.actions.push({
        action: 'cancel',
        value: {},
      });
    },
    actionChanged(e, index) {
      const action = e.target.value
      let action_value = {}
      switch (action) {
        case 'show_notice':
          action_value = {type: 'notice', message: ''}
          break
        case 'rename_method':
          action_value = {title: ''}
          break
        case 'subtitle':
          action_value = {subtitle: ''}
          break
      }

      this.rule.actions[index].value = action_value
    }
  }
}
</script>

<style scoped>
.button-secondary {
  font-size: 8pt;
  min-height: auto;
  padding: 1px 6px 1px 1px;
  height: 26px;
}

.button-secondary .dashicons {
  font-size: 10pt;
  margin-top: 5px;
}

.button-primary .dashicons {
  font-size: 10pt;
  margin-top: 5px;
}

select {
  line-height: 2;
}

.delete-action-icon span {
  font-size: 15px;
  margin-top: 9px;
}

table th {
  padding: 15px 10px;
}

h3 {
  margin:0 !important;
}
</style>
