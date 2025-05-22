<template>
  <span>
     <input style="display: none;" type="file" ref="file" @change="readFile()"/>
      <button style="float:right; margin-right:5px;" class="button-secondary" type="button"
              @click="importRulesClick">Import</button>
         <small v-if="invalidFileExtension" class="warning">Invalid file extension, only JSON files allowed.</small>
         <small v-if="invalidFileContent" class="warning">Invalid JSON content, make sure the json object is valid.</small>

  </span>
</template>

<script>
import {mapMutations} from "vuex";

export default {
  name: "ButtonImport",
  data() {
    return {
      file: null,
      content: null,
      invalidFileContent: false,
      invalidFileExtension: false,
    }
  },
  methods: {
    ...mapMutations(['add_to_rules']),
    importRules() {
      const rules = this.content.filter((rule) => {
        return rule.value && rule.type
      })

      rules.forEach((rule) => this.add_to_rules(rule))

    },
    importRulesClick() {
      this.invalidFileContent = false
      this.invalidFileExtension = false
      this.$refs.file.click()
    },
    readFile() {
      this.file = this.$refs.file.files[0];
      if (!this.file) {
        return
      }

      if (!this.file.name.includes(".json")) {
        this.invalidFileExtension = true
        return;
      }
      const reader = new FileReader();

      reader.onload = (res) => {
        try {
          this.content = JSON.parse(res.target.result);
          this.importRules()
        } catch (e) {
          this.invalidFileContent = true
        }

      };
      reader.onerror = (err) => console.log(err);
      reader.readAsText(this.file);
    }
  }
}
</script>

<style scoped>
.warning {
  color: coral;
  float: right;
  margin-right: 8px;
  margin-top: 4px;
}
</style>
