<template>
  <div class="min-h-screen bg-[#fdfaf5] flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[30%] right-[5%] w-[50%] h-[50%] bg-[#2D6A4F]/10 rounded-full blur-[110px] animate-blob animation-delay-4000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div class="w-full max-w-md bg-white/70 backdrop-blur-2xl rounded-[2.5rem] p-8 sm:p-12 shadow-[0_20px_50px_rgba(45,106,79,0.08)] border border-white relative z-10">
      
      <div class="text-center mb-10">
        <router-link :to="{ name: 'home' }" class="inline-block mb-4">
          <span class="text-3xl font-black italic tracking-tighter text-[#2D6A4F]">SABANA</span>
        </router-link>
        <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Lupa PIN</h1>
        <p class="text-xs font-bold text-gray-500">
          Identitas Anda diperlukan untuk memulihkan akses akun.
        </p>
      </div>

      <form @submit.prevent="handleRequestOtp" class="space-y-6">
        
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
          <div v-if="safeErrorMessage" class="flex items-start p-4 rounded-2xl bg-red-50/90 border border-red-100 backdrop-blur-sm">
            <p class="text-[10px] font-bold text-red-700 leading-snug uppercase tracking-wider">{{ safeErrorMessage }}</p>
          </div>
        </transition>

        <div class="relative pb-6">
          <input 
            v-model="form.nik" 
            @input="formatNumeric('nik', 16)"
            @blur="v$.nik.$touch()"
            type="text" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-bold text-gray-800', v$.nik.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK (16 Digit)</label>
          <span v-if="v$.nik.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.nik.$errors[0].$message }}</span>
        </div>

        <div class="relative pb-6">
          <input 
            v-model="form.whatsapp_number" 
            @input="formatNumeric('whatsapp_number', 15)"
            @blur="v$.whatsapp_number.$touch()"
            type="tel" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-bold text-gray-800', v$.whatsapp_number.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.whatsapp_number.$error ? 'text-red-500' : 'text-[#D4A373]']">No. WhatsApp</label>
          <span v-if="v$.whatsapp_number.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.whatsapp_number.$errors[0].$message }}</span>
        </div>

        <button 
          :disabled="isSubmitting" 
          type="submit" 
          class="group relative w-full flex justify-center items-center py-5 border border-transparent rounded-2xl text-xs font-black text-white bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] uppercase tracking-[0.2em] hover:shadow-lg hover:shadow-[#2D6A4F]/30 focus:outline-none transition-all duration-300 overflow-hidden disabled:opacity-70"
        >
          <span class="relative z-10">{{ isSubmitting ? 'MEMPROSES...' : 'KIRIM KODE RESET' }}</span>
          <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out z-0"></div>
        </button>

        <div class="text-center pt-2">
          <router-link :to="{ name: 'login' }" class="text-[10px] font-black text-gray-400 hover:text-[#D4A373] uppercase tracking-widest transition-colors">
            Kembali ke Login
          </router-link>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useForgotPin } from '../../composables/useForgotPin';

const router = useRouter();
const { isSubmitting, errorMessage, sendOtp } = useForgotPin();

const form = ref({
  nik: '',
  whatsapp_number: ''
});

const wjb = helpers.withMessage('Wajib diisi', required);
const lkp16 = helpers.withMessage('Harus 16 digit', (val: string) => val.length === 16);

const rules = computed(() => ({
  nik: { required: wjb, length: lkp16 },
  whatsapp_number: { required: wjb }
}));

const v$ = useVuelidate(rules, form);

const safeErrorMessage = computed(() => {
  if (!errorMessage.value) return '';
  const err = errorMessage.value.toLowerCase();
  
  if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || errorMessage.value.length > 80) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return errorMessage.value;
});

const formatNumeric = (field: 'nik' | 'whatsapp_number', maxLength: number) => {
  form.value[field] = form.value[field].replace(/\D/g, '').substring(0, maxLength);
  if (errorMessage.value) errorMessage.value = ''; 
};

const handleRequestOtp = async () => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const result = await sendOtp(form.value);
  
  if (result.success) {
    // Navigasi langsung ke Reset PIN (tanpa menggunakan alert sama sekali)
    router.push({ 
      name: 'reset-pin', 
      query: { nik: form.value.nik, wa: form.value.whatsapp_number } 
    });
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