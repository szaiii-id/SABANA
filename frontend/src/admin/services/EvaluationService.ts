import { evaluationApi } from '../api/evaluationApi';
import type { Evaluation, EvaluationListResponse } from '../types/evaluation';

export const EvaluationService = {
  async getList(params?: Record<string, string>): Promise<EvaluationListResponse> {
    const response = await evaluationApi.getList(params);
    return response.data;
  },
  async approve(id: string): Promise<void> {
    await evaluationApi.approve(id);
  },

  async revoke(id: string, notes: string): Promise<void> {
    await evaluationApi.revoke(id, { notes });
  },
};