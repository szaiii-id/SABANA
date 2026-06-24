<template>
  <div class="bg-white rounded-[2rem] p-6 shadow-sm ring-1 ring-slate-200/60">
    
    <!-- Header -->
    <div class="flex items-center gap-3 mb-5">
      <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-slate-800 text-[15px]">
          {{ selectedProgram ? 'Program Terpilih' : 'Pilih Program' }}
        </h3>
        <p class="text-[13px] text-slate-400">Untuk <span class="font-medium text-slate-600">{{ citizenName }}</span></p>
      </div>
    </div>

    <!-- STATE 1: Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div v-for="i in 3" :key="i" class="rounded-2xl border border-slate-200 overflow-hidden animate-pulse">
        <div class="h-40 bg-slate-100"></div>
        <div class="p-4 space-y-3">
          <div class="h-5 bg-slate-100 rounded w-3/4"></div>
          <div class="h-4 bg-slate-100 rounded w-full"></div>
        </div>
      </div>
    </div>

    <!-- STATE 2: Empty -->
    <div v-else-if="!programs.length" class="text-center py-12">
      <p class="font-medium text-slate-500">Tidak ada program aktif</p>
    </div>

    <!-- STATE 3: Program Terpilih -->
    <div v-else-if="selectedProgram" class="space-y-3">
      <div class="rounded-2xl border-2 border-[#1B4332] bg-gradient-to-br from-[#1B4332]/[0.03] to-[#2D6A4F]/[0.03] overflow-hidden">
        <div class="flex flex-col sm:flex-row">
          <div class="relative w-full sm:w-48 h-40 sm:h-auto flex-shrink-0 overflow-hidden bg-gradient-to-br from-[#1B4332]/5 to-[#D4A373]/10">
            <img v-if="selectedProgram.banner_url" :src="selectedProgram.banner_url" :alt="selectedProgram.title" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-12 h-12 text-[#D4A373]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
            <div class="absolute top-2 left-2">
              <span class="px-2.5 py-1 rounded-full text-[9px] font-bold uppercase bg-[#1B4332] text-white shadow-sm">
                Dipilih
              </span>
            </div>
          </div>

          <div class="flex-1 p-4 flex flex-col justify-center">
            <h4 class="font-bold text-slate-800 text-[16px] leading-snug">{{ selectedProgram.title }}</h4>
            <p v-if="selectedProgram.description" class="text-[13px] text-slate-500 mt-1 line-clamp-2">{{ selectedProgram.description }}</p>
            <div class="flex items-center gap-3 mt-2">
              <span v-if="selectedProgram.benefit_amount" class="text-[15px] font-bold text-[#D4A373]">
                Rp {{ formatCurrency(selectedProgram.benefit_amount) }}
              </span>
              <span v-if="selectedProgram.quota_total" class="text-[12px] text-slate-400">
                {{ formatNumber(selectedProgram.quota_total) }} kuota
              </span>
            </div>
          </div>
        </div>
      </div>

      <button 
        @click="$emit('unselect')"
        class="text-[13px] font-medium text-slate-500 hover:text-[#1B4332] transition-colors flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Pilih program lain
      </button>
    </div>

    <!-- STATE 4: Grid Program -->
    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div 
          v-for="program in paginatedPrograms" :key="program.id"
          @click="!program.has_submitted && $emit('select', program)"
          :class="[
            'rounded-2xl border-2 overflow-hidden transition-all duration-300',
            program.has_submitted 
              ? 'border-slate-100 opacity-60 cursor-not-allowed' 
              : 'border-slate-200 cursor-pointer hover:border-[#1B4332]/40 hover:shadow-lg'
          ]"
        >
          <div class="relative h-40 overflow-hidden bg-gradient-to-br from-[#1B4332]/5 to-[#D4A373]/10">
            <img 
              v-if="program.banner_url" 
              :src="program.banner_url" 
              :alt="program.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg class="w-12 h-12 text-[#D4A373]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
            <div class="absolute top-2 left-2">
              <span v-if="program.has_submitted" class="px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-amber-100/90 backdrop-blur-sm text-amber-700 shadow-sm">
                Sudah Terdaftar
              </span>
              <span v-else class="px-2.5 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider bg-white/90 backdrop-blur-sm text-[#2D6A4F] shadow-sm">
                Aktif
              </span>
            </div>
          </div>

          <div class="p-4">
            <h4 class="font-bold text-slate-800 text-[15px] leading-snug line-clamp-2">
              {{ program.title }}
            </h4>
            <p v-if="program.description" class="text-[12px] text-slate-500 mt-1 line-clamp-2 leading-relaxed">
              {{ program.description }}
            </p>
            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100">
              <span v-if="program.benefit_amount" class="text-[13px] font-bold text-[#D4A373]">
                Rp {{ formatCurrency(program.benefit_amount) }}
              </span>
              <span v-if="program.has_submitted" class="text-[11px] font-bold text-amber-600 ml-auto">
                Sudah Terdaftar
              </span>
              <span v-else-if="program.quota_total" class="text-[12px] text-slate-400 ml-auto">
                {{ formatNumber(program.quota_total) }} kuota
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="totalPages > 1" class="flex justify-center mt-5">
        <nav class="flex items-center gap-1">
          <button :disabled="currentPage === 1" @click="currentPage--" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed">←</button>
          <button v-for="page in totalPages" :key="page" @click="currentPage = page" :class="['w-8 h-8 text-[12px] font-medium rounded-lg', currentPage === page ? 'bg-[#1B4332] text-white' : 'text-slate-600 hover:bg-slate-100']">{{ page }}</button>
          <button :disabled="currentPage === totalPages" @click="currentPage++" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed">→</button>
        </nav>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { AssistanceProgramSchema } from '../../../types/assistance';

const props = defineProps<{
  citizenName: string;
  programs: AssistanceProgramSchema[];
  selectedId: string | null;
  loading: boolean;
}>();

defineEmits<{
  select: [program: AssistanceProgramSchema];
  unselect: [];
}>();

const currentPage = ref(1);
const perPage = 6;

const totalPages = computed(() => Math.ceil(props.programs.length / perPage));

const paginatedPrograms = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return props.programs.slice(start, start + perPage);
});

const selectedProgram = computed(() => {
  if (!props.selectedId) return null;
  return props.programs.find(p => p.id === props.selectedId) || null;
});

const formatCurrency = (num: number): string => new Intl.NumberFormat('id-ID').format(num);
const formatNumber = (num: number): string => new Intl.NumberFormat('id-ID').format(num);
</script>