<template>
  <div class="min-h-screen bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-4 py-6 md:px-6 space-y-5">
      
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 bg-gradient-to-br from-[#1B4332] to-[#2D6A4F] rounded-xl flex items-center justify-center shadow-md">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">Log Aktivitas</h1>
          <p class="text-[13px] text-slate-500 font-medium">Rekam jejak seluruh aktivitas sistem</p>
        </div>
      </div>

      <LogFilter @filter="handleFilter" />

      <LogTable
        :logs="logs"
        :loading="loading"
        :currentPage="currentPage"
        :totalPages="totalPages"
        :total="total"
        @pageChange="handlePageChange"
      />

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useActivityLog } from '../../composables/useActivityLog';
import LogFilter from '../../components/activity-log/LogFilter.vue';
import LogTable from '../../components/activity-log/LogTable.vue';

const { logs, loading, currentPage, totalPages, total, fetchLogs } = useActivityLog();
const activeFilters = ref<Record<string, string>>({});

onMounted(() => fetchLogs());

const handleFilter = (filters: Record<string, string>) => {
  activeFilters.value = filters;
  currentPage.value = 1;
  fetchLogs({ ...filters, page: 1 });
};

const handlePageChange = (page: number) => {
  fetchLogs({ ...activeFilters.value, page });
};
</script>