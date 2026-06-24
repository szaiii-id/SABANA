<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden border border-[#E8D5C4]">
        
        <div class="px-8 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <h3 class="text-xl font-black text-[#1B4332]">Ganti Password</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-red-500 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-8 space-y-5">
          <div>
            <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Password Saat Ini</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 transition-colors" :class="errors.current_password ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input 
                ref="currentPasswordInput"
                v-model="form.current_password" 
                :type="showCurrent ? 'text' : 'password'"
                placeholder="Masukkan password saat ini"
                :class="[
                  'w-full pl-12 pr-12 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm',
                  errors.current_password ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10'
                ]"
                @input="validateCurrentPassword"
              >
              <button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-[#6B705C] transition-colors">
                <svg v-if="!showCurrent" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
              </button>
            </div>
            <p v-if="errors.current_password" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.current_password }}</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Password Baru</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 transition-colors" :class="errors.new_password ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#D4A373]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
              </div>
              <input 
                ref="newPasswordInput"
                v-model="form.new_password" 
                :type="showNew ? 'text' : 'password'"
                placeholder="Minimal 8 karakter"
                :class="[
                  'w-full pl-12 pr-12 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm',
                  errors.new_password ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#D4A373] focus:ring-4 focus:ring-[#D4A373]/10'
                ]"
                @input="validateNewPassword"
              >
              <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-[#6B705C] transition-colors">
                <svg v-if="!showNew" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
              </button>
            </div>
            <p v-if="errors.new_password" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.new_password }}</p>
            <p v-else class="text-[10px] font-medium text-[#6B705C] mt-1.5 ml-1">Minimal 8 karakter</p>
          </div>

          <p v-if="errorMessage" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ errorMessage }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="$emit('close')" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
            <button type="submit" :disabled="submitting" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Mengubah...' : 'Ubah Password' }}
            </button>
          </div>
        </form>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, reactive, nextTick, onMounted } from 'vue';

defineProps<{ open: boolean; submitting: boolean; errorMessage: string }>();
const emit = defineEmits<{ close: []; save: [currentPassword: string, newPassword: string] }>();

const form = reactive({ current_password: '', new_password: '' });
const errors = reactive({ current_password: '', new_password: '' });
const showCurrent = ref(false);
const showNew = ref(false);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
const newPasswordInput = ref<HTMLInputElement | null>(null);

const validateCurrentPassword = () => {
  errors.current_password = '';
  if (!form.current_password) {
    errors.current_password = 'Password saat ini wajib diisi.';
  }
};

const validateNewPassword = () => {
  errors.new_password = '';
  if (!form.new_password) {
    errors.new_password = 'Password baru wajib diisi.';
  } else if (form.new_password.length < 8) {
    errors.new_password = 'Password minimal 8 karakter.';
  }
};

const handleSubmit = () => {
  errors.current_password = '';
  errors.new_password = '';

  if (!form.current_password) {
    errors.current_password = 'Password saat ini wajib diisi.';
    return;
  }
  if (!form.new_password) {
    errors.new_password = 'Password baru wajib diisi.';
    return;
  }
  if (form.new_password.length < 8) {
    errors.new_password = 'Password minimal 8 karakter.';
    return;
  }
  if (form.new_password === form.current_password) {
    errors.new_password = 'Password baru tidak boleh sama dengan saat ini.';
    return;
  }

  emit('save', form.current_password, form.new_password);
};

onMounted(() => {
  nextTick(() => currentPasswordInput.value?.focus());
});
</script>