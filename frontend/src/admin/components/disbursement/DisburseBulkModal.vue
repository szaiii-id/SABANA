<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] p-8 max-w-sm w-full shadow-2xl border border-[#E8D5C4] text-center">
        
        <!-- Icon -->
        <div class="w-16 h-16 bg-[#FDF8F1] rounded-2xl flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        
        <h3 class="text-xl font-black text-[#1B4332] mb-2">Konfirmasi Penyaluran Massal</h3>
        <p class="text-sm text-[#8B5E3C] mb-6">Anda akan menyalurkan <strong class="text-[#1B4332] text-lg">{{ count }}</strong> bantuan sekaligus.</p>
        
        <!-- Actions -->
        <div class="grid grid-cols-2 gap-3">
          <button @click="$emit('close')" class="py-3.5 bg-[#FAF6F0] text-[#8B5E3C] font-bold rounded-xl hover:bg-[#E8D5C4] transition-all text-sm border border-[#E8D5C4]">
            Batal
          </button>
          <button @click="$emit('confirm')" :disabled="isSubmitting" class="py-3.5 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm disabled:opacity-50 flex items-center justify-center gap-2">
            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Ya, Salurkan Semua
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  count: number;
  isSubmitting: boolean;
}>();

defineEmits<{
  close: [];
  confirm: [];
}>();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>