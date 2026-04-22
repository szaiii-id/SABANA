<template>
  <div class="min-h-screen bg-[#fdfaf5] flex items-center justify-center p-6 relative overflow-hidden">
    
    <transition 
      enter-active-class="transition duration-500 ease-out" 
      enter-from-class="transform -translate-y-10 opacity-0" 
      enter-to-class="transform translate-y-0 opacity-100" 
      leave-active-class="transition duration-300 ease-in" 
      leave-from-class="transform translate-y-0 opacity-100" 
      leave-to-class="transform -translate-y-10 opacity-0"
    >
      <div v-if="sessionAlert" class="fixed top-8 left-1/2 transform -translate-x-1/2 z-50 w-[90%] max-w-md">
        <div class="bg-amber-50/95 border-l-4 border-amber-500 p-4 rounded-r-2xl shadow-[0_15px_30px_rgba(245,158,11,0.15)] flex items-start gap-3 backdrop-blur-xl">
          <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div>
            <h3 class="text-[11px] font-black text-amber-800 uppercase tracking-widest mb-1">Pemberitahuan Keamanan</h3>
            <p class="text-xs font-bold text-amber-700 leading-relaxed">{{ sessionAlert }}</p>
          </div>
          <button @click="sessionAlert = ''" class="ml-auto text-amber-400 hover:text-amber-600 transition-colors p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </transition>

    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[30%] right-[5%] w-[50%] h-[50%] bg-[#2D6A4F]/10 rounded-full blur-[110px] animate-blob animation-delay-4000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div class="w-full max-w-md bg-white/70 backdrop-blur-2xl rounded-[2.5rem] p-8 sm:p-12 shadow-[0_20px_50px_rgba(45,106,79,0.08)] border border-white relative z-10">
      
      <div class="text-center mb-10">
        <router-link to="/" class="inline-block mb-4 transform hover:scale-105 transition-transform">
          <span class="text-3xl font-[1000] italic tracking-tighter text-[#2D6A4F]">SABANA</span>
        </router-link>
        <h1 class="text-2xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Masuk Akun</h1>
        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest leading-relaxed">
          Gunakan NIK dan PIN Baru Anda
        </p>
      </div>

      <form @submit.prevent="onSubmit" class="space-y-6">
        
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
          <div v-if="safeAuthError" class="p-4 rounded-2xl bg-red-50 text-red-700 text-[10px] font-black border border-red-100 uppercase tracking-wider leading-relaxed">
            {{ safeAuthError }}
          </div>
        </transition>

        <div class="relative pb-6">
          <input 
            v-model="formData.nik" 
            @input="formatNumeric('nik', 16)"
            @blur="v$.nik.$touch()"
            type="text" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-bold text-gray-800', v$.nik.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#2D6A4F] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.nik.$error ? 'text-red-500' : 'text-[#2D6A4F]']">NIK (16 Digit)</label>
          <span v-if="v$.nik.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.nik.$errors[0].$message }}</span>
        </div>

        <div class="relative pb-6">
          <input 
            v-model="formData.pin" 
            @input="formatNumeric('pin', 6)"
            @blur="v$.pin.$touch()"
            type="password" inputmode="numeric" placeholder=" " 
            :class="['peer w-full border-2 rounded-2xl px-4 py-4 outline-none transition-all font-black text-center tracking-[1em] text-xl text-gray-800', v$.pin.$error ? 'border-red-500 bg-red-50' : 'bg-gray-50 border-gray-200 focus:border-[#D4A373] focus:bg-white']"
          />
          <label :class="['absolute left-4 top-4 text-[10px] font-black uppercase tracking-widest transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-[10px] peer-focus:bg-white peer-focus:px-1 peer-valid:-top-2.5 peer-valid:text-[10px] peer-valid:bg-white peer-valid:px-1', v$.pin.$error ? 'text-red-500' : 'text-[#D4A373]']">PIN Keamanan</label>
          <span v-if="v$.pin.$error" class="absolute bottom-1 left-2 text-[10px] font-bold text-red-500">{{ v$.pin.$errors[0].$message }}</span>
        </div>

        <div class="flex justify-end pt-0">
          <router-link :to="{ name: 'forgot-pin' }" class="text-[10px] font-black text-gray-400 hover:text-[#D4A373] uppercase tracking-widest transition-colors">
            Lupa PIN?
          </router-link>
        </div>

        <button 
          :disabled="isSubmitting" 
          type="submit" 
          class="group relative w-full flex justify-center items-center py-5 border border-transparent rounded-2xl text-xs font-black text-white bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] uppercase tracking-[0.2em] hover:shadow-lg hover:shadow-[#2D6A4F]/30 transition-all duration-300 overflow-hidden disabled:opacity-70"
        >
          <span class="relative z-10">{{ isSubmitting ? 'MEMPROSES...' : 'MASUK KE LAYANAN' }}</span>
          <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out z-0"></div>
        </button>

      </form>

      <div class="mt-8 text-center pt-6 border-t border-gray-100">
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">
          Belum terdaftar sebagai warga? 
          <router-link :to="{ name: 'register' }" class="text-[#D4A373] hover:text-[#2D6A4F] transition-colors ml-1">Daftar Akun</router-link>
        </p>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, helpers } from '@vuelidate/validators';
import { useAuth } from '../../composables/useAuth';

const { isSubmitting, authError, submitLogin } = useAuth();

const formData = ref({
  nik: '',
  pin: ''
});

const sessionAlert = ref('');

// Aturan Validasi Vuelidate sesuai Register
const wjb = helpers.withMessage('Wajib diisi', required);
const lkp16 = helpers.withMessage('Harus 16 digit', (val: string) => val.length === 16);
const lkp6 = helpers.withMessage('Harus 6 digit', (val: string) => val.length === 6);

const rules = computed(() => ({
  nik: { required: wjb, length: lkp16 },
  pin: { required: wjb, length: lkp6 }
}));

const v$ = useVuelidate(rules, formData);

// Filter Keamanan Pesan Error 
const safeAuthError = computed(() => {
  if (!authError.value) return '';
  const err = authError.value.toLowerCase();
  
  if (err.includes('sql') || err.includes('exception') || err.includes('typeerror') || err.includes('server error') || err.includes('undefined') || authError.value.length > 80) {
    return 'Layanan sedang sibuk atau terjadi gangguan sistem. Silakan coba beberapa saat lagi.';
  }
  
  return authError.value;
});

onMounted(() => {
  // Tangkap trigger session timeout dari composable useIdleTimeout
  if (localStorage.getItem('session_expired')) {
    sessionAlert.value = 'Sesi Anda telah berakhir otomatis demi keamanan. Silakan masuk kembali.';
    localStorage.removeItem('session_expired');
    
    // Hilangkan popup otomatis setelah 10 detik
    setTimeout(() => {
      sessionAlert.value = '';
    }, 10000);
  }

  // Bersihkan sisa token
  if (localStorage.getItem('token')) {
    localStorage.clear();
  }
});

const formatNumeric = (field: 'nik' | 'pin', maxLength: number) => {
  formData.value[field] = formData.value[field].replace(/\D/g, '').substring(0, maxLength);
  if (authError.value) authError.value = ''; 
};

const onSubmit = async () => {
  const isFormValid = await v$.value.$validate();
  if (!isFormValid) return;

  const result = await submitLogin(formData.value);
  if (result.success) {
    window.location.href = '/dashboard'; 
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

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>