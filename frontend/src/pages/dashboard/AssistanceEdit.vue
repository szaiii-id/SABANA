<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAssistance } from '../../composables/useAssistance';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload } from '../../types/assistance';

import AssistanceStep2 from '../../components/assistance/AssistanceStep2.vue';
import AssistanceStep3 from '../../components/assistance/AssistanceStep3.vue';

// Konstanta untuk menghindari error TypeScript pada komponen
const EMPTY_PROGRAM: AssistanceProgramSchema = {
  id: '0',
  title: 'Memuat...',
  badge: '',
  iconSvg: '',
  inputs: [],
  files: []
};

const router = useRouter();
const { 
  fetchPrograms, fetchRegencies, fetchDistricts, fetchVillages, updateAssistance,
  programs, regencies, districts, villages, isLoading, fetchDetail 
} = useAssistance();

const currentStep = ref(2);
const selectedProgram = ref<AssistanceProgramSchema | null>(null);
const notification = reactive({ type: '', message: '' });
const registrationId = ref(''); 
const registrationNumber = ref('');
const isDataReady = ref(false);

const showConfirmModal = ref(false);
const isProcessingLong = ref(false);
const isSuccess = ref(false);

const existingEvidences = ref<Record<string, string>>({});

const formData = reactive<AssistanceSubmissionPayload>({
  program_id: '', regency_id: '', district_id: '', village_id: '',  
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: {}, files: {},
});

onMounted(async () => {
  const draft = localStorage.getItem('SABANA_DRAFT');
  if (!draft) { router.push({ name: 'history' }); return; }

  const { id } = JSON.parse(draft);
  registrationId.value = id;

  try {
    isLoading.value = true;
    
    // 1. Tarik Data Utama
    const data = await fetchDetail(id); 
    
    // 2. Mapping Program
    await fetchPrograms();
    const pId = data.program_id || (data.program ? data.program.id : null);
    
    selectedProgram.value = programs.value.find(p => String(p.id) === String(pId)) || null;
    if (!selectedProgram.value && programs.value.length > 0) {
        selectedProgram.value = programs.value[0];
    }
    formData.program_id = selectedProgram.value?.id || pId;

    // 3. Mapping Statis
    registrationNumber.value = data.registration_number;
    formData.disbursement_method = data.disbursement_method;
    formData.bank_account_number = data.bank_account_number || '';

    // 4. Mapping Dinamis
    let dynamicInputs = typeof data.submission_data === 'string' ? JSON.parse(data.submission_data) : (data.submission_data || {});
    formData.dynamicInputs = { ...dynamicInputs };

    if (data.evidences && Array.isArray(data.evidences)) {
        data.evidences.forEach((ev: any) => {
            existingEvidences.value[ev.image_type] = ev.image_url;
        });
    }

    // 5. Mapping Wilayah
    await fetchRegencies();
    
    formData.regency_id = String(data.regency_id || data.regency?.id || '');
    
    if (formData.regency_id) {
        await fetchDistricts(formData.regency_id);
        formData.district_id = String(data.district_id || data.district?.id || '');
        
        if (formData.district_id) {
            await fetchVillages(formData.district_id);
            formData.village_id = String(data.village_id || data.village?.id || '');
        }
    }

    isDataReady.value = true;
    // notification.message sudah dihapus di sini
  } catch (err) {
    showError("Gagal memuat data dari server.");
    console.error("Critical Load Error:", err);
  } finally {
    isLoading.value = false;
  }
});

const showError = (msg: string) => {
  notification.type = 'error';
  notification.message = msg;
};

const handleRegencyChange = async () => {
  formData.district_id = ''; formData.village_id = '';
  if (formData.regency_id) await fetchDistricts(formData.regency_id);
};

const handleDistrictChange = async () => {
  formData.village_id = '';
  if (formData.district_id) await fetchVillages(formData.district_id);
};

const requestUpdate = () => {
  showConfirmModal.value = true;
};

const executeUpdate = async () => {
  showConfirmModal.value = false;
  notification.message = '';
  isProcessingLong.value = false;

  const processingTimer = setTimeout(() => {
    if (isLoading.value) isProcessingLong.value = true;
  }, 2000);

  try {
    await updateAssistance(registrationId.value, formData);
    clearTimeout(processingTimer);
    localStorage.removeItem('SABANA_DRAFT');
    isSuccess.value = true;
  } catch (error: any) {
    clearTimeout(processingTimer);
    isProcessingLong.value = false;
    showError(error.response?.data?.message || 'Gagal menyimpan perubahan.');
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-8 font-sans relative">
    
    <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
      <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl animate-fade-in text-center">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
        </div>
        <h3 class="text-xl font-black text-slate-800 mb-2">Simpan Perubahan?</h3>
        <p class="text-slate-500 text-sm mb-8">Pastikan data dan foto yang Anda unggah sudah benar. Data akan dikirim ulang untuk ditinjau oleh Admin.</p>
        <div class="flex gap-3">
          <button @click="showConfirmModal = false" class="flex-1 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all">Batal</button>
          <button @click="executeUpdate" class="flex-1 py-3 bg-amber-500 text-white font-bold rounded-xl shadow-lg shadow-amber-200 hover:-translate-y-1 transition-all">Ya, Simpan</button>
        </div>
      </div>
    </div>

    <div v-if="isSuccess" class="max-w-3xl mx-auto mt-10 bg-white rounded-3xl p-8 md:p-12 text-center shadow-xl border border-slate-100 animate-fade-in">
      <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
      </div>
      <h2 class="text-3xl font-extrabold mb-2 text-slate-800">Perbaikan Berhasil!</h2>
      <p class="text-slate-500 mb-8 font-medium">Data Anda dengan Kode Lacak <b class="text-slate-700">{{ registrationNumber }}</b> telah diperbarui dan sedang menunggu tinjauan ulang.</p>
      <button @click="$router.push({ name: 'history' })" class="block w-full bg-[#2D6A4F] hover:bg-[#1B4332] text-white font-bold py-4 rounded-xl transition-all shadow-lg">
        Kembali ke Riwayat
      </button>
    </div>

    <div v-else-if="isProcessingLong" class="max-w-2xl mx-auto mt-10 bg-white rounded-3xl p-12 text-center shadow-xl border border-blue-50 animate-fade-in">
      <h2 class="text-2xl font-black text-slate-800 mb-4">Menyimpan Perubahan...</h2>
      <p class="text-slate-500 mb-8 leading-relaxed text-sm">Sistem sedang mengunggah berkas baru Anda ke server. Harap tunggu sebentar.</p>
    </div>

    <div v-else class="max-w-6xl mx-auto bg-white rounded-3xl shadow-sm overflow-hidden relative">
      <div v-if="isLoading && !isDataReady" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-2xl shadow-2xl border border-slate-100 font-bold text-slate-700">Memproses...</div>
      </div>

      <div class="bg-amber-600 p-8 text-white relative">
        <div class="absolute top-4 right-4 bg-white/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Mode Revisi</div>
        <h1 class="text-3xl font-bold mb-1">Perbaiki Pengajuan</h1>
        <p class="text-amber-100 text-sm tracking-wide font-medium">
          Program: <strong v-if="selectedProgram">{{ selectedProgram.title }}</strong>
        </p>
      </div>

      <div class="p-8 min-h-[400px]">
        <div v-if="notification.message" class="mb-6 p-4 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl font-bold">
          {{ notification.message }}
        </div>

        <transition name="fade" mode="out-in">
          <div v-if="!isDataReady" key="loading-state" class="text-center py-10 text-slate-500">Memuat data...</div>

          <AssistanceStep2 
            v-else-if="currentStep === 2" 
            :key="registrationId + '-step2'"
            :formData="formData" 
            :selectedProgram="selectedProgram || EMPTY_PROGRAM" 
            :regencies="regencies" 
            :districts="districts" 
            :villages="villages"
            @regencyChange="handleRegencyChange" 
            @districtChange="handleDistrictChange"
            @prev="$router.push({ name: 'history' })" 
            @next="currentStep = 3" 
            @errorMsg="showError"
          />
          
          <AssistanceStep3 
            v-else-if="currentStep === 3" 
            :key="registrationId + '-step3'"
            :formData="formData" 
            :selectedProgram="selectedProgram || EMPTY_PROGRAM" 
            :isLoading="isLoading"
            :existingEvidences="existingEvidences"
            @prev="currentStep = 2" 
            @submit="requestUpdate" 
            @errorMsg="showError"
          />
        </transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>