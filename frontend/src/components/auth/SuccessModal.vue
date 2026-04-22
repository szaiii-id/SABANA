<template>
  <transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#081c15]/60 backdrop-blur-sm">
      <transition
        enter-active-class="transition duration-500 ease-out delay-100"
        enter-from-class="transform scale-90 opacity-0 translate-y-8"
        enter-to-class="transform scale-100 opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform scale-100 opacity-100 translate-y-0"
        leave-to-class="transform scale-95 opacity-0 translate-y-4"
      >
        <div class="w-full max-w-sm bg-white rounded-[2.5rem] p-8 text-center shadow-2xl border border-white relative overflow-hidden">
          
          <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-b from-[#2D6A4F]/10 to-transparent z-0"></div>
          
          <div class="relative z-10">
            <div class="mx-auto w-20 h-20 bg-gradient-to-tr from-[#2D6A4F] to-[#40916c] text-white rounded-full flex items-center justify-center shadow-xl shadow-[#2D6A4F]/30 mb-6 transform hover:scale-110 transition-transform duration-300">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            
            <h3 class="text-2xl font-black text-[#081c15] mb-2 tracking-tight">
              Verifikasi Berhasil!
            </h3>
            
            <p class="text-gray-500 text-sm font-medium mb-8 leading-relaxed px-2">
              Identitas Anda telah tervalidasi. Akun <span class="text-[#2D6A4F] font-black italic">SABANA</span> Anda kini sudah aktif dan siap digunakan.
            </p>
            
            <button 
              @click="$emit('confirm')" 
              class="group relative w-full py-4 bg-gradient-to-r from-[#2D6A4F] to-[#1B4332] text-white rounded-2xl font-bold tracking-widest text-sm hover:shadow-lg hover:shadow-[#2D6A4F]/40 transition-all active:scale-95 overflow-hidden"
            >
              <span class="relative z-10 uppercase">Lanjut ke Login</span>
              <div class="absolute inset-0 h-full w-full bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out z-0"></div>
            </button>
          </div>

        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup lang="ts">
defineProps<{
  show: boolean
}>();

defineEmits<{
  (e: 'confirm'): void
}>();
</script>