import api from './axios';
import type { UpdatePinPayload, SecurityApiResponse } from '../types/security';

export const securityApi = {
  updatePin: async (payload: UpdatePinPayload): Promise<SecurityApiResponse> => {
    const response = await api.put<SecurityApiResponse>('/citizen/security/pin', payload);
    return response.data;
  }
};