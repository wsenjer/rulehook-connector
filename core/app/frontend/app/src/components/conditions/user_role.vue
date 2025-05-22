<template>
  <div>
    <label for="selectedRole" class="str_label">Role</label>
    <select name="role" id="selectedRole" v-model="value.role" :value="value.role" @change="$emit('changed', value)">
      <option v-for="role in user_roles" :value="role.id">{{ role.name }}</option>
    </select>
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: "user_role",
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    ...mapGetters(['user_roles'])
  },
  created() {
    if (!this.value.role && this.user_roles.length > 0) {
      this.value.role = this.user_roles[0].id
    }
  }
}
</script>

<style scoped>
  select {
    width: 100% !important;
  }
</style>
