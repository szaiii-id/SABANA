<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-sm overflow-hidden border border-[#E8D5C4] p-8 text-center">
        
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        
        <h3 class="text-lg font-black text-[#1B4332] mb-2">Berhasil!</h3>
        <p class="text-sm text-[#6B705C] mb-6">{{ message }}</p>
        
        <button @click="$emit('close')" class="w-full px-5 py-3 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-xl hover:shadow-lg transition-all">
          Tutup
        </button>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{ show: boolean; message: string }>();
defineEmits<{ close: [] }>();
</script>