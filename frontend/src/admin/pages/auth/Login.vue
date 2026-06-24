<template>
  <AdminAuthLayout maxWidth="md">
    
    <!-- Session Alert -->
    <transition 
      enter-active-class="transition duration-500 ease-out" 
      enter-from-class="transform -translate-y-6 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100" 
      leave-active-class="transition duration-300 ease-in" 
      leave-from-class="transform translate-y-0 opacity-100" 
      leave-to-class="transform -translate-y-6 opacity-0"
    >
      <div v-if="sessionAlert" class="mb-6 bg-[#FFF8E1] border-l-4 border-[#FF8F00] p-4 rounded-r-2xl shadow-md flex items-start gap-3 -mt-4">
        <div class="w-8 h-8 bg-[#FF8F00]/10 rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-[#FF8F00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>
        <div class="flex-grow">
          <h3 class="text-[10px] font-black text-[#E65100] uppercase tracking-widest mb-1">Peringatan Keamanan</h3>
          <p class="text-xs font-semibold text-[#BF360C] leading-relaxed">{{ sessionAlert }}</p>
        </div>
        <button @click="sessionAlert = ''" class="text-[#FF8F00] hover:text-[#E65100] transition-colors p-1 rounded-full hover:bg-[#FF8F00]/10">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </transition>

    <!-- Brand Header -->
    <div class="text-center mb-10">
      <div class="inline-block mb-4">
        <h1 class="text-4xl font-black italic tracking-tight text-[#1B4332] leading-none">
          SABANA
        </h1>
        <div class="flex items-center justify-center gap-3 mt-1">
          <div class="h-px w-8 bg-gradient-to-r from-transparent to-[#D4A373]"></div>
          <span class="text-xs font-bold tracking-[0.3em] text-[#D4A373] uppercase">Center</span>
          <div class="h-px w-8 bg-gradient-to-l from-transparent to-[#D4A373]"></div>
        </div>
      </div>
      <p class="text-xs font-semibold text-[#6B705C] uppercase tracking-[0.2em]">Portal Administrasi</p>
    </div>

    <!-- Login Form -->
    <form @submit.prevent="onSubmit" class="space-y-5">
      
      <!-- Server Error -->
      <transition 
        enter-active-class="transition duration-300 ease-out" 
        enter-from-class="transform -translate-y-4 opacity-0" 
        enter-to-class="transform translate-y-0 opacity-100"
      >
        <div v-if="safeAuthError" class="p-4 rounded-2xl bg-[#FEF2F2] border border-[#FCA5A5] shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-[#FEE2E2] rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-[#DC2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </div>
            <p class="text-sm font-bold text-[#991B1B]">{{ safeAuthError }}</p>
          </div>
        </div>
      </transition>

      <!-- NIP Input -->
      <div>
        <label class="block text-xs font-black text-[#1B4332] uppercase tracking-widest mb-2 ml-1">
          NIP Pegawai
        </label>
        <div class="relative group">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg 
              class="h-5 w-5 transition-colors duration-300" 
              :class="validationErrors.nip ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" 
              fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
            </svg>
          </div>
          <input 
            ref="nipInput"
            v-model="formData.nip" 
            @input="formatNumeric('nip', 18)"
            type="text" 
            inputmode="numeric" 
            placeholder="Masukkan 18 digit NIP Anda"
            :disabled="isRateLimited"
            :class="[
              'w-full pl-12 pr-5 py-4 border-2 rounded-2xl outline-none transition-all duration-300 font-semibold placeholder:font-medium shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed',
              validationErrors.nip 
                ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10' 
                : 'border-[#E8D5C4] bg-white focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10 hover:border-[#D4A373]'
            ]"
          />
        </div>
        <p v-if="validationErrors.nip" class="text-[10px] font-bold text-[#DC2626] mt-1 ml-1">{{ validationErrors.nip }}</p>
        <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1 ml-1">Gunakan NIP resmi dari kepegawaian</p>
      </div>

      <!-- Password Input -->
      <div>
        <label class="block text-xs font-black text-[#1B4332] uppercase tracking-widest mb-2 ml-1">
          Kata Sandi
        </label>
        <div class="relative group">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg 
              class="h-5 w-5 transition-colors duration-300" 
              :class="validationErrors.password ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#D4A373]'" 
              fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <input 
            ref="passwordInput"
            v-model="formData.password" 
            :type="showPassword ? 'text' : 'password'"
            placeholder="Masukkan kata sandi"
            :disabled="isRateLimited"
            :class="[
              'w-full pl-12 pr-12 py-4 border-2 rounded-2xl outline-none transition-all duration-300 font-bold text-lg tracking-wider placeholder:font-medium placeholder:tracking-normal shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed',
              validationErrors.password 
                ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:border-[#DC2626] focus:ring-4 focus:ring-[#DC2626]/10' 
                : 'border-[#E8D5C4] bg-white focus:border-[#D4A373] focus:ring-4 focus:ring-[#D4A373]/10 hover:border-[#D4A373]'
            ]"
          />
          <button 
            type="button"
            @click="showPassword = !showPassword"
            :disabled="isRateLimited"
            class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-[#6B705C] transition-colors disabled:cursor-not-allowed"
          >
            <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
            </svg>
          </button>
        </div>
        <p v-if="validationErrors.password" class="text-[10px] font-bold text-[#DC2626] mt-1 ml-1">{{ validationErrors.password }}</p>
        <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1 ml-1">Gunakan kata sandi yang telah diberikan</p>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center px-1 pt-1">
        <label class="flex items-center gap-3 cursor-pointer group">
          <div class="relative flex items-center justify-center">
            <input 
              type="checkbox" 
              v-model="formData.rememberNip"
              :disabled="isRateLimited"
              class="peer appearance-none w-5 h-5 border-2 border-[#D4A373] rounded-lg checked:bg-[#1B4332] checked:border-[#1B4332] transition-all cursor-pointer focus:ring-4 focus:ring-[#1B4332]/10 disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
              <path d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <span class="text-xs font-semibold text-[#6B705C] group-hover:text-[#1B4332] transition-colors select-none">
            Ingat NIP Saya
          </span>
        </label>
      </div>

      <!-- Submit Button -->
      <button 
        :disabled="isSubmitting || isRateLimited" 
        type="submit" 
        class="group relative w-full flex justify-center items-center py-5 mt-4 border border-transparent rounded-2xl text-sm font-black text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] uppercase tracking-[0.2em] hover:shadow-2xl hover:shadow-[#1B4332]/25 transition-all duration-500 overflow-hidden disabled:opacity-60 disabled:cursor-not-allowed"
      >
        <span class="relative z-10 flex items-center gap-2">
          <template v-if="isRateLimited">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Terkunci
          </template>
          <template v-else-if="isSubmitting">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Memverifikasi...
          </template>
          <template v-else>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            Masuk ke Sistem
          </template>
        </span>
        <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-white/0 via-white/10 to-white/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out z-0"></div>
      </button>
    </form>
  </AdminAuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../../composables/useAuth';
import { getSafeErrorMessage } from '../../utils/errorHandler';
import AdminAuthLayout from '../../layouts/AdminAuthLayout.vue';

const router = useRouter();
const { isSubmitting, authError, isRateLimited, submitLogin } = useAuth();

const safeAuthError = computed(() => getSafeErrorMessage(authError.value));

const sessionAlert = ref('');
const showPassword = ref(false);
const nipInput = ref<HTMLInputElement | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);

const validationErrors = ref({
  nip: '',
  password: ''
});

const formData = ref({
  nip: '',
  password: '',
  rememberNip: false
});

onMounted(() => {
  const isSessionExpired = localStorage.getItem('admin_session_expired');
  const savedNip = localStorage.getItem('remembered_nip');
  
  if (savedNip) {
    formData.value.nip = savedNip;
    formData.value.rememberNip = true;
    nextTick(() => {
      passwordInput.value?.focus();
    });
  } else {
    nextTick(() => {
      nipInput.value?.focus();
    });
  }
  
  if (isSessionExpired) {
    sessionAlert.value = 'Sesi Anda telah berakhir otomatis demi keamanan. Silakan masuk kembali untuk melanjutkan pekerjaan.';
    localStorage.removeItem('admin_session_expired'); 
  }
});

const formatNumeric = (field: 'nip', maxLength: number) => {
  formData.value[field] = formData.value[field].replace(/\D/g, '').substring(0, maxLength);
  clearErrors();
};

const clearErrors = () => {
  if (authError.value) authError.value = '';
  validationErrors.value.nip = '';
  validationErrors.value.password = '';
};

const validateForm = (): boolean => {
  let isValid = true;
  validationErrors.value.nip = '';
  validationErrors.value.password = '';

  if (!formData.value.nip) {
    validationErrors.value.nip = 'NIP wajib diisi.';
    isValid = false;
  } else if (formData.value.nip.length !== 18) {
    validationErrors.value.nip = 'NIP harus 18 digit.';
    isValid = false;
  }

  if (!formData.value.password) {
    validationErrors.value.password = 'Kata sandi wajib diisi.';
    isValid = false;
  } else if (formData.value.password.length < 8) {
    validationErrors.value.password = 'Kata sandi minimal 8 karakter.';
    isValid = false;
  }

  return isValid;
};

const onSubmit = async () => {
  if (isRateLimited.value) return;
  
  clearErrors();

  if (!validateForm()) return;

  if (formData.value.rememberNip) {
    localStorage.setItem('remembered_nip', formData.value.nip);
  } else {
    localStorage.removeItem('remembered_nip');
  }

  const result = await submitLogin({
    nip: formData.value.nip,
    password: formData.value.password
  });

  if (result.success) {
    const adminPrefix = import.meta.env.VITE_ADMIN_PORTAL_PREFIX || 'sabana-center-63';
    router.push(`/${adminPrefix}/dashboard`);
  }
};
</script>