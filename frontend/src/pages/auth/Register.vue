<template>
  <div class="min-h-screen bg-[#fdfaf5] flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[30%] right-[5%] w-[50%] h-[50%] bg-[#2D6A4F]/10 rounded-full blur-[110px] animate-blob animation-delay-4000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div class="w-full max-w-xl bg-white/70 backdrop-blur-2xl rounded-[2.5rem] p-8 md:p-12 shadow-[0_20px_50px_rgba(45,106,79,0.08)] border border-white relative z-10 mt-10 mb-10">
      
      <div class="text-center mb-10">
        <router-link to="/" class="inline-block mb-4">
          <span class="text-3xl font-black italic tracking-tighter text-[#2D6A4F]">SABANA</span>
        </router-link>
        <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Registrasi Warga</h1>
        <p class="text-sm font-bold text-gray-500">Gunakan data kependudukan yang valid.</p>
      </div>

      <div v-if="safeAuthError" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-3 animate-pulse">
        <span class="text-red-500 font-black">⚠</span>
        <p class="text-xs font-bold text-red-600 leading-relaxed">{{ safeAuthError }}</p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-2">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
          <div class="relative pb-6">
            <input 
              v-model="formData.nik" 
              @input="formatNumeric('nik', 16)"
              @blur="v$.nik.$touch()"
              type="text" inputmode="numeric" placeholder=" " 
              :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.nik.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
            />
            <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK (16 Digit)</label>
            <span v-if="v$.nik.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.nik.$errors[0].$message }}</span>
          </div>

          <div class="relative pb-6">
            <input 
              v-model="formData.family_card_number" 
              @input="formatNumeric('family_card_number', 16)"
              @blur="v$.family_card_number.$touch()"
              type="text" inputmode="numeric" placeholder=" " 
              :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.family_card_number.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
            />
            <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.family_card_number.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nomor KK</label>
            <span v-if="v$.family_card_number.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.family_card_number.$errors[0].$message }}</span>
          </div>
        </div>

        <div class="relative pb-6">
          <input 
            v-model.trim="formData.full_name" 
            @blur="v$.full_name.$touch()"
            type="text" placeholder=" " 
            :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.full_name.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.full_name.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nama Lengkap (Sesuai KTP)</label>
          <span v-if="v$.full_name.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.full_name.$errors[0].$message }}</span>
        </div>

        <div class="relative pb-6">
          <input 
            v-model="formData.whatsapp_number" 
            @input="formatNumeric('whatsapp_number', 15)"
            @blur="v$.whatsapp_number.$touch()"
            type="tel" inputmode="numeric" placeholder=" " 
            :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.whatsapp_number.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.whatsapp_number.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nomor WhatsApp Aktif</label>
          <span v-if="v$.whatsapp_number.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.whatsapp_number.$errors[0].$message }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 pb-2">
          <div class="relative pb-6">
            <input 
              v-model="formData.pin" 
              @input="formatNumeric('pin', 6)"
              @blur="v$.pin.$touch()"
              type="password" inputmode="numeric" placeholder=" " 
              :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-center tracking-[0.5em] text-xl text-gray-800', v$.pin.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#D4A373] focus:bg-white']"
            />
            <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.pin.$error ? 'text-red-500' : 'text-[#D4A373]']">Buat PIN (6 Digit)</label>
            <span v-if="v$.pin.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.pin.$errors[0].$message }}</span>
          </div>

          <div class="relative pb-6">
            <input 
              v-model="formData.pin_confirmation" 
              @input="formatNumeric('pin_confirmation', 6)"
              @blur="v$.pin_confirmation.$touch()"
              type="password" inputmode="numeric" placeholder=" " 
              :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-center tracking-[0.5em] text-xl text-gray-800', v$.pin_confirmation.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#D4A373] focus:bg-white']"
            />
            <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.pin_confirmation.$error ? 'text-red-500' : 'text-[#D4A373]']">Konfirmasi PIN</label>
            <span v-if="v$.pin_confirmation.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.pin_confirmation.$errors[0].$message }}</span>
          </div>
        </div>

        <button :disabled="isSubmitting" type="submit" class="w-full py-4 bg-[#2D6A4F] text-white rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-[#1b4332] hover:shadow-[0_10px_20px_rgba(45,106,79,0.3)] hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center mt-2">
          <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isSubmitting ? 'MEMPROSES...' : 'DAFTAR SEKARANG' }}
        </button>

      </form>

      <div class="mt-8 text-center">
        <p class="text-xs font-bold text-gray-500">
          Sudah terdaftar di SABANA? 
          <router-link :to="{ name: 'login' }" class="text-[#D4A373] hover:text-[#2D6A4F] transition-colors ml-1 uppercase tracking-wider">Masuk di sini</router-link>
        </p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, sameAs, helpers } from '@vuelidate/validators';

import type { RegisterPayload } from '../../types/auth';
import { useAuth } from '../../composables/useAuth';

const router = useRouter();
const { isSubmitting, authError, submitRegistration } = useAuth();

const formData = ref<RegisterPayload>({
  nik: '',
  family_card_number: '',
  full_name: '',
  whatsapp_number: '',
  pin: '',
  pin_confirmation: ''
});

// MENAMBAHKAN FILTER KEAMANAN ERROR (ANTI-BOCOR)
const safeAuthError = computed(() => {
  if (!authError.value) return '';
  const err = authError.value.toLowerCase();
  
  if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || authError.value.length > 80) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return authError.value;
});

const formatNumeric = (field: keyof RegisterPayload, maxLength: number) => {
  if (field === 'full_name') return; 
  let val = formData.value[field].replace(/\D/g, '');
  formData.value[field] = val.substring(0, maxLength);
};

const wjb = helpers.withMessage('Wajib diisi', required);
const lkp16 = helpers.withMessage('Harus 16 digit', (val: string) => val.length === 16);
const lkp6 = helpers.withMessage('Harus 6 digit', (val: string) => val.length === 6);

const rules = computed(() => ({
  nik: { required: wjb, length: lkp16 },
  family_card_number: { required: wjb, length: lkp16 },
  full_name: { required: wjb },
  whatsapp_number: { required: wjb },
  pin: { required: wjb, length: lkp6 },
  pin_confirmation: { 
    required: wjb, 
    sameAs: helpers.withMessage('PIN tidak cocok', sameAs(formData.value.pin)) 
  }
}));

const v$ = useVuelidate(rules, formData);

const onSubmit = async () => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const result = await submitRegistration(formData.value);
  if (result.success) {
    router.replace({ 
      name: 'verify-otp', 
      query: { 
        nik: formData.value.nik, 
        wa: formData.value.whatsapp_number 
      } 
    });
  }
};
</script>

<style scoped>
/* KODE INI SAMA SEKALI TIDAK DIUBAH */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}

/* Menambahkan keyframes JIKA App.vue tidak ada. 
   Jika di App.vue sudah ada, ini tidak akan merusak apapun. */
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