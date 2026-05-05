<template>
  <AuthLayout maxWidth="md">
    <div class="text-center mb-10">
      <router-link :to="{ name: 'login' }" class="inline-block mb-4 transform hover:scale-105 transition-transform">
        <span class="text-3xl font-[1000] italic tracking-tighter text-[#2D6A4F]">SABANA</span>
      </router-link>
      <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Lupa PIN</h1>
      <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest leading-relaxed">
        Kami akan mengirimkan PIN sementara ke WhatsApp Anda
      </p>
    </div>

    <form @submit.prevent="handleRequestOtp" class="space-y-6">
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="safeError" class="p-4 rounded-2xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100 uppercase tracking-wider leading-relaxed text-center">
          {{ safeError }}
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
        <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK Terdaftar</label>
        <span v-if="v$.nik.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.nik.$errors[0].$message }}</span>
      </div>

      <div class="relative pb-6">
        <input 
          v-model="form.whatsapp_number" 
          @input="formatNumeric('whatsapp_number', 15)"
          @blur="v$.whatsapp_number.$touch()"
          type="tel" inputmode="numeric" placeholder=" " 
          :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-bold text-gray-800', v$.whatsapp_number.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
        />
        <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.whatsapp_number.$error ? 'text-red-500' : 'text-[#2D6A4F]']">No WhatsApp (Aktif)</label>
        <span v-if="v$.whatsapp_number.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.whatsapp_number.$errors[0].$message }}</span>
      </div>

      <button :disabled="isSubmitting" type="submit" class="w-full py-5 bg-[#2D6A4F] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-[#1b4332] shadow-lg shadow-[#2D6A4F]/20 transition-all disabled:opacity-70 flex items-center justify-center">
        {{ isSubmitting ? 'MENGIRIM...' : 'KIRIM PIN SEMENTARA' }}
      </button>
    </form>

    <div class="mt-8 text-center pt-6 border-t border-gray-100">
      <router-link :to="{ name: 'login' }" class="text-[10px] font-bold text-gray-400 hover:text-[#D4A373] uppercase tracking-widest transition-colors">
        Kembali ke Login
      </router-link>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useForgotPin } from '../../composables/useForgotPin';
import { getSafeErrorMessage } from '../../utils/errorHandler';
import AuthLayout from '../../layouts/AuthLayout.vue';

const router = useRouter();
const { isSubmitting, errorMessage, sendOtp } = useForgotPin();
const form = ref({ nik: '', whatsapp_number: '' });

const safeError = computed(() => getSafeErrorMessage(errorMessage.value));
const rules = {
  nik: { required: helpers.withMessage('NIK wajib diisi', required), length: helpers.withMessage('Harus 16 digit', (val: string) => val.length === 16) },
  whatsapp_number: { required: helpers.withMessage('No WhatsApp wajib diisi', required) }
};
const v$ = useVuelidate(rules, form);

const formatNumeric = (field: 'nik' | 'whatsapp_number', maxLength: number) => {
  form.value[field] = form.value[field].replace(/\D/g, '').substring(0, maxLength);
  if (errorMessage.value) errorMessage.value = ''; 
};

const handleRequestOtp = async () => {
  if (!(await v$.value.$validate())) return;
  const result = await sendOtp(form.value);
  if (result.success) {
    router.push({ name: 'reset-pin', query: { nik: form.value.nik, wa: form.value.whatsapp_number } });
  }
};
</script>