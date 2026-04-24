import api from './axios';
import type { SendWhatsappPayload, SendEmailPayload, ReportApiResponse } from '../types/report';

export const reportApi = {
  sendWhatsapp: async (payload: SendWhatsappPayload): Promise<ReportApiResponse> => {
    const response = await api.post<ReportApiResponse>('/citizen/report/whatsapp', payload);
    return response.data;
  },
  
  sendEmail: async (payload: SendEmailPayload): Promise<ReportApiResponse> => {
    const response = await api.post<ReportApiResponse>('/citizen/report/email', payload);
    return response.data;
  }
};