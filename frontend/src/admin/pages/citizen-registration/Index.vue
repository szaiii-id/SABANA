<template>
  <div class="flex flex-col h-full space-y-6">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">Pendaftaran Warga</h2>
        <p class="text-[#6B705C] font-medium mt-1 text-sm">
          Daftarkan warga ke sistem SABANA untuk mendapatkan akses atau langsung diajukan bantuan.
        </p>
      </div>
      <button 
        @click="openRegisterModal" 
        class="flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-2xl shadow-lg shadow-[#1B4332]/20 hover:shadow-xl hover:scale-[1.02] transition-all duration-300"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Daftarkan Warga
      </button>
    </div>

    <!-- Search Filters -->
    <RegistrationCitizenFilters 
      v-model="filters" 
      :is-searching="isSearching"
      @update:model-value="onSearchChange" 
    />

    <!-- Table -->
    <RegistrationCitizenTable 
      :citizens="citizens" 
      :loading="loading" 
      :total="total"
      :currentPage="currentPage"
      :totalPages="totalPages"
      @edit="openEditModal"
      @resendPin="handleResendPin"
      @pageChange="changePage"
    />

    <!-- Modals -->
    <RegistrationCitizenModal 
      :open="isRegisterModalOpen" 
      :submitting="isSubmitting" 
      :error="errorMessage"
      @close="isRegisterModalOpen = false" 
      @submit="handleSubmit" 
    />

    <RegistrationCitizenSuccessModal 
      :open="showSuccessModal" 
      :citizen="createdCitizen" 
      :accessPin="accessPin"
      @close="handleCloseSuccessModal" 
    />

    <RegistrationCitizenEditModal 
      :open="isEditModalOpen"
      :submitting="isSubmitting"
      :error="errorMessage"
      :citizen="selectedCitizen"
      @close="isEditModalOpen = false"
      @submit="handleUpdateCitizen"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import RegistrationCitizenFilters from '../../components/registration-citizen/RegistrationCitizenFilters.vue';
import RegistrationCitizenTable from '../../components/registration-citizen/RegistrationCitizenTable.vue';
import RegistrationCitizenModal from '../../components/registration-citizen/RegistrationCitizenModal.vue';
import RegistrationCitizenSuccessModal from '../../components/registration-citizen/RegistrationCitizenSuccessModal.vue';
import RegistrationCitizenEditModal from '../../components/registration-citizen/RegistrationCitizenEditModal.vue';
import { useRegistrationCitizen } from '../../composables/useRegistrationCitizen';
import { registrationCitizenApi } from '../../api/registrationCitizenApi';
import type { RegisterCitizenPayload, RegisteredCitizen } from '../../types/registration-citizen';

// ===== TYPES =====
interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

interface UpdateCitizenData {
  nik: string;
  full_name: string;
  family_card_number: string;
  whatsapp_number: string;
}

// ===== COMPOSABLES =====
const { 
  citizens, 
  loading, 
  isSubmitting, 
  errorMessage, 
  currentPage, 
  totalPages, 
  total, 
  fetchCitizens,
  searchCitizens,  // ✅ Import method baru
  registerCitizen, 
  resendPin 
} = useRegistrationCitizen();

// ===== STATE =====
const filters = ref({ search: '' });
const isSearching = ref(false);
const isRegisterModalOpen = ref(false);
const showSuccessModal = ref(false);
const createdCitizen = ref<RegisteredCitizen | null>(null);
const accessPin = ref('');
const isEditModalOpen = ref(false);
const selectedCitizen = ref<RegisteredCitizen | null>(null);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let currentRequestId = 0;

// ===== SEARCH =====

const onSearchChange = (value: { search: string }): void => {
  filters.value = value;
  currentPage.value = 1;
  
  const query = value.search.trim();
  
  if (!query) {
    isSearching.value = true;
    fetchData();
    return;
  }
  
  if (query.length < 3) {
    isSearching.value = false;
    return;
  }
  
  isSearching.value = true;
  searchData();
};

const fetchData = async (): Promise<void> => {
  const requestId = ++currentRequestId;
  
  await fetchCitizens({ 
    search: filters.value.search,
    page: currentPage.value, 
    per_page: 15,
  });
  
  if (requestId === currentRequestId) {
    isSearching.value = false;
  }
};

// ✅ Method baru untuk search via Elasticsearch
const searchData = async (): Promise<void> => {
  const requestId = ++currentRequestId;
  
  await searchCitizens(
    filters.value.search,
    currentPage.value,
    15
  );
  
  if (requestId === currentRequestId) {
    isSearching.value = false;
  }
};

const changePage = (page: number): void => {
  currentPage.value = page;
  isSearching.value = true;
  
  const query = filters.value.search.trim();
  
  if (query.length >= 3) {
    searchData();
  } else {
    fetchData();
  }
};

// ===== MODAL HANDLERS =====

const openRegisterModal = (): void => {
  errorMessage.value = '';
  isRegisterModalOpen.value = true;
};

const handleSubmit = async (payload: RegisterCitizenPayload): Promise<void> => {
  const result = await registerCitizen(payload);
  if (result) {
    isRegisterModalOpen.value = false;
    createdCitizen.value = result.citizen || null;
    accessPin.value = result.access_pin || '';
    showSuccessModal.value = true;
  }
};

const handleCloseSuccessModal = (): void => {
  showSuccessModal.value = false;
  fetchData();
};

const handleResendPin = async (id: string): Promise<void> => {
  await resendPin(id);
  fetchData();
};

const openEditModal = (citizen: RegisteredCitizen): void => {
  selectedCitizen.value = citizen;
  errorMessage.value = '';
  isEditModalOpen.value = true;
};

const handleUpdateCitizen = async (data: UpdateCitizenData): Promise<void> => {
  if (!selectedCitizen.value) return;
  
  try {
    await registrationCitizenApi.update(selectedCitizen.value.id, data);
    isEditModalOpen.value = false;
    fetchData();
  } catch (e: unknown) {
    const apiError = e as ApiError;
    errorMessage.value = apiError.response?.data?.message || 'Gagal memperbarui data.';
  }
};

// ===== LIFECYCLE =====

onMounted(() => {
  fetchData();
});

onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
});
</script>