import adminApi from './axios';
import type { AccountPayload, AccountFilters } from '../types/account';

export const accountEndpoint = {
  getAll: (params?: AccountFilters) => adminApi.get('accounts', { params }),
  create: (data: AccountPayload) => adminApi.post('accounts', data),
  update: (id: string, data: Partial<AccountPayload>) => adminApi.put(`accounts/${id}`, data),
  delete: (id: string) => adminApi.delete(`accounts/${id}`),
  activate: (id: string) => adminApi.patch(`accounts/${id}/activate`),
  resetPassword: (id: string, password: string) => adminApi.patch(`accounts/${id}/reset-password`, { password }),
};