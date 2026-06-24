<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden border border-[#E8D5C4]">
        
        <div class="px-8 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <h3 class="text-xl font-black text-[#1B4332]">Edit Profil</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-red-500 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-8 space-y-5">
          <div>
            <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">NIP</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4z" />
                </svg>
              </div>
              <input :value="nip" type="text" disabled class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-[#E8D5C4] bg-gray-50 text-[#6B705C] outline-none font-medium text-sm cursor-not-allowed">
            </div>
            <p class="text-[10px] font-medium text-[#6B705C] mt-1 ml-1">NIP tidak dapat diubah</p>
          </div>

          <div>
            <label class="block text-xs font-bold text-[#6B705C] uppercase tracking-wider mb-2">Nama Lengkap</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 transition-colors" :class="errors.name ? 'text-[#DC2626]' : 'text-[#9CA3AF] group-focus-within:text-[#1B4332]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input 
                ref="nameInput"
                v-model="form.name" 
                type="text" 
                placeholder="Masukkan nama lengkap"
                :class="[
                  'w-full pl-12 pr-4 py-3.5 rounded-xl border-2 outline-none transition-all font-medium text-sm',
                  errors.name ? 'border-[#FCA5A5] bg-[#FEF2F2] focus:ring-4 focus:ring-[#DC2626]/10' : 'border-[#E8D5C4] focus:border-[#1B4332] focus:ring-4 focus:ring-[#1B4332]/10'
                ]"
                @input="validateName"
              >
            </div>
            <p v-if="errors.name" class="text-[10px] font-bold text-[#DC2626] mt-1.5 ml-1">{{ errors.name }}</p>
          </div>

          <p v-if="errorMessage" class="text-sm text-red-600 bg-red-50 p-3 rounded-xl border border-red-200">{{ errorMessage }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="$emit('close')" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
            <button type="submit" :disabled="submitting" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-70">
              <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, reactive, watch, nextTick } from 'vue';

const props = defineProps<{ open: boolean; nip: string; name: string; submitting: boolean; errorMessage: string }>();
const emit = defineEmits<{ close: []; save: [name: string] }>();

const form = reactive({ name: '' });
const errors = reactive({ name: '' });
const nameInput = ref<HTMLInputElement | null>(null);

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    form.name = props.name;
    errors.name = '';
    nextTick(() => nameInput.value?.focus());
  }
});

const validateName = () => {
  errors.name = '';
  if (!form.name.trim()) {
    errors.name = 'Nama wajib diisi.';
  }
};

const handleSubmit = () => {
  errors.name = '';
  if (!form.name.trim()) {
    errors.name = 'Nama wajib diisi.';
    return;
  }
  emit('save', form.name.trim());
};
</script>