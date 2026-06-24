import adminApi from './axios';
import type { DisbursementListResponse } from '../types/disbursement';

export const disbursementApi = {
  getList: (params?: Record<string, string>) =>
    adminApi.get<DisbursementListResponse>('disbursements', { params }),

  store: (submissionId: string, notes?: string) =>
    adminApi.post('disbursements', { submission_id: submissionId, notes }),

  bulkStore: (submissionIds: string[], notes?: string) =>
    adminApi.post('disbursements/bulk', { submission_ids: submissionIds, notes }),
};