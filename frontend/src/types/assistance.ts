export interface Region {
  id: string;
  name: string;
}

export interface FormInputSchema {
  key: string;
  label: string;
  type: string;
}

export interface FormFileSchema {
  key: string;
  label: string;
}

export interface AssistanceProgramSchema {
  id: string;
  title: string;       
  badge: string;       
  iconSvg: string;     
  inputs: FormInputSchema[]; 
  files: FormFileSchema[];   
}

export interface AssistanceSubmissionPayload {
  program_id: string;
  regency_id: string;
  district_id: string;
  village_id: string;
  disbursement_method: 'village_cash' | 'bpd_transfer';
  bank_account_number?: string;
  dynamicInputs: Record<string, string | number>;
  files: Record<string, File>;
}

export interface AssistanceResponse {
  success: boolean;
  message: string;
  data: {
    registration_number: string;
    status: string;
  };
}