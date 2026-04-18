<template>
  <footer class="bg-[#F1F8F5] border-t border-[#2D6A4F]/10 py-4 mt-auto">
    <div class="container mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
      
      <div class="flex items-center gap-3">
        <span class="text-lg font-black italic tracking-tighter text-[#2D6A4F]">
          SABANA
        </span>
        <span class="text-[10px] font-bold text-[#D4A373] uppercase tracking-widest border-l border-[#D4A373]/30 pl-3">
          Kalimantan Selatan
        </span>
      </div>

      <div class="text-right">
        <p class="text-[11px] text-gray-500 font-medium">
          &copy; {{ currentYear }} Pemerintah Kota Banjarmasin. All Rights Reserved.
        </p>
      </div>
      
    </div>
  </footer>
</template>

<script setup lang="ts">
const currentYear = new Date().getFullYear();
</script>