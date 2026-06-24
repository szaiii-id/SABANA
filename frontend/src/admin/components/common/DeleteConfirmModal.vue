<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden border border-[#E8D5C4] p-8 text-center">
        
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        
        <h3 class="text-lg font-black text-[#1B4332] mb-2">Konfirmasi Nonaktifkan</h3>
        <p class="text-sm text-[#6B705C] mb-6" v-html="message"></p>
        
        <div class="flex gap-3">
          <button @click="$emit('close')" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 transition-colors">Batal</button>
          <button @click="$emit('confirm')" :disabled="submitting" class="flex-1 px-5 py-3 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition-all flex items-center justify-center gap-2 disabled:opacity-70">
            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Ya, Nonaktifkan
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{ open: boolean; message: string; submitting: boolean }>();
defineEmits<{ close: []; confirm: [] }>();
</script>