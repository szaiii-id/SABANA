<template>
  <AuthLayout maxWidth="md">
    
    <transition 
      enter-active-class="transition duration-500 ease-out" 
      enter-from-class="transform -translate-y-4 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100" 
      leave-active-class="transition duration-300 ease-in" 
      leave-from-class="transform translate-y-0 opacity-100" 
      leave-to-class="transform -translate-y-4 opacity-0"
    >
      <div v-if="sessionAlert" class="mb-8 bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-2xl shadow-sm flex items-start gap-3">
        <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div class="flex-grow">
          <h3 class="text-[10px] font-black text-amber-800 uppercase tracking-widest mb-1">Keamanan Sesi</h3>
          <p class="text-[11px] font-bold text-amber-700 leading-relaxed">{{ sessionAlert }}</p>
        </div>
        <button @click="sessionAlert = ''" class="text-amber-400 hover:text-amber-600 transition-colors p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </transition>

    <div class="text-center mb-10">
      <router-link to="/" class="inline-block mb-4 transform hover:scale-105 transition-transform">
        <span class="text-3xl font-[1000] italic tracking-tighter text-[#2D6A4F]">SABANA</span>
      </router-link>
      <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-1">Selamat Datang</h1>
      <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] leading-relaxed italic">Sistem Informasi Bantuan Banua</p>
    </div>

    <form @submit.prevent="onSubmit" class="space-y-4">
      
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="safeAuthError" class="p-4 rounded-2xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100 uppercase tracking-wider text-center shadow-sm">
          {{ safeAuthError }}
        </div>
      </transition>

      <div class="relative">
        <input 
          v-model="formData.nik" 
          @input="formatNumeric('nik', 16)"
          @blur="v$.nik.$touch()"
          type="text" inputmode="numeric" placeholder=" " 
          :class="['peer w-full border-2 rounded-2xl px-5 py-4 outline-none transition-all font-bold text-gray-800', v$.nik.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-100 focus:border-[#2D6A4F] focus:bg-white shadow-sm']"
        />
        <label :class="['absolute left-5 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-2 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-2', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK (16 Digit)</label>
        <p v-if="v$.nik.$error" class="text-[10px] font-bold text-red-500 mt-2 ml-4 text-left animate-pulse">{{ v$.nik.$errors[0].$message }}</p>
      </div>

      <div class="relative">
        <input 
          ref="pinInput"
          v-model="formData.pin" 
          @input="formatNumeric('pin', 6)"
          @blur="v$.pin.$touch()"
          type="password" inputmode="numeric" placeholder=" " 
          :class="['peer w-full border-2 rounded-2xl px-5 py-4 outline-none transition-all font-black text-center tracking-[1.2em] text-xl text-gray-800', v$.pin.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-100 focus:border-[#D4A373] focus:bg-white shadow-sm']"
        />
        <label :class="['absolute left-5 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-2 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-2', v$.pin.$error ? 'text-red-500' : 'text-[#D4A373]']">PIN Keamanan</label>
        <p v-if="v$.pin.$error" class="text-[10px] font-bold text-red-500 mt-2 ml-4 text-left animate-pulse">{{ v$.pin.$errors[0].$message }}</p>
      </div>

      <div class="flex items-center justify-between px-2 pt-2">
        <label class="flex items-center gap-3 cursor-pointer group">
          <div class="relative flex items-center justify-center">
            <input 
              type="checkbox" 
              v-model="formData.rememberNik" 
              class="peer appearance-none w-5 h-5 border-2 border-gray-200 rounded-lg checked:bg-[#2D6A4F] checked:border-[#2D6A4F] transition-all cursor-pointer"
            />
            <svg class="absolute w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4">
              <path d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-[#2D6A4F] transition-colors">Ingat NIK Saya</span>
        </label>

        <router-link :to="{ name: 'forgot-pin' }" class="text-[10px] font-black text-gray-400 hover:text-[#D4A373] uppercase tracking-widest transition-colors">Lupa PIN?</router-link>
      </div>

      <button :disabled="isSubmitting" type="submit" class="group relative w-full flex justify-center items-center py-5 border border-transparent rounded-[1.5rem] text-xs font-black text-white bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] uppercase tracking-[0.3em] hover:shadow-xl hover:shadow-[#2D6A4F]/20 transition-all duration-300 overflow-hidden disabled:opacity-70 mt-4">
        <span class="relative z-10">{{ isSubmitting ? 'MENGAUTENTIKASI...' : 'MASUK KE LAYANAN' }}</span>
        <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out z-0"></div>
      </button>
    </form>

    <div class="mt-10 text-center pt-8 border-t border-gray-100">
      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">
        Belum memiliki akun warga? 
        <router-link :to="{ name: 'register' }" class="text-[#D4A373] hover:text-[#2D6A4F] transition-colors ml-1 font-black">Daftar Sekarang</router-link>
      </p>
    </div>

  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useAuth } from '../../composables/useAuth';
import { getSafeErrorMessage } from '../../utils/errorHandler'; 
import AuthLayout from '../../layouts/AuthLayout.vue'; 

const { isSubmitting, authError, submitLogin } = useAuth();
const pinInput = ref<HTMLInputElement | null>(null);

const formData = ref({ 
  nik: '', 
  pin: '',
  rememberNik: false 
});

const sessionAlert = ref('');

const wjb = helpers.withMessage('Wajib diisi', required);
const lkp16 = helpers.withMessage('Harus 16 digit', (val: string) => val.length === 16);
const lkp6 = helpers.withMessage('Harus 6 digit', (val: string) => val.length === 6);

const rules = computed(() => ({
  nik: { required: wjb, length: lkp16 },
  pin: { required: wjb, length: lkp6 }
}));

const v$ = useVuelidate(rules, formData);

const safeAuthError = computed(() => getSafeErrorMessage(authError.value));

onMounted(() => {
  // 1. Simpan nilai sebelum clear
  const isSessionExpired = localStorage.getItem('session_expired');
  const savedNik = localStorage.getItem('remembered_nik');
  
  // 2. Load NIK jika ada
  if (savedNik) {
    formData.value.nik = savedNik;
    formData.value.rememberNik = true;
    nextTick(() => {
      pinInput.value?.focus();
    });
  }
  
  // 3. Bersihkan localStorage
  localStorage.clear();
  
  // 4. Restore remembered_nik
  if (savedNik) {
    localStorage.setItem('remembered_nik', savedNik);
  }
  
  // 5. Set session alert setelah clear (pakai variabel yang sudah disimpan)
  if (isSessionExpired) {
    sessionAlert.value = 'Sesi Anda telah berakhir otomatis demi keamanan. Silakan masuk kembali menggunakan PIN Anda.';
  }
});

const formatNumeric = (field: 'nik' | 'pin', maxLength: number) => {
  formData.value[field] = formData.value[field].replace(/\D/g, '').substring(0, maxLength);
  if (authError.value) authError.value = ''; 
};

const onSubmit = async () => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  if (formData.value.rememberNik) {
    localStorage.setItem('remembered_nik', formData.value.nik);
  } else {
    localStorage.removeItem('remembered_nik');
  }

  const result = await submitLogin({
    nik: formData.value.nik,
    pin: formData.value.pin
  });
  
  if (result.success) {
    window.location.href = '/dashboard'; 
  }
};
</script>