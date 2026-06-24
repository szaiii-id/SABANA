<template>
  <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
    <div class="flex items-center gap-4">
      <!-- Custom Checkbox -->
      <label class="relative flex items-center cursor-pointer">
        <input 
          type="checkbox" 
          :checked="isSelected"
          @change="$emit('toggle')"
          class="sr-only"
        />
        <div :class="[
          'w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all',
          isSelected 
            ? 'bg-[#1B4332] border-[#1B4332]' 
            : 'border-[#E8D5C4] bg-white hover:border-[#D4A373]'
        ]">
          <svg v-if="isSelected" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </label>

      <!-- Avatar -->
      <div class="w-12 h-12 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-sm">
        {{ submission.citizen.full_name?.charAt(0) }}
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-slate-800 text-[15px] truncate">{{ submission.citizen.full_name }}</h3>
        <p class="text-[13px] text-slate-500">{{ submission.citizen.nik }}</p>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-[11px] text-slate-400">{{ submission.program.name }}</span>
          <span class="text-slate-300">·</span>
          <span class="text-[11px] font-bold text-[#1B4332]">Rp {{ formatCurrency(submission.program.benefit_amount) }}</span>
          <span class="text-slate-300">·</span>
          <span class="text-[11px] text-slate-400">{{ formatMethod(submission.disbursement_method) }}</span>
        </div>
      </div>

      <!-- Action -->
      <button 
        @click="$emit('disburse')"
        :disabled="isSubmitting"
        class="px-4 py-2 bg-[#1B4332] text-white text-[12px] font-bold rounded-xl hover:bg-[#2D6A4F] disabled:opacity-50 transition-all flex-shrink-0"
      >
        <svg v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin inline" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        {{ isSubmitting ? '...' : 'Salurkan' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DisbursementSubmission } from '../../types/disbursement';

defineProps<{
  submission: DisbursementSubmission;
  isSelected: boolean;
  isSubmitting: boolean;
}>();

defineEmits<{
  toggle: [];
  disburse: [];
}>();

const formatCurrency = (num: number | null | undefined): string => {
  if (num == null) return '0';
  return new Intl.NumberFormat('id-ID').format(num);
};

const formatMethod = (method?: string): string => {
  return method === 'village_cash' ? 'Tunai' : 'Transfer BPD';
};
</script>