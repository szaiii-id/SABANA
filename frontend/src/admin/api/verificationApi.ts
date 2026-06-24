import adminApi from './axios';
import type { VerificationFilters } from '../types/verification';

export const verificationEndpoint = {
  getAll: (params?: VerificationFilters) => adminApi.get('verifications', { params }),
  getById: (id: string) => adminApi.get(`verifications/${id}`),
  approve: (id: string) => adminApi.post(`verifications/${id}/approve`),
  reject: (id: string, data: { notes: string }) => adminApi.post(`verifications/${id}/reject`, data),
  requestRevision: (id: string, data: { notes: string; revision_items?: string[] }) => adminApi.post(`verifications/${id}/request-revision`, data),
  complete: (id: string) => adminApi.post(`verifications/${id}/complete`),
  unvalidate: (id: string, data: { notes: string }) => adminApi.post(`verifications/${id}/unvalidate`, data),
  bulkComplete: (ids: string[]) => adminApi.post('verifications/bulk-complete', { ids }),
};