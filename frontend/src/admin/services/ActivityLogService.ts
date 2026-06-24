import { activityLogApi } from '../api/activityLogApi';
import type { ActivityLog, ActivityLogFilters } from '../types/activityLog';

export const ActivityLogService = {
  async getAll(filters?: ActivityLogFilters): Promise<{
    data: ActivityLog[];
    meta: { current_page: number; total: number; last_page: number; per_page: number };
  }> {
    const response = await activityLogApi.getAll(filters);
    return {
      data: response.data.data || [],
      meta: response.data.meta || {
        current_page: 1,
        total: 0,
        last_page: 1,
        per_page: 15,
      },
    };
  },
};