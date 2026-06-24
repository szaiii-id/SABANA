<template>
  <footer class="text-center py-4">
    <div class="flex items-center justify-center gap-2">
      <span class="text-xs font-medium text-[#6B705C]">&copy; {{ currentYear }}</span>
      <span class="text-xs font-bold text-[#1B4332]">SABANA Center</span>
      <span class="w-1 h-1 bg-[#D4A373] rounded-full"></span>
      <span class="text-xs text-[#6B705C]">Hak Cipta Dilindungi</span>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue';
const currentYear = computed(() => new Date().getFullYear());
</script>