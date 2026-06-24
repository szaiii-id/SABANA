<template>
  <div class="min-h-screen bg-[#FAF6F0] flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Background Ornament -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-15%] right-[-10%] w-[80%] h-[80%] bg-[#1B4332]/8 rounded-full blur-[180px] animate-blob"></div>
      <div class="absolute bottom-[-20%] left-[-15%] w-[75%] h-[75%] bg-[#D4A373]/10 rounded-full blur-[150px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[40%] left-[50%] w-[60%] h-[60%] bg-[#2D6A4F]/5 rounded-full blur-[120px] animate-blob animation-delay-4000"></div>
    </div>

    <!-- Card Login - BERSIH TANPA GARIS -->
    <div 
      :class="[
        'w-full bg-white/95 backdrop-blur-2xl rounded-[3rem] shadow-[0_30px_80px_rgba(27,67,50,0.1)] border border-[#E8D5C4] relative z-10 transition-all', 
        maxWidthClass,
        extraPadding ? 'p-10 md:p-14 mt-10 mb-10' : 'p-10 sm:p-14'
      ]"
    >
      <slot></slot> 
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
  maxWidth: { type: String, default: 'md' },
  extraPadding: { type: Boolean, default: false }
});

const maxWidthClass = computed(() => {
  if (props.maxWidth === 'xl') return 'max-w-xl';
  if (props.maxWidth === 'lg') return 'max-w-lg';
  return 'max-w-md';
});
</script>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(40px, -60px) scale(1.1); }
  66% { transform: translate(-30px, 30px) scale(0.92); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 20s infinite alternate cubic-bezier(0.4, 0, 0.2, 1); }
.animation-delay-2000 { animation-delay: 2.5s; }
.animation-delay-4000 { animation-delay: 5s; }
</style>