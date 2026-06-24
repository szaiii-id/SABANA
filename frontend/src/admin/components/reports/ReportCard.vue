<script setup lang="ts">
import type { ReportItem } from '../../types/reportAdmin'
import { DocumentArrowDownIcon } from '@heroicons/vue/24/outline'

defineProps<{
  report: ReportItem
  isDownloading: boolean
}>()

const emit = defineEmits<{
  'download': []
}>()
</script>

<template>
  <div
    class="group bg-white/80 backdrop-blur-md rounded-2xl border border-[#E8D5C4] p-6 hover:shadow-lg hover:border-[#2D6A4F]/30 transition-all duration-300 flex flex-col justify-between cursor-pointer"
  >
    <!-- Icon & Title -->
    <div>
      <div class="w-10 h-10 bg-[#F0F7F4] rounded-xl flex items-center justify-center mb-3 group-hover:bg-[#2D6A4F] transition-colors">
        <DocumentArrowDownIcon class="w-5 h-5 text-[#2D6A4F] group-hover:text-white transition-colors" />
      </div>
      <h3 class="font-semibold text-[#1B4332] text-sm leading-tight">{{ report.title }}</h3>
      <p class="text-xs text-[#6B705C] mt-1.5 line-clamp-2">{{ report.description }}</p>
    </div>

    <!-- Button -->
    <button
      @click="emit('download')"
      :disabled="isDownloading"
      class="mt-5 w-full flex items-center justify-center gap-2 bg-gradient-to-r from-[#1B4332] to-[#2D6A4F] text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:from-[#16382A] hover:to-[#245A3F] disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md shadow-[#1B4332]/10 hover:shadow-lg hover:shadow-[#1B4332]/20 active:scale-[0.98]"
    >
      <!-- Spinner -->
      <svg
        v-if="isDownloading"
        class="animate-spin h-4 w-4 text-white"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
      <DocumentArrowDownIcon v-else class="w-4 h-4" />
      {{ isDownloading ? 'Mengunduh...' : 'Cetak PDF' }}
    </button>
  </div>
</template>