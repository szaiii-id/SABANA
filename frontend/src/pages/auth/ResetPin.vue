<template>
  <div class="min-h-screen bg-[#fdfaf5] flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div class="w-full max-w-md bg-white/70 backdrop-blur-2xl rounded-[2.5rem] p-8 sm:p-12 shadow-xl border border-white relative z-10">
      
      <div class="text-center mb-10">
        <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Verifikasi OTP</h1>
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
          Untuk NIK: {{ formData.nik }}
        </p>
      </div>

      <form @submit.prevent="handleReset" class="space-y-6">
        
        <transition enter-active-class="animate-fade-in">
          <div v-if="safeErrorMessage" class="p-4 rounded-2xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100 uppercase tracking-wider">
            {{ safeErrorMessage }}
          </div>
        </transition>

        <transition enter-active-class="animate-fade-in">
          <div v-if="successMessage" class="flex items-center p-4 rounded-2xl bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 backdrop-blur-sm mb-4">
            <svg class="w-5 h-5 text-[#2D6A4F] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="ml-3 text-[10px] font-bold text-[#1b4332] leading-snug uppercase tracking-wider">{{ successMessage }}</p>
          </div>
        </transition>

        <div class="relative pb-6">
          <input 
            v-model="formData.otp" 
            @input="formatNumeric('otp', 6)"
            @blur="v$.otp.$touch()"
            type="text" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl text-gray-800', v$.otp.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.otp.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Kode OTP WA</label>
          <span v-if="v$.otp.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.otp.$errors[0].$message }}</span>
        </div>

        <div class="relative pb-6">
          <input 
            v-model="formData.new_pin" 
            @input="formatNumeric('new_pin', 6)"
            @blur="v$.new_pin.$touch()"
            type="password" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl text-gray-800', v$.new_pin.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.new_pin.$error ? 'text-red-500' : 'text-[#D4A373]']">PIN Baru (6 Digit)</label>
          <span v-if="v$.new_pin.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.new_pin.$errors[0].$message }}</span>
        </div>

        <div class="relative pb-6">
          <input 
            v-model="formData.new_pin_confirmation" 
            @input="formatNumeric('new_pin_confirmation', 6)"
            @blur="v$.new_pin_confirmation.$touch()"
            type="password" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl text-gray-800', v$.new_pin_confirmation.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.new_pin_confirmation.$error ? 'text-red-500' : 'text-[#D4A373]']">Ulangi PIN Baru</label>
          <span v-if="v$.new_pin_confirmation.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.new_pin_confirmation.$errors[0].$message }}</span>
        </div>

        <button 
          :disabled="isSubmitting" 
          type="submit" 
          class="w-full py-5 rounded-2xl bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-[#2D6A4F]/20 disabled:opacity-70 transition-all"
        >
          {{ isSubmitting ? 'MEMVERIFIKASI...' : 'RESET PIN SEKARANG' }}
        </button>
      </form>

      <div class="mt-8 text-center pt-6 border-t border-gray-100">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 leading-relaxed">
          OTP Kedaluwarsa atau Tidak Masuk?
        </p>
        <button 
          @click="handleResend" 
          :disabled="isResending"
          class="text-[#D4A373] hover:text-[#2D6A4F] font-black text-[10px] uppercase tracking-[0.2em] transition-all disabled:opacity-50"
        >
          {{ isResending ? 'MENGIRIM ULANG...' : 'KIRIM ULANG KODE WA' }}
        </button>
      </div>

      <div class="text-center mt-4">
        <button @click.prevent="router.replace({ name: 'forgot-pin' })" class="text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-600">
          Ganti Nomor WhatsApp
        </button>
      </div>

    </div>

    <SuccessModal :show="showSuccessModal" @confirm="handleSuccessConfirm" />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, sameAs, helpers } from '@vuelidate/validators';
import { useForgotPin } from '../../composables/useForgotPin';
import type { ResetPinPayload } from '../../types/auth';
import SuccessModal from '../../components/auth/SuccessModal.vue';

const router = useRouter();
const route = useRoute();
const { isSubmitting, isResending, errorMessage, sendOtp, executeReset } = useForgotPin();

const showSuccessModal = ref(false);
const successMessage = ref('');

const formData = ref<ResetPinPayload>({
  nik: '',
  whatsapp_number: '',
  otp: '',
  new_pin: '',
  new_pin_confirmation: '' 
});

// Aturan Validasi Vuelidate
const wjb = helpers.withMessage('Wajib diisi', required);
const lkp6 = helpers.withMessage('Harus 6 digit', (val: string) => val.length === 6);

const rules = computed(() => ({
  otp: { required: wjb, length: lkp6 },
  new_pin: { required: wjb, length: lkp6 },
  new_pin_confirmation: { 
    required: wjb, 
    sameAs: helpers.withMessage('PIN tidak cocok', sameAs(formData.value.new_pin)) 
  }
}));

const v$ = useVuelidate(rules, formData);

// Keamanan Pesan Error (Anti-Bocor)
const safeErrorMessage = computed(() => {
  if (!errorMessage.value) return '';
  const err = errorMessage.value.toLowerCase();
  
  if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || errorMessage.value.length > 80) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return errorMessage.value;
});

onMounted(() => {
  formData.value.nik = route.query.nik as string || '';
  formData.value.whatsapp_number = route.query.wa as string || '';

  if (!formData.value.nik || !formData.value.whatsapp_number) {
    router.replace({ name: 'forgot-pin' });
  }
});

const formatNumeric = (field: keyof ResetPinPayload, maxLength: number) => {
  formData.value[field] = formData.value[field].replace(/\D/g, '').substring(0, maxLength);
  if (errorMessage.value) errorMessage.value = '';
  if (successMessage.value) successMessage.value = '';
};

const handleSuccessConfirm = () => {
  showSuccessModal.value = false;
  window.location.href = '/login';
};

const handleReset = async () => {
  // Jalankan validasi Vuelidate sebelum submit
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const result = await executeReset(formData.value);
  
  if (result.success) {
    // Bersihkan sesi lama yang dihanguskan BE
    localStorage.removeItem('token'); 
    localStorage.clear();            
    
    // Tampilkan Modal Sukses pengganti Alert
    showSuccessModal.value = true;
  }
};

const handleResend = async () => {
  const result = await sendOtp({ 
    nik: formData.value.nik, 
    whatsapp_number: formData.value.whatsapp_number 
  });
  
  if (result.success) {
    formData.value.otp = ''; 
    v$.value.otp.$reset(); // Reset validasi state OTP

    successMessage.value = 'Kode OTP baru telah dikirim ke WhatsApp Anda.';
    
    // Sembunyikan notifikasi setelah 5 detik
    setTimeout(() => {
      successMessage.value = '';
    }, 5000);
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

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>