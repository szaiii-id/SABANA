<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <div class="max-w-6xl mx-auto px-4 py-6 md:px-6 space-y-5">
      
      <EvaluationHeader
        :total="evaluations.length"
        :filterStatus="filterStatus"
        @update:filterStatus="handleFilterChange"
      />

      <Transition name="fade">
        <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center gap-3">
          <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <p class="text-sm font-bold text-green-700">{{ successMessage }}</p>
        </div>
      </Transition>

      <Transition name="fade">
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
          <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm font-bold text-red-700">{{ error }}</p>
        </div>
      </Transition>

      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 shadow-sm ring-1 ring-slate-200/60 animate-pulse">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-200 rounded-xl"></div>
            <div class="space-y-2 flex-1">
              <div class="h-4 bg-slate-200 rounded w-1/3"></div>
              <div class="h-3 bg-slate-200 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!evaluations.length" class="bg-white rounded-[2rem] p-12 shadow-sm ring-1 ring-slate-200/60 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-slate-700">Belum Ada Evaluasi</h3>
        <p class="text-sm text-slate-500 mt-1">Tidak ada warga yang perlu dievaluasi saat ini.</p>
      </div>

      <div v-else class="space-y-4">
        <EvaluationCard
          v-for="evaluation in evaluations"
          :key="evaluation.id"
          :evaluation="evaluation"
          :isSubmittingAny="isSubmittingAny"
          :submittingId="submittingId"
          :currentRole="currentRole"
          @approve="openApproveModal"
          @revoke="openRevokeModal"
        />
      </div>

    </div>

    <ApproveEvaluationModal
      :open="showApproveModal"
      :evaluation="selectedEvaluation"
      :isSubmitting="isSubmittingAny"
      @close="showApproveModal = false"
      @confirm="handleApprove"
    />

    <RevokeEvaluationModal
      :open="showRevokeModal"
      :citizenName="selectedEvaluation?.citizen.full_name || ''"
      :citizenNik="selectedEvaluation?.citizen.nik || ''"
      :smartScore="selectedEvaluation?.new_submission?.smart_score"
      :recommendation="selectedEvaluation?.new_submission?.recommendation || null"
      :notes="revokeNotes"
      :isSubmitting="isSubmittingAny"
      @close="showRevokeModal = false"
      @confirm="handleRevoke"
      @update:notes="revokeNotes = $event"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useEvaluation } from '../../composables/useEvaluation';
import type { Evaluation } from '../../types/evaluation';
import EvaluationHeader from '../../components/evaluation/EvaluationHeader.vue';
import EvaluationCard from '../../components/evaluation/EvaluationCard.vue';
import ApproveEvaluationModal from '../../components/evaluation/ApproveEvaluationModal.vue';
import RevokeEvaluationModal from '../../components/evaluation/RevokeEvaluationModal.vue';

const { evaluations, loading, error, successMessage, fetchEvaluations, approveEvaluation, revokeEvaluation } = useEvaluation();

const submittingId = ref<string | null>(null);
const showApproveModal = ref(false);
const showRevokeModal = ref(false);
const selectedEvaluation = ref<Evaluation | null>(null);
const revokeNotes = ref('');
const filterStatus = ref('');
const currentRole = ref('');

const isSubmittingAny = computed(() => submittingId.value !== null);

onMounted(() => {
  const user = JSON.parse(localStorage.getItem('admin_user') || '{}');
  currentRole.value = user.role || '';
  fetchEvaluations();
});

const handleFilterChange = (status: string) => {
  filterStatus.value = status;
  const params: Record<string, string> = {};
  if (status) params.status = status;
  fetchEvaluations(params);
};

const openApproveModal = (evaluation: Evaluation) => {
  selectedEvaluation.value = evaluation;
  showApproveModal.value = true;
};

const openRevokeModal = (evaluation: Evaluation) => {
  selectedEvaluation.value = evaluation;
  revokeNotes.value = '';
  showRevokeModal.value = true;
};

const handleApprove = async () => {
  if (!selectedEvaluation.value) return;
  submittingId.value = selectedEvaluation.value.id;
  const success = await approveEvaluation(selectedEvaluation.value.id);
  if (success) {
    showApproveModal.value = false;
    await fetchEvaluations();
  }
  submittingId.value = null;
};

const handleRevoke = async () => {
  if (!selectedEvaluation.value || revokeNotes.value.trim().length < 10) return;
  submittingId.value = selectedEvaluation.value.id;
  const success = await revokeEvaluation(selectedEvaluation.value.id, revokeNotes.value.trim());
  if (success) {
    showRevokeModal.value = false;
    await fetchEvaluations();
  }
  submittingId.value = null;
};
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