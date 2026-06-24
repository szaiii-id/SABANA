<template>
  <div class="flex flex-col space-y-4 h-full p-4 md:p-6">
    
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
      <div>
        <p class="text-[#2D6A4F] font-black text-[8px] uppercase tracking-[0.3em] mb-1">Sistem Verifikasi</p>
        <h2 class="text-2xl font-black text-[#1B4332] tracking-tight leading-none">Antrean Verifikasi</h2>
        <p class="text-[#6B705C] font-medium mt-0.5 text-xs">Tinjau dan proses pengajuan bantuan warga berdasarkan prioritas.</p>
      </div>
      <div class="flex items-center gap-3 bg-[#FAF6F0] px-4 py-2.5 rounded-2xl border border-[#E8D5C4]">
        <div class="text-right">
          <p class="text-[7px] font-black text-[#8B5E3C] uppercase tracking-widest">{{ summaryLabel }}</p>
          <p class="text-lg font-black text-[#1B4332] leading-none">{{ total }}</p>
        </div>
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="summaryBadgeColor">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
        </div>
      </div>
    </div>

    <VerificationFilters :modelValue="filters" @update:modelValue="onFilterChange" />

    <VerificationTable 
      :showCheckbox="filters.status === 'validated'"
      :verifications="verifications"
      :loading="loading"
      :currentPage="currentPage"
      :totalPages="totalPages"
      :total="total"
      :selectedIds="selectedIds"
      @select="handleSelect"
      @pageChange="changePage"
      @update:selectedIds="selectedIds = $event"
      @bulkComplete="handleBulkComplete"
      @clearSelection="selectedIds = []"
    />

    <ConfirmModal
      :open="showBulkConfirm"
      title="Tandai Selesai?"
      :message="'Anda akan menandai ' + selectedCount + ' pengajuan sebagai selesai.'"
      variant="warning"
      confirm-text="Ya, Tandai Selesai"
      cancel-text="Batal"
      @close="showBulkConfirm = false"
      @confirm="executeBulkComplete"
    />

    <SuccessModal :show="showSuccess" :message="successMessage" @close="showSuccess = false" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import VerificationFilters from '../../components/verification/VerificationFilters.vue';
import VerificationTable from '../../components/verification/VerificationTable.vue';
import ConfirmModal from '../../components/common/ConfirmModal.vue';
import SuccessModal from '../../components/common/SuccessModal.vue';
import { useVerification } from '../../composables/useVerification';
import type { VerificationData, VerificationFiltersValues } from '../../types/verification'; // ← Tambah VerificationFilters

const router = useRouter();

const {
  verifications, loading,
  currentPage, totalPages, total, showSuccess, successMessage,
  fetchVerifications, showSuccessMessage, bulkComplete
} = useVerification();

const filters = ref<VerificationFiltersValues>({ 
  search: '', 
  status: '', 
  recommendation: '', 
  smartSort: '' 
});

const selectedIds = ref<string[]>([]);
const showBulkConfirm = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// ← Ubah tipe parameter jadi VerificationFilters
const onFilterChange = (value: VerificationFiltersValues) => {
  filters.value = { ...value };
  debounceSearch();
};

const debounceSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  currentPage.value = 1;
  searchTimeout = setTimeout(fetchData, 500);
};

const fetchData = () => {
  selectedIds.value = [];
  fetchVerifications({ ...filters.value, page: currentPage.value, per_page: 15 });
};

const changePage = (page: number) => {
  currentPage.value = page;
  fetchData();
};

const handleSelect = (item: VerificationData) => {
  router.push({ name: 'admin.verifications.detail', params: { id: item.id } });
};

const handleBulkComplete = () => {
  showBulkConfirm.value = true;
};

const executeBulkComplete = async () => {
  showBulkConfirm.value = false;
  const success = await bulkComplete(selectedIds.value);
  if (success) {
    selectedIds.value = [];
    fetchData();
  }
};

const selectedCount = computed(() => selectedIds.value.length);

const summaryLabel = computed(() => {
  switch (filters.value.status) {
    case 'pending': return 'Menunggu Verifikasi';
    case 'needs_revision': return 'Perlu Perbaikan';
    case 'validated': return 'Telah Disetujui';
    case 'rejected': return 'Ditolak';
    case 'completed': return 'Selesai';
    default: return 'Total Pengajuan';
  }
});

const summaryBadgeColor = computed(() => {
  switch (filters.value.status) {
    case 'pending': return 'bg-yellow-500';
    case 'needs_revision': return 'bg-blue-500';
    case 'validated': return 'bg-green-500';
    case 'rejected': return 'bg-red-500';
    case 'completed': return 'bg-gray-500';
    default: return 'bg-[#1B4332]';
  }
});

onMounted(() => { fetchData(); });
</script>