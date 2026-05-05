<template>
  <div class="min-h-screen bg-[#fdfaf5] flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[70%] h-[70%] bg-[#2D6A4F]/15 rounded-full blur-[120px] animate-blob"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] bg-[#D4A373]/15 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
      <div class="absolute top-[30%] right-[5%] w-[50%] h-[50%] bg-[#2D6A4F]/10 rounded-full blur-[110px] animate-blob animation-delay-4000"></div>
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    </div>

    <div 
      :class="[
        'w-full bg-white/70 backdrop-blur-2xl rounded-[2.5rem] shadow-[0_20px_50px_rgba(45,106,79,0.08)] border border-white relative z-10', 
        maxWidthClass,
        extraPadding ? 'p-8 md:p-12 mt-10 mb-10' : 'p-8 sm:p-12'
      ]"
    >
      <slot></slot> 
    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
  maxWidth: { type: String, default: 'md' }, // Opsi: 'md', 'lg', 'xl'
  extraPadding: { type: Boolean, default: false } // True untuk halaman Register yg panjang
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
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 15s infinite alternate ease-in-out; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }
</style>