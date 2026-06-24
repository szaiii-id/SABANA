import { ref } from 'vue';
import { ActivityLogService } from '../services/ActivityLogService';
import type { ActivityLog, ActivityLogFilters } from '../types/activityLog';

export function useActivityLog() {
  const logs = ref<ActivityLog[]>([]);
  const loading = ref(false);
  const currentPage = ref(1);
  const totalPages = ref(1);
  const total = ref(0);

  const fetchLogs = async (filters: ActivityLogFilters = {}) => {
    loading.value = true;
    try {
      const result = await ActivityLogService.getAll({
        ...filters,
        page: filters.page || currentPage.value,
      });
      logs.value = result.data || [];
      currentPage.value = result.meta.current_page;
      totalPages.value = result.meta.last_page;
      total.value = result.meta.total;
    } catch (e: unknown) {
      console.error('Failed to fetch logs', e);
      logs.value =  [];
    } finally {
      loading.value = false;
    }
  };

  return { logs, loading, currentPage, totalPages, total, fetchLogs };
}