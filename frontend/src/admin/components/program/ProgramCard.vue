<template>
  <div class="bg-white border border-[#E8D5C4] rounded-3xl overflow-hidden shadow-sm hover:shadow-xl hover:border-[#D4A373] transition-all duration-300 group flex flex-col">
    
    <div class="relative h-44 bg-gradient-to-br from-[#1B4332]/5 to-[#D4A373]/5 flex items-center justify-center overflow-hidden">
      <img v-if="program.banner_url" :src="program.banner_url" :alt="program.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
      <div v-else class="flex flex-col items-center gap-2">
        <svg class="w-14 h-14 text-[#D4A373]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <span class="text-[11px] font-bold text-[#D4A373]/40 uppercase tracking-wider">No Image</span>
      </div>
      <div class="absolute top-3 right-3">
        <span :class="statusBadge(program.status)" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm backdrop-blur-sm">
          {{ formatStatus(program.status) }}
        </span>
      </div>
    </div>

    <div class="p-5 flex flex-col flex-1">
      <h3 class="font-black text-[#1B4332] text-lg mb-1 line-clamp-2 leading-tight">{{ program.name }}</h3>
      <p class="text-xs text-[#6B705C] line-clamp-2 mb-4 leading-relaxed">{{ program.description }}</p>
      
      <div class="space-y-2.5 text-xs flex-1">
        <div v-if="program.start_date" class="flex items-center gap-2.5 text-[#6B705C]">
          <div class="w-8 h-8 rounded-lg bg-[#FAF6F0] flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <span class="font-medium">{{ formatDate(program.start_date) }} - {{ formatDate(program.end_date) }}</span>
        </div>
        
        <div v-if="program.quota_total" class="flex items-center gap-2.5 text-[#6B705C]">
          <div class="w-8 h-8 rounded-lg bg-[#FAF6F0] flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <span class="font-medium">Kuota: <strong>{{ formatNumber(program.quota_total) }}</strong> penerima</span>
        </div>

        <div v-if="program.benefit_amount" class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-[#D4A373]/10 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-[#D4A373]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <span class="font-bold text-[#D4A373]">Rp {{ formatCurrency(program.benefit_amount) }} <span class="font-medium text-[#6B705C]">/ orang</span></span>
        </div>
      </div>

      <div class="flex gap-2.5 mt-5 pt-4 border-t border-[#E8D5C4]">
        <button @click="$emit('edit', program)" class="flex-1 px-4 py-2.5 text-xs font-bold text-[#1B4332] bg-[#FAF6F0] rounded-xl hover:bg-[#1B4332] hover:text-white transition-all duration-300 flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
          Edit
        </button>
        <button v-if="program.status === 'active'" @click="$emit('close', program)" class="flex-1 px-4 py-2.5 text-xs font-bold text-amber-700 bg-amber-50 rounded-xl hover:bg-amber-100 transition-all duration-300 flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
          Tutup
        </button>
        <button v-if="program.status === 'closed'" @click="$emit('reopen', program)" class="flex-1 px-4 py-2.5 text-xs font-bold text-green-700 bg-green-50 rounded-xl hover:bg-green-100 transition-all duration-300 flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          Buka
        </button>
        <button @click="$emit('duplicate', program)" class="flex-1 px-4 py-2.5 text-xs font-bold text-blue-700 bg-blue-50 rounded-xl hover:bg-blue-100 transition-all duration-300 flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
          Duplikat
        </button>
        <button @click="$emit('delete', program)" class="flex-1 px-4 py-2.5 text-xs font-bold text-[#6B705C] bg-gray-50 rounded-xl hover:bg-red-50 hover:text-red-600 transition-all duration-300 flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          Hapus
        </button>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import type { ProgramData } from '../../types/program';

defineProps<{ program: ProgramData }>();
defineEmits<{ 
  edit: [program: ProgramData]; 
  delete: [program: ProgramData];
  close: [program: ProgramData];
  reopen: [program: ProgramData];
  duplicate: [program: ProgramData];
}>();

const formatStatus = (status: string | null) => ({ draft: 'Draft', active: 'Aktif', closed: 'Tertutup', completed: 'Selesai' }[status || ''] || 'Draft');
const statusBadge = (status: string | null) => ({ draft: 'bg-white/80 text-gray-600', active: 'bg-green-100/90 text-green-700', closed: 'bg-amber-100/90 text-amber-700', completed: 'bg-blue-100/90 text-blue-700' }[status || ''] || 'bg-white/80 text-gray-600');
const formatDate = (date: string | null) => date ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';
const formatNumber = (num: number) => new Intl.NumberFormat('id-ID').format(num);
const formatCurrency = (num: number) => new Intl.NumberFormat('id-ID').format(num);
</script>