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
  downloadAssistancePdfApi
} from '../api/assistanceApi';

import type { 
  AssistanceSubmissionPayload, 
  AssistanceResponse, 
  Region,
  AssistanceProgramSchema 
} from '../types/assistance';

export class AssistanceService {
  
  static async getPrograms(): Promise<AssistanceProgramSchema[]> {
    const rawData = await getProgramsApi();
    
    return rawData.map((item: any) => ({
      id: item.id,
      // 1. Ambil title, karena BE Resource sudah mengirimnya sebagai 'title'
      title: item.title, 
      
      // 2. Ambil badge langsung (bukan dari criteria lagi)
      badge: item.badge || 'Bantuan Aktif',
      
      // 3. Biarkan icon default ini
      iconSvg: item.iconSvg || '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
      
      // 4. Ambil inputs & files langsung (bukan dari criteria lagi)
      inputs: item.inputs || [],
      files: item.files || []
    }));
  }

  // Region Handlers
  static async getRegencies(): Promise<Region[]> {
    return await getRegenciesApi();
  }

  static async getDistricts(regencyId: string): Promise<Region[]> {
    return await getDistrictsApi(regencyId);
  }

  static async getVillages(districtId: string): Promise<Region[]> {
    return await getVillagesApi(districtId);
  }

  // Submission Handler
  static async submitRegistration(payload: AssistanceSubmissionPayload): Promise<AssistanceResponse> {
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
      formData.append(key, value as string);
    });

    Object.entries(payload.files).forEach(([key, file]) => {
      formData.append(key, file);
    });

    return await submitAssistanceApi(formData);
  }

  // Riwayat Handler
  static async getMySubmissions(): Promise<any> {
    return await getMySubmissionsApi();
  }

  static async updateRegistration(id: string, payload: AssistanceSubmissionPayload): Promise<any> {
    const formData = new FormData();

    formData.append('regency_id', payload.regency_id);
    formData.append('district_id', payload.district_id);
    formData.append('village_id', payload.village_id);
    formData.append('disbursement_method', payload.disbursement_method);
    if (payload.bank_account_number) {
        formData.append('bank_account_number', payload.bank_account_number);
    }

    Object.entries(payload.dynamicInputs).forEach(([key, value]) => {
        formData.append(key, value as string);
    });

    Object.entries(payload.files).forEach(([key, file]) => {
        if (file instanceof File) {
            formData.append(key, file);
        }
    });

    return await updateAssistanceApi(id, formData);
  }

  static async cancelRegistration(registrationNumber: string): Promise<any> {
      return await deleteAssistanceApi(registrationNumber);
  }

  static async getSubmissionDetail(id: string): Promise<any> {
      return await getSubmissionDetailApi(id);
  }

  static async downloadReceipt(id: string): Promise<Blob> {
    return await downloadAssistancePdfApi(id);
  }
}