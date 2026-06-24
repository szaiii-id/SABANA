<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden border border-[#E8D5C4] p-8 text-center">
        
        <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
          </svg>
        </div>
        
        <h3 class="text-lg font-black text-[#1B4332] mb-2">Kirim PIN Baru?</h3>
        <p class="text-sm text-[#6B705C] mb-6">
          PIN baru akan digenerate dan dikirim ke WhatsApp <strong>{{ citizenName }}</strong> ({{ citizenWhatsapp }}).
        </p>
        
        <div class="flex gap-3">
          <button @click="$emit('close')" class="flex-1 py-3 px-4 bg-stone-100 text-[#8B5E3C] font-bold rounded-xl hover:bg-stone-200 transition-colors text-sm">
            Batal
          </button>
          <button @click="$emit('confirm')" class="flex-1 py-3 px-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-sm text-sm">
            Ya, Kirim
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  citizenName: string;
  citizenWhatsapp: string;
}>();

defineEmits<{
  close: [];
  confirm: [];
}>();
</script>