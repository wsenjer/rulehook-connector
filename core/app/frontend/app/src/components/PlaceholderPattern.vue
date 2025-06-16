<script setup>
import { computed } from 'vue'

const props = defineProps({
  className: {
    type: String,
    default: '',
  },
})

const classes = computed(() => `skeleton ${props.className}`)
</script>

<template>
  <div :class="classes"></div>
</template>

<style scoped>
@reference '../assets/main.css';
.skeleton {
  @apply relative overflow-hidden bg-gray-200 dark:bg-gray-800 rounded-md animate-pulse;
  background-image: linear-gradient(
    110deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.15) 20%,
    rgba(255, 255, 255, 0) 40%
  );
  background-size: 200% 100%;
  background-position: 180% 0;
}

.dark .skeleton {
  background-image: linear-gradient(
    110deg,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.15) 20%,
    rgba(0, 0, 0, 0) 40%
  );
}

@keyframes pulse {
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
