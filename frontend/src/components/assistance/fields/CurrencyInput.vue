<template>
  <div class="relative">
    <input 
      :value="displayValue"
      :aria-label="placeholder || 'Masukkan nilai'"
      @focus="focused = true"
      @blur="focused = false"
      @input="onInput"
      :placeholder="placeholder || '0'"
      inputmode="numeric"
      :class="[
        'w-full p-4 rounded-2xl border-2 outline-none transition-all font-bold text-[#4A3728]',
        error ? 'border-red-300 bg-red-50' : 'border-transparent bg-white shadow-sm focus:border-[#2D6A4F]'
      ]"
    />
    <p v-if="errorMessage" class="text-[9px] font-bold text-red-500 mt-1 px-1" role="alert">{{ errorMessage }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
  modelValue: string | number;
  error?: boolean;
  errorMessage?: string;
  placeholder?: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const focused = ref(false);

const displayValue = computed(() => {
  if (focused.value) {
    return typeof props.modelValue === 'string' 
      ? props.modelValue.replace(/\D/g, '') 
      : props.modelValue || '';
  }
  const num = typeof props.modelValue === 'string' 
    ? parseInt(props.modelValue.replace(/\D/g, '')) || 0 
    : Number(props.modelValue) || 0;
  return num ? 'Rp ' + new Intl.NumberFormat('id-ID').format(num) : '';
});

const onInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value.replace(/\D/g, '');
  emit('update:modelValue', val);
};
</script>