<template>
  <div class="bg-white border border-[#E8D5C4] rounded-3xl shadow-sm overflow-hidden flex flex-col">
    
    <div v-if="loading" class="p-8 space-y-4">
      <div v-for="i in 3" :key="i" class="flex items-center gap-4 animate-pulse">
        <div class="h-12 w-12 rounded-2xl bg-gray-200"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-1/3"></div>
          <div class="h-3 bg-gray-200 rounded w-1/4"></div>
        </div>
        <div class="h-8 w-16 bg-gray-200 rounded-xl"></div>
      </div>
    </div>

    <div v-else-if="!citizens.length" class="py-16 text-center">
      <div class="w-20 h-20 bg-[#FAF6F0] rounded-3xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-10 h-10 text-[#D4A373]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <p class="font-bold text-[#1B4332] text-lg">Belum ada warga terdaftar</p>
      <p class="text-sm text-[#6B705C] mt-1">Klik "Daftarkan Warga" untuk menambahkan.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0] text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-8 py-5 rounded-tl-3xl w-[38%]">Warga</th>
            <th class="px-5 py-5 w-[17%]">No. KK</th>
            <th class="px-5 py-5 w-[14%]">WhatsApp</th>
            <th class="px-8 py-5 text-right rounded-tr-3xl w-[31%]">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="citizen in citizens" :key="citizen.id" class="hover:bg-[#FAF6F0]/30 transition-colors">
            <td class="px-8 py-5">
              <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] flex items-center justify-center text-white font-black text-base shadow-md flex-shrink-0">
                  {{ citizen.full_name?.charAt(0).toUpperCase() || 'W' }}
                </div>
                <div class="min-w-0">
                  <router-link 
                    :to="{ name: 'admin.citizen-registration.detail', params: { id: citizen.id } }"
                    class="font-bold text-[#1F2937] text-sm truncate hover:text-[#1B4332] hover:underline transition-colors block"
                  >
                    {{ citizen.full_name }}
                  </router-link>
                  <p class="text-xs text-[#6B705C] font-medium mt-1">{{ citizen.nik }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-5">
              <span class="text-sm font-bold text-[#4A3728] tracking-wide">{{ citizen.family_card_number }}</span>
            </td>
            <td class="px-5 py-5">
              <span :class="citizen.whatsapp_number ? 'text-[#1B4332] font-bold' : 'text-[#6B705C]/50'" class="text-sm">
                {{ citizen.whatsapp_number || '—' }}
              </span>
            </td>
            <td class="px-8 py-5 text-right">
              <div class="flex items-center justify-end gap-2">
                <button 
                  @click="$emit('edit', citizen)"
                  class="px-4 py-2.5 text-xs font-bold text-amber-700 bg-amber-50 rounded-xl hover:bg-amber-500 hover:text-white transition-all duration-300 shadow-sm"
                >
                  Edit
                </button>
                <router-link 
                  :to="{ name: 'admin.citizen-registration.detail', params: { id: citizen.id } }"
                  class="px-4 py-2.5 text-xs font-bold text-[#1B4332] bg-[#FAF6F0] rounded-xl hover:bg-[#1B4332] hover:text-white transition-all duration-300 shadow-sm"
                >
                  Detail
                </router-link>
                <button 
                  v-if="citizen.whatsapp_number"
                  @click="requestResendPin(citizen)" 
                  class="px-4 py-2.5 text-xs font-bold text-blue-700 bg-blue-50 rounded-xl hover:bg-blue-500 hover:text-white transition-all duration-300 shadow-sm"
                >
                  Kirim PIN
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && citizens.length > 0" class="px-8 py-4 border-t border-[#E8D5C4] bg-[#FAF6F0]/50 flex items-center justify-between">
      <span class="text-xs font-medium text-[#6B705C]">
        Total <span class="font-bold text-[#1B4332]">{{ total }}</span> warga
      </span>
      <div class="flex items-center gap-2">
        <button 
          :disabled="currentPage <= 1" 
          @click="$emit('pageChange', currentPage - 1)" 
          class="px-4 py-2 text-xs font-bold rounded-xl transition-colors" 
          :class="currentPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'"
        >Prev</button>
        <span class="text-xs font-bold text-[#1B4332]">{{ currentPage }} / {{ totalPages || 1 }}</span>
        <button 
          :disabled="currentPage >= totalPages" 
          @click="$emit('pageChange', currentPage + 1)" 
          class="px-4 py-2 text-xs font-bold rounded-xl transition-colors" 
          :class="currentPage >= totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'"
        >Next</button>
      </div>
    </div>

    <SendPinConfirmModal 
      :open="showResendConfirm"
      :citizenName="resendTarget?.full_name || ''"
      :citizenWhatsapp="resendTarget?.whatsapp_number || ''"
      @close="showResendConfirm = false; resendTarget = null"
      @confirm="confirmResendPin"
    />

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { RegisteredCitizen } from '../../types/registration-citizen';
import SendPinConfirmModal from '../common/SendPinConfirmModal.vue';

defineProps<{
  citizens: RegisteredCitizen[];
  loading: boolean;
  total: number;
  currentPage: number;
  totalPages: number;
}>();

const emit = defineEmits<{
  edit: [citizen: RegisteredCitizen];
  resendPin: [id: string];
  pageChange: [page: number];
}>();

const showResendConfirm = ref(false);
const resendTarget = ref<RegisteredCitizen | null>(null);

const requestResendPin = (citizen: RegisteredCitizen) => {
  resendTarget.value = citizen;
  showResendConfirm.value = true;
};

const confirmResendPin = () => {
  if (resendTarget.value) {
    emit('resendPin', resendTarget.value.id);
  }
  showResendConfirm.value = false;
  resendTarget.value = null;
};
</script>