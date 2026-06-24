<template>
  <div class="bg-white border border-[#E8D5C4] rounded-2xl overflow-hidden shadow-sm mb-5">
    <div class="px-6 py-4 bg-[#FAF6F0] border-b border-[#E8D5C4]">
      <h3 class="text-sm font-black text-[#1B4332] uppercase tracking-wider">1. Kriteria dan Bobot (Sub Alternatif)</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0]/50 text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-5 py-3">Kode</th>
            <th class="px-5 py-3">Kriteria</th>
            <th class="px-5 py-3 text-center">Sifat</th>
            <th class="px-5 py-3 text-center">Bobot</th>
            <th class="px-5 py-3 text-center">Nilai Ideal</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="(c, index) in criteria" :key="c.key" class="hover:bg-[#FAF6F0]/30">
            <td class="px-5 py-3 text-sm font-bold text-[#1B4332]">C{{ index + 1 }}</td>
            <td class="px-5 py-3 text-sm font-bold text-[#1B4332]">{{ c.label }}</td>
            <td class="px-5 py-3 text-center">
              <span v-if="c.sifat === 'benefit'" class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase">Benefit</span>
              <span v-else class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase">Cost</span>
            </td>
            <td class="px-5 py-3 text-center text-sm font-bold text-[#1B4332]">{{ c.weight }}%</td>
            <td class="px-5 py-3 text-center text-sm font-bold text-[#1B4332]">{{ formatIdeal(c) }}</td>
          </tr>
          <tr class="bg-[#FAF6F0]/80 font-black">
            <td colspan="3" class="px-5 py-3 text-right text-xs text-[#1B4332] uppercase">Total Bobot</td>
            <td class="px-5 py-3 text-center text-sm font-black text-[#1B4332]">{{ totalWeight }}%</td>
            <td class="px-5 py-3"></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { SpkCriteria } from '../../types/spk'

const props = defineProps<{
  criteria: SpkCriteria[]
}>()

const totalWeight = computed<number>(() => {
  return props.criteria.reduce((sum: number, c: SpkCriteria) => sum + c.weight, 0)
})

const formatIdeal = (c: SpkCriteria): string => {
  if (c.type === 'currency') {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(c.ideal_value)
  }
  return new Intl.NumberFormat('id-ID').format(c.ideal_value)
}
</script>