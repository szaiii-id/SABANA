import { 
  submitAssistanceApi, 
  getRegenciesApi, 
  getDistrictsApi, 
  getVillagesApi,
  getProgramsApi,
  getMySubmissionsApi,
  updateAssistanceApi,
  deleteAssistanceApi,
  getSubmissionDetailApi,
  downloadAssistancePdfApi,
  getDisbursementReceiptApi,           
  downloadDisbursementReceiptPdfApi    
} from '../api/assistanceApi';

import type { 
  AssistanceSubmissionPayload, 
  AssistanceResponse, 
  Region,
  AssistanceProgramSchema,
  HistoryResponse,
  DisbursementReceiptResponse,
  SubmissionDetailData,
  FormInputSchema,
  FormDocumentSchema
} from '../types/assistance';

interface RawProgramResponse {
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
  inputs?: FormInputSchema[];
  documents?: FormDocumentSchema[];
  has_submitted?: boolean;
}

interface UpdateAssistanceResponse {
  success: boolean;
  message: string;
  data?: {
    registration_number: string;
    status: string;
  };
}

interface DeleteAssistanceResponse {
  success: boolean;
  message: string;
}

export class AssistanceService {
  
  static async getPrograms(): Promise<AssistanceProgramSchema[]> {
    const rawData: RawProgramResponse[] = await getProgramsApi();
    
    return rawData.map((item: RawProgramResponse): AssistanceProgramSchema => ({
      id: item.id,
      title: item.title,
      slug: item.slug || '',
      description: item.description || '',
      badge: item.badge || 'Bantuan Aktif',
      banner_url: item.banner_url || null,
      start_date: item.start_date || null,
      end_date: item.end_date || null,
      quota_total: item.quota_total || null,
      benefit_amount: item.benefit_amount || null,
      inputs: item.inputs || [],
      documents: item.documents || [],
      has_submitted: item.has_submitted ?? false,
    }));
  }

  static async getRegencies(): Promise<Region[]> {
    return await getRegenciesApi();
  }

  static async getDistricts(regencyId: string): Promise<Region[]> {
    return await getDistrictsApi(regencyId);
  }

  static async getVillages(districtId: string): Promise<Region[]> {
    return await getVillagesApi(districtId);
  }

  static async submitRegistration(payload: AssistanceSubmissionPayload, idempotencyKey?: string): Promise<AssistanceResponse> {
      const formData = new FormData();
      formData.append('program_id', payload.program_id);
      formData.append('regency_id', payload.regency_id);
      formData.append('district_id', payload.district_id);
      formData.append('village_id', payload.village_id);
      formData.append('disbursement_method', payload.disbursement_method);
      
      if (payload.disbursement_method === 'bpd_transfer' && payload.bank_account_number) {
        formData.append('bank_account_number', payload.bank_account_number);
      }

      Object.entries(payload.dynamicInputs).forEach(([key, value]) => {
        formData.append(key, String(value));
      });

      Object.entries(payload.files).forEach(([key, file]) => {
        if (file instanceof File) {
          formData.append(key, file);
        }
      });

      return await submitAssistanceApi(formData, idempotencyKey);
  }

  static async getMySubmissions(): Promise<HistoryResponse> {
    return await getMySubmissionsApi();
  }

  static async updateRegistration(id: string, payload: AssistanceSubmissionPayload): Promise<UpdateAssistanceResponse> {
    const formData = new FormData();
    formData.append('regency_id', payload.regency_id);
    formData.append('district_id', payload.district_id);
    formData.append('village_id', payload.village_id);
    formData.append('disbursement_method', payload.disbursement_method);
    if (payload.bank_account_number) {
        formData.append('bank_account_number', payload.bank_account_number);
    }

    if (payload.is_evaluation) {
        formData.append('is_evaluation', '1');
    }

    Object.entries(payload.dynamicInputs).forEach(([key, value]) => {
        formData.append(key, String(value));
    });

    Object.entries(payload.files).forEach(([key, file]) => {
        if (file instanceof File) {
            formData.append(key, file);
        }
    });

    return await updateAssistanceApi(id, formData);
  }

  static async cancelRegistration(registrationNumber: string): Promise<DeleteAssistanceResponse> {
      return await deleteAssistanceApi(registrationNumber);
  }

  static async getSubmissionDetail(id: string): Promise<{ success: boolean; data: SubmissionDetailData }> {
      return await getSubmissionDetailApi(id);
  }

  static async downloadReceipt(id: string): Promise<Blob> {
    return await downloadAssistancePdfApi(id);
  }

  static async getDisbursementReceipt(submissionId: string): Promise<DisbursementReceiptResponse> {
    return await getDisbursementReceiptApi(submissionId);
  }

  static async downloadDisbursementReceiptPdf(submissionId: string): Promise<Blob> {
    return await downloadDisbursementReceiptPdfApi(submissionId);
  }
}