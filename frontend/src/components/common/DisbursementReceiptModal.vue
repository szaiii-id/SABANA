<template>
  <Transition name="modal">
    <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-[#4A3728]/30 backdrop-blur-sm" @click="$emit('close')"></div>
      
      <div class="bg-white rounded-[2rem] max-w-lg w-full shadow-2xl relative z-10 border border-[#F3E5D8] overflow-hidden">
        
        <!-- Header Hijau -->
        <div class="bg-[#1B4332] p-6 text-white text-center">
          <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <CheckBadgeIcon class="w-8 h-8" />
          </div>
          <h2 class="text-xl font-black tracking-tight">Bukti Penyaluran</h2>
          <p class="text-[#A7C4B5] text-xs font-medium mt-1">{{ data?.reference_number }}</p>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5 max-h-[50vh] overflow-y-auto">
          
          <!-- Data Penerima -->
          <div>
            <p class="text-[9px] font-black text-[#1B4332] uppercase tracking-[0.2em] mb-3">Data Penerima</p>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">Nama</span>
                <span class="font-bold text-[#4A3728]">{{ data?.citizen_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">NIK</span>
                <span class="font-bold text-[#4A3728]">{{ data?.citizen_nik }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">No. KK</span>
                <span class="font-bold text-[#4A3728]">{{ data?.family_card_number }}</span>
              </div>
            </div>
          </div>

          <hr class="border-[#F3E5D8]">

          <!-- Data Bantuan -->
          <div>
            <p class="text-[9px] font-black text-[#1B4332] uppercase tracking-[0.2em] mb-3">Data Bantuan</p>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">Program</span>
                <span class="font-bold text-[#4A3728]">{{ data?.program_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">No. Registrasi</span>
                <span class="font-bold text-[#4A3728]">{{ data?.registration_number }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[#8B5E3C]">Jumlah</span>
                <span class="text-lg font-black text-[#1B4332]">Rp {{ formatAmount(data?.amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">Metode</span>
                <span class="font-bold text-[#4A3728]">{{ data?.method }}</span>
              </div>
            </div>
          </div>

          <hr class="border-[#F3E5D8]">

          <!-- Data Penyaluran -->
          <div>
            <p class="text-[9px] font-black text-[#1B4332] uppercase tracking-[0.2em] mb-3">Data Penyaluran</p>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">Tanggal</span>
                <span class="font-bold text-[#4A3728]">{{ data?.disbursed_at }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-[#8B5E3C]">Petugas</span>
                <span class="font-bold text-[#4A3728]">{{ data?.officer_name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-5 bg-[#FDFBF9] border-t border-[#F3E5D8] flex gap-3">
          <button 
            @click="handleDownloadPdf"
            :disabled="isDownloading"
            class="flex-1 py-3 bg-[#1B4332] text-white font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-[#2D6A4F] transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
            <ArrowDownTrayIcon class="w-4 h-4" />
            {{ isDownloading ? 'Mengunduh...' : 'Download PDF' }}
          </button>
          <button 
            @click="$emit('close')"
            class="flex-1 py-3 bg-stone-100 text-[#8B5E3C] font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-stone-200 transition-colors">
            Tutup
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { CheckBadgeIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import type { DisbursementReceipt } from '../../types/assistance';

const props = defineProps<{
  visible: boolean;
  data: DisbursementReceipt | null;
  submissionId: string;
}>();

const emit = defineEmits<{
  close: [];
  download: [submissionId: string];
}>();

const isDownloading = ref(false);

const formatAmount = (amount?: number) => {
  if (!amount) return '0';
  return amount.toLocaleString('id-ID');
};

const handleDownloadPdf = () => {
  isDownloading.value = true;
  emit('download', props.submissionId);
  setTimeout(() => {
    isDownloading.value = false;
  }, 2000);
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.95) translateY(20px);
}
</style>