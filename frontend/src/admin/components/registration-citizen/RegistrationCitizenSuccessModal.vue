<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
      <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden border border-[#E8D5C4]">
        
        <div class="p-10 text-center">
          <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          
          <h3 class="text-2xl font-black text-[#1B4332] mb-2">Warga Berhasil Didaftarkan</h3>
          
          <div class="bg-[#FAF6F0] rounded-2xl p-5 mb-6 space-y-3 text-left">
            <div class="flex justify-between">
              <span class="text-xs font-bold text-[#6B705C]">NIK</span>
              <span class="text-sm font-bold text-[#1B4332]">{{ citizen?.nik }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-xs font-bold text-[#6B705C]">Nama</span>
              <span class="text-sm font-bold text-[#1B4332]">{{ citizen?.full_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-xs font-bold text-[#6B705C]">No. KK</span>
              <span class="text-sm font-bold text-[#1B4332]">{{ citizen?.family_card_number }}</span>
            </div>
            <div v-if="citizen?.whatsapp_number" class="flex justify-between">
              <span class="text-xs font-bold text-[#6B705C]">WhatsApp</span>
              <span class="text-sm font-bold text-[#1B4332]">{{ citizen?.whatsapp_number }}</span>
            </div>
          </div>

          <button 
            @click="$emit('close')" 
            class="w-full px-6 py-3.5 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white font-bold rounded-xl hover:shadow-lg transition-all"
          >
            Selesai
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  citizen: any;
  accessPin: string;
}>();

defineEmits<{ close: [] }>();
</script>