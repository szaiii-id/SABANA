<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="submitting ? null : $emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden border border-[#E8D5C4]">
        
        <div class="flex-shrink-0 px-10 py-6 border-b border-[#E8D5C4] flex justify-between items-center bg-[#FAF6F0]">
          <h3 class="text-xl font-black text-[#1B4332]">{{ mode === 'add' ? 'Tambah Program' : 'Edit Program' }}</h3>
          <button @click="$emit('close')" :disabled="submitting" class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Error Section -->
        <div v-if="validationError || error" class="flex-shrink-0 mx-10 mt-6 p-4 bg-red-50 border border-red-200 rounded-xl space-y-1">
          <p v-if="validationError" class="text-sm font-bold text-red-700">{{ validationError }}</p>
          <p v-if="error" class="text-sm font-bold text-red-700">{{ error }}</p>
        </div>

        <!-- Form Content -->
        <div class="flex-1 overflow-y-auto p-10 space-y-6" :class="{ 'hidden': submitting }">
          <slot></slot>
        </div>

        <!-- OVERLAY UPLOAD PROGRESS -->
        <div v-if="submitting" class="flex-1 flex items-center justify-center p-10">
          <div class="w-full max-w-md">
            <UploadProgress :step="uploadStep" :progress="uploadProgress" />
          </div>
        </div>

        <div class="flex-shrink-0 px-10 py-5 border-t border-[#E8D5C4] bg-gray-50 flex justify-end gap-3">
          <button type="button" @click="$emit('close')" :disabled="submitting" class="px-6 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors disabled:opacity-50">Batal</button>
          <button @click="$emit('save')" :disabled="submitting" class="px-6 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-70">
            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            {{ submitting ? 'Menyimpan...' : 'Simpan Program' }}
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import UploadProgress from '../common/UploadProgress.vue';

defineProps<{ 
  open: boolean; 
  mode: 'add' | 'edit'; 
  submitting: boolean; 
  error: string;
  uploadStep?: 'data' | 'banner' | 'done';
  uploadProgress?: number;
  validationError?: string;
}>();

defineEmits<{ close: []; save: [] }>();
</script>