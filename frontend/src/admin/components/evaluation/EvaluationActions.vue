<template>
  <div class="flex items-center gap-2 flex-shrink-0">
    <template v-if="currentRole === 'regency_admin' || currentRole === 'super_admin'">
      
      <!-- ✅ Validated → Lanjutkan -->
      <button 
        v-if="evaluation.new_submission?.status === 'validated'"
        @click="$emit('approve')"
        :disabled="isSubmittingAny"
        class="px-4 py-2 bg-green-600 text-white text-[12px] font-bold rounded-xl hover:bg-green-700 disabled:opacity-50 transition-all flex items-center gap-1.5"
      >
        <svg v-if="submittingId === evaluation.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Lanjutkan
      </button>

      <!-- ✅ Pending → Hentikan -->
      <button 
        v-if="evaluation.new_submission?.status === 'pending'"
        @click="$emit('revoke')"
        :disabled="isSubmittingAny"
        class="px-4 py-2 bg-red-50 text-red-600 text-[12px] font-bold rounded-xl hover:bg-red-100 border border-red-200 disabled:opacity-50 transition-all"
      >
        Hentikan
      </button>

    </template>
    <span v-else class="text-[12px] text-slate-400">
      {{ currentRole === 'village_officer' ? 'Survei warga' : 'Menunggu keputusan' }}
    </span>
  </div>
</template>

<script setup lang="ts">
import type { Evaluation } from '../../types/evaluation';

defineProps<{
  evaluation: Evaluation;
  isSubmittingAny: boolean;
  submittingId: string | null;
  currentRole: string;
}>();

defineEmits<{
  approve: [];
  revoke: [];
}>();
</script>