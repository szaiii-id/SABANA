<!-- src/admin/components/spk/SpkRankingTable.vue -->
<template>
  <div>
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0]/50 text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th class="px-4 py-3 text-center">Rank</th>
            <th class="px-4 py-3">Nama Alternatif</th>
            <th class="px-4 py-3 text-center">Nilai Akhir (V)</th>
            <th class="px-4 py-3 text-center">Rekomendasi</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th v-if="quota" class="px-4 py-3 text-center">Kuota</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-for="row in data" :key="row.id" 
            :class="[
              'hover:bg-[#FAF6F0]/30 transition-colors',
              quota && row.rank <= quota ? 'bg-green-50/30' : '',
              quota && row.rank > quota ? 'bg-red-50/20' : ''
            ]">
            <td class="px-4 py-3 text-center">
              <span class="text-sm font-black text-[#1B4332]">{{ row.rank }}</span>
            </td>
            <td class="px-4 py-3 text-sm font-bold text-[#1B4332]">{{ row.name }}</td>
            <td class="px-4 py-3 text-center">
              <span class="text-[10px] text-[#6B705C]">{{ getFormulaText(row) }}</span>
              <br>
              <span class="text-sm font-black" :class="scoreColor(row.total)">
                {{ row.total ? (row.total * 100).toFixed(2) : '-' }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="recommendationBadge(row.recommendation.color)" class="px-2 py-1 rounded-full text-[9px] font-bold uppercase">
                {{ row.recommendation.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="statusBadge(row.status)" class="px-2 py-1 rounded-full text-[9px] font-bold uppercase">
                {{ statusLabel(row.status) }}
              </span>
            </td>
            <td v-if="quota" class="px-4 py-3 text-center">
              <span v-if="row.rank <= quota" class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-[9px] font-bold uppercase">Masuk</span>
              <span v-else class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-[9px] font-bold uppercase">Tidak</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { SpkRankingRow } from '../../types/spk'

defineProps<{
  data: SpkRankingRow[]
  quota: number | null
}>()

const getFormulaText = (row: SpkRankingRow): string => {
  const score = row.total
  if (!score && score !== 0) return 'Belum dinilai'
  return `V = ${(score * 100).toFixed(2)} / 100 = ${score.toFixed(4)}`
}

const scoreColor = (score: number | null): string => {
  if (!score) return 'text-gray-400'
  if (score >= 0.70) return 'text-green-600'
  if (score >= 0.50) return 'text-blue-600'
  if (score >= 0.30) return 'text-yellow-600'
  return 'text-red-600'
}

const recommendationBadge = (color: string): string => {
  const badges: Record<string, string> = {
    green: 'bg-green-100 text-green-700',
    blue: 'bg-blue-100 text-blue-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
  }
  return badges[color] || 'bg-gray-100 text-gray-700'
}

const statusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    validated: 'Disetujui',
    rejected: 'Ditolak',
    completed: 'Selesai',
    needs_revision: 'Revisi',
  }
  return labels[status] || status
}

const statusBadge = (status: string): string => {
  const badges: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    validated: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    completed: 'bg-gray-100 text-gray-700',
    needs_revision: 'bg-blue-100 text-blue-700',
  }
  return badges[status] || 'bg-gray-100 text-gray-700'
}
</script>