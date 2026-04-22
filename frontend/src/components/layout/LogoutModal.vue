<template>
  <transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-[#1B4332]/40 backdrop-blur-sm">
      
      <div class="w-full max-w-sm bg-white rounded-[2.5rem] p-8 shadow-2xl border border-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-[#2D6A4F]/5 rounded-full -mr-12 -mt-12"></div>
        
        <div class="text-center relative z-10">
          <div class="w-20 h-20 bg-red-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </div>
          
          <h3 class="text-xl font-[1000] text-gray-800 uppercase tracking-tight mb-2">Keluar Aplikasi?</h3>
          <p class="text-xs font-bold text-gray-500 mb-8 leading-relaxed">
            Sesi Anda akan berakhir. Anda perlu masuk kembali untuk mengakses layanan SABANA.
          </p>

          <div class="grid grid-cols-2 gap-4">
            <button 
              @click="$emit('close')"
              class="py-4 px-6 rounded-2xl bg-gray-50 text-gray-500 font-black text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-colors"
            >
              Batal
            </button>
            <button 
              @click="$emit('confirm')"
              class="py-4 px-6 rounded-2xl bg-red-500 text-white font-black text-[10px] uppercase tracking-widest shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all"
            >
              Ya, Keluar
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
defineProps<{ show: boolean }>();
defineEmits(['close', 'confirm']);
</script>