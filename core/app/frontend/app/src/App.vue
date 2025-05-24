<script setup>
import { ref, computed, onMounted } from 'vue'
import { Toaster, toast } from 'vue-sonner'
import 'vue-sonner/style.css'

import api from '@/composable/api.js'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'

const shippingMethodEnabled = ref(false)
const methodTitle = ref('Shipping')

const connectionInfo = ref({
  storeId: '',
  teamId: '',
})

const isConnected = ref(false)
const apiKey = ref('')
const baseUrl = ref('http://127.0.0.1:8000')
const apiEndpoint = ref('/api/v1/validate-api-key')

const lastSyncTime = ref('')
const currency = ref('USD')
const devMode = ref(false)
const formattedLastSync = computed(() => {
  if (!lastSyncTime.value) return 'Never'
  return lastSyncTime.value
})

const isConnecting = ref(false)
const connectStore = () => {
  if (!apiKey.value) return
  isConnecting.value = true
  const url = baseUrl.value + apiEndpoint.value
  api
    .post('', { apiKey: apiKey.value.trim(), action: 'rulehook_validate_api_key' })
    .then((response) => {
      if (response.data.valid && response.data.valid === true) {
        isConnected.value = true
        connectionInfo.value.storeId = response.data.storeId
        connectionInfo.value.teamId = response.data.teamId
        toast.success('Store connected successfully.')
      }
      if(response.data.reason) {
        const actionName = response.data.reason === 'inactive' ? 'Activate Key' : 'Generate Key';

        toast.error('Error', {
          description: response.data.message,
          action: {
            label: actionName,
            onClick: (e) => {
              e.preventDefault();
              window.location.href = `${baseUrl.value}/settings/api-keys`
            }
        }})
      }
    })
    .catch((error) => {
      if (error.response && error.response.status === 401) {
        toast.error('Invalid API key. Please try again.')
      } else {
        toast.error('An error occurred while connecting to the store. Please try again later.')
      }
    })
    .finally(() => {
      isConnecting.value = false
    })
}
const productsSynced = ref(0)
const shippingZonesSynced = ref(0)
const isLoading = ref(true)
onMounted(() => {
  window.addEventListener('beforeunload', function (event) {
    event.stopImmediatePropagation()
  })
  loadApp()
})
const isDisconnecting = ref(false)
const disconnect = () => {
  isDisconnecting.value = true
  api
    .post('', { action: 'rulehook_disconnect' })
    .then((response) => {
      if (response.data.ok) {
        isConnected.value = false
        connectionInfo.value.storeId = ''
        connectionInfo.value.teamId = ''
        apiKey.value = ''
        toast.success('Store disconnected successfully.')
      }
    })
    .catch((error) => {
      toast.error('Error disconnecting store. Please try again later.')
    })
    .finally(() => {
      isDisconnecting.value = false
    })
}

const isSyncing = ref(false)
const sync = () => {
  isSyncing.value = true
  api
    .post('', { action: 'rulehook_sync' })
    .then((response) => {
      if (response.data.ok) {
        toast.success('Store synced successfully.')
      }

      if (response.data.error) {
        toast.error(response.data.error)
      }
    })
    .catch((error) => {
      toast.error('Error syncing store. Please try again later.')
    })
    .finally(() => {
      isSyncing.value = false
    })
}

const toggleDevMode = () => {
  api
    .post('', { action: 'rulehook_toggle_dev_mode' })
    .then((response) => {
      if (response.data.ok) {
        toast.success('Dev mode toggled successfully.')
        loadApp()
      }
    })
    .catch((error) => {
      toast.error('Error toggling dev mode. Please try again later.')
    })
}

const loadApp = () => {
  api
    .post('', { action: 'rulehook_load_app_data' })
    .then((response) => {
      if (response.data.teamId) {
        isConnected.value = response.data.isConnected
        connectionInfo.value.storeId = response.data.storeId
        connectionInfo.value.teamId = response.data.teamId
        lastSyncTime.value = response.data.lastSyncTime
        currency.value = response.data.currency
        productsSynced.value = response.data.productsSynced
        shippingZonesSynced.value = response.data.shippingZonesSynced
      }
      devMode.value = response.data.devMode
      shippingMethodEnabled.value = response.data.shippingMethodEnabled
      methodTitle.value = response.data.methodTitle
      isLoading.value = false
    })
    .catch((error) => {
      toast.error('Error fetching connection info. Please try again later.')
    })
}

// Add a function to save shipping method settings
const isSavingSettings = ref(false)
const saveShippingMethodSettings = () => {
  isSavingSettings.value = true
  api
    .post('', {
      action: 'rulehook_save_shipping_settings',
      shippingMethodEnabled: shippingMethodEnabled.value,
      methodTitle: methodTitle.value,
    })
    .then((response) => {
      if (response.data.ok) {
        toast.success('Shipping settings saved successfully.')
      } else {
        toast.error('Error saving shipping settings.')
      }
    })
    .catch((error) => {
      toast.error('Error saving shipping settings. Please try again later.')
    })
    .finally(() => {
      isSavingSettings.value = false
    })
}
</script>

<template>
  <div class="rulehook-layout">
    <Toaster richColors />
    <!-- Header Section -->
    <div class="rulehook-card mb-6">
      <div class="rulehook-card__header border-b border-gray-200 pb-4">
        <p class="text-2xl font-bold text-gray-900 !p-0 !m-0">RuleHook.com Integration</p>
      </div>
      <div class="rulehook-card__body p-4">
        <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
        <div v-else>
          <div
            v-if="isConnected"
            class="flex flex-col sm:flex-row sm:justify-between sm:items-center"
          >
            <div class="text-gray-700">
              <p v-if="connectionInfo.teamId" class="mb-1">
                <span class="font-medium">Team ID:</span> {{ connectionInfo.teamId }}
              </p>
              <p v-if="connectionInfo.storeId" class="mb-1">
                <span class="font-medium">Store ID:</span> {{ connectionInfo.storeId }}
              </p>
            </div>

            <div class="mt-4 sm:mt-0 flex gap-3">
              <a
                :href="`${baseUrl}/dashboard`"
                target="_blank"
                class="rulehook-button is-primary text-sm px-4 py-2"
              >
                Open Dashboard
              </a>
              <button
                type="button"
                @click="disconnect()"
                class="rulehook-button is-secondary text-sm px-4 py-2"
              >
                <span v-if="isDisconnecting" class="dashicons dashicons-update animate-spin"></span>
                Disconnect
              </button>
            </div>
          </div>
          <div v-else class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
            <div class="max-w-lg">
              <p class="mb-4 text-gray-700">Enter your RuleHook API key to connect your store:</p>
              <div class="flex flex-col sm:flex-row gap-1">
                <div class="flex-grow sm:w-96">
                  <input
                    type="text"
                    v-model="apiKey"
                    placeholder="Enter your API key"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                <div class="mt-2 sm:mt-0">
                  <button
                    type="button"
                    @click="connectStore"
                    class="rulehook-button is-primary text-sm px-3 py-1 w-full sm:w-auto"
                  >
                    <span
                      v-if="isConnecting"
                      class="dashicons dashicons-update animate-spin"
                    ></span>
                    Connect
                  </button>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500">
                Don't have an API key?
                <a
                  :href="`${baseUrl}/register`"
                  target="_blank"
                  class="text-indigo-600 hover:text-indigo-800"
                  >Create an account</a
                >
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Store Sync Status -->
    <div class="rulehook-card mb-6">
      <div class="rulehook-card__header border-b border-gray-200">
        <p class="text-2xl font-bold text-gray-900 !p-0 !m-0">Store Sync Status</p>
      </div>
      <div class="rulehook-card__body p-4">
        <PlaceholderPattern v-if="isLoading" class="w-full h-32" />
        <div v-else>
          <div class="flex flex-col sm:flex-row items-start sm:items-center my-4 gap-4">
            <button
              type="button"
              @click="sync()"
              class="rulehook-button is-primary text-sm px-4 py-2"
            >
              <span v-if="isSyncing" class="dashicons dashicons-update animate-spin"></span>

              Sync Now
            </button>
            <label class="inline-flex items-center">
              <input
                type="checkbox"
                v-model="devMode"
                @change="toggleDevMode"
                class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Enable Dev Mode</span>
            </label>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Last Sync -->
            <div class="status-card">
              <div class="status-card-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
              </div>
              <div class="status-card-content">
                <p class="status-card-label">Last Sync</p>
                <p class="status-card-value">{{ formattedLastSync }}</p>
              </div>
            </div>

            <!-- Products Synced -->
            <div class="status-card">
              <div class="status-card-icon">
                <span class="dashicons dashicons-products"></span>
              </div>
              <div class="status-card-content">
                <p class="status-card-label">Products Synced</p>
                <p class="status-card-value">{{ productsSynced }}</p>
              </div>
            </div>

            <!-- Shipping Zones -->
            <div class="status-card">
              <div class="status-card-icon">
                <span class="dashicons dashicons-location"></span>
              </div>
              <div class="status-card-content">
                <p class="status-card-label">Shipping Zones</p>
                <p class="status-card-value">{{ shippingZonesSynced }}</p>
              </div>
            </div>

            <!-- Currency -->
            <div class="status-card">
              <div class="status-card-icon">
                <span class="dashicons dashicons-money-alt"></span>
              </div>
              <div class="status-card-content">
                <p class="status-card-label">Currency</p>
                <p class="status-card-value">{{ currency }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add this new section after the Store Sync Status card -->
    <div class="rulehook-card mb-6">
      <div class="rulehook-card__header border-b border-gray-200">
        <p class="text-2xl font-bold text-gray-900 !p-0 !m-0">Shipping Method Settings</p>
      </div>
      <div class="rulehook-card__body p-4">
        <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
        <div v-else>
          <div class="mb-4">
            <label class="inline-flex items-center mb-4">
              <input
                type="checkbox"
                v-model="shippingMethodEnabled"
                class="form-checkbox h-5 w-5 text-indigo-600 border-gray-300 rounded"
              />
              <span class="ml-2 text-gray-700">Enable RuleHook shipping method</span>
            </label>

            <div class="mt-4">
              <label for="method-title" class="block text-sm font-medium text-gray-700 mb-1">
                Method Title
              </label>
              <div class="flex-grow sm:w-full md:w-full lg:w-2/5">
                <input
                  id="method-title"
                  type="text"
                  v-model="methodTitle"
                  placeholder="Shipping"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <p class="text-xs text-gray-500 mt-1">
                This controls the title seen by customers during checkout.
              </p>
            </div>
            <button
              type="button"
              @click="saveShippingMethodSettings"
              class="rulehook-button is-primary text-sm px-4 py-2 mt-4"
              :disabled="isSavingSettings"
            >
              <span v-if="isSavingSettings" class="dashicons dashicons-update animate-spin"></span>
              Save Settings
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference './assets/main.css';

/* WooCommerce-like styling */
.rulehook-layout {
  @apply max-w-5xl my-8 p-4 text-gray-700;
}

.rulehook-card {
  @apply bg-white rounded shadow-sm border border-gray-200 overflow-hidden;
}

.rulehook-card__header {
  @apply px-4 py-3 bg-white;
}

.rulehook-button {
  @apply cursor-pointer inline-flex items-center justify-center rounded font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.rulehook-button.is-primary {
  @apply bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 border border-transparent;
}

.rulehook-button.is-secondary {
  @apply bg-white text-gray-700 hover:bg-gray-50 focus:ring-indigo-500 border border-gray-300;
}

/* Status card styling */
.status-card {
  @apply bg-white rounded border border-gray-200 p-4 flex items-center shadow-sm hover:shadow transition-shadow duration-200;
}

.status-card-icon {
  @apply mr-3 flex items-center justify-center bg-indigo-100 text-indigo-600 w-10 h-10 rounded-full flex-shrink-0;
}

.status-card-content {
  @apply flex-grow;
}

.status-card-label {
  @apply text-sm text-gray-600 mb-1;
}

.status-card-value {
  @apply text-base font-medium text-gray-900;
}

/* WP dashicons styling */
.dashicons {
  @apply inline-block align-middle;
  font-family: dashicons;
  font-weight: 400;
  font-style: normal;
  font-size: 20px;
  line-height: 1;
}

.mr-1 {
  @apply mr-1;
}
</style>
