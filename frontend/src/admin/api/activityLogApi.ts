import adminApi from './axios';
import type { ActivityLogFilters, ActivityLogResponse } from '../types/activityLog';

export const activityLogApi = {
  getAll: (params?: ActivityLogFilters) =>
    adminApi.get<ActivityLogResponse>('activity-logs', { params }),
};