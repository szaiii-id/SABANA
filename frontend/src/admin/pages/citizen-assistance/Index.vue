<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <div class="max-w-6xl mx-auto px-4 py-6 md:px-6 space-y-5">
      
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center shadow-md">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">Ajukan Bantuan</h1>
          <p class="text-[13px] text-slate-500 font-medium">Ajukan bantuan untuk warga yang sudah terdaftar</p>
        </div>
      </div>

      <CitizenSearchSection
        :query="searchQuery"
        :results="searchResults"
        :selected="selectedCitizen"
        :isSearching="isSearching"
        :hasSearched="hasSearched"
        :hasMore="hasMoreResults"
        :isLoadingMore="isLoadingMore"
        @search="handleSearch"
        @select="handleSelectCitizen"
        @remove="handleRemoveCitizen"
        @loadMore="loadMoreCitizens"
      />

      <ProgramSelectSection
        v-if="selectedCitizen"
        :citizenName="selectedCitizen.full_name"
        :programs="programs"
        :selectedId="selectedProgram?.id ?? null"
        :loading="isLoadingPrograms"
        @select="handleSelectProgram"
        @unselect="handleUnselectProgram"
      />

      <SubmissionFormSection
        v-if="selectedCitizen && selectedProgram && !showSuccess"
        :formData="formData"
        :selectedProgram="selectedProgram"
        :regencies="regencies"
        :districts="districts"
        :villages="villages"
        :isSubmitting="isSubmitting"
        :error="errorMessage"
        :success="''"
        :registrationNumber="''"
        @submit="handleSubmit"
        @regencyChange="handleRegencyChange"
        @districtChange="handleDistrictChange"
        @cancel="handleRemoveCitizen"
        @update:error="errorMessage = $event"
      />

      <div v-if="showSuccess" class="bg-white rounded-[2rem] p-8 shadow-sm ring-1 ring-slate-200/60 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-1">{{ successMessage }}</h3>
        <p class="text-sm text-slate-500 mb-4">No. Registrasi: {{ lastRegistrationNumber }}</p>
        <button @click="handleReset" class="px-6 py-2.5 bg-[#1B4332] text-white text-sm font-bold rounded-xl hover:bg-[#2D6A4F] transition-all">
          Ajukan Lagi
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue';
import CitizenSearchSection from '../../components/citizen-assistance/CitizenSearchSection.vue';
import ProgramSelectSection from '../../components/citizen-assistance/ProgramSelectSection.vue';
import SubmissionFormSection from '../../components/citizen-assistance/SubmissionFormSection.vue';
import { useCitizenAssistance } from '../../composables/useCitizenAssistance';
import { useRegion } from '../../composables/useRegion';
import type { AssistanceSubmissionPayload } from '../../../types/assistance';

const { 
  searchResults, selectedCitizen, programs, selectedProgram,
  isSubmitting, errorMessage, successMessage, isSearching,
  hasMoreResults, isLoadingMore,
  searchCitizen, loadMoreCitizens, selectCitizen, fetchPrograms,
  selectProgram, submitAssistance, resetForm 
} = useCitizenAssistance();

const { regencies, districts, villages, fetchRegencies, fetchDistricts, fetchVillages } = useRegion();

const searchQuery = ref('');
const hasSearched = ref(false);
const isLoadingPrograms = ref(false);
const showSuccess = ref(false);
const lastRegistrationNumber = ref('');
const previewUrls = ref<string[]>([]);

const formData = reactive<AssistanceSubmissionPayload>({
  program_id: '', regency_id: '', district_id: '', village_id: '',  
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: {}, files: {},
});

const revokePreviewUrls = () => {
  previewUrls.value.forEach(url => {
    if (url.startsWith('blob:')) URL.revokeObjectURL(url);
  });
  previewUrls.value = [];
};

const resetFormData = () => {
  formData.program_id = '';
  formData.regency_id = '';
  formData.district_id = '';
  formData.village_id = '';
  formData.bank_account_number = '';
  formData.disbursement_method = 'village_cash';
  formData.dynamicInputs = {};
  formData.files = {};
};

onMounted(async () => {
  isLoadingPrograms.value = true;
  try {
    await fetchPrograms();
    await fetchRegencies();
  } finally {
    isLoadingPrograms.value = false;
  }
});

onBeforeUnmount(() => {
  revokePreviewUrls();
});

const handleSearch = (query: string) => {
  searchQuery.value = query;
  hasSearched.value = true;
  searchCitizen(query);
};

const handleSelectCitizen = (citizen: any) => {
  selectCitizen(citizen);
  searchQuery.value = '';
  hasSearched.value = false;
};

const handleRemoveCitizen = () => {
  revokePreviewUrls();
  resetForm();
  resetFormData();
  searchQuery.value = '';
  hasSearched.value = false;
  showSuccess.value = false;
};

const handleSelectProgram = (program: any) => {
  selectProgram(program);
  formData.program_id = program.id;
  formData.dynamicInputs = {};
  formData.files = {};
  errorMessage.value = '';
  program.inputs.forEach((input: any) => {
    formData.dynamicInputs[input.key] = '';
  });
};

const handleUnselectProgram = () => {
  revokePreviewUrls();
  selectedProgram.value = null;
  formData.program_id = '';
  formData.dynamicInputs = {};
  formData.files = {};
  errorMessage.value = '';
};

const handleRegencyChange = async () => {
  formData.district_id = ''; 
  formData.village_id = '';
  if (formData.regency_id) await fetchDistricts(formData.regency_id);
};

const handleDistrictChange = async () => {
  formData.village_id = '';
  if (formData.district_id) await fetchVillages(formData.district_id);
};

const handleSubmit = async () => {
  if (!selectedCitizen.value || !selectedProgram.value) return;

  const payload = {
    citizen_id: selectedCitizen.value.id,
    program_id: formData.program_id,
    regency_id: formData.regency_id,
    district_id: formData.district_id,
    village_id: formData.village_id,
    disbursement_method: formData.disbursement_method,
    bank_account_number: formData.bank_account_number,
    dynamicInputs: formData.dynamicInputs,  
    files: formData.files,
  };

  const result = await submitAssistance(payload);
  if (result) {
    lastRegistrationNumber.value = result.data?.registration_number || '';
    showSuccess.value = true;
  }
};

const handleReset = () => {
  handleRemoveCitizen();
};
</script>