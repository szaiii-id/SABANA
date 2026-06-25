import axiosInstance from './axios'; 
import type { AssistanceResponse, Region, HistoryResponse, DisbursementReceiptResponse, SubmissionDetailData } from '../types/assistance';

interface RawProgramItem {
  id: string;
  title: string;
  slug?: string;
  description?: string;
  badge?: string;
  banner_url?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  quota_total?: number | null;
  benefit_amount?: number | null;
  inputs?: Array<{ key: string; label: string; type: string; options?: Array<{ value: string; score: number }> }>;
  documents?: Array<{ key: string; label: string; description?: string; required?: boolean }>;
  has_submitted?: boolean;
}

interface UpdateResponse {
  success: boolean;
  message: string;
  data?: {
    registration_number: string;
    status: string;
  };
}

interface DeleteResponse {
  success: boolean;
  message: string;
}

export const getProgramsApi = async (): Promise<RawProgramItem[]> => {
  const response = await axiosInstance.get('/citizen/assistance-categories');
  return response.data.data as RawProgramItem[];
};

export const submitAssistanceApi = async (formData: FormData, idempotencyKey?: string): Promise<AssistanceResponse> => {
  const headers: Record<string, string> = {};
  
  if (idempotencyKey) {
    headers['X-Idempotency-Key'] = idempotencyKey;
  }

  const response = await axiosInstance.post<AssistanceResponse>(
    '/citizen/assistance/submit', 
    formData, 
    {
      headers,
      timeout: 120000,
    }
  );
  return response.data;
};

export const getRegenciesApi = async (): Promise<Region[]> => {
  const response = await axiosInstance.get('/citizen/regions/regencies');
  const rawData: unknown = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData as Region[] : Object.values(rawData as Record<string, Region>);
};

export const getDistrictsApi = async (regencyId: string): Promise<Region[]> => {
  const response = await axiosInstance.get('/citizen/regions/districts', {
    params: { regency_id: regencyId }
  });
  const rawData: unknown = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData as Region[] : Object.values(rawData as Record<string, Region>);
};

export const getVillagesApi = async (districtId: string): Promise<Region[]> => {
  const response = await axiosInstance.get('/citizen/regions/villages', {
    params: { district_id: districtId }
  });
  const rawData: unknown = response.data?.data || response.data;
  return Array.isArray(rawData) ? rawData as Region[] : Object.values(rawData as Record<string, Region>);
};

export const getMySubmissionsApi = async (): Promise<HistoryResponse> => {
  const response = await axiosInstance.get<HistoryResponse>('/citizen/assistance/submissions');
  return response.data; 
};

export const updateAssistanceApi = async (id: string, formData: FormData): Promise<UpdateResponse> => {
  formData.append('_method', 'PUT'); 
  
  const response = await axiosInstance.post<UpdateResponse>(`/citizen/assistance/submissions/${id}`, formData, {
    timeout: 120000, 
  });
  return response.data;
};

export const deleteAssistanceApi = async (registrationNumber: string): Promise<DeleteResponse> => {
  const response = await axiosInstance.delete<DeleteResponse>(`/citizen/assistance/${registrationNumber}`);
  return response.data;
};

export const getSubmissionDetailApi = async (id: string): Promise<{ success: boolean; data: SubmissionDetailData }> => {
  const response = await axiosInstance.get<{ success: boolean; data: SubmissionDetailData }>(`/citizen/assistance/submissions/${id}`);
  return response.data;
};

export const downloadAssistancePdfApi = async (id: string): Promise<Blob> => {
  const response = await axiosInstance.get(`/citizen/assistance/submissions/${id}/download`, {
    responseType: 'blob',
    timeout: 30000,
  });
  return response.data;
};

export const getDisbursementReceiptApi = async (submissionId: string): Promise<DisbursementReceiptResponse> => {
  const response = await axiosInstance.get<DisbursementReceiptResponse>(
    `/citizen/assistance/submissions/${submissionId}/receipt`
  );
  return response.data;
};

export const downloadDisbursementReceiptPdfApi = async (submissionId: string): Promise<Blob> => {
  const response = await axiosInstance.get(
    `/citizen/assistance/submissions/${submissionId}/receipt/pdf`,
    { responseType: 'blob', timeout: 30000 }
  );
  return response.data;
};