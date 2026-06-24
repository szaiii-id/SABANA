import adminApi from './axios';
import type { CitizenAssistancePayload } from '../types/citizen-assistance';

export const citizenAssistanceApi = {
  searchCitizen: (q: string) =>
    adminApi.get('citizen-registration/citizens/search', { params: { q } }),

  getPrograms: (citizenId?: string) =>
    adminApi.get('programs/active', { params: citizenId ? { citizen_id: citizenId } : {} }),

  searchCitizenPaginated: (q: string, page: number) =>
    adminApi.get('citizen-registration/citizens/search-paginated', { params: { q, page } }),

  submit: (payload: CitizenAssistancePayload) => {
    const formData = new FormData();
    formData.append('citizen_id', payload.citizen_id);
    formData.append('program_id', payload.program_id);
    formData.append('regency_id', payload.regency_id);
    formData.append('district_id', payload.district_id);
    formData.append('village_id', payload.village_id);
    formData.append('disbursement_method', payload.disbursement_method);

    if (payload.bank_account_number) {
      formData.append('bank_account_number', payload.bank_account_number);
    }

    Object.entries(payload.dynamicInputs).forEach(([key, value]) => {
      formData.append(key, String(value));
    });

    Object.entries(payload.files).forEach(([key, file]) => {
      if (file instanceof File) {
        formData.append(key, file);
      }
    });

    return adminApi.post('citizen-assistance/submit', formData, {
      timeout: 120000,
    });
  },
};