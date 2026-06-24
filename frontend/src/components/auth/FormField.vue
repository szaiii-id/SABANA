<template>
  <div class="relative mb-5">
    <input 
      :value="modelValue"
      @input="handleInput"
      @blur="handleBlur"
      :type="type"
      :inputmode="inputmode"
      :maxlength="maxlength"
      :disabled="disabled"
      :readonly="disabled"
      :placeholder="' '"
      :class="[
        'peer w-full border-2 rounded-2xl px-5 py-4 outline-none transition-all font-bold text-base',
        disabled 
          ? 'bg-gray-200 text-gray-500 cursor-not-allowed border-gray-300' 
          : 'bg-gray-50 text-gray-800',
        inputClass,
        !disabled && error 
          ? 'border-red-500 bg-red-50' 
          : !disabled 
            ? `border-gray-200 ${focusColor || 'focus:border-[#2D6A4F]'} focus:bg-white` 
            : ''
      ]"
    />
    <label 
      :class="[
        'absolute left-5 top-4 text-xs font-black uppercase tracking-widest transition-all pointer-events-none',
        'peer-placeholder-shown:top-4 peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-400',
        'peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1',
        'peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1',
        'peer-disabled:-top-2.5 peer-disabled:text-[10px] peer-disabled:bg-gray-200 peer-disabled:px-1',
        error ? 'text-red-500' : labelColor || 'text-[#2D6A4F]'
      ]"
    >
      {{ label }}
    </label>
    <p v-if="error && errorMessage" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left">
      {{ errorMessage }}
    </p>
    <slot name="hint"></slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  modelValue: string;
  label: string;
  type?: string;
  disabled?: boolean;
  error?: boolean;
  errorMessage?: string;
  inputClass?: string;
  labelColor?: string;
  focusColor?: string;
  maxlength?: string | number;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'blur'): void;
}>();

const inputmode = computed(() => 
  props.type === 'tel' || props.type === 'numeric' ? 'numeric' : 'text'
);

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
};

const handleBlur = () => {
  emit('blur');
};
</script>