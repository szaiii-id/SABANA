<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden border border-[#E8D5C4]">
        
        <div class="px-8 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <h3 class="text-xl font-black text-[#1B4332]">Reset Password</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-red-500 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-8 space-y-5">
          
          <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
              <p class="text-xs font-bold text-amber-800">Anda akan mereset password untuk:</p>
              <p class="text-sm font-black text-amber-900 mt-0.5">{{ targetName }}</p>
              <p class="text-[10px] text-amber-600 mt-1">{{ targetNip }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Password Baru</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 transition-colors" :class="errors.password ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#D4A373]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
              </div>
              <input 
                ref="passwordInput"
                v-model="form.password" 
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan password baru"
                :class="[
                  'w-full pl-12 pr-12 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm',
                  errors.password ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#D4A373] focus:ring-4 focus:ring-[#D4A373]/10'
                ]"
                @input="validatePassword"
              >
              <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-[#6B705C] transition-colors">
                <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
              </button>
            </div>
            <p v-if="errors.password" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.password }}</p>
            <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">Minimal 8 karakter</p>
          </div>

          <p v-if="safeErrorMessage" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ safeErrorMessage }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="$emit('close')" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
            <button type="submit" :disabled="submitting" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Mereset...' : 'Reset Password' }}
            </button>
          </div>
        </form>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, onMounted } from 'vue';
import { getSafeErrorMessage } from '../../utils/errorHandler';

const props = defineProps<{
  open: boolean;
  targetName: string;
  targetNip: string;
  submitting: boolean;
  serverError: string;
}>();

const emit = defineEmits<{
  close: [];
  save: [password: string];
}>();

const form = reactive({ password: '' });
const errors = reactive({ password: '' });
const showPassword = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const safeErrorMessage = computed(() => getSafeErrorMessage(props.serverError));

const validatePassword = () => {
  errors.password = '';
  if (!form.password) {
    errors.password = 'Password wajib diisi.';
  } else if (form.password.length < 8) {
    errors.password = 'Password minimal 8 karakter.';
  }
};

const handleSubmit = () => {
  errors.password = '';
  
  if (!form.password) {
    errors.password = 'Password wajib diisi.';
    return;
  }
  if (form.password.length < 8) {
    errors.password = 'Password minimal 8 karakter.';
    return;
  }

  emit('save', form.password);
};

onMounted(() => {
  nextTick(() => passwordInput.value?.focus());
});
</script>