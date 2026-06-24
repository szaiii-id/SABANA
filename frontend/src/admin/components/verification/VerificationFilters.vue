<template>
  <div class="bg-white p-4 rounded-3xl border border-[#E8D5C4] shadow-sm flex flex-col sm:flex-row gap-4">
    <!-- Search -->
    <div class="relative flex-grow">
      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
      </div>
      <input :value="local.search" @input="update('search', ($event.target as HTMLInputElement).value)" type="text" placeholder="Cari NIK atau Nama..." class="w-full pl-11 pr-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors" />
    </div>

    <!-- Status -->
    <div class="sm:w-44">
      <select :value="local.status" @change="update('status', ($event.target as HTMLSelectElement).value)" class="w-full px-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors appearance-none">
        <option value="">Semua Status</option>
        <option value="pending">Pending</option>
        <option value="validated">Disetujui</option>
        <option value="rejected">Ditolak</option>
        <option value="needs_revision">Perlu Revisi</option>
        <option value="completed">Selesai</option>
      </select>
    </div>

    <!-- Rekomendasi -->
    <div class="sm:w-48">
      <select :value="local.recommendation" @change="update('recommendation', ($event.target as HTMLSelectElement).value)" class="w-full px-4 py-3 bg-[#FAF6F0] border-transparent focus:border-[#D4A373] focus:bg-white focus:ring-0 rounded-2xl text-sm font-medium transition-colors appearance-none">
        <option value="">Semua Rekomendasi</option>
        <option value="highly_recommended">Sangat Direkomendasikan</option>
        <option value="recommended">Direkomendasikan</option>
        <option value="considered">Dipertimbangkan</option>
        <option value="not_recommended">Tidak Direkomendasikan</option>
        <option value="unscored">Belum Dinilai</option>
      </select>
    </div>

    <!-- Sort SMART Toggle -->
    <button 
      @click="toggleSmartSort"
      :class="[
        'px-4 py-3 rounded-2xl text-sm font-bold transition-all flex items-center gap-2',
        local.smartSort ? 'bg-[#1B4332] text-white shadow-sm' : 'bg-[#FAF6F0] text-[#6B705C] hover:bg-[#E8D5C4]'
      ]"
      title="Urutkan berdasarkan skor SMART"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
      </svg>
      <span class="hidden sm:inline">SMART</span>
      <span v-if="local.smartSort === 'desc'" class="text-[10px]">↓</span>
      <span v-else-if="local.smartSort === 'asc'" class="text-[10px]">↑</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import type { VerificationFiltersValues } from '../../types/verification';

// Interface lokal untuk komponen (semua field wajib)
interface LocalFilters {
  search: string;
  status: string;
  recommendation: string;
  smartSort: string;
}

const props = defineProps<{ modelValue: VerificationFiltersValues }>();
const emit = defineEmits<{ 'update:modelValue': [value: VerificationFiltersValues] }>();

const local = reactive<LocalFilters>({ 
  search: props.modelValue.search || '',
  status: props.modelValue.status || '',
  recommendation: props.modelValue.recommendation || '',
  smartSort: props.modelValue.smartSort || ''
});

watch(() => props.modelValue, (v) => {
  local.search = v.search || '';
  local.status = v.status || '';
  local.recommendation = v.recommendation || '';
  local.smartSort = v.smartSort || '';
});

const update = (key: keyof LocalFilters, value: string) => {
  local[key] = value;
  emit('update:modelValue', { ...local });
};

const toggleSmartSort = () => {
  if (!local.smartSort || local.smartSort === 'desc') {
    local.smartSort = 'asc';
  } else {
    local.smartSort = 'desc';
  }
  emit('update:modelValue', { ...local });
};
</script>