import adminApi from './axios';
import type { EvaluationListResponse, RevokeEvaluationPayload } from '../types/evaluation';

export const evaluationApi = {
  /**
   * Get list evaluasi berdasarkan wilayah admin.
   */
  getList: (params?: Record<string, string>) =>
    adminApi.get<EvaluationListResponse>('evaluations', { params }),

  /**
   * Approve evaluasi — lanjutkan bantuan.
   */
  approve: (id: string) =>
    adminApi.post(`evaluations/${id}/approve`),

  /**
   * Revoke evaluasi — hentikan bantuan.
   */
  revoke: (id: string, payload: RevokeEvaluationPayload) =>
    adminApi.post(`evaluations/${id}/revoke`, payload),
};