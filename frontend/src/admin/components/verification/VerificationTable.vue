<template>
  <div class="bg-white border border-[#E8D5C4] rounded-3xl shadow-sm overflow-hidden flex flex-col">
    
    <!-- Bulk Action Bar -->
    <Transition name="slide-down">
      <div v-if="selectedIds.length > 0 && showCheckbox" 
        class="px-6 py-3 bg-[#1B4332] text-white flex items-center justify-between">
        <span class="text-xs font-bold">
          {{ selectedIds.length }} pengajuan dipilih
        </span>
        <div class="flex items-center gap-3">
          <button @click="$emit('clearSelection')" 
            class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white/70 hover:text-white transition-colors">
            Batal
          </button>
          <button @click="$emit('bulkComplete')" 
            class="px-5 py-2 text-[10px] font-black uppercase tracking-wider bg-white text-[#1B4332] rounded-xl hover:bg-emerald-100 transition-all shadow-sm">
            Tandai Selesai
          </button>
        </div>
      </div>
    </Transition>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-[#FAF6F0] text-[#8B7355] text-xs uppercase tracking-wider font-black border-b border-[#E8D5C4]">
            <th v-if="showCheckbox" class="px-4 py-4 w-12 rounded-tl-3xl">
              <label class="flex items-center justify-center cursor-pointer">
                <div :class="[
                  'w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200',
                  isAllSelected ? 'bg-[#1B4332] border-[#1B4332]' : 
                  isIndeterminate ? 'bg-[#1B4332] border-[#1B4332]' : 
                  'border-[#D4A373] bg-white hover:border-[#1B4332]'
                ]">
                  <svg v-if="isAllSelected" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7" /></svg>
                  <svg v-else-if="isIndeterminate" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 12h14" /></svg>
                </div>
                <input type="checkbox" :checked="isAllSelected" @change="toggleAll" class="sr-only" />
              </label>
            </th>
            <th v-else class="px-4 py-4 w-12 rounded-tl-3xl"></th>
            <th class="px-6 py-4 text-left">Warga</th>
            <th class="px-6 py-4 text-center">Program</th>
            <th class="px-6 py-4 text-center">Skor SMART</th>
            <th class="px-6 py-4 text-center hidden md:table-cell">Wilayah</th>
            <th class="px-6 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-center rounded-tr-3xl w-20">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#E8D5C4]/30">
          <tr v-if="loading">
            <td :colspan="showCheckbox ? 7 : 6" class="px-6 py-12 text-center">
              <div class="flex items-center justify-center gap-2 text-[#6B705C]">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memuat data...
              </div>
            </td>
          </tr>
          <tr v-else-if="!verifications.length">
            <td :colspan="showCheckbox ? 7 : 6" class="px-6 py-12 text-center text-[#6B705C]">
              <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="font-bold text-gray-400 text-lg">Tidak ada antrean verifikasi</p>
            </td>
          </tr>
          <tr 
            v-for="item in verifications" :key="item.id" 
            :class="[
              'transition-colors group',
              selectedIds.includes(item.id) 
                ? 'bg-[#1B4332]/5' 
                : 'hover:bg-[#FAF6F0]/30'
            ]"
          >
            <td v-if="showCheckbox" class="px-4 py-4" @click.stop>
              <label class="flex items-center justify-center cursor-pointer">
                <div :class="[
                  'w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200',
                  selectedIds.includes(item.id) 
                    ? 'bg-[#1B4332] border-[#1B4332]' 
                    : 'border-[#D4A373] bg-white hover:border-[#1B4332]'
                ]">
                  <svg v-if="selectedIds.includes(item.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="4"><path d="M5 13l4 4L19 7" /></svg>
                </div>
                <input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleItem(item.id)" class="sr-only" />
              </label>
            </td>
            <td v-else class="px-4 py-4"></td>
            <td class="px-6 py-4 text-left cursor-pointer" @click="$emit('select', item)">
              <p class="font-bold text-[#1B4332] text-sm">{{ item.citizen?.full_name || 'Tidak diketahui' }}</p>
              <p class="text-xs text-[#6B705C] mt-0.5">{{ item.citizen?.nik || '-' }}</p>
            </td>
            <td class="px-6 py-4 text-center cursor-pointer" @click="$emit('select', item)">
              <span class="text-xs font-bold text-[#1B4332]">{{ item.program?.name || '-' }}</span>
            </td>
            <td class="px-6 py-4 text-center cursor-pointer" @click="$emit('select', item)">
              <div class="inline-flex flex-col items-center">
                <span class="text-sm font-black" :class="smartScoreClass(item.smart_score)">
                  {{ item.smart_score ? (item.smart_score * 100).toFixed(1) : '-' }}
                </span>
                <span :class="recommendationBadge(item.recommendation?.color)" 
                  class="mt-1 px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wide">
                  {{ item.recommendation?.label || '-' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-center hidden md:table-cell cursor-pointer" @click="$emit('select', item)">
              <p class="text-xs text-[#6B705C]">{{ item.wilayah?.village || item.wilayah?.district || '-' }}</p>
            </td>
            <td class="px-6 py-4 text-center cursor-pointer" @click="$emit('select', item)">
              <span :class="statusBadge(item.status)" class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide">
                {{ formatStatus(item.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click.stop="$emit('select', item)" class="px-4 py-2 text-xs font-bold text-[#1B4332] bg-[#FAF6F0] rounded-xl hover:bg-[#1B4332] hover:text-white transition-all group-hover:shadow-sm">
                Detail
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-if="!loading && verifications.length > 0" class="px-6 py-3 border-t border-[#E8D5C4] bg-[#FAF6F0]/50 flex items-center justify-between">
      <span class="text-xs font-medium text-[#6B705C]">Menampilkan <span class="font-bold text-[#1B4332]">{{ verifications.length }}</span> dari <span class="font-bold text-[#1B4332]">{{ total }}</span> pengajuan</span>
      <div v-if="totalPages > 1" class="flex items-center gap-2">
        <button :disabled="currentPage <= 1" @click="$emit('pageChange', currentPage - 1)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors" :class="currentPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">← Prev</button>
        <span class="text-xs font-bold text-[#1B4332]">{{ currentPage }} / {{ totalPages }}</span>
        <button :disabled="currentPage >= totalPages" @click="$emit('pageChange', currentPage + 1)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors" :class="currentPage >= totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-[#1B4332] hover:bg-[#1B4332]/10'">Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { VerificationData } from '../../types/verification';

const props = defineProps<{
  verifications: VerificationData[];
  loading: boolean;
  currentPage: number;
  totalPages: number;
  total: number;
  selectedIds: string[];
  showCheckbox: boolean;
}>();

const emit = defineEmits<{
  select: [item: VerificationData];
  pageChange: [page: number];
  'update:selectedIds': [ids: string[]];
  bulkComplete: [];
  clearSelection: [];
}>();

const isAllSelected = computed(() => 
  props.showCheckbox && props.verifications.length > 0 && 
  props.verifications.every(item => props.selectedIds.includes(item.id))
);

const isIndeterminate = computed(() => 
  props.showCheckbox && props.selectedIds.length > 0 && !isAllSelected.value
);

const toggleAll = () => {
  if (!props.showCheckbox) return;
  if (isAllSelected.value) {
    emit('update:selectedIds', []);
  } else {
    emit('update:selectedIds', props.verifications.map(item => item.id));
  }
};

const toggleItem = (id: string) => {
  if (!props.showCheckbox) return;
  const newIds = props.selectedIds.includes(id)
    ? props.selectedIds.filter(i => i !== id)
    : [...props.selectedIds, id];
  emit('update:selectedIds', newIds);
};

const formatStatus = (status: string) => ({
  pending: 'Pending', validated: 'Disetujui', rejected: 'Ditolak',
  needs_revision: 'Revisi', completed: 'Selesai'
}[status] || status);

const statusBadge = (status: string) => ({
  pending: 'bg-yellow-100 text-yellow-700', validated: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700', needs_revision: 'bg-blue-100 text-blue-700',
  completed: 'bg-gray-100 text-gray-700'
}[status] || 'bg-gray-100 text-gray-700');

const smartScoreClass = (score: number | null) => {
  if (!score) return 'text-gray-400';
  if (score >= 0.70) return 'text-green-600';
  if (score >= 0.50) return 'text-yellow-600';
  if (score >= 0.30) return 'text-orange-600';
  return 'text-red-600';
};

const recommendationBadge = (color: string) => ({
  green: 'bg-green-100 text-green-700', yellow: 'bg-yellow-100 text-yellow-700',
  orange: 'bg-orange-100 text-orange-700', red: 'bg-red-100 text-red-700',
  gray: 'bg-gray-100 text-gray-500'
}[color] || 'bg-gray-100 text-gray-500');
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-100%);
}
</style>