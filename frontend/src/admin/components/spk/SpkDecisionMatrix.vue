<!-- src/admin/components/spk/SpkDecisionMatrix.vue -->
<template>
  <div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0]/50 text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-4 py-3">No</th>
            <th class="px-4 py-3">NIK</th>
            <th class="px-4 py-3">Nama Alternatif</th>
            <th v-for="c in criteria" :key="c.key" class="px-4 py-3 text-center">{{ c.label }}</th>
            <th class="px-4 py-3 text-center">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="(row, index) in data" :key="row.id" class="hover:bg-[#FAF6F0]/30">
            <td class="px-4 py-3 text-sm font-bold text-[#1B4332]">A{{ index + 1 }}</td>
            <td class="px-4 py-3 text-xs text-[#6B705C] font-mono">{{ row.nik }}</td>
            <td class="px-4 py-3 text-sm font-bold text-[#1B4332]">{{ row.name }}</td>
            <td v-for="c in criteria" :key="c.key" class="px-4 py-3 text-center text-sm text-[#1B4332] font-bold">
              {{ getDisplay(row, c.key) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="statusBadge(row.status)" class="px-2 py-1 rounded-full text-[9px] font-bold uppercase">
                {{ statusLabel(row.status) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SpkCriteria, SpkDecisionRow } from '../../types/spk'

defineProps<{
  data: SpkDecisionRow[]
  criteria: SpkCriteria[]
}>()

const getDisplay = (row: SpkDecisionRow, key: string): string => {
  const val = row[key]
  if (val && typeof val === 'object' && 'display' in val) {
    return (val as { display: string }).display
  }
  return String(val ?? '-')
}

const statusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    pending: 'Pending', validated: 'Disetujui', rejected: 'Ditolak',
    completed: 'Selesai', needs_revision: 'Revisi',
  }
  return labels[status] || status
}

const statusBadge = (status: string): string => {
  const badges: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700', validated: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700', completed: 'bg-gray-100 text-gray-700',
    needs_revision: 'bg-blue-100 text-blue-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>