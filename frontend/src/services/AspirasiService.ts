import apiClient from './api';

export interface AspirasiPayload {
  nama: string;
  email: string;
  subjek: string;
  pesan: string;
}

export default {
  kirimAspirasi(data: AspirasiPayload) {
    // Karena baseURL sudah http://localhost:8000/api/v1
    // Cukup panggil '/kontak'
    return apiClient.post('/api/v1/kontak', data);
  }
};