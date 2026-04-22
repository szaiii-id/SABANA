import api from './axios';
import type { AspirasiPayload, ApiResponse } from '../types/aspirasi';

export const aspirasiEndpoint = {
  kirim: (data: AspirasiPayload) => api.post<ApiResponse>('/kontak', data),
};
