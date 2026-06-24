<template>
  <div class="h-full flex flex-col justify-center max-w-5xl mx-auto py-8 lg:py-12 px-4 md:px-8">
    
    <div class="grid lg:grid-cols-12 gap-10 lg:gap-16 items-start">
      
      <div class="lg:col-span-5 lg:sticky lg:top-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] mb-6 border border-[#2D6A4F]/20">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        
        <h2 class="text-3xl lg:text-4xl font-[1000] text-gray-900 tracking-tight leading-tight mb-4">
          Keamanan <br/>
          <span class="text-[#2D6A4F]">Akun.</span>
        </h2>
        
        <p class="text-gray-500 leading-relaxed font-medium text-sm lg:text-base">
          Pastikan akun SABANA Anda tetap aman dengan melakukan pembaruan PIN secara berkala. Jangan bagikan PIN Anda kepada siapapun, termasuk petugas.
        </p>
      </div>

      <div class="lg:col-span-7">
        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl shadow-gray-200/40 p-6 md:p-10">

          <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
            <div v-if="notification.message" :class="['mb-8 p-4 rounded-2xl flex items-start gap-3 border', notification.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800']">
              <svg v-if="notification.type === 'success'" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <svg v-else class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <p class="text-xs font-bold leading-relaxed">{{ notification.message }}</p>
            </div>
          </transition>

          <form @submit.prevent="handleSubmit">
            
            <div class="mb-6">
              <label for="input-current-pin" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">PIN Saat Ini</label>
              <input 
                id="input-current-pin"
                v-model="form.current_pin" 
                @input="formatNumeric('current_pin')"
                type="password" 
                inputmode="numeric"
                placeholder="Masukkan PIN Saat Ini"
                :class="['w-full bg-gray-50 border-2 rounded-2xl px-5 py-4 outline-none transition-all font-black text-center tracking-[1em] placeholder:tracking-normal placeholder:text-sm text-gray-800 focus:bg-white', currentPinError ? 'border-red-400 bg-red-50' : 'border-gray-100 focus:border-[#2D6A4F]']"
              />
              <p v-if="currentPinError" class="text-[10px] font-bold text-red-500 mt-2 ml-2 animate-pulse">{{ currentPinError }}</p>
            </div>

            <div class="h-px bg-gray-100 w-full mb-8"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="input-new-pin" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">PIN Baru</label>
                <input 
                  id="input-new-pin"
                  v-model="form.new_pin" 
                  @input="formatNumeric('new_pin')"
                  type="password" 
                  inputmode="numeric"
                  placeholder="6 Digit PIN Baru"
                  :class="['w-full bg-gray-50 border-2 rounded-2xl px-5 py-4 outline-none transition-all font-black text-center tracking-[1em] placeholder:tracking-normal placeholder:text-sm placeholder:font-bold placeholder:text-gray-300 text-xl text-gray-800 focus:bg-white', newPinError ? 'border-red-400 bg-red-50' : 'border-gray-100 focus:border-[#2D6A4F]']"
                />
                <p v-if="newPinError" class="text-[10px] font-bold text-red-500 mt-2 ml-2 animate-pulse">{{ newPinError }}</p>
              </div>

              <div>
                <label for="input-confirm-pin" class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Konfirmasi PIN</label>
                <input 
                  id="input-confirm-pin"
                  v-model="form.new_pin_confirmation" 
                  @input="formatNumeric('new_pin_confirmation')"
                  type="password" 
                  inputmode="numeric"
                  placeholder="Ulangi PIN Baru"
                  :class="['w-full bg-gray-50 border-2 rounded-2xl px-5 py-4 outline-none transition-all font-black text-center tracking-[1em] placeholder:tracking-normal placeholder:text-sm placeholder:font-bold placeholder:text-gray-300 text-xl text-gray-800 focus:bg-white', confirmPinError ? 'border-red-400 bg-red-50' : 'border-gray-100 focus:border-[#2D6A4F]']"
                />
                <p v-if="confirmPinError" class="text-[10px] font-bold text-red-500 mt-2 ml-2 animate-pulse">{{ confirmPinError }}</p>
              </div>
            </div>

            <button 
              type="submit" 
              :disabled="isSubmitting"
              class="w-full mt-8 py-5 bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white rounded-2xl font-black uppercase tracking-[0.2em] text-xs hover:shadow-xl hover:shadow-[#2D6A4F]/20 hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center"
            >
              <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ isSubmitting ? 'MEMPROSES...' : 'PERBARUI PIN' }}
            </button>
          </form>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { securityApi } from '../../api/securityApi';
import { getSafeErrorMessage } from '../../utils/errorHandler';

// ===== TYPES =====
interface ApiError {
  response?: {
    status?: number;
    data?: {
      message?: string;
      errors?: Record<string, string[]>;
    };
  };
}

type FormField = 'current_pin' | 'new_pin' | 'new_pin_confirmation';

// ===== COMPOSABLES =====
const router = useRouter();

// ===== STATE =====
const isSubmitting = ref(false);
const notification = reactive({ type: '' as string, message: '' });

const form = reactive({
  current_pin: '',
  new_pin: '',
  new_pin_confirmation: '',
});

// ===== REAL-TIME VALIDATION =====
const currentPinError = computed<string>(() => {
  if (!form.current_pin) return '';
  if (form.current_pin.length !== 6) return 'PIN harus 6 digit';
  return '';
});

const newPinError = computed<string>(() => {
  if (!form.new_pin) return '';
  if (form.new_pin.length !== 6) return 'PIN harus 6 digit';
  if (form.current_pin.length === 6 && form.new_pin === form.current_pin) return 'PIN baru tidak boleh sama dengan PIN saat ini';
  return '';
});

const confirmPinError = computed<string>(() => {
  if (!form.new_pin_confirmation) return '';
  if (form.new_pin.length === 6 && form.new_pin_confirmation !== form.new_pin) return 'Konfirmasi PIN tidak sesuai';
  return '';
});

// ===== METHODS =====
const formatNumeric = (field: FormField): void => {
  form[field] = form[field].replace(/\D/g, '').substring(0, 6);
  notification.message = '';
};

const validateForm = (): boolean => {
  if (!form.current_pin || !form.new_pin || !form.new_pin_confirmation) return false;
  return !currentPinError.value && !newPinError.value && !confirmPinError.value;
};

const handleSubmit = async (): Promise<void> => {
  if (!validateForm()) {
    notification.type = 'error';
    notification.message = 'Semua field wajib diisi dengan benar.';
    return;
  }

  isSubmitting.value = true;
  notification.message = '';

  try {
    await securityApi.updatePin({ ...form });

    localStorage.removeItem('sabana_token');

    await router.replace({
      name: 'login',
      query: {
        message: 'PIN berhasil diperbarui. Silakan login dengan PIN baru Anda.',
      },
    });
  } catch (error: unknown) {
    const apiError = error as ApiError;
    notification.type = 'error';
    const status = apiError.response?.status;

    if (status === 422 && apiError.response?.data?.errors) {
      const serverErrors = apiError.response.data.errors;
      if (serverErrors.current_pin) notification.message = serverErrors.current_pin[0];
      else if (serverErrors.new_pin) notification.message = serverErrors.new_pin[0];
      notification.message = notification.message || 'Periksa kembali data yang Anda masukkan.';
    } else if (status === 422) {
      notification.message = apiError.response?.data?.message || 'Periksa kembali data Anda.';
    } else if (status === 400 || status === 401) {
      notification.message = getSafeErrorMessage(apiError.response?.data?.message || 'Permintaan ditolak.');
    } else {
      notification.message = getSafeErrorMessage(apiError.response?.data?.message || 'Terjadi kesalahan sistem.');
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>