export interface CitizenSearchResult {
  id: string;
  nik: string;
  full_name: string;
  family_card_number: string;
  whatsapp_number: string | null;
}

export interface CitizenAssistancePayload {
  citizen_id: string;
  program_id: string;
  regency_id: string;
  district_id: string;
  village_id: string;
  disbursement_method: 'village_cash' | 'bpd_transfer';
  bank_account_number?: string;
  dynamicInputs: Record<string, string | number>;
  files: Record<string, File>;
}

export interface CitizenAssistanceResponse {
  status: string;
  message: string;
  data: {
    id: string;
    registration_number: string;
    status: string;
    program: {
      id: string;
      name: string;
    };
    citizen: {
      id: string;
      nik: string;
      full_name: string;
    };
    created_at: string;
  };
}