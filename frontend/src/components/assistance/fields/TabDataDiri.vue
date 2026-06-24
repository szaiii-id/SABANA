<template>
  <div class="bg-[#FDF8F4] p-6 sm:p-8 rounded-[2.5rem] border-2 border-[#F3E5D8]">
    
    <!-- Empty State -->
    <div v-if="!inputs.length" class="text-center py-10 text-[#8B5E3C]/50 text-xs font-medium">
      Tidak ada data tambahan yang diperlukan.
    </div>

    <!-- Input Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div v-for="input in inputs" :key="input.key" class="space-y-2">
        
        <!-- Label + Badge Revisi -->
        <div class="flex items-center gap-2 px-1">
          <label :for="'input-' + input.key" class="text-[10px] font-black text-[#8B5E3C] uppercase tracking-[0.2em]">
            {{ input.label }}
          </label>
          <span 
            v-if="revisionItems?.includes(input.key)" 
            class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-md text-[9px] font-bold animate-pulse"
          >
            ⚠ Perlu Diperbaiki
          </span>
        </div>

        <!-- SELECT -->
        <select 
          v-if="input.type === 'select'"
          :id="'input-' + input.key"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          @change="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLSelectElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold custom-select appearance-none"
        >
          <option value="" disabled class="text-gray-400">Pilih {{ input.label }}</option>
          <option v-for="opt in input.options" :key="opt.value" :value="opt.value" class="text-[#4A3728] font-bold">
            {{ opt.value }}
          </option>
        </select>

        <!-- CURRENCY -->
        <CurrencyInput
          v-else-if="input.type === 'currency'"
          :modelValue="modelValue[input.key]"
          :error="!!errors[input.key]"
          :errorMessage="errors[input.key] || ''"
          :placeholder="'Masukkan ' + input.label"
          @update:modelValue="$emit('update:modelValue', { ...modelValue, [input.key]: $event }); $emit('clearError', input.key)"
          @blur="$emit('blur', input.key)"
        />

        <!-- ✅ DATE -->
        <input 
          v-else-if="input.type === 'date'"
          :id="'input-' + input.key"
          type="date"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          @input="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLInputElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold"
        />

        <!-- ✅ TEXTAREA -->
        <textarea 
          v-else-if="input.type === 'textarea'"
          :id="'input-' + input.key"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          :placeholder="'Masukkan ' + input.label + '...'"
          rows="3"
          @input="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLTextAreaElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold resize-none"
        ></textarea>

        <!-- ✅ DECIMAL — pakai type number -->
        <input 
          v-else-if="input.type === 'decimal'"
          :id="'input-' + input.key"
          type="number"
          step="0.01"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          :placeholder="'Masukkan ' + input.label + '...'"
          @input="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLInputElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold"
        />

        <!-- ✅ NUMBER -->
        <input 
          v-else-if="input.type === 'number'"
          :id="'input-' + input.key"
          type="number"
          step="1"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          :placeholder="'Masukkan ' + input.label + '...'"
          @input="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLInputElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold"
        />

        <!-- TEXT (default) -->
        <input 
          v-else
          :id="'input-' + input.key"
          type="text"
          :value="modelValue[input.key]"
          :aria-label="input.label"
          :placeholder="'Masukkan ' + input.label + '...'"
          @input="$emit('update:modelValue', { ...modelValue, [input.key]: ($event.target as HTMLInputElement).value }); $emit('clearError', input.key)"
          class="w-full p-4 rounded-2xl border-2 border-transparent bg-white shadow-sm outline-none transition-all focus:border-[#2D6A4F] text-[#4A3728] font-bold"
        />

        <!-- Error -->
        <p v-if="errors[input.key]" class="text-[9px] font-bold text-red-500 px-1" role="alert">{{ errors[input.key] }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { FormInputSchema } from '../../../types/assistance';
import CurrencyInput from './CurrencyInput.vue';

defineProps<{
  inputs: FormInputSchema[];
  modelValue: Record<string, string | number>;
  errors: Record<string, string>;
  revisionItems?: string[];
}>();

defineEmits<{
  'update:modelValue': [value: Record<string, string | number>];
  clearError: [key: string];
  blur: [key: string];
}>();
</script>

<style scoped>
.custom-select {
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238B5E3C'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1.2rem center;
  background-size: 1rem;
  padding-right: 2.5rem;
}
</style>