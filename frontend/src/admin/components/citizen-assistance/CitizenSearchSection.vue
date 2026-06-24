<template>
  <div class="bg-white rounded-[2rem] p-6 shadow-sm ring-1 ring-slate-200/60">
    
    <!-- Header -->
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 bg-[#1B4332]/8 rounded-xl flex items-center justify-center">
        <svg class="w-5 h-5 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-slate-800 text-[15px]">Cari Warga</h3>
        <p class="text-[13px] text-slate-400">Cari berdasarkan NIK atau nama lengkap</p>
      </div>
    </div>

    <!-- Search Input -->
    <div class="relative">
      <input 
        :value="query"
        @input="$emit('search', ($event.target as HTMLInputElement).value)"
        type="text" 
        placeholder="Ketik NIK atau nama (min. 3 karakter)..."
        autocomplete="off"
        class="w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#1B4332] focus:ring-2 focus:ring-[#1B4332]/10 focus:bg-white rounded-xl text-[14px] text-slate-700 placeholder:text-slate-400 transition-all outline-none"
      />
      <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
        <svg class="h-[18px] w-[18px] text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
        <div class="w-[18px] h-[18px] border-2 border-slate-200 border-t-[#1B4332] rounded-full animate-spin"></div>
      </div>
    </div>

    <!-- Hasil Pencarian -->
    <div v-if="results.length" class="mt-3 bg-white border border-slate-200 rounded-xl max-h-64 overflow-y-auto shadow-sm">
      <div 
        v-for="citizen in results" :key="citizen.id"
        @click="$emit('select', citizen)"
        class="px-4 py-3.5 cursor-pointer hover:bg-slate-50 transition-colors flex items-center gap-3 border-b border-slate-50 last:border-0"
      >
        <div class="w-9 h-9 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-lg flex items-center justify-center text-white font-bold text-[13px] flex-shrink-0">
          {{ citizen.full_name?.charAt(0) }}
        </div>
        <div class="min-w-0">
          <p class="text-[14px] font-medium text-slate-800 truncate">{{ citizen.full_name }}</p>
          <p class="text-[12px] text-slate-400 font-mono">{{ citizen.nik }}</p>
        </div>
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="px-4 py-2.5 text-center border-t border-slate-100">
        <button 
          v-if="!isLoadingMore"
          @click="$emit('loadMore')"
          class="text-[12px] font-medium text-[#1B4332] hover:underline"
        >
          Tampilkan lebih banyak
        </button>
        <div v-else class="flex items-center justify-center gap-2 text-[12px] text-slate-400">
          <div class="w-3.5 h-3.5 border-2 border-slate-300 border-t-[#1B4332] rounded-full animate-spin"></div>
          Memuat...
        </div>
      </div>

      <div v-else class="px-4 py-2.5 text-center border-t border-slate-100">
        <p class="text-[11px] text-slate-400">Semua data telah ditampilkan</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="query.length >= 3 && !isSearching && hasSearched" class="mt-4 py-10 text-center">
      <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <p class="text-[14px] font-medium text-slate-500">Warga tidak ditemukan</p>
      <p class="text-[12px] text-slate-400 mt-1">Coba periksa kembali NIK atau nama</p>
    </div>

    <!-- Warga Terpilih -->
    <transition name="slide-up">
      <div v-if="selected" class="mt-4 p-4 bg-gradient-to-r from-[#1B4332]/4 to-[#2D6A4F]/4 rounded-xl border border-[#1B4332]/10">
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-3.5 min-w-0">
            <div class="w-11 h-11 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center text-white font-bold text-[15px] flex-shrink-0 shadow-sm">
              {{ selected.full_name?.charAt(0) }}
            </div>
            <div class="min-w-0">
              <p class="font-semibold text-slate-800 text-[14px] truncate">{{ selected.full_name }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-[12px] text-slate-500">NIK: {{ selected.nik }}</span>
                <span class="text-slate-300">·</span>
                <span class="text-[12px] text-slate-500">KK: {{ selected.family_card_number }}</span>
              </div>
              <p v-if="selected.whatsapp_number" class="text-[12px] text-slate-400 mt-0.5">
                WA: {{ selected.whatsapp_number }}
              </p>
            </div>
          </div>
          <button 
            @click="$emit('remove')" 
            class="px-3.5 py-2 text-[12px] font-medium text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all flex-shrink-0"
          >
            Ganti
          </button>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import type { CitizenSearchResult } from '../../types/citizen-assistance';

defineProps<{
  query: string;
  results: CitizenSearchResult[];
  selected: CitizenSearchResult | null;
  isSearching: boolean;
  hasSearched: boolean;
  hasMore: boolean;
  isLoadingMore: boolean;
}>();

defineEmits<{
  search: [query: string];
  select: [citizen: CitizenSearchResult];
  remove: [];
  loadMore: [];
}>();
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

.overflow-y-auto::-webkit-scrollbar {
  width: 5px;
}
.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #E2E8F0;
  border-radius: 10px;
}
</style>