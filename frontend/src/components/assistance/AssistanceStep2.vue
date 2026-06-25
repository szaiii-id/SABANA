<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload, Region } from '../../types/assistance';
import { MapPinIcon, CreditCardIcon, IdentificationIcon } from '@heroicons/vue/24/outline';
import ConfirmModal from '../common/ConfirmModal.vue';
import TabDomisili from './fields/TabDomisili.vue';
import TabDataDiri from './fields/TabDataDiri.vue';
import TabPenyaluran from './fields/TabPenyaluran.vue';

const props = defineProps<{
  formData: AssistanceSubmissionPayload;
  selectedProgram: AssistanceProgramSchema;
  regencies: Region[];
  districts: Region[];
  villages: Region[];
  revisionItems?: string[];
}>();

const emit = defineEmits(['next', 'prev', 'regencyChange', 'districtChange', 'errorMsg']);

const fieldErrors = reactive<Record<string, string>>({});
const activeTab = ref<'domisili' | 'data' | 'dana'>('domisili');
const showBackModal = ref(false);

const revItems = computed(() => props.revisionItems || []);

const isTabValid = (tab: string): boolean => {
  switch (tab) {
    case 'domisili':
      return !!props.formData.regency_id && !!props.formData.district_id && !!props.formData.village_id;
    case 'data':
      if (!props.selectedProgram.inputs.length) return true;
      return props.selectedProgram.inputs.every(input => !!props.formData.dynamicInputs[input.key]);
    case 'dana':
      return true;
    default:
      return false;
  }
};

const isCurrentTabValid = computed(() => isTabValid(activeTab.value));

const canProceed = computed(() => isCurrentTabValid.value);

const clearError = (key: string) => { delete fieldErrors[key]; };

const handleTabClick = (key: 'domisili' | 'data' | 'dana') => {
  if (key === 'data' && !isTabValid('domisili')) return;
  if (key === 'dana' && (!isTabValid('domisili') || !isTabValid('data'))) return;
  activeTab.value = key;
};

const handleNextTab = () => {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);
  
  if (activeTab.value === 'domisili') {
    if (!props.formData.regency_id) { fieldErrors.regency_id = 'Kabupaten wajib dipilih'; return; }
    if (!props.formData.district_id) { fieldErrors.district_id = 'Kecamatan wajib dipilih'; return; }
    if (!props.formData.village_id) { fieldErrors.village_id = 'Desa/Kelurahan wajib dipilih'; return; }
  }
  
  if (activeTab.value === 'data') {
    let hasError = false;
    props.selectedProgram.inputs.forEach(input => {
      const value = props.formData.dynamicInputs[input.key];
      
      // Cek empty
      if (
        value === undefined || 
        value === null || 
        (typeof value === 'string' && value.trim() === '')
      ) {
        fieldErrors[input.key] = `${input.label} wajib diisi`;
        hasError = true;
        return;
      }
      
      if (['number', 'decimal', 'currency'].includes(input.type)) {
        const numValue = Number(String(value).replace(/[^\d.-]/g, ''));
        if (isNaN(numValue)) {
          fieldErrors[input.key] = `${input.label} harus berupa angka`;
          hasError = true;
        }
      }
    });
    if (hasError) {
      emit('errorMsg', 'Mohon lengkapi semua data yang diperlukan.');
      return;
    }
  }

  activeTab.value = activeTab.value === 'domisili' ? 'data' : 'dana';
};

const handleBack = () => {
  const hasData = props.formData.regency_id || props.formData.district_id || props.formData.village_id 
    || Object.values(props.formData.dynamicInputs).some(v => v)
    || props.formData.bank_account_number;

  if (hasData) {
    showBackModal.value = true;
  } else {
    emit('prev');
  }
};

const confirmBack = () => {
  showBackModal.value = false;
  emit('prev');
};

const validateAndSubmit = () => {
  Object.keys(fieldErrors).forEach(key => delete fieldErrors[key]);

  if (props.formData.disbursement_method === 'bpd_transfer' && !props.formData.bank_account_number) {
    fieldErrors.bank_account_number = 'Nomor Rekening wajib diisi untuk transfer BPD';
    emit('errorMsg', 'Mohon isi nomor rekening BPD.');
    return;
  }

  emit('next');
};

const tabs = [
  { key: 'domisili', label: 'Domisili', icon: MapPinIcon },
  { key: 'data', label: 'Data Diri', icon: IdentificationIcon },
  { key: 'dana', label: 'Penyaluran', icon: CreditCardIcon },
] as const;
</script>

<template>
  <div class="animate-fade-in">
    
    <div class="mb-8 text-center">
      <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#FDF8F1] rounded-full mb-4">
        <span class="w-2 h-2 bg-[#2D6A4F] rounded-full animate-pulse"></span>
        <span class="text-[9px] font-black text-[#2D6A4F] uppercase tracking-[0.3em]">Langkah 2 dari 3</span>
      </div>
      <h3 class="text-2xl font-black text-[#1B4332] tracking-tight">Lengkapi Data</h3>
      <p class="text-[#8B5E3C]/50 text-[11px] font-medium mt-1">Pastikan semua informasi terisi dengan benar.</p>
    </div>

    <div class="flex bg-[#FDF8F1] rounded-2xl p-1.5 mb-6">
      <button 
        v-for="tab in tabs" :key="tab.key"
        @click="handleTabClick(tab.key)"
        :class="[
          'flex-1 flex items-center justify-center gap-2 py-3 rounded-xl text-[11px] font-bold transition-all duration-300',
          activeTab === tab.key 
            ? 'bg-white text-[#2D6A4F] shadow-sm' 
            : 'text-[#8B5E3C]/60 hover:text-[#4A3728]'
        ]"
      >
        <component :is="tab.icon" class="w-4 h-4" />
        {{ tab.label }}
      </button>
    </div>

    <form @submit.prevent="validateAndSubmit" novalidate>
      
      <TabDomisili
        v-show="activeTab === 'domisili'"
        :regencyId="formData.regency_id"
        :districtId="formData.district_id"
        :villageId="formData.village_id"
        :regencies="regencies"
        :districts="districts"
        :villages="villages"
        :regencyError="fieldErrors.regency_id"
        :districtError="fieldErrors.district_id"
        :villageError="fieldErrors.village_id"
        @update:regencyId="formData.regency_id = $event; emit('regencyChange')"
        @update:districtId="formData.district_id = $event; emit('districtChange')"
        @update:villageId="formData.village_id = $event"
        @clearError="clearError"
      />

      <TabDataDiri
        v-show="activeTab === 'data'"
        :inputs="selectedProgram.inputs"
        :modelValue="formData.dynamicInputs"
        :errors="fieldErrors"
        :revisionItems="revItems"
        @update:modelValue="formData.dynamicInputs = { ...formData.dynamicInputs, ...$event }"
        @clearError="clearError"
      />

      <TabPenyaluran
        v-show="activeTab === 'dana'"
        :modelValue="formData.disbursement_method"
        :bankAccountNumber="formData.bank_account_number"
        :bankAccountError="fieldErrors.bank_account_number"
        @update:modelValue="formData.disbursement_method = ($event as 'village_cash' | 'bpd_transfer')"
        @update:bankAccountNumber="formData.bank_account_number = $event"
        @clearError="clearError"
      />

      <div class="flex items-center justify-between pt-8">
        <button type="button" @click="handleBack" class="text-[#8B5E3C] font-black text-[10px] tracking-[0.2em] uppercase hover:text-[#2D6A4F] transition-all">← Kembali</button>
        
        <div class="flex gap-2">
          <button 
            v-if="activeTab !== 'dana'"
            type="button"
            :disabled="!canProceed"
            @click="handleNextTab"
            :class="[
              'px-8 py-4 font-black text-[11px] uppercase tracking-[0.3em] rounded-2xl transition-all',
              canProceed 
                ? 'bg-[#2D6A4F] text-white shadow-lg shadow-green-900/20 hover:scale-105 active:scale-95' 
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]"
          >
            Lanjut
          </button>
          <button 
            v-else
            type="submit"
            :disabled="!canProceed"
            :class="[
              'px-8 py-4 font-black text-[11px] uppercase tracking-[0.3em] rounded-2xl transition-all',
              canProceed 
                ? 'bg-[#2D6A4F] text-white shadow-lg shadow-green-900/20 hover:scale-105 active:scale-95' 
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]"
          >
            Verifikasi Berkas →
          </button>
        </div>
      </div>
    </form>

    <ConfirmModal
      :open="showBackModal"
      title="Yakin Kembali?"
      message="Data yang sudah Anda isi akan hilang dan tidak bisa dikembalikan."
      variant="warning"
      confirm-text="Ya, Kembali"
      cancel-text="Batal"
      @close="showBackModal = false"
      @confirm="confirmBack"
    />
  </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>