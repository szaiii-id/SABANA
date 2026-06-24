<!-- src/admin/pages/spk/Index.vue -->
<template>
  <div class="flex flex-col h-full px-4 md:px-6 py-6">
    
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-black text-[#1B4332] tracking-tight">SPK - Sistem Pendukung Keputusan</h2>
      <p class="text-[#6B705C] font-medium mt-1 text-sm">Metode Simple Additive Weighting (SMART) - Pilih program untuk melihat perhitungan</p>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
      <div class="relative max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#6B705C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari program..."
          class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-[#E8D5C4] text-sm focus:border-[#1B4332] outline-none transition-colors"
          @input="onSearch"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="flex items-center gap-2 text-[#6B705C] text-sm">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Memuat program...
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
      <p class="text-sm font-bold text-red-700">{{ error }}</p>
      <button @click="fetchPrograms()" class="mt-3 px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-colors">
        Coba Lagi
      </button>
    </div>

    <!-- Empty -->
    <div v-else-if="!programs.length" class="text-center py-20">
      <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <p class="font-bold text-gray-400 text-lg">Tidak ada program aktif</p>
      <p class="text-sm text-[#6B705C] mt-1">Program akan muncul di sini jika sudah aktif dan dalam masa pendaftaran.</p>
    </div>

    <!-- Card Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="program in programs"
        :key="program.id"
        @click="selectProgram(program.id)"
        class="bg-white border-2 border-[#E8D5C4] rounded-2xl overflow-hidden cursor-pointer transition-all duration-200 hover:border-[#1B4332] hover:shadow-lg group"
      >
        <!-- Banner -->
        <div class="w-full h-36 bg-gradient-to-br from-[#1B4332]/5 to-[#D4A373]/10 overflow-hidden">
          <img 
            v-if="program.banner_url" 
            :src="program.banner_url" 
            :alt="program.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <svg class="w-12 h-12 text-[#D4A373]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
        </div>

        <!-- Content -->
        <div class="p-5">
          <div class="flex items-start justify-between mb-2">
            <h3 class="text-base font-black text-[#1B4332] leading-tight flex-1 mr-3">{{ program.name }}</h3>
            <span class="px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-[9px] font-bold uppercase tracking-wider flex-shrink-0">Aktif</span>
          </div>

          <p class="text-xs text-[#6B705C] mb-4 line-clamp-2">{{ program.description || 'Tidak ada deskripsi.' }}</p>

          <div class="flex flex-wrap gap-2 mb-4">
            <span class="px-2.5 py-1 bg-[#FAF6F0] text-[#1B4332] rounded-lg text-[10px] font-bold">
              {{ program.submissions_count }} Pendaftar
            </span>
            <span v-if="program.quota_total" class="px-2.5 py-1 bg-[#FAF6F0] text-[#1B4332] rounded-lg text-[10px] font-bold">
              Kuota: {{ program.quota_total }}
            </span>
            <span class="px-2.5 py-1 bg-[#FAF6F0] text-[#6B705C] rounded-lg text-[10px] font-bold">
              {{ program.criteria_count }} Kriteria
            </span>
          </div>

          <div class="pt-3 border-t border-[#E8D5C4] flex items-center justify-between">
            <span class="text-[10px] text-[#6B705C] uppercase tracking-wider font-bold">Lihat SPK</span>
            <svg class="w-4 h-4 text-[#D4A373] group-hover:text-[#1B4332] group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSpk } from '../../composables/useSpk'

const router = useRouter()
const { programs, isLoading, error, fetchPrograms } = useSpk()

const searchQuery = ref('')
let debounceTimer: ReturnType<typeof setTimeout>

const onSearch = (): void => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchPrograms(searchQuery.value || undefined)
  }, 300)
}

const selectProgram = (programId: string): void => {
  router.push({ name: 'admin.spk.program', params: { programId } })
}

onMounted(() => {
  fetchPrograms()
})
</script>