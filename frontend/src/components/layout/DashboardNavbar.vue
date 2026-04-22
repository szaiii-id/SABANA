<template>
  <nav class="relative z-20 bg-white/70 backdrop-blur-xl border-b border-white sticky top-0 h-20 flex items-center">
    <div class="max-w-7xl mx-auto px-6 w-full flex justify-between items-center">
      <router-link to="/dashboard" class="text-2xl font-[1000] italic tracking-tighter text-[#2D6A4F]">
        SABANA
      </router-link>
      <button @click="$emit('logout')" class="px-5 py-2 rounded-xl bg-red-50 text-red-600 font-black text-[10px] uppercase tracking-widest hover:bg-red-100 transition-all">
        Keluar
      </button>
    </div>
  </nav>
</template>
<script setup lang="ts">
defineEmits(['logout']);
</script>