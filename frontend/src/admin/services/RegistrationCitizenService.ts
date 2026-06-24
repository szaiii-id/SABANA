import { registrationCitizenApi } from '../api/registrationCitizenApi';
import type { RegisterCitizenPayload } from '../types/registration-citizen';

export const RegistrationCitizenService = {
  async getList(filters?: { search?: string; page?: number; per_page?: number }) {
    const response = await registrationCitizenApi.getList(filters);
    return response.data;
  },

  async search(query: string) {
    const response = await registrationCitizenApi.search(query);
    return response.data;
  },

  async create(payload: RegisterCitizenPayload) {
    const response = await registrationCitizenApi.create(payload);
    return response.data;
  },

  async resendPin(id: string) {
    const response = await registrationCitizenApi.resendPin(id);
    return response.data;
  },
};