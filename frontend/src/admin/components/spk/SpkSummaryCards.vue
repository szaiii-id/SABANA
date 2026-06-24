<template>
  <div class="bg-white border border-[#E8D5C4] rounded-2xl overflow-hidden shadow-sm mb-5">
    <div class="px-6 py-4 bg-[#FAF6F0] border-b border-[#E8D5C4]">
      <h3 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">6. Ringkasan</h3>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-[#FAF6F0] rounded-xl p-4 text-center">
          <p class="text-2xl font-black text-[#1B4332]">{{ summary.total }}</p>
          <p class="text-[10px] font-bold text-[#6B705C] uppercase mt-1">Total Alternatif</p>
        </div>
        <div class="bg-[#FAF6F0] rounded-xl p-4 text-center">
          <p class="text-2xl font-black text-[#1B4332]">{{ summary.quota || '-' }}</p>
          <p class="text-[10px] font-bold text-[#6B705C] uppercase mt-1">Kuota</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-100">
          <p class="text-2xl font-black text-green-700">{{ formatScore(summary.highest) }}</p>
          <p class="text-[10px] font-bold text-green-600 uppercase mt-1">Skor Tertinggi</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center border border-red-100">
          <p class="text-2xl font-black text-red-700">{{ formatScore(summary.lowest) }}</p>
          <p class="text-[10px] font-bold text-red-600 uppercase mt-1">Skor Terendah</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-100">
          <p class="text-2xl font-black text-blue-700">{{ formatScore(summary.average) }}</p>
          <p class="text-[10px] font-bold text-blue-600 uppercase mt-1">Rata-rata</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-4 text-center border border-amber-100">
          <p class="text-2xl font-black text-amber-700">{{ summary.eligible }}</p>
          <p class="text-[10px] font-bold text-amber-600 uppercase mt-1">Layak (>=0.50)</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SpkSummary } from '../../types/spk'

defineProps<{
  summary: SpkSummary
}>()

const formatScore = (value: number): string => {
  if (value === 0) return '0.0000'
  return value.toFixed(4)
}
</script>