<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <div class="max-w-6xl mx-auto px-4 py-6 md:px-6 space-y-5">
      
      <DisbursementHeader
        :total="submissions.length"
        :selectedCount="selectedIds.length"
        :filterStatus="filterStatus"
        @update:filterStatus="handleFilterChange"
        @bulkDisburse="openBulkModal"
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
            <div class="space-y-2 flex-1"><div class="h-4 bg-slate-200 rounded w-1/3"></div><div class="h-3 bg-slate-200 rounded w-1/2"></div></div>
          </div>
        </div>
      </div>

      <div v-else-if="!submissions.length" class="bg-white rounded-[2rem] p-12 shadow-sm ring-1 ring-slate-200/60 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-slate-700">Tidak Ada Antrean</h3>
        <p class="text-sm text-slate-500 mt-1">Semua bantuan sudah disalurkan.</p>
      </div>

      <div v-else class="space-y-4">
        <DisbursementCard
          v-for="submission in submissions"
          :key="submission.id"
          :submission="submission"
          :isSelected="selectedIds.includes(submission.id)"
          :isSubmitting="submittingId === submission.id"
          @toggle="toggleSelect(submission.id)"
          @disburse="openSingleModal(submission)"
        />
      </div>

    </div>

    <DisburseSingleModal
      :open="showSingleModal"
      :submission="selectedForDisburse"
      :isSubmitting="submittingId !== null"
      @close="showSingleModal = false"
      @confirm="handleDisburse"
    />

    <DisburseBulkModal
      :open="showBulkModal"
      :count="selectedIds.length"
      :isSubmitting="submitting"
      @close="showBulkModal = false"
      @confirm="handleBulkDisburse"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useDisbursement } from '../../composables/useDisbursement';
import type { DisbursementSubmission } from '../../types/disbursement';
import DisbursementHeader from '../../components/disbursement/DisbursementHeader.vue';
import DisbursementCard from '../../components/disbursement/DisbursementCard.vue';
import DisburseSingleModal from '../../components/disbursement/DisburseSingleModal.vue';
import DisburseBulkModal from '../../components/disbursement/DisburseBulkModal.vue';

const { submissions, loading, error, successMessage, fetchList, disburse, bulkDisburse, resetMessages } = useDisbursement();

const selectedIds = ref<string[]>([]);
const submittingId = ref<string | null>(null);
const submitting = ref(false);
const showSingleModal = ref(false);
const showBulkModal = ref(false);
const selectedForDisburse = ref<DisbursementSubmission | null>(null);
const filterStatus = ref('');

onMounted(() => fetchList());

const handleFilterChange = (status: string) => {
  filterStatus.value = status;
  fetchList(status ? { status } : undefined);
};

const toggleSelect = (id: string) => {
  const idx = selectedIds.value.indexOf(id);
  if (idx >= 0) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
};

const openSingleModal = (submission: DisbursementSubmission) => {
  selectedForDisburse.value = submission;
  showSingleModal.value = true;
};

const openBulkModal = () => {
  showBulkModal.value = true;
};

const handleDisburse = async () => {
  if (!selectedForDisburse.value) return;
  submittingId.value = selectedForDisburse.value.id;
  const success = await disburse(selectedForDisburse.value.id);
  if (success) {
    showSingleModal.value = false;
    selectedIds.value = [];
    await fetchList();
    setTimeout(() => resetMessages(), 3000);
  }
  submittingId.value = null;
};

const handleBulkDisburse = async () => {
  submitting.value = true;
  const success = await bulkDisburse(selectedIds.value);
  if (success) {
    showBulkModal.value = false;
    selectedIds.value = [];
    await fetchList();
    setTimeout(() => resetMessages(), 3000);
  }
  submitting.value = false;
};
</script>