export interface VerificationData {
  id: string;
  registration_number: string;
  status: 'pending' | 'validated' | 'rejected' | 'completed' | 'needs_revision';
  smart_score: number | null;
  recommendation: {
    label: string;
    color: 'green' | 'yellow' | 'orange' | 'red' | 'gray';
  };
  citizen: {
    id: string;
    nik: string;
    full_name: string;
  } | null;
  program: {
    id: string;
    name: string;
    benefit_amount?: number | null;
  } | null;
  wilayah: {
    village: string | null;
    district: string | null;
    regency: string | null;
  };
  created_at: string;
}

export interface CriteriaInput {
    key: string;
    label: string;
    type: string;
    sifat?: 'benefit' | 'cost' | 'none';
    weight?: number;
    ideal_value?: number;
    required?: boolean;
    options?: Array<{ value: string; score: number }>;
}

export interface CriteriaDocument {
  key: string;
  label: string;
  required?: boolean;
  ocr?: boolean;
  nlp_match?: boolean;
}

export interface ProgramCriteria {
    inputs?: CriteriaInput[];
    documents?: CriteriaDocument[];
    [key: string]: unknown;
}

export interface VerificationDetail extends Omit<VerificationData, 'program'> {
  disbursement_method: string;
  bank_account_number: string | null;
  submission_data: Record<string, unknown> & { anomalies?: Array<{ type: string; severity: string; message: string }> };
  program: {
    id: string;
    name: string;
    benefit_amount?: number | null;
    criteria: ProgramCriteria;
  } | null;
  evidences: Array<{
    id: string;
    image_type: string;
    image_url: string;
    ai_result?: {
      success: boolean;
      matches?: Array<{
        key: string;
        label: string;
        match_status: string;
        match_score: number;
        input_value?: string;
        ocr_value?: string | null;
      }>;
      summary?: {
        total: number;
        cocok: number;
        parsial?: number;
        tidak_cocok?: number;
        tidak_ditemukan?: number;
      };
    } | null;
  }>;
  disbursement?: {
    id: string;
    amount: number;
    disbursed_at: string;
    method: string;
    reference_number: string;
  } | null;
}

export interface VerificationFiltersValues {
  status?: string;
  program_id?: string;
  search?: string;
  page?: number;
  per_page?: number;
  recommendation?: string;  
  smartSort?: string;
}