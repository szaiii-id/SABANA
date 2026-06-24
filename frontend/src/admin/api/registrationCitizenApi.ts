import adminApi from './axios';
import type { RegisterCitizenPayload, CitizenListFilters } from '../types/registration-citizen';

export const registrationCitizenApi = {
  getList: (params?: CitizenListFilters) =>
    adminApi.get('citizen-registration/citizens', { params }),

  search: (q: string) =>
    adminApi.get('citizen-registration/citizens/search', { params: { q } }),

  searchPaginated: (params: { q: string; page?: number; per_page?: number }) =>
    adminApi.get('citizen-registration/citizens/search-paginated', { params }),

  create: (data: RegisterCitizenPayload) =>
    adminApi.post('citizen-registration/create', data),

  update: (id: string, data: Partial<RegisterCitizenPayload>) =>
    adminApi.put(`citizen-registration/citizens/${id}`, data),

  resendPin: (id: string) =>
    adminApi.post(`citizen-registration/create/resend-pin/${id}`),

  resetAndPrintPin: (id: string) =>
    adminApi.post(`citizen-registration/citizens/${id}/reset-pin-card`, {}, {
        responseType: 'blob',
    }),
};