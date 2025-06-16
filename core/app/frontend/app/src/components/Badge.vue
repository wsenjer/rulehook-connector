<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  value: {
    type: [String, Number],
    default: ''
  },
  withPulse: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  rounded: {
    type: Boolean,
    default: false
  },
  outline: {
    type: Boolean,
    default: false
  },
  icon: {
    type: String,
    default: ''
  },
  separator: {
    type: String,
    default: ':'
  },
  valueVariant: {
    type: String,
    default: '',
    validator: (value) => ['', 'primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value)
  }
});

const variantClasses = computed(() => {
  const variants = {
    primary: props.outline
      ? 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50'
      : 'bg-blue-600 text-white hover:bg-blue-700',
    secondary: props.outline
      ? 'bg-transparent text-gray-600 border border-gray-600 hover:bg-gray-50'
      : 'bg-gray-600 text-white hover:bg-gray-700',
    success: props.outline
      ? 'bg-transparent text-green-600 border border-green-600 hover:bg-green-50'
      : 'bg-green-600 text-white hover:bg-green-700',
    danger: props.outline
      ? 'bg-transparent text-red-600 border border-red-600 hover:bg-red-50'
      : 'bg-red-600 text-white hover:bg-red-700',
    warning: props.outline
      ? 'bg-transparent text-amber-600 border border-amber-600 hover:bg-amber-50'
      : 'bg-amber-600 text-white hover:bg-amber-700',
    info: props.outline
      ? 'bg-transparent text-sky-600 border border-sky-600 hover:bg-sky-50'
      : 'bg-sky-600 text-white hover:bg-sky-700'
  };

  return variants[props.variant];
});

const valueVariantClasses = computed(() => {
  if (!props.valueVariant) return '';

  const variants = {
    primary: 'bg-blue-700 text-white',
    secondary: 'bg-gray-700 text-white',
    success: 'bg-green-700 text-white',
    danger: 'bg-red-700 text-white',
    warning: 'bg-amber-700 text-white',
    info: 'bg-sky-700 text-white'
  };

  return variants[props.valueVariant];
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base'
  };

  return sizes[props.size];
});

const paddingClasses = computed(() => {
  const paddings = {
    sm: props.label && props.value ? 'pl-2 pr-2' : 'px-2 py-0.5',
    md: props.label && props.value ? 'pl-2.5 pr-2.5' : 'px-2.5 py-1',
    lg: props.label && props.value ? 'pl-3 pr-3' : 'px-3 py-1.5'
  };

  return paddings[props.size];
});

const valueClasses = computed(() => {
  if (!props.valueVariant) return '';

  const paddingBySize = {
    sm: 'py-0.5 px-2',
    md: 'py-1 px-2.5',
    lg: 'py-1.5 px-3'
  };

  return `${valueVariantClasses.value} ${paddingBySize[props.size]}`;
});

const roundedClasses = computed(() => {
  return props.rounded ? 'rounded-full' : 'rounded';
});

const badgeClasses = computed(() => {
  return `inline-flex items-center font-medium transition-colors ${variantClasses.value} ${sizeClasses.value} ${paddingClasses.value} ${roundedClasses.value}`;
});

const hasLabelAndValue = computed(() => {
  return !!props.label && !!props.value;
});
</script>

<template>
  <span :class="badgeClasses">
    <template v-if="hasLabelAndValue">
      <span class="py-1 flex items-center">
        <i v-if="withPulse" class="w-3 h-3 mr-1 inline bg-green-400 rounded-full border-2 border-green-100 shadow-md animate-pulse"></i>
        {{ label }}
        <span class="mx-1 opacity-75">{{ separator }}</span>
      </span>
      <span :class="[valueClasses, valueVariant ? roundedClasses + ' ml-0 rounded-l-none' : '']">
        {{ value }}
      </span>
    </template>
    <template v-else>
      <i v-if="icon" :class="icon" class="mr-1"></i>
      {{ label || value }}
    </template>
  </span>
</template>
