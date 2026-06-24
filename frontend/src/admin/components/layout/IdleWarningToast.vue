<template>
  <Transition name="slide-down">
    <div 
      v-if="isWarning" 
      class="fixed top-6 right-6 z-50 max-w-sm bg-white rounded-2xl shadow-2xl border border-[#FFB74D] p-5"
    >
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 bg-[#FFF3E0] rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-[#FF8F00] animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-sm font-bold text-[#E65100]">Sesi akan berakhir</p>
          <p class="text-xs text-[#BF360C] mt-1">
            <span class="font-black">{{ remainingSeconds }} detik</span> lagi
          </p>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{
  isWarning: boolean;
  remainingSeconds: number;
}>();
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>