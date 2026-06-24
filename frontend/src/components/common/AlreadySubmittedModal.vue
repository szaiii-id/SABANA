<template>
  <transition name="modal">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-[#4A3728]/30 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl relative z-10 border border-[#F3E5D8]">
        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-5">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-black mb-2 tracking-tight">Sudah Terdaftar</h3>
        <p class="text-[#8B5E3C] text-xs mb-6 leading-relaxed font-medium">
          Anda sudah mengajukan permohonan untuk program <strong>{{ programTitle }}</strong>. 
          Silakan cek riwayat untuk melihat status pengajuan Anda.
        </p>
        <div class="grid grid-cols-2 gap-3">
          <button @click="$emit('close')" class="py-3 px-4 bg-stone-100 text-[#8B5E3C] font-black uppercase tracking-widest text-[9px] rounded-xl hover:bg-stone-200 transition-colors">Tutup</button>
          <button @click="$emit('goHistory')" class="py-3 px-4 bg-amber-500 text-white font-black uppercase tracking-widest text-[9px] rounded-xl shadow-lg shadow-amber-200 hover:bg-amber-600 transition-all">
            Cek Riwayat
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  programTitle: string;
}>();

defineEmits<{
  close: [];
  goHistory: [];
}>();
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>