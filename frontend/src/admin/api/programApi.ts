import adminApi from './axios';
import type { ProgramPayload, ProgramFilters } from '../types/program';

export const programEndpoint = {
  getAll: (params?: ProgramFilters) => adminApi.get('programs', { params }),
  
  create: (data: any) => {
    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('description', data.description);
    formData.append('start_date', data.start_date);
    formData.append('end_date', data.end_date);
    formData.append('status', data.status);
    
    if (data.quota_total) formData.append('quota_total', String(data.quota_total));
    if (data.benefit_amount) formData.append('benefit_amount', String(data.benefit_amount));
    if (data.criteria && Object.keys(data.criteria).length > 0) {
      formData.append('criteria', JSON.stringify(data.criteria));
    }
    if (data.ai_config && Object.keys(data.ai_config).length > 0) {
      formData.append('ai_config', JSON.stringify(data.ai_config));
    }
    
    return adminApi.post('programs', formData);
  },
  
  update: (id: string, data: any) => {
    const formData = new FormData();
    if (data.name) formData.append('name', data.name);
    if (data.description) formData.append('description', data.description);
    if (data.start_date) formData.append('start_date', data.start_date);
    if (data.end_date) formData.append('end_date', data.end_date);
    if (data.status) formData.append('status', data.status);
    if (data.quota_total) formData.append('quota_total', String(data.quota_total));
    if (data.benefit_amount) formData.append('benefit_amount', String(data.benefit_amount));
    if (data.criteria && Object.keys(data.criteria).length > 0) {
      formData.append('criteria', JSON.stringify(data.criteria));
    }
    if (data.ai_config && Object.keys(data.ai_config).length > 0) {
      formData.append('ai_config', JSON.stringify(data.ai_config));
    }
    
    return adminApi.post(`programs/${id}`, formData, {
      params: { _method: 'PUT' }
    });
  },
  
  delete: (id: string) => adminApi.delete(`programs/${id}`),
  close: (id: string) => adminApi.post(`programs/${id}/close`),
  reopen: (id: string) => adminApi.post(`programs/${id}/reopen`),
  duplicate: (id: string) => adminApi.post(`programs/${id}/duplicate`),

  uploadBanner: (id: string, file: File, onProgress?: (percent: number) => void) => {
    const formData = new FormData();
    formData.append('banner', file);
    
    return adminApi.post(`programs/${id}/banner`, formData, {
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
          onProgress(percent);
        }
      }
    });
  },
};