<template>
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center shadow-md">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">Penyaluran Bantuan</h1>
        <p class="text-[13px] text-slate-500 font-medium">Salurkan bantuan yang sudah disetujui</p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <span v-if="selectedCount > 0" class="text-sm font-bold text-[#1B4332] bg-[#1B4332]/10 px-4 py-2 rounded-xl">
        {{ selectedCount }} terpilih
      </span>
      <button 
        v-if="selectedCount > 0"
        @click="$emit('bulkDisburse')"
        class="px-4 py-2 bg-[#1B4332] text-white text-sm font-bold rounded-xl hover:bg-[#2D6A4F] transition-all"
      >
        Salurkan yang Dipilih
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  total: number;
  selectedCount: number;
  filterStatus: string;
}>();

defineEmits<{
  'update:filterStatus': [value: string];
  bulkDisburse: [];
}>();
</script>