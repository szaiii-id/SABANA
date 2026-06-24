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
          <svg class="w-8 h-8 text-[#2D6A4F] transform -rotate-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
        </div>
        <h2 class="text-3xl font-black text-[#081c15] tracking-tight mb-2">Verifikasi OTP</h2>
        <p class="text-gray-500 text-sm leading-relaxed font-medium">
          Masukkan 6 digit kode yang dikirim ke WhatsApp<br>
          <span class="font-black text-[#D4A373] text-lg mt-1 block tracking-widest">{{ maskedWaNumber }}</span>
        </p>
      </div>

      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="safeError" class="flex items-center p-4 rounded-2xl bg-red-50/90 border border-red-100 backdrop-blur-sm mb-4">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="ml-3 text-sm font-bold text-red-700 leading-snug">{{ safeError }}</p>
        </div>
      </transition>

      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="successMessage" class="flex items-center p-4 rounded-2xl bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 backdrop-blur-sm mb-4">
          <svg class="w-5 h-5 text-[#2D6A4F] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="ml-3 text-sm font-bold text-[#1b4332] leading-snug">{{ successMessage }}</p>
        </div>
      </transition>

      <form @submit.prevent="verifyOtpCode">
        <div class="mb-10">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-4 text-center">Masukkan Kode OTP</label>
          <div class="flex justify-center gap-3 sm:gap-4" @paste="handlePaste">
            <input
              v-for="(_, index) in otpArray"
              :key="index"
              :ref="el => { if (el) otpInputRefs[index] = el as HTMLInputElement }"
              v-model="otpArray[index]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="w-12 h-14 sm:w-16 sm:h-20 text-center text-3xl font-black text-[#081c15] bg-white/60 border-2 border-gray-200/60 rounded-2xl focus:bg-white focus:border-[#2D6A4F] focus:ring-4 focus:ring-[#2D6A4F]/15 focus:outline-none transition-all duration-300 shadow-sm"
              :class="{'border-red-300 focus:border-red-500 focus:ring-red-500/15 bg-red-50/50': safeError}"
              @input="handleOtpInput(index, $event)"
              @keydown="handleOtpKeydown(index, $event)"
              :disabled="isLoading"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading || !isOtpComplete"
          class="group relative w-full flex justify-center items-center py-5 border border-transparent rounded-2xl text-sm font-black text-white bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] uppercase tracking-[0.2em] hover:shadow-lg hover:shadow-[#2D6A4F]/30 focus:outline-none focus:ring-4 focus:ring-[#2D6A4F]/40 transition-all duration-300 overflow-hidden disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!isLoading" class="relative z-10">AKTIFKAN AKUN</span>
          <span v-else class="relative z-10">VERIFIKASI...</span>
          <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out"></div>
        </button>
      </form>

      <div class="mt-10 pt-8 border-t border-gray-100 flex flex-col items-center">
        <div class="flex items-center gap-4 mb-4">
          <button 
            type="button"
            @click="changePhoneNumber"
            class="text-xs font-bold text-gray-400 hover:text-[#D4A373] transition-colors flex items-center gap-1"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Ganti Nomor WhatsApp
          </button>
        </div>
        
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Tidak menerima kode?</span>
        <button 
          type="button"
          @click="resendOtp"
          :disabled="timer > 0 || isResending"
          class="text-sm font-black transition-all duration-300 focus:outline-none tracking-wide"
          :class="timer > 0 || isResending ? 'text-gray-400 cursor-not-allowed' : 'text-[#D4A373] hover:text-[#b88c60] hover:underline'"
        >
          <span v-if="isResending" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            MENGIRIM ULANG...
          </span>
          <span v-else-if="timer > 0">Kirim ulang dalam {{ timerDisplay }}</span>
          <span v-else>KIRIM ULANG KODE</span>
        </button>
      </div>

    </div>

    <SuccessModal :show="showSuccessModal" @confirm="handleSuccessConfirm" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AuthService from '../../services/AuthService';
import SuccessModal from '../../components/auth/SuccessModal.vue';
import { getSafeErrorMessage } from '../../utils/errorHandler';
import { formatDuration } from '../../composables/useFormatDuration';

// ===== TYPES =====
interface ApiError {
  response?: {
    data?: {
      message?: string;
    };
  };
}

// ===== COMPOSABLES =====
const route = useRoute();
const router = useRouter();

// ===== STATE =====
const waNumber = (route.query.wa as string) || '';
const nikNumber = (route.query.nik as string) || '';

const otpArray = ref(['', '', '', '', '', '']);
const otpInputRefs = ref<HTMLInputElement[]>([]);
const isLoading = ref(false);
const isResending = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const timer = ref(60);
const showSuccessModal = ref(false);

let interval: ReturnType<typeof setInterval> | null = null;
let successTimeout: ReturnType<typeof setTimeout> | null = null;

// ===== COMPUTED =====
const maskedWaNumber = computed(() => {
  const wa = waNumber || '';
  if (wa.length < 8) return wa;
  return wa.substring(0, 4) + '****' + wa.substring(wa.length - 4);
});

const timerDisplay = computed(() => formatDuration(timer.value));
const safeError = computed(() => getSafeErrorMessage(errorMessage.value));
const isOtpComplete = computed(() => otpArray.value.every((d) => d !== ''));

// ===== LIFECYCLE =====
onMounted(() => {
  if (!nikNumber || !waNumber) {
    router.replace({ name: 'register' });
    return;
  }
  startTimer();
  nextTick(() => otpInputRefs.value[0]?.focus());
});

onUnmounted(() => {
  if (interval) clearInterval(interval);
  if (successTimeout) clearTimeout(successTimeout);
});

// ===== METHODS =====
const startTimer = () => {
  timer.value = 60;
  if (interval) clearInterval(interval);
  interval = setInterval(() => {
    if (timer.value > 0) timer.value--;
    else {
      if (interval) clearInterval(interval);
      interval = null;
    }
  }, 1000);
};

const changePhoneNumber = () => {
  router.push({ 
    name: 'register', 
    query: { nik: nikNumber, wa: waNumber, edit: 'true' } 
  });
};

const handleOtpInput = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement;
  const val = target.value.replace(/\D/g, '');
  otpArray.value[index] = val;
  errorMessage.value = '';
  successMessage.value = '';
  if (val && index < 5) nextTick(() => otpInputRefs.value[index + 1]?.focus());
};

const handleOtpKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !otpArray.value[index] && index > 0) {
    otpInputRefs.value[index - 1]?.focus();
  }
};

const handlePaste = (event: ClipboardEvent) => {
  event.preventDefault();
  const data = event.clipboardData?.getData('text').replace(/\D/g, '').substring(0, 6);
  if (data) {
    data.split('').forEach((char, i) => { if (i < 6) otpArray.value[i] = char; });
    nextTick(() => otpInputRefs.value[Math.min(data.length, 5)]?.focus());
  }
};

const verifyOtpCode = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    await AuthService.verifyOtp({ nik: nikNumber, whatsapp_number: waNumber, otp: otpArray.value.join('') });
    showSuccessModal.value = true;
  } catch (error: unknown) {
    const apiError = error as ApiError;
    errorMessage.value = apiError.response?.data?.message || 'Kode OTP salah.';
    otpArray.value = ['', '', '', '', '', ''];
    nextTick(() => otpInputRefs.value[0]?.focus());
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
    if (successTimeout) clearTimeout(successTimeout);
    successTimeout = setTimeout(() => { successMessage.value = ''; }, 5000);
  } catch (error: unknown) {
    const apiError = error as ApiError;
    errorMessage.value = apiError.response?.data?.message || 'Gagal mengirim ulang kode OTP.';
  } finally {
    isResending.value = false;
  }
};

const handleSuccessConfirm = () => { 
  showSuccessModal.value = false; 
  router.push({ name: 'login' }); 
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