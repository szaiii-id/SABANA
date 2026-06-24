<!-- src/admin/components/spk/SpkWeightedMatrix.vue -->
<template>
  <div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0]/50 text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3">Nama Alternatif</th>
            <th v-for="c in criteria" :key="c.key" class="px-4 py-3 text-center">{{ c.label }}<br><span class="font-normal text-[10px] text-[#6B705C]">x {{ c.weight }}%</span></th>
            <th class="px-4 py-3 text-center">TOTAL</th>
            <th class="px-4 py-3 text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="(row, index) in data" :key="row.id" class="hover:bg-[#FAF6F0]/30">
            <td class="px-4 py-3 text-sm font-bold text-[#1B4332]">A{{ index + 1 }}</td>
            <td class="px-4 py-3 text-sm font-bold text-[#1B4332]">{{ row.name }}</td>
            <td v-for="c in criteria" :key="c.key" class="px-4 py-3 text-center">
              <span class="text-[10px] text-[#6B705C] leading-relaxed">{{ getFormulaText(row, c) }}</span><br>
              <span class="text-sm font-bold text-[#1B4332]">{{ formatValue(row[c.key + '_weighted']) }}</span>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-[10px] text-[#6B705C] leading-relaxed">{{ getTotalFormula(row) }}</span><br>
              <span class="text-sm font-black text-[#1B4332]">{{ formatValue(row.total) }}</span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="statusBadge(row.status)" class="px-2 py-1 rounded-full text-[9px] font-bold uppercase">{{ statusLabel(row.status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SpkCriteria, SpkWeightedRow } from '../../types/spk'

const props = defineProps<{ data: SpkWeightedRow[]; criteria: SpkCriteria[] }>()

const formatValue = (val: number | string | undefined): string => typeof val === 'number' ? val.toFixed(4) : String(val ?? '-')

const getFormulaText = (row: SpkWeightedRow, c: SpkCriteria): string => {
  const norm = row[c.key + '_norm']; const weight = row[c.key + '_weight']
  return typeof norm === 'number' && typeof weight === 'number' ? `${norm.toFixed(4)} x ${weight.toFixed(4)}` : '-'
}

const getTotalFormula = (row: SpkWeightedRow): string => {
  const parts: string[] = []
  for (const c of props.criteria) {
    const w = row[c.key + '_weighted']
    if (typeof w === 'number') parts.push(w.toFixed(4))
  }
  return parts.length ? parts.join(' + ') : '-'
}

const statusLabel = (s: string): string => ({ pending: 'Pending', validated: 'Disetujui', rejected: 'Ditolak', completed: 'Selesai', needs_revision: 'Revisi' }[s] || s)
const statusBadge = (s: string): string => ({ pending: 'bg-yellow-100 text-yellow-700', validated: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700', completed: 'bg-gray-100 text-gray-700', needs_revision: 'bg-blue-100 text-blue-700' }[s] || 'bg-gray-100 text-gray-700')
</script>