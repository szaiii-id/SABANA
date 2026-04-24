<template>
  <div class="h-full flex flex-col justify-center max-w-5xl mx-auto py-8 lg:py-12">
    
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
      
      <div class="lg:col-span-5 lg:sticky lg:top-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] mb-6 border border-[#2D6A4F]/20">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        
        <h2 class="text-3xl lg:text-4xl font-[1000] text-gray-900 tracking-tight leading-tight mb-4">
          Perbarui <br/>
          <span class="text-[#2D6A4F]">Kunci Keamanan.</span>
        </h2>
        
        <p class="text-gray-500 leading-relaxed font-medium mb-8">
          PIN ini digunakan untuk masuk ke Portal SABANA dan menyetujui setiap pengajuan bantuan. Hindari menggunakan angka yang mudah ditebak seperti tanggal lahir atau angka berurutan.
        </p>

        <div class="flex items-center gap-4 p-4 rounded-2xl bg-orange-50 border border-orange-100/50 text-orange-800">
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-xs font-bold tracking-wide">PIN yang baru akan otomatis menggantikan PIN lama di semua perangkat.</span>
        </div>
      </div>

      <div class="lg:col-span-7">
        <div class="bg-white rounded-[2rem] p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100">
          
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

          <form @submit.prevent="handleSubmit" class="flex flex-col gap-8">
            
            <div class="flex flex-col gap-3">
              <label class="text-sm font-bold text-gray-700">PIN Saat Ini</label>
              <div class="relative">
                <input 
                  v-model="form.current_pin" 
                  @input="filterNumeric('current_pin')"
                  type="password" 
                  inputmode="numeric"
                  maxlength="6"
                  placeholder="••••••"
                  :class="errors.current_pin ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-[#2D6A4F]/10 focus:border-[#2D6A4F] hover:border-gray-300'"
                  class="w-full px-5 py-4 bg-gray-50/50 rounded-xl text-xl font-black tracking-[0.5em] border-2 outline-none transition-all placeholder:tracking-normal placeholder:font-medium placeholder:text-base placeholder:text-gray-400"
                />
              </div>
              <span v-if="errors.current_pin" class="text-xs font-bold text-red-500">{{ errors.current_pin }}</span>
            </div>

            <div class="h-px bg-gray-100 w-full my-2"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="flex flex-col gap-3">
                <label class="text-sm font-bold text-gray-700">PIN Baru</label>
                <input 
                  v-model="form.new_pin" 
                  @input="filterNumeric('new_pin')"
                  type="password" 
                  inputmode="numeric"
                  maxlength="6"
                  placeholder="••••••"
                  :class="errors.new_pin ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-[#2D6A4F]/10 focus:border-[#2D6A4F] hover:border-gray-300'"
                  class="w-full px-5 py-4 bg-gray-50/50 rounded-xl text-xl font-black tracking-[0.5em] border-2 outline-none transition-all placeholder:tracking-normal placeholder:font-medium placeholder:text-base placeholder:text-gray-400"
                />
                <span v-if="errors.new_pin" class="text-xs font-bold text-red-500">{{ errors.new_pin }}</span>
              </div>

              <div class="flex flex-col gap-3">
                <label class="text-sm font-bold text-gray-700">Konfirmasi PIN Baru</label>
                <input 
                  v-model="form.new_pin_confirmation" 
                  @input="filterNumeric('new_pin_confirmation')"
                  type="password" 
                  inputmode="numeric"
                  maxlength="6"
                  placeholder="••••••"
                  :class="errors.new_pin_confirmation ? 'border-red-300 focus:ring-red-100 focus:border-red-500' : 'border-gray-200 focus:ring-[#2D6A4F]/10 focus:border-[#2D6A4F] hover:border-gray-300'"
                  class="w-full px-5 py-4 bg-gray-50/50 rounded-xl text-xl font-black tracking-[0.5em] border-2 outline-none transition-all placeholder:tracking-normal placeholder:font-medium placeholder:text-base placeholder:text-gray-400"
                />
                <span v-if="errors.new_pin_confirmation" class="text-xs font-bold text-red-500">{{ errors.new_pin_confirmation }}</span>
              </div>
            </div>

            <button 
              type="submit"
              :disabled="isSubmitting"
              class="mt-4 w-full py-4 bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-[#2D6A4F]/25 hover:shadow-[#2D6A4F]/40 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:hover:-translate-y-0 disabled:hover:shadow-none flex items-center justify-center gap-2"
            >
              <svg v-if="isSubmitting" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isSubmitting ? 'Menyimpan Perubahan...' : 'Simpan Kunci Keamanan' }}</span>
            </button>

          </form>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { securityApi } from '../../api/securityApi';
import type { UpdatePinPayload } from '../../types/security';

const form = reactive<UpdatePinPayload>({
  current_pin: '',
  new_pin: '',
  new_pin_confirmation: ''
});

const errors = reactive({
  current_pin: '',
  new_pin: '',
  new_pin_confirmation: ''
});

const notification = reactive({
  type: '', 
  message: ''
});

const isSubmitting = ref(false);

const filterNumeric = (field: keyof UpdatePinPayload) => {
  form[field] = form[field].replace(/\D/g, '');
  errors[field] = ''; 
  notification.message = ''; 
};

const validateForm = () => {
  let isValid = true;
  
  if (form.current_pin.length !== 6) {
    errors.current_pin = 'PIN saat ini harus 6 digit';
    isValid = false;
  }
  if (form.new_pin.length !== 6) {
    errors.new_pin = 'PIN baru harus 6 digit';
    isValid = false;
  }
  if (form.new_pin_confirmation !== form.new_pin) {
    errors.new_pin_confirmation = 'Konfirmasi PIN tidak sesuai';
    isValid = false;
  }
  if (form.current_pin === form.new_pin && form.new_pin.length === 6) {
    errors.new_pin = 'PIN baru tidak boleh sama dengan PIN saat ini';
    isValid = false;
  }

  return isValid;
};

const handleSubmit = async () => {
  if (!validateForm()) return;

  isSubmitting.value = true;
  notification.message = '';
  
  try {
    const response = await securityApi.updatePin(form);
    
    notification.type = 'success';
    notification.message = response.message || 'PIN berhasil diperbarui.';
    
    form.current_pin = '';
    form.new_pin = '';
    form.new_pin_confirmation = '';
    
  } catch (error: any) {
    notification.type = 'error';
    const status = error.response?.status;

    if (status === 422 && error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      errors.current_pin = serverErrors.current_pin ? serverErrors.current_pin[0] : '';
      errors.new_pin = serverErrors.new_pin ? serverErrors.new_pin[0] : '';
      notification.message = 'Periksa kembali data yang Anda masukkan.';
    } 
    else if (status === 400 || status === 401) {
      notification.message = error.response?.data?.message || 'Permintaan ditolak oleh sistem.';
    } 
    else {
      notification.message = 'Terjadi gangguan pada layanan. Silakan coba beberapa saat lagi.';
      console.error('[System Error]:', error.response?.data || error.message);
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>