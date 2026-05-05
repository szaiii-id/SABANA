import axiosInstance from './axios'; 
import type { AssistanceResponse, Region } from '../types/assistance';

/**
 * Mengambil daftar Kategori Bantuan (Program)
 */
export const getProgramsApi = async (): Promise<any[]> => {
  const response = await axiosInstance.get('/citizen/assistance-categories');
  return response.data.data;
};

/**
 * Mengirim seluruh data pendaftaran (Multipart untuk File + JSONB)
 */
export const submitAssistanceApi = async (formData: FormData): Promise<AssistanceResponse> => {
  const response = await axiosInstance.post<AssistanceResponse>(
    '/citizen/assistance/submit', 
    formData, 
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }
  );
  return response.data;
};

/**
 * Mengambil daftar Kabupaten/Kota di Kalimantan Selatan
 */
export const getRegenciesApi = async (): Promise<Region[]> => {
  const response = await axiosInstance.get('/citizen/regions/regencies');
  const rawData = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData : Object.values(rawData);
};

/**
 * Mengambil daftar Kecamatan berdasarkan ID Kabupaten
 */
export const getDistrictsApi = async (regencyId: string): Promise<Region[]> => {
  const response = await axiosInstance.get(`/citizen/regions/districts`, {
    params: { regency_id: regencyId }
  });
  const rawData = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData : Object.values(rawData);
};

/**
 * Mengambil daftar Desa/Kelurahan berdasarkan ID Kecamatan
 */
export const getVillagesApi = async (districtId: string): Promise<Region[]> => {
  const response = await axiosInstance.get(`/citizen/regions/villages`, {
    params: { district_id: districtId }
  });
  const rawData = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData : Object.values(rawData);
};

/**
 * Mengambil daftar Riwayat Pengajuan Bantuan Warga
 */
export const getMySubmissionsApi = async (): Promise<any> => {
  const response = await axiosInstance.get('/citizen/assistance/submissions');
  return response.data; 
};

export const updateAssistanceApi = async (id: string, formData: FormData): Promise<any> => {
  formData.append('_method', 'PUT'); 
  
  const response = await axiosInstance.post(`/citizen/assistance/submissions/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  return response.data;
};

export const deleteAssistanceApi = async (registrationNumber: string): Promise<any> => {
  const response = await axiosInstance.delete(`/citizen/assistance/${registrationNumber}`);
  return response.data;
};

export const getSubmissionDetailApi = async (id: string): Promise<any> => {
  const response = await axiosInstance.get(`/citizen/assistance/submissions/${id}`);
  return response.data;
};

export const downloadAssistancePdfApi = async (id: string): Promise<Blob> => {
  const response = await axiosInstance.get(`/citizen/assistance/submissions/${id}/download`, {
    responseType: 'blob',
  });
  return response.data;
};