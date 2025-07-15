<script setup>
import {ref, computed, onMounted, onBeforeUnmount} from 'vue'
import { Toaster, toast } from 'vue-sonner'
import 'vue-sonner/style.css'

import api from '@/composable/api.js'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'

const shippingMethodEnabled = ref(false)

const connectionInfo = ref({
  storeId: '',
  teamId: '',
})

const pluginUri = ref(window.rulehook.plugin_uri)
const isConnected = ref(false)
const apiKey = ref('')
const baseUrl = ref('http://127.0.0.1:8000')
const connectionUrl = ref('')

const lastSyncTime = ref('')
const devMode = ref(false)
const formattedLastSync = computed(() => {
  if (!lastSyncTime.value) return 'Never'
  return lastSyncTime.value
})
const messageListener = ref(null)
const pollTimer = ref(null)
let isMounted = true
const isConnecting = ref(false)

const connectStore = () => {
  isConnecting.value = true

  // Open popup with specific dimensions and features for OAuth
  const width = 600;
  const height = 700;
  const left = window.screen.width / 2 - width / 2;
  const top = window.screen.height / 2 - height / 2;

  const popup = window.open(
    connectionUrl.value,
    'oauth-popup',
    `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=yes`
  );

  // Create the message listener and store it for cleanup
  messageListener.value = (event) => {
    // Guard against execution after component unmount
    if (!isMounted) return;

    if (event.data.action === 'windowClosed') {
      isConnecting.value = false;
    }

    if(event.data.action === 'connectionSuccess') {
      api
        .post('', { apiKey: event.data.apiKey, action: 'rulehook_validate_api_key' })
        .then((response) => {
          // Check again before updating refs
          if (!isMounted) return;

          if (response.data.valid && response.data.valid === true) {
            isConnected.value = true
            connectionInfo.value.storeId = response.data.storeId
            connectionInfo.value.teamId = response.data.teamId
            toast.success('Store connected successfully.')
            sync()
          }
        });
    }
  };

  window.addEventListener('message', messageListener.value);

  // Setup polling with cleanup
  pollTimer.value = window.setInterval(() => {
    if (popup && popup.closed) {
      window.clearInterval(pollTimer.value);
      pollTimer.value = null;
      cleanupEventListener();
    }
  }, 500);
}

const productsSynced = ref(0)
const shippingClassesSynced = ref(0)
const categoriesSynced = ref(0)
const isLoading = ref(true)
onMounted(() => {
  window.addEventListener('beforeunload', function (event) {
    event.stopImmediatePropagation()
  })
  loadApp()
})
const isDisconnecting = ref(false)
const disconnect = () => {
  if (!isMounted) return; // Add guard

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
    .catch(() => {
      if (!isMounted) return; // Add guard
      toast.error('Error disconnecting store. Please try again later.')
    })
    .finally(() => {
      if (!isMounted) return; // Add guard
      isDisconnecting.value = false
    })
}

const isSyncing = ref(false)
const sync = () => {
  if (!isMounted) return; // Add guard

  isSyncing.value = true
  api
    .post('', { action: 'rulehook_sync' })
    .then((response) => {
      if (response.data.ok) {
        toast.success('Store synced successfully.')
        loadApp()
      }

      if (response.data.error) {
        toast.error(response.data.error)
      }
    })
    .catch(() => {
      if (!isMounted) return; // Add guard

      toast.error('Error syncing store. Please try again later.')
    })
    .finally(() => {
      if (!isMounted) return; // Add guard

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
    .catch(() => {
      toast.error('Error toggling dev mode. Please try again later.')
    })
}

const loadApp = () => {
  if (!isMounted) return; // Add guard

  api
    .post('', { action: 'rulehook_load_app_data' })
    .then((response) => {
      if (response.data.teamId) {
        isConnected.value = response.data.isConnected
        connectionInfo.value.storeId = response.data.storeId
        connectionInfo.value.teamId = response.data.teamId
        lastSyncTime.value = response.data.lastSyncTime
        productsSynced.value = response.data.productsSynced
        shippingClassesSynced.value = response.data.shippingClassesSynced
        categoriesSynced.value = response.data.categoriesSynced
      }
      devMode.value = response.data.devMode
      shippingMethodEnabled.value = response.data.shippingMethodEnabled
      connectionUrl.value = response.data.connectionUrl
      isLoading.value = false

    })
    .catch((error) => {
      if (!isMounted) return; // Add guard
      console.log(error)
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
    })
    .then((response) => {
      if (response.data.ok) {
        toast.success('Shipping settings saved successfully.')
      } else {
        toast.error('Error saving shipping settings.')
      }
    })
    .catch(() => {
      toast.error('Error saving shipping settings. Please try again later.')
    })
    .finally(() => {
      isSavingSettings.value = false
    })
}
const cleanupEventListener = () => {
  if (messageListener.value) {
    window.removeEventListener('message', messageListener.value);
    messageListener.value = null;
  }
}
onBeforeUnmount(() => {
  isMounted = false;
  if (pollTimer.value) {
    window.clearInterval(pollTimer.value);
    pollTimer.value = null;
  }
  cleanupEventListener();
})
</script>

<template>
  <Toaster richColors />
  <div class="max-w-6xl mx-auto p-6">

    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
      <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
      <div v-else class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <img :src="pluginUri + 'app/dist/rulehook.png'" alt="RuleHook Logo" class="h-12 w-12 border-indigo-500 border-2 inline-block" />
          <div>
            <h1 class="text-2xl !my-0 !py-0 font-bold text-gray-900">RuleHook.com Integration</h1>
            <p class="text-gray-600 !my-0 !py-0">Manage your store synchronization and shipping settings</p>
          </div>
        </div>
        <div v-if="isConnected" class="flex items-center space-x-3">
          <a :href="`${baseUrl}/dashboard`" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 flex items-center space-x-2">
            <svg class="w-4 h-4 text-white" fill="none"  stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            <span class="text-white">Open Dashboard</span>
          </a>
          <button
            type="button"
            @click="disconnect()"
            class="px-4 py-2 cursor-pointer text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200"
          >
            <span v-if="isDisconnecting" class="dashicons dashicons-update animate-spin"></span>
            Disconnect
          </button>
        </div>
        <div v-else class="max-w-lg mx-auto flex flex-col items-center justify-center">
          <button
            type="button"
            @click="connectStore"
            class="bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 flex items-center space-x-2 text-sm px-4 py-1 w-auto"
          >
        <span
          v-if="isConnecting"
          class="dashicons dashicons-update animate-spin"
        ></span>
            Authorize This Site
          </button>
          <p class="mt-2 text-xs text-gray-500 text-center">
            Start your RuleHook account or connect your site now to unlock advanced shipping logic that drives more sales, leads, and conversions.
          </p>
        </div>
      </div>
    </div>


    <!-- Connection Status & Settings -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
        <div v-else class="flex items-center justify-between">
          <div>
            <h3 class="text-lg !my-2 font-semibold text-gray-900">Connection Status</h3>
            <div v-if="isConnected" class="flex items-center space-x-2 mt-2">
              <i class="w-3 h-3 mr-1 inline bg-green-400 rounded-full border-2 border-green-100 shadow-md animate-pulse"></i>
              <span class="text-success font-medium"> Connected</span>
            </div>
            <div v-else class="flex items-center space-x-2 mt-2">
              <i class="w-3 h-3 mr-1 inline bg-red-400 rounded-full border-2 border-red-100 shadow-md"></i>
              <span class="text-danger font-medium"> Not Connected</span>
            </div>
          </div>
          <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-primary" fill="none"  stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24"  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 19a1 1 0 0 1-1-1v-2a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a1 1 0 0 1-1 1z"/><path d="M17 21v-2"/><path d="M19 14V6.5a1 1 0 0 0-7 0v11a1 1 0 0 1-7 0V10"/><path d="M21 21v-2"/><path d="M3 5V3"/><path d="M4 10a2 2 0 0 1-2-2V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2a2 2 0 0 1-2 2z"/><path d="M7 5V3"/></svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
        <div v-else class="flex items-center justify-between">
          <div>
            <h3 class="text-lg !my-2 font-semibold text-gray-900">Store ID</h3>
            <div class="flex items-center space-x-2 mt-2">
              <code class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{{connectionInfo.storeId}}</code>
            </div>
          </div>
          <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
        <div v-else class="flex items-center justify-between">
          <div>
            <h3 class="text-lg !my-2 font-semibold text-gray-900">Shipping Method</h3>
            <div class="mt-3">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="shippingMethodEnabled" @change="saveShippingMethodSettings" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                <span class="ml-3 text-sm font-medium text-gray-700">Enable RuleHook shipping</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sync Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
      <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
      <div v-else class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Store Synchronization</h2>
        <div class="flex items-center space-x-3">
          <label class="flex items-center space-x-2">
            <input type="checkbox"      v-model="devMode"
                   @change="toggleDevMode" class="rounded border-gray-300 text-primary focus:ring-primary" checked>
            <span class="text-sm text-gray-700">Enable Dev Mode</span>
          </label>
          <button @click="sync()" :disabled="isSyncing" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 flex items-center space-x-2">
            <svg class="w-4 h-4" :class="{'animate-spin': isSyncing}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Sync Now</span>
          </button>
        </div>
      </div>
      <PlaceholderPattern v-if="isLoading" class="w-full h-24" />
      <!-- Stats Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-r rounded-lg p-6 border border-primary/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-primary !text-sm !font-medium">Last Sync</p>
              <p class="!text-xl font-bold text-blue-900 mt-1">{{ formattedLastSync }}</p>
            </div>
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>


        <div class="bg-gradient-to-r rounded-lg p-6 border border-primary/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-primary !text-sm !font-medium">Products Synced</p>
              <p class="!text-xl font-bold text-blue-900 mt-1">{{ productsSynced }}</p>
            </div>
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-r rounded-lg p-6 border border-primary/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-primary !text-sm !font-medium">Shipping Classes</p>
              <p class="!text-xl font-bold text-blue-900 mt-1">{{ shippingClassesSynced }}</p>
            </div>
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
          </div>
        </div>


        <div class="bg-gradient-to-r rounded-lg p-6 border border-primary/50">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-primary !text-sm !font-medium">Categories</p>
              <p class="!text-xl font-bold text-blue-900 mt-1">{{ categoriesSynced }}</p>
            </div>
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
          </div>
        </div>




      </div>
    </div>


  </div>

</template>

<style scoped>
@reference './assets/main.css';

/* WP dashicons styling */
.dashicons {
  @apply inline-block align-middle;
  font-family: dashicons;
  font-weight: 400;
  font-style: normal;
  font-size: 20px;
  line-height: 1;
}

p {
  @apply my-0 py-0;
}
</style>
