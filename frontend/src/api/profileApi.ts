import api from './axios';
import type { CitizenProfile, UpdateProfilePayload, ProfileApiResponse } from '../types/profile';

export const profileApi = {
  getProfile: async (): Promise<CitizenProfile> => {
    const response = await api.get<{ data: CitizenProfile }>('/citizen/profile');
    return response.data.data;
  },
  
  updateProfile: async (payload: UpdateProfilePayload): Promise<ProfileApiResponse> => {
    const response = await api.patch<ProfileApiResponse>('/citizen/profile', payload);
    return response.data;
  }
};