import { aspirasiEndpoint } from '../api/aspirasiApi';
import type { AspirasiPayload } from '../types/aspirasi';

export const AspirasiService = {
  async kirimAspirasi(payload: AspirasiPayload) {
    
    const response = await aspirasiEndpoint.kirim(payload);
    return response.data;
  }
};