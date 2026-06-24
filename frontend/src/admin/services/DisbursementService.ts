import { disbursementApi } from '../api/disbursementApi';
import type { DisbursementSubmission } from '../types/disbursement';

export const DisbursementService = {
  async getList(params?: Record<string, string>): Promise<DisbursementSubmission[]> {
    const response = await disbursementApi.getList(params);
    return response.data.data || [];
  },

  async disburse(submissionId: string, notes?: string): Promise<void> {
    await disbursementApi.store(submissionId, notes);
  },

  async bulkDisburse(submissionIds: string[], notes?: string): Promise<any> {
    const response = await disbursementApi.bulkStore(submissionIds, notes);
    return response.data;
  },
};