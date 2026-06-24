<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] p-8 max-w-md w-full shadow-2xl border border-slate-200">
        
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        
        <h3 class="text-xl font-black text-slate-800 text-center mb-4">Hentikan Bantuan?</h3>
        
        <!-- Info Warga -->
        <div class="bg-slate-50 rounded-xl p-4 mb-4 space-y-2">
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">Nama</span>
            <span class="text-[13px] font-bold text-slate-800">{{ citizenName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-[12px] text-slate-500">NIK</span>
            <span class="text-[13px] font-mono text-slate-800">{{ citizenNik }}</span>
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

        <textarea 
          :value="notes"
          @input="$emit('update:notes', ($event.target as HTMLTextAreaElement).value)"
          rows="3"
          placeholder="Tulis alasan penghentian (min. 10 karakter)..."
          class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/10 outline-none resize-none mb-2"
        ></textarea>
        <p class="text-[10px] text-slate-400 mb-5">{{ notes.length }}/500 (min. 10)</p>
        
        <div class="grid grid-cols-2 gap-3">
          <button @click="$emit('close')" class="py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all text-sm">Batal</button>
          <button @click="$emit('confirm')" :disabled="notes.trim().length < 10 || isSubmitting" class="py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 disabled:opacity-50 transition-all text-sm">Hentikan</button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  open: boolean;
  citizenName: string;
  citizenNik: string;
  notes: string;
  isSubmitting: boolean;
  smartScore?: number | null;
  recommendation?: { label: string; color: string } | null;
}>();

defineEmits<{
  close: [];
  confirm: [];
  'update:notes': [value: string];
}>();

const smartScoreDisplay = computed(() => {
  return props.smartScore != null ? props.smartScore.toFixed(1) : '-';
});

const smartScoreColor = computed(() => {
  const color = props.recommendation?.color;
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
  return props.recommendation?.label || 'Belum Dinilai';
});

const recommendationColor = computed(() => smartScoreColor.value);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>