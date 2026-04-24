<template>
  <div class="h-full flex flex-col justify-center max-w-5xl mx-auto py-8 lg:py-12">
    
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
      
      <div class="lg:col-span-5 lg:sticky lg:top-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#D4A373]/10 text-[#D4A373] mb-6 border border-[#D4A373]/20">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        
        <h2 class="text-3xl lg:text-4xl font-[1000] text-gray-900 tracking-tight leading-tight mb-4">
          Data Diri <br/>
          <span class="text-[#D4A373]">Penduduk.</span>
        </h2>
        
        <p class="text-gray-500 leading-relaxed font-medium mb-8">
          Pastikan nama lengkap dan nomor WhatsApp Anda selalu aktif. Nomor WhatsApp akan digunakan sebagai sarana komunikasi utama jika terjadi kendala pada akun Anda.
        </p>

        <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-200 text-gray-600">
          <svg class="w-5 h-5 shrink-0 mt-0.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <div class="flex flex-col">
            <span class="text-xs font-bold tracking-wide text-gray-800">Kenapa NIK dikunci?</span>
            <span class="text-[11px] font-medium mt-1">Nomor Induk Kependudukan (NIK) dan Kartu Keluarga (KK) bersifat permanen dan mengikat pada identitas akun dasar Anda.</span>
          </div>
        </div>
      </div>

      <div class="lg:col-span-7">
        <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 relative min-h-[400px]">
          
          <div v-if="isLoadingData" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-[2rem] z-10 flex items-center justify-center">
             <svg class="animate-spin h-8 w-8 text-[#D4A373]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div 
              v-if="notification.message" 
              :class="notification.type === 'success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-600'"
              class="flex items-center gap-3 p-4 mb-8 rounded-xl border text-sm font-bold"
            >
              <svg v-if="notification.type === 'success'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ notification.message }}
            </div>
          </transition>

          <form @submit.prevent="handlePreSubmit" class="flex flex-col gap-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="flex flex-col gap-3">
                <label class="text-sm font-bold text-gray-700">Nomor Induk Kependudukan</label>
                <input 
                  v-model="formData.nik" 
                  type="text" 
                  disabled
                  class="w-full px-5 py-4 bg-gray-100/70 text-gray-500 rounded-xl text-base font-bold border border-gray-200 cursor-not-allowed"
                />
              </div>

              <div class="flex flex-col gap-3">
                <label class="text-sm font-bold text-gray-700">Nomor Kartu Keluarga</label>
                <input 
                  v-model="formData.family_card_number" 
                  type="text" 
                  disabled
                  class="w-full px-5 py-4 bg-gray-100/70 text-gray-500 rounded-xl text-base font-bold border border-gray-200 cursor-not-allowed"
                />
              </div>
            </div>

            <div class="h-px bg-gray-100 w-full my-1"></div>

            <div class="flex flex-col gap-3">
              <label class="text-sm font-bold text-gray-700">Nama Lengkap (Sesuai KTP)</label>
              <input 
                v-model="formData.full_name" 
                @input="clearError('full_name')"
                type="text" 
                placeholder="Masukkan nama lengkap"
                :class="errors.full_name ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-[#D4A373]/10 focus:border-[#D4A373] hover:border-gray-300'"
                class="w-full px-5 py-4 bg-gray-50/50 rounded-xl text-base font-bold border-2 outline-none transition-all uppercase"
              />
              <span v-if="errors.full_name" class="text-xs font-bold text-red-500">{{ errors.full_name }}</span>
            </div>

            <div class="flex flex-col gap-3">
              <label class="text-sm font-bold text-gray-700">Nomor WhatsApp Aktif</label>
              <input 
                v-model="formData.whatsapp_number" 
                @input="filterPhone"
                type="text" 
                inputmode="numeric"
                placeholder="Contoh: 081234567890"
                :class="errors.whatsapp_number ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-[#D4A373]/10 focus:border-[#D4A373] hover:border-gray-300'"
                class="w-full px-5 py-4 bg-gray-50/50 rounded-xl text-base font-bold border-2 outline-none transition-all tracking-wide"
              />
              <span v-if="errors.whatsapp_number" class="text-xs font-bold text-red-500">{{ errors.whatsapp_number }}</span>
            </div>

            <button 
              type="submit"
              :disabled="isSubmitting || isLoadingData"
              class="mt-4 w-full py-4 bg-gradient-to-r from-[#D4A373] to-[#BC8A5F] text-white rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-[#D4A373]/25 hover:shadow-[#D4A373]/40 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:hover:-translate-y-0 disabled:hover:shadow-none flex items-center justify-center gap-2"
            >
              <svg v-if="isSubmitting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Data Diri' }}</span>
            </button>

          </form>
        </div>
      </div>
      
    </div>

    <transition
      enter-active-class="ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm">
        <transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-8 scale-95"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 scale-100"
          leave-to-class="opacity-0 translate-y-8 scale-95"
        >
          <div v-if="showConfirmModal" class="bg-white rounded-[2rem] w-full max-w-sm p-8 shadow-2xl relative">
            
            <div class="w-16 h-16 bg-orange-50 rounded-[1.5rem] flex items-center justify-center mb-6 border border-orange-100">
              <svg class="w-8 h-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
              </svg>
            </div>

            <h3 class="text-xl font-[1000] text-gray-900 tracking-tight mb-2">Konfirmasi Simpan</h3>
            <p class="text-sm font-medium text-gray-500 mb-8 leading-relaxed">
              Apakah Anda yakin data yang dimasukkan sudah benar? Kesalahan penulisan nomor WhatsApp dapat menghambat proses pencairan bantuan.
            </p>

            <div class="flex flex-col gap-3">
              <button 
                @click="executeSubmit"
                class="w-full py-3.5 bg-gradient-to-r from-[#D4A373] to-[#BC8A5F] text-white rounded-xl font-bold text-sm tracking-wide shadow-md shadow-[#D4A373]/20 hover:shadow-lg hover:shadow-[#D4A373]/40 hover:-translate-y-0.5 transition-all duration-300"
              >
                Ya, Simpan Data
              </button>
              <button 
                @click="showConfirmModal = false"
                class="w-full py-3.5 bg-white text-gray-500 rounded-xl font-bold text-sm tracking-wide border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition-colors"
              >
                Batal
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { profileApi } from '../../api/profileApi';

const formData = reactive({
  nik: '',
  family_card_number: '',
  full_name: '',
  whatsapp_number: ''
});

const errors = reactive({
  full_name: '',
  whatsapp_number: ''
});

const notification = reactive({
  type: '', 
  message: ''
});

const isLoadingData = ref(true);
const isSubmitting = ref(false);
const showConfirmModal = ref(false);

const fetchProfile = async () => {
  isLoadingData.value = true;
  try {
    const data = await profileApi.getProfile();
    formData.nik = data.nik;
    formData.family_card_number = data.family_card_number;
    formData.full_name = data.full_name;
    formData.whatsapp_number = data.whatsapp_number;
  } catch (error) {
    notification.type = 'error';
    notification.message = 'Gagal memuat data profil. Silakan muat ulang halaman.';
  } finally {
    isLoadingData.value = false;
  }
};

onMounted(() => {
  fetchProfile();
});

const clearError = (field: keyof typeof errors) => {
  errors[field] = '';
  notification.message = '';
};

const filterPhone = () => {
  formData.whatsapp_number = formData.whatsapp_number.replace(/\D/g, '');
  clearError('whatsapp_number');
};

const validateForm = () => {
  let isValid = true;
  
  if (!formData.full_name.trim()) {
    errors.full_name = 'Nama lengkap wajib diisi';
    isValid = false;
  }
  
  if (!formData.whatsapp_number || formData.whatsapp_number.length < 10) {
    errors.whatsapp_number = 'Nomor WhatsApp tidak valid (minimal 10 angka)';
    isValid = false;
  }

  return isValid;
};

const handlePreSubmit = () => {
  if (validateForm()) {
    showConfirmModal.value = true;
  }
};

const executeSubmit = async () => {
  showConfirmModal.value = false;
  isSubmitting.value = true;
  notification.message = '';
  
  try {
    const payload = {
      full_name: formData.full_name,
      whatsapp_number: formData.whatsapp_number
    };
    
    const response = await profileApi.updateProfile(payload);
    
    notification.type = 'success';
    notification.message = response.message || 'Data diri berhasil diperbarui.';

    const currentUserData = localStorage.getItem('user');
    if (currentUserData) {
      const parsed = JSON.parse(currentUserData);
      parsed.full_name = formData.full_name;
      localStorage.setItem('user', JSON.stringify(parsed));
      
      window.dispatchEvent(new Event('storage'));
    }
    
  } catch (error: any) {
    notification.type = 'error';
    const status = error.response?.status;

    if (status === 422 && error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      errors.full_name = serverErrors.full_name ? serverErrors.full_name[0] : '';
      errors.whatsapp_number = serverErrors.whatsapp_number ? serverErrors.whatsapp_number[0] : '';
      notification.message = 'Periksa kembali data yang Anda masukkan.';
    } 
    else if (status === 400 || status === 401) {
      notification.message = error.response?.data?.message || 'Permintaan ditolak oleh sistem.';
    } 
    else {
      notification.message = 'Terjadi gangguan pada layanan. Silakan coba beberapa saat lagi.';
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>