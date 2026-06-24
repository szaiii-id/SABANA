<template>
  <div class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200/60 hover:shadow-md transition-shadow">
    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
      
      <!-- Avatar + Info -->
      <div class="flex items-center gap-4 flex-1 min-w-0">
        <div class="w-12 h-12 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-sm">
          {{ evaluation.citizen.full_name?.charAt(0) }}
        </div>
        <div class="min-w-0">
          <h3 class="font-bold text-slate-800 text-[15px] truncate">{{ evaluation.citizen.full_name }}</h3>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[12px] text-slate-500 font-mono">{{ evaluation.citizen.nik }}</span>
          </div>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[11px] text-slate-400">{{ evaluation.program.name }}</span>
            <span class="text-slate-300">·</span>
            <span class="text-[11px] text-slate-400">{{ evaluation.location.village }}</span>
          </div>
        </div>
      </div>

      <!-- Status + Actions -->
      <div class="flex items-center gap-4 flex-shrink-0">
        <span :class="statusBadge(evaluation.status)" class="px-3 py-1.5 rounded-xl text-[11px] font-bold">
          {{ evaluation.status_label }}
        </span>

        <!-- ✅ Tombol berdasarkan status new_submission -->
        <EvaluationActions
          v-if="evaluation.status === 'updated'"
          :evaluation="evaluation"
          :isSubmittingAny="isSubmittingAny"
          :submittingId="submittingId"
          :currentRole="currentRole"
          @approve="$emit('approve', evaluation)"
          @revoke="$emit('revoke', evaluation)"
        />
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import type { Evaluation } from '../../types/evaluation';
import EvaluationActions from './EvaluationActions.vue';

defineProps<{
  evaluation: Evaluation;
  isSubmittingAny: boolean;
  submittingId: string | null;
  currentRole: string;
}>();

defineEmits<{
  approve: [evaluation: Evaluation];
  revoke: [evaluation: Evaluation];
}>();

const statusBadge = (status: string): string => {
  const map: Record<string, string> = {
    triggered: 'bg-yellow-100 text-yellow-700',
    updated: 'bg-blue-100 text-blue-700',
    approved: 'bg-green-100 text-green-700',
    revoked: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-gray-100 text-gray-700';
};
</script>