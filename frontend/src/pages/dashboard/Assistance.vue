<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useAssistance } from '../../composables/useAssistance';
import type { AssistanceProgramSchema, AssistanceSubmissionPayload } from '../../types/assistance';

// Import Komponen Anak
import AssistanceStep1 from '../../components/assistance/AssistanceStep1.vue';
import AssistanceStep2 from '../../components/assistance/AssistanceStep2.vue';
import AssistanceStep3 from '../../components/assistance/AssistanceStep3.vue';
import { CheckBadgeIcon, RocketLaunchIcon, ArrowPathIcon } from '@heroicons/vue/24/solid';

const { 
  submitAssistance, fetchPrograms, fetchRegencies, fetchDistricts, fetchVillages,
  programs, regencies, districts, villages, isLoading 
} = useAssistance();

const currentStep = ref(1);
const selectedProgram = ref<AssistanceProgramSchema | null>(null);
const isSuccess = ref(false);
const isProcessingLong = ref(false);
const registrationNumber = ref('');
const notification = reactive({ type: '', message: '' });

const formData = reactive<AssistanceSubmissionPayload>({
  program_id: '', regency_id: '', district_id: '', village_id: '',  
  disbursement_method: 'village_cash', bank_account_number: '',
  dynamicInputs: {}, files: {},
});

onMounted(async () => {
  try {
    await fetchPrograms();
    await fetchRegencies();
  } catch (error) {
    showError('Gagal memuat data. Silakan muat ulang halaman.');
  }
});

const showError = (msg: string) => {
  notification.type = 'error';
  notification.message = msg;
};

const handleSelectProgram = (program: AssistanceProgramSchema) => {
  selectedProgram.value = program;
  formData.program_id = program.id;
  formData.dynamicInputs = {};
  formData.files = {};
  notification.message = '';
  currentStep.value = 2;
};

const handleRegencyChange = () => {
  formData.district_id = ''; formData.village_id = '';
  if (formData.regency_id) fetchDistricts(formData.regency_id);
};

const handleDistrictChange = () => {
  formData.village_id = '';
  if (formData.district_id) fetchVillages(formData.district_id);
};

const getFilePreview = (file: File) => {
  return URL.createObjectURL(file);
};

watch(() => formData, (newVal) => {
  const draft = JSON.parse(JSON.stringify(newVal));
  localStorage.setItem('SABANA_DRAFT', JSON.stringify(draft)); 
}, { deep: true });

const handleSubmit = async () => {
  notification.message = '';
  isProcessingLong.value = false;

  const finalPayload = JSON.parse(JSON.stringify(formData));
  localStorage.setItem('SABANA_DRAFT', JSON.stringify(finalPayload));

  const processingTimer = setTimeout(() => {
    if (isLoading.value) isProcessingLong.value = true;
  }, 2000);

  try {
    const res = await submitAssistance(formData);
    clearTimeout(processingTimer);
    
    localStorage.removeItem('SABANA_DRAFT');
    
    registrationNumber.value = res.data.registration_number;
    isSuccess.value = true;
    isProcessingLong.value = false;
  } catch (error: any) {
    clearTimeout(processingTimer);
    isProcessingLong.value = false;
    const serverMessage = error.response?.data?.message;
    showError(serverMessage || 'Gagal mengirim pengajuan.');
  }
};

// Menentukan judul header dinamis
const displayHeaderTitle = computed(() => {
  if (currentStep.value === 1) return 'Pilih Program';
  return selectedProgram.value?.title || 'Data Pengajuan';
});
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-8 font-sans antialiased text-slate-900">
    
    <div v-if="isSuccess" class="max-w-4xl mx-auto mt-6">
      <div class="bg-white rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-200 border border-white">
        <div class="bg-[#2D6A4F] p-12 text-center text-white relative">
          <div class="relative z-10">
            <CheckBadgeIcon class="w-20 h-20 text-emerald-400 mx-auto mb-4 drop-shadow-lg" />
            <h2 class="text-4xl font-black mb-2 italic">BERHASIL TERKIRIM!</h2>
            <p class="text-emerald-100/80 font-medium">Data Anda sedang masuk dalam sistem antrian verifikasi</p>
          </div>
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 -mr-20 -mt-20 rounded-full blur-3xl"></div>
        </div>

        <div class="p-8 md:p-12">
          <div class="flex flex-col items-center mb-12 text-center">
             <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4">Kode Lacak Registrasi</span>
             <div class="bg-slate-50 border-2 border-dashed border-slate-200 px-12 py-6 rounded-3xl group transition-all hover:border-[#2D6A4F]">
                <span class="text-4xl md:text-6xl font-black text-slate-800 tracking-widest font-mono">{{ registrationNumber }}</span>
             </div>
             <p class="mt-4 text-xs text-slate-400">Silakan tangkap layar (screenshot) halaman ini sebagai bukti.</p>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div v-for="(file, key) in formData.files" :key="key" class="relative aspect-square rounded-3xl overflow-hidden shadow-md group">
              <img :src="getFilePreview(file)" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-125" />
              <div class="absolute inset-0 bg-black/40 flex items-end p-4">
                <span class="text-white text-[9px] font-black uppercase tracking-widest truncate">{{ String(key).replace(/_/g, ' ') }}</span>
              </div>
            </div>
          </div>

          <button @click="$router.push({ name: 'history' })" class="w-full py-5 bg-slate-900 text-white rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-[#2D6A4F] transition-all shadow-xl shadow-slate-200">
            Lihat Riwayat Sekarang
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="isProcessingLong" class="max-w-2xl mx-auto mt-20 text-center animate-fade-in">
       <div class="relative w-24 h-24 mx-auto mb-8">
          <div class="absolute inset-0 border-4 border-slate-200 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-[#2D6A4F] rounded-full border-t-transparent animate-spin"></div>
       </div>
       <h2 class="text-3xl font-black text-slate-800 mb-2">MENGUNGGAH BERKAS...</h2>
       <p class="text-slate-500 font-medium px-8">Server sedang memproses data biner Anda. Jangan tutup halaman ini sampai proses selesai.</p>
    </div>

    <div v-else class="max-w-5xl mx-auto">
      
      <div class="bg-white rounded-[2.5rem] p-4 shadow-sm border border-slate-100 mb-6 flex items-center justify-between px-8">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white font-black text-xl">
            {{ currentStep }}
          </div>
          <div>
            <h4 class="text-slate-800 font-black text-lg leading-none mb-1">{{ displayHeaderTitle }}</h4>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest">Langkah {{ currentStep }} dari 3</p>
          </div>
        </div>
        <div class="hidden md:flex items-center gap-2">
            <div v-for="s in 3" :key="s" :class="[currentStep >= s ? 'w-8 bg-[#2D6A4F]' : 'w-2 bg-slate-200']" class="h-2 rounded-full transition-all duration-500"></div>
        </div>
      </div>

      <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden relative min-h-[500px]">
        
        <div v-if="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex flex-col items-center justify-center">
            <div class="flex flex-col items-center animate-bounce">
              <RocketLaunchIcon class="w-16 h-16 text-[#2D6A4F] mb-4" />
              <span class="font-black text-slate-800 uppercase tracking-widest">SABANA SENDING DATA...</span>
            </div>
        </div>

        <div v-if="notification.message" class="mx-8 mt-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 flex items-center gap-3 font-bold rounded-r-xl">
           <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
           {{ notification.message }}
        </div>

        <div class="p-8 md:p-12">
          <transition name="page" mode="out-in">
            <AssistanceStep1 
              v-if="currentStep === 1" :programs="programs" 
              @select="handleSelectProgram" 
            />
            <AssistanceStep2 
              v-else-if="currentStep === 2 && selectedProgram" 
              :formData="formData" :selectedProgram="selectedProgram" 
              :regencies="regencies" :districts="districts" :villages="villages"
              @regencyChange="handleRegencyChange" @districtChange="handleDistrictChange"
              @prev="currentStep = 1" @next="currentStep = 3" @errorMsg="showError"
            />
            <AssistanceStep3 
              v-else-if="currentStep === 3 && selectedProgram" 
              :formData="formData" :selectedProgram="selectedProgram" :isLoading="isLoading"
              @prev="currentStep = 2" @submit="handleSubmit" @errorMsg="showError"
            />
          </transition>
        </div>

        <div class="p-8 bg-slate-50 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
           <div class="flex items-center gap-2">
              <span class="font-black text-slate-800 text-sm tracking-tight">SABANA</span>
              <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
              <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sarana Bantuan Anak Banua</span>
           </div>
           <div class="flex items-center gap-1 text-[10px] font-bold text-slate-400">
              <ArrowPathIcon class="w-3 h-3" />
              Sistem Terenkripsi End-to-End
           </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Transisi Halaman (Samping) */
.page-enter-active, .page-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.page-enter-from { opacity: 0; transform: translateX(20px); }
.page-leave-to { opacity: 0; transform: translateX(-20px); }

.animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Custom Scrollbar untuk tampilan modern */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #CBD5E1; }
</style>