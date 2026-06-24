<template>
  <div class="text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
      :class="displayProgress >= 100 ? 'bg-green-100' : 'bg-blue-100'">
      <svg v-if="displayProgress >= 100" class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      <svg v-else class="w-8 h-8 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
    
    <h3 class="text-lg font-black text-[#1B4332] mb-2">
      {{ displayProgress >= 100 ? 'Berhasil' : step === 'banner' ? 'Mengunggah Berkas' : 'Menyimpan Program' }}
    </h3>
    
    <p class="text-sm text-[#6B705C] mb-4">
      {{ displayProgress >= 100 ? 'Program berhasil disimpan.' : step === 'banner' ? 'Mengunggah berkas ke server.' : 'Menyimpan data program.' }}
    </p>
    
    <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
      <div 
        class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-300"
        :style="{ width: displayProgress + '%' }"
      ></div>
    </div>
    
    <p class="text-xs text-[#6B705C]">{{ displayProgress }}%</p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';

const props = defineProps<{
  step?: 'data' | 'banner' | 'done';
  progress?: number;
}>();

const displayProgress = ref(0);
let interval: ReturnType<typeof setInterval> | null = null;

const animateTo = (target: number) => {
  if (interval) clearInterval(interval);
  interval = setInterval(() => {
    if (displayProgress.value < target) displayProgress.value = Math.min(displayProgress.value + 1, target);
    else if (interval) clearInterval(interval);
  }, 80);
};

watch(() => props.progress, (v) => { if (v !== undefined) animateTo(v); }, { immediate: true });
onUnmounted(() => { if (interval) clearInterval(interval); });
</script>