<template>
  <div class="flex flex-col space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-[#1B4332] tracking-tight">Manajemen Program</h1>
        <p class="text-[#6B705C] font-medium mt-1 text-sm">Kelola program bantuan sosial SABANA Center.</p>
      </div>
      <button 
        @click="openAddModal" 
        aria-label="Tambah program baru"
        class="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-2xl shadow-lg shadow-[#1B4332]/20 hover:shadow-xl hover:scale-[1.02] transition-all duration-300"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Tambah Program
      </button>
    </div>

    <!-- Toast error untuk operasi non-modal -->
    <div 
      v-if="toastError" 
      role="alert" 
      aria-live="assertive"
      class="flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-2xl"
    >
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-bold text-red-700">{{ toastError }}</p>
      </div>
      <button 
        @click="toastError = ''" 
        aria-label="Tutup notifikasi error"
        class="text-red-400 hover:text-red-600 transition-colors p-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>

    <ProgramFilters v-model="filters" @update:model-value="debounceSearch" />

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <ProgramCardSkeleton v-for="i in 6" :key="i" />
    </div>

    <ProgramEmptyState v-else-if="!programs.length" />

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <ProgramCard 
        v-for="program in programs" 
        :key="program.id" 
        :program="program" 
        @edit="openEditModal" 
        @delete="confirmDelete"
        @close="confirmClose"
        @reopen="handleReopen"
        @duplicate="handleDuplicate"
      />
    </div>

    <div v-if="!loading && totalPages > 1" class="flex items-center justify-center gap-4 pt-4">
      <button 
        :disabled="currentPage <= 1" 
        @click="changePage(currentPage - 1)" 
        aria-label="Halaman sebelumnya"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-colors" 
        :class="currentPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'"
      >← Sebelumnya</button>
      <span class="text-sm font-bold text-[#1B4332]" aria-live="polite">{{ currentPage }} / {{ totalPages }}</span>
      <button 
        :disabled="currentPage >= totalPages" 
        @click="changePage(currentPage + 1)" 
        aria-label="Halaman selanjutnya"
        class="px-4 py-2 text-xs font-bold rounded-xl transition-colors" 
        :class="currentPage >= totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'"
      >Selanjutnya →</button>
    </div>

    <ProgramModal 
      :open="isModalOpen" 
      :mode="modalMode" 
      :submitting="isSubmitting" 
      :error="errorMessage" 
      :uploadStep="uploadStep" 
      :uploadProgress="uploadProgress" 
      :validationError="validationToast"
      @close="handleCloseModal" 
      @save="onSave"
    >
      <ProgramFormFields :form="formData" :mode="modalMode" :errors="validationErrors" :existingBannerUrl="existingBannerUrl" @bannerChange="handleBannerChange" />
      <ProgramCriteriaSection :existing="formData.criteria" @update="handleCriteriaUpdate" />
    </ProgramModal>

    <DeleteConfirmModal :open="isDeleteModalOpen" :message="deleteMessage" :submitting="isSubmitting" @close="isDeleteModalOpen = false" @confirm="executeAction" />
    <SuccessModal :show="showSuccess" :message="successMessage" @close="showSuccess = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import ProgramService from '../../services/ProgramService';
import ProgramFilters from '../../components/program/ProgramFilters.vue';
import ProgramCard from '../../components/program/ProgramCard.vue';
import ProgramCardSkeleton from '../../components/program/ProgramCardSkeleton.vue';
import ProgramEmptyState from '../../components/program/ProgramEmptyState.vue';
import ProgramModal from '../../components/program/ProgramModal.vue';
import ProgramFormFields from '../../components/program/ProgramFormFields.vue';
import ProgramCriteriaSection from '../../components/program/ProgramCriteriaSection.vue';
import DeleteConfirmModal from '../../components/common/DeleteConfirmModal.vue';
import SuccessModal from '../../components/common/SuccessModal.vue';
import { useProgramList } from '../../composables/useProgramList';
import { useProgramForm } from '../../composables/useProgramForm';
import { useProgramSave } from '../../composables/useProgramSave';
import type { ProgramData } from '../../types/program';

interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

function isApiError(error: unknown): error is ApiError {
  return typeof error === 'object' && error !== null && 'response' in error;
}

const { programs, loading, filters, currentPage, totalPages, fetchData, debounceSearch, changePage } = useProgramList();
const { formData, validationErrors, modalMode, editId, isModalOpen, validateForm, openAddModal, openEditModal, closeModal, clearDraft, handleCriteriaUpdate, handleBannerChange } = useProgramForm();
const { isSubmitting, errorMessage, uploadProgress, uploadStep, showSuccess, successMessage, handleSave, showSuccessMessage } = useProgramSave();

const isDeleteModalOpen = ref(false);
const actionTarget = ref<ProgramData | null>(null);
const actionMode = ref<'delete' | 'close'>('delete');
const deleteMessage = ref('');
const toastError = ref('');
const validationToast = ref('');

const existingBannerUrl = computed<string | null>(() => {
  if (modalMode.value === 'edit') {
    const data = formData.value as Record<string, unknown>;
    return typeof data['banner_url'] === 'string' ? data['banner_url'] : null;
  }
  return null;
});

const handleCloseModal = () => {
  validationToast.value = '';
  closeModal();
};

const onSave = async () => {
  validationToast.value = '';
  
  if (!validateForm()) {
    validationToast.value = 'Mohon lengkapi semua field yang wajib diisi.';
    return;
  }
  
  await handleSave(formData.value, modalMode.value, editId.value, () => {
    clearDraft();
    closeModal();
    fetchData();
  });
};

const confirmDelete = (program: ProgramData) => {
  actionTarget.value = program;
  actionMode.value = 'delete';
  deleteMessage.value = `Anda akan menghapus program <strong>${program.name}</strong>. Data pendaftaran warga tetap aman.`;
  isDeleteModalOpen.value = true;
};

const confirmClose = (program: ProgramData) => {
  actionTarget.value = program;
  actionMode.value = 'close';
  deleteMessage.value = `Anda akan menutup program <strong>${program.name}</strong>. Warga baru tidak dapat mendaftar, data yang sudah ada tetap aman.`;
  isDeleteModalOpen.value = true;
};

const handleReopen = async (program: ProgramData) => {
  try {
    await ProgramService.reopenProgram(program.id);
    showSuccessMessage('Program berhasil dibuka kembali.');
    fetchData();
  } catch (e: unknown) {
    if (isApiError(e)) {
      toastError.value = e.response?.data?.message || 'Gagal membuka program.';
    } else {
      toastError.value = 'Gagal membuka program.';
    }
  }
};

const handleDuplicate = async (program: ProgramData) => {
  try {
    await ProgramService.duplicateProgram(program.id);
    showSuccessMessage('Program berhasil diduplikasi.');
    fetchData();
  } catch (e: unknown) {
    if (isApiError(e)) {
      toastError.value = e.response?.data?.message || 'Gagal menduplikasi program.';
    } else {
      toastError.value = 'Gagal menduplikasi program.';
    }
  }
};

const executeAction = async () => {
  if (!actionTarget.value) return;
  try {
    if (actionMode.value === 'close') {
      await ProgramService.closeProgram(actionTarget.value.id);
      showSuccessMessage('Program berhasil ditutup.');
    } else {
      await ProgramService.deleteProgram(actionTarget.value.id);
      showSuccessMessage('Program berhasil dihapus.');
    }
    isDeleteModalOpen.value = false;
    actionTarget.value = null;
    fetchData();
  } catch (e: unknown) {
    if (isApiError(e)) {
      toastError.value = e.response?.data?.message || 'Gagal memproses program.';
    } else {
      toastError.value = 'Gagal memproses program.';
    }
    isDeleteModalOpen.value = false;
  }
};
</script>