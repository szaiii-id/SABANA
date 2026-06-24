<template>
  <div class="flex items-center justify-between">
    
    <!-- Left -->
    <div class="flex items-center gap-4">
      <div class="w-10 h-10 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center shadow-sm">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
        </svg>
      </div>
      <div>
        <h1 class="text-lg font-bold text-[#1B4332]">Antrean Evaluasi</h1>
        <p class="text-[12px] text-[#8B5E3C]/60">Evaluasi berkala 6 bulan</p>
      </div>
    </div>

    <!-- Right -->
    <div class="flex items-center gap-3">
      <div class="sm:w-44">
        <select 
          :value="filterStatus"
          @change="$emit('update:filterStatus', ($event.target as HTMLSelectElement).value)"
          class="w-full px-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium text-[#4A3728] transition-colors appearance-none cursor-pointer"
        >
          <option value="">Semua Status</option>
          <option value="triggered">Menunggu Warga</option>
          <option value="updated">Siap Diputuskan</option>
        </select>
      </div>
      
      <span v-if="total > 0" class="text-sm font-bold text-[#1B4332] bg-[#FAF6F0] px-4 py-2 rounded-2xl border border-[#E8D5C4]">
        {{ total }} evaluasi
      </span>
    </div>

  </div>
</template>

<script setup lang="ts">
defineProps<{
  total: number;
  filterStatus: string;
}>();

defineEmits<{
  'update:filterStatus': [value: string];
}>();
</script>