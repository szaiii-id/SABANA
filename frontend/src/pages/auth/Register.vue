<template>
  <AuthLayout maxWidth="xl" :extraPadding="true">
    
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
        <div class="relative mb-5">
          <input v-model="formData.nik" @input="formatNumeric('nik', 16)" @blur="v$.nik.$touch()" type="text" inputmode="numeric" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.nik.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']" />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK (16 Digit)</label>
          <p v-if="v$.nik.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.nik.$errors[0].$message }}</p>
        </div>

        <div class="relative mb-5">
          <input v-model="formData.family_card_number" @input="formatNumeric('family_card_number', 16)" @blur="v$.family_card_number.$touch()" type="text" inputmode="numeric" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.family_card_number.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']" />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.family_card_number.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nomor KK</label>
          <p v-if="v$.family_card_number.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.family_card_number.$errors[0].$message }}</p>
        </div>
      </div>

      <div class="relative mb-5">
        <input v-model.trim="formData.full_name" @blur="v$.full_name.$touch()" type="text" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.full_name.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']" />
        <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.full_name.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nama Lengkap (Sesuai KTP)</label>
        <p v-if="v$.full_name.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.full_name.$errors[0].$message }}</p>
      </div>

      <div class="relative mb-5">
        <input v-model="formData.whatsapp_number" @input="formatNumeric('whatsapp_number', 15)" @blur="v$.whatsapp_number.$touch()" type="tel" inputmode="numeric" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-gray-800', v$.whatsapp_number.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#2D6A4F] focus:bg-white']" />
        <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.whatsapp_number.$error ? 'text-red-500' : 'text-[#2D6A4F]']">Nomor WhatsApp Aktif</label>
        <p v-if="v$.whatsapp_number.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.whatsapp_number.$errors[0].$message }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 pb-2">
        <div class="relative mb-5">
          <input v-model="formData.pin" @input="formatNumeric('pin', 6)" @blur="v$.pin.$touch()" type="password" inputmode="numeric" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-center tracking-[0.5em] text-xl text-gray-800', v$.pin.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#D4A373] focus:bg-white']" />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.pin.$error ? 'text-red-500' : 'text-[#D4A373]']">Buat PIN (6 Digit)</label>
          <p v-if="v$.pin.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.pin.$errors[0].$message }}</p>
        </div>

        <div class="relative mb-5">
          <input v-model="formData.pin_confirmation" @input="formatNumeric('pin_confirmation', 6)" @blur="v$.pin_confirmation.$touch()" type="password" inputmode="numeric" placeholder=" " :class="['peer w-full bg-gray-50 border-2 rounded-2xl px-4 py-3 outline-none transition-all font-bold text-center tracking-[0.5em] text-xl text-gray-800', v$.pin_confirmation.$error ? 'border-red-500 bg-red-50' : 'border-gray-200 focus:border-[#D4A373] focus:bg-white']" />
          <label :class="['absolute left-4 top-3.5 text-xs font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.pin_confirmation.$error ? 'text-red-500' : 'text-[#D4A373]']">Konfirmasi PIN</label>
          <p v-if="v$.pin_confirmation.$error" class="text-[10px] font-bold text-red-500 mt-1.5 ml-2 text-left animate-pulse">{{ v$.pin_confirmation.$errors[0].$message }}</p>
        </div>
      </div>

      <div :class="['mb-6 bg-gray-50/50 border rounded-2xl p-4 flex items-start gap-4 transition-colors', v$.agree_terms.$error ? 'border-red-200 bg-red-50/30' : 'border-gray-100']">
        <div class="relative flex items-center justify-center mt-0.5 shrink-0">
          <input 
            type="checkbox" 
            v-model="formData.agree_terms" 
            @change="v$.agree_terms.$touch()"
            class="peer appearance-none w-5 h-5 border-2 border-gray-300 rounded-lg checked:bg-[#2D6A4F] checked:border-[#2D6A4F] transition-all cursor-pointer"
          />
          <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
            <path d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <div class="flex-1">
          <label class="text-[10px] sm:text-[11px] font-medium text-gray-500 leading-relaxed cursor-pointer select-none block" @click="formData.agree_terms = !formData.agree_terms; v$.agree_terms.$touch()">
            Saya menyatakan bahwa data kependudukan (NIK & No. KK) yang saya masukkan adalah <strong class="text-gray-700">benar dan milik saya pribadi</strong>. Saya mengizinkan SABANA untuk memprosesnya sesuai dengan <a href="#" class="text-[#2D6A4F] hover:underline font-black whitespace-nowrap">Kebijakan Privasi</a>.
          </label>
          <p v-if="v$.agree_terms.$error" class="text-[10px] font-bold text-red-500 mt-2 animate-pulse">⚠ Anda harus menyetujui syarat & ketentuan untuk melanjutkan.</p>
        </div>
      </div>

      <button :disabled="isSubmitting" type="submit" class="w-full py-4 bg-[#2D6A4F] text-white rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-[#1b4332] hover:shadow-[0_10px_20px_rgba(45,106,79,0.3)] hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center mt-2">
        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        {{ isSubmitting ? 'MEMPROSES...' : 'DAFTAR SEKARANG' }}
      </button>

    </form>

    <div class="mt-8 text-center">
      <p class="text-xs font-bold text-gray-500">
        Sudah terdaftar di SABANA? 
        <router-link :to="{ name: 'login' }" class="text-[#D4A373] hover:text-[#2D6A4F] transition-colors ml-1 uppercase tracking-wider font-black">Masuk di sini</router-link>
      </p>
    </div>

  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, sameAs, helpers } from '@vuelidate/validators';

import type { RegisterPayload } from '../../types/auth'; 
import { useAuth } from '../../composables/useAuth';
import { getSafeErrorMessage } from '../../utils/errorHandler'; 
import AuthLayout from '../../layouts/AuthLayout.vue'; 

const router = useRouter();
const { isSubmitting, authError, submitRegistration } = useAuth();

const formData = ref<RegisterPayload & { agree_terms: boolean }>({
  nik: '', family_card_number: '', full_name: '', whatsapp_number: '', pin: '', pin_confirmation: '', agree_terms: false
});

const safeAuthError = computed(() => getSafeErrorMessage(authError.value));

// PERBAIKAN TYPESCRIPT: Tipe khusus untuk field yang berupa angka saja
type NumericFields = 'nik' | 'family_card_number' | 'whatsapp_number' | 'pin' | 'pin_confirmation';

const formatNumeric = (field: NumericFields, maxLength: number) => {
  let val = String(formData.value[field]).replace(/\D/g, '');
  (formData.value as any)[field] = val.substring(0, maxLength);
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
  pin_confirmation: { required: wjb, sameAs: helpers.withMessage('PIN tidak cocok', sameAs(formData.value.pin)) },
  agree_terms: { sameAs: helpers.withMessage('Anda harus menyetujui syarat & ketentuan', sameAs(true)) }
}));

const v$ = useVuelidate(rules, formData);

const onSubmit = async () => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const { agree_terms, ...payloadToSend } = formData.value;
  const result = await submitRegistration(payloadToSend as RegisterPayload);
  
  if (result.success) {
    router.replace({ name: 'verify-otp', query: { nik: formData.value.nik, wa: formData.value.whatsapp_number } });
  }
};
</script>