<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] p-8 max-w-md w-full shadow-2xl border border-slate-200">
        
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        
        <h3 class="text-xl font-black text-slate-800 text-center mb-4">Lanjutkan Bantuan?</h3>
        
        <!-- Info Warga -->
        <div class="bg-slate-50 rounded-xl p-4 mb-6 space-y-2">
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">Nama</span>
            <span class="text-[13px] font-bold text-slate-800">{{ evaluation?.citizen.full_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">NIK</span>
            <span class="text-[13px] font-mono text-slate-800">{{ evaluation?.citizen.nik }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">Skor SMART</span>
            <span class="text-[13px] font-bold" :class="smartScoreColor">{{ smartScoreDisplay }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">Rekomendasi</span>
            <span class="text-[12px] font-bold" :class="recommendationColor">{{ recommendationLabel }}</span>
          </div>
        </div>

        <p class="text-sm text-slate-500 text-center mb-6">Bantuan akan dilanjutkan untuk warga ini.</p>

        <div class="grid grid-cols-2 gap-3">
          <button @click="$emit('close')" class="py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all text-sm">
            Batal
          </button>
          <button @click="$emit('confirm')" :disabled="isSubmitting" class="py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 disabled:opacity-50 transition-all text-sm flex items-center justify-center gap-2">
            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Ya, Lanjutkan
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Evaluation } from '../../types/evaluation';

const props = defineProps<{
  open: boolean;
  evaluation: Evaluation | null;
  isSubmitting: boolean;
}>();

defineEmits<{
  close: [];
  confirm: [];
}>();

const smartScoreDisplay = computed(() => {
  const score = props.evaluation?.new_submission?.smart_score;
  return score != null ? score.toFixed(1) : '-';
});

const smartScoreColor = computed(() => {
  const color = props.evaluation?.new_submission?.recommendation?.color;
  const map: Record<string, string> = {
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    orange: 'text-orange-600',
    red: 'text-red-600',
    gray: 'text-slate-400',
  };
  return map[color || 'gray'] || 'text-slate-400';
});

const recommendationLabel = computed(() => {
  return props.evaluation?.new_submission?.recommendation?.label || 'Belum Dinilai';
});

const recommendationColor = computed(() => smartScoreColor.value);
</script>