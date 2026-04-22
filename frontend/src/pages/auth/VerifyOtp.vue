<template>
  <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8 relative w-full">
    
    <div id="auth-bg" class="absolute inset-0 z-0 overflow-hidden pointer-events-none bg-[#fdfaf5]">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[30%] right-[5%] w-[50%] h-[50%] bg-[#2D6A4F]/10 rounded-full blur-[110px] animate-blob animation-delay-4000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div class="relative w-full max-w-lg bg-white/70 backdrop-blur-2xl border border-white shadow-[0_20px_50px_rgba(45,106,79,0.08)] rounded-[2.5rem] p-8 sm:p-12 z-10">
      
      <div class="mb-10 text-center">
        <div class="inline-flex w-16 h-16 bg-white rounded-2xl items-center justify-center shadow-lg shadow-[#2D6A4F]/10 mb-6 border border-gray-100 transform rotate-3">
          <svg class="w-8 h-8 text-[#2D6A4F] transform -rotate-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        </div>
        <h2 class="text-3xl font-black text-[#081c15] tracking-tight mb-2">
          Verifikasi Identitas
        </h2>
        <p class="text-gray-500 text-sm leading-relaxed font-medium">
          Kode 6-digit telah dikirim melalui WhatsApp ke:<br>
          <span class="font-black text-[#D4A373] text-lg mt-1 block tracking-widest">{{ maskedWaNumber }}</span>
        </p>
      </div>

      <form @submit.prevent="submitOtp" class="space-y-8">
        
        <transition 
          enter-active-class="transition duration-300 ease-out" 
          enter-from-class="transform -translate-y-4 opacity-0" 
          enter-to-class="transform translate-y-0 opacity-100" 
          leave-active-class="transition duration-200 ease-in" 
          leave-from-class="transform translate-y-0 opacity-100" 
          leave-to-class="transform -translate-y-4 opacity-0"
        >
          <div v-if="safeErrorMessage" class="flex items-center p-4 rounded-2xl bg-red-50/90 border border-red-100 backdrop-blur-sm mb-4">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="ml-3 text-sm font-bold text-red-700 leading-snug">{{ safeErrorMessage }}</p>
          </div>
        </transition>

        <transition 
          enter-active-class="transition duration-300 ease-out" 
          enter-from-class="transform -translate-y-4 opacity-0" 
          enter-to-class="transform translate-y-0 opacity-100" 
          leave-active-class="transition duration-200 ease-in" 
          leave-from-class="transform translate-y-0 opacity-100" 
          leave-to-class="transform -translate-y-4 opacity-0"
        >
          <div v-if="successMessage" class="flex items-center p-4 rounded-2xl bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 backdrop-blur-sm mb-4">
            <svg class="w-5 h-5 text-[#2D6A4F] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="ml-3 text-sm font-bold text-[#1b4332] leading-snug">{{ successMessage }}</p>
          </div>
        </transition>

        <div>
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4 text-center">Masukkan Kode OTP</label>
          <div class="flex justify-between gap-2 sm:gap-3">
            <input
              v-for="(digit, index) in 6"
              :key="index"
              ref="otpInputRefs"
              v-model="otpArray[index]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="w-12 h-14 sm:w-16 sm:h-16 text-center text-3xl font-black text-[#081c15] bg-white/60 border-2 border-gray-200/60 rounded-2xl focus:bg-white focus:border-[#2D6A4F] focus:ring-4 focus:ring-[#2D6A4F]/15 focus:outline-none transition-all duration-300 shadow-sm"
              :class="{'border-red-300 focus:border-red-500 focus:ring-red-500/15 bg-red-50/50': safeErrorMessage}"
              @input="handleInput(index, $event)"
              @keydown="handleKeydown(index, $event)"
              @paste="handlePaste"
              :disabled="isLoading"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading || otpArray.join('').length !== 6"
          class="group relative w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-2xl text-base font-bold text-white bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] hover:shadow-lg hover:shadow-[#2D6A4F]/30 focus:outline-none focus:ring-4 focus:ring-[#2D6A4F]/40 transition-all duration-300 overflow-hidden disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white/80" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span v-if="!isLoading" class="tracking-wide">Aktifkan Akun</span>
          <span v-else class="tracking-wide">Memproses...</span>
          
          <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
        </button>
      </form>

      <div class="mt-10 pt-8 border-t border-gray-100 flex flex-col items-center">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Tidak menerima kode?</span>
        <button 
          @click="resendOtp" 
          :disabled="timer > 0 || isResending"
          class="text-sm font-black transition-all duration-300 focus:outline-none tracking-wide text-[#D4A373] hover:text-[#b88c60] hover:underline"
        >
          <span v-if="isResending" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            MENGIRIM ULANG...
          </span>
          <span v-else-if="timer > 0">Kirim ulang dalam {{ timer }} detik</span>
          <span v-else>KIRIM ULANG SEKARANG</span>
        </button>

        <div class="mt-8 pt-8 border-t border-gray-100/50 w-full flex flex-col items-center">
          <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 text-center leading-relaxed">
            Ada kesalahan data NIK, KK, atau WhatsApp?
          </p>
          <router-link 
            to="/register" 
            class="flex items-center gap-2 text-sm font-black text-[#2D6A4F] hover:text-[#1b4332] transition-all hover:scale-105"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            PERBAIKI DATA
          </router-link>
        </div>
      </div>

    </div>

    <SuccessModal :show="showSuccessModal" @confirm="handleSuccessConfirm" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthService from '../../services/AuthService';
import SuccessModal from '../../components/auth/SuccessModal.vue';

const route = useRoute();
const router = useRouter();

const nikNumber = (route.query.nik as string) || '';
const waNumber = (route.query.wa as string) || '';

const showSuccessModal = ref(false);
const successMessage = ref('');

const handleSuccessConfirm = () => {
  showSuccessModal.value = false;
  // Saat sukses verifikasi OTP, lempar ke login agar bisa masuk menggunakan PIN
  window.location.href = '/auth/login';
};

const maskedWaNumber = computed(() => {
  if (!waNumber) return '';
  const length = waNumber.length;
  if (length < 8) return waNumber;
  return `${waNumber.slice(0, 4)} •••• ${waNumber.slice(-4)}`;
});

const otpArray = ref(['', '', '', '', '', '']);
const otpInputRefs = ref<HTMLInputElement[]>([]);
const isLoading = ref(false);
const isResending = ref(false);
const errorMessage = ref('');
const timer = ref(60);
let intervalId: ReturnType<typeof setInterval>;

// PENAMBAHAN: Filter Keamanan Error (Anti-Bocor)
const safeErrorMessage = computed(() => {
  if (!errorMessage.value) return '';
  const err = errorMessage.value.toLowerCase();
  
  if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || errorMessage.value.length > 80) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return errorMessage.value;
});

watch(otpArray, (newVal) => {
  if (newVal.join('').length === 6 && !isLoading.value) {
    submitOtp();
  }
}, { deep: true });

onMounted(() => {
  if (!nikNumber || !waNumber) {
    router.replace({ name: 'register' }); 
    return;
  }
  startTimer();
  nextTick(() => {
    if (otpInputRefs.value[0]) otpInputRefs.value[0].focus();
  });
});

onUnmounted(() => {
  clearInterval(intervalId);
});

const startTimer = () => {
  timer.value = 60;
  clearInterval(intervalId);
  intervalId = setInterval(() => {
    if (timer.value > 0) timer.value--;
    else clearInterval(intervalId);
  }, 1000);
};

const handleInput = (index: number, event: Event) => {
  const input = event.target as HTMLInputElement;
  const value = input.value;
  errorMessage.value = '';
  successMessage.value = ''; 

  if (!/^\d$/.test(value)) {
    otpArray.value[index] = '';
    return;
  }

  if (value && index < 5) {
    nextTick(() => otpInputRefs.value[index + 1].focus());
  }
};

const handleKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpArray.value[index] && index > 0) {
    nextTick(() => otpInputRefs.value[index - 1].focus());
  }
};

const handlePaste = (event: ClipboardEvent) => {
  event.preventDefault();
  const pastedData = event.clipboardData?.getData('text/plain').trim();
  
  if (pastedData && /^\d{6}$/.test(pastedData)) {
    const digits = pastedData.split('');
    digits.forEach((digit, index) => {
      if (index < 6) otpArray.value[index] = digit;
    });
    nextTick(() => otpInputRefs.value[5].focus());
  }
};

const submitOtp = async () => {
  const otpCode = otpArray.value.join('');
  if (otpCode.length !== 6) return;

  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await AuthService.verifyOtp({
      nik: nikNumber,
      whatsapp_number: waNumber,
      otp: otpCode
    });
    
    showSuccessModal.value = true;
    
  } catch (error: any) {
    if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors;
      errorMessage.value = validationErrors?.otp?.[0] || validationErrors?.nik?.[0] || 'Kode OTP tidak valid.';
    } else {
      // Biarkan ini di-handle oleh computed safeErrorMessage
      errorMessage.value = error.response?.data?.message || 'Terjadi kesalahan sistem server.';
    }
    otpArray.value = ['', '', '', '', '', ''];
    nextTick(() => otpInputRefs.value[0].focus());
  } finally {
    isLoading.value = false;
  }
};

const resendOtp = async () => {
  if (timer.value > 0) return;
  isResending.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await AuthService.resendOtp({ nik: nikNumber, whatsapp_number: waNumber });
    
    successMessage.value = 'Kode OTP baru telah dikirim ke WhatsApp Anda.';
    startTimer();
    
    setTimeout(() => {
      successMessage.value = '';
    }, 5000);
    
  } catch (error: any) {
    if (error.response?.status === 422 && error.response.data.errors?.otp) {
      errorMessage.value = error.response.data.errors.otp[0];
    } else {
      errorMessage.value = error.response?.data?.message || 'Gagal mengirim ulang kode OTP.';
    }
  } finally {
    isResending.value = false;
  }
};
</script>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 15s infinite alternate ease-in-out; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }
</style>