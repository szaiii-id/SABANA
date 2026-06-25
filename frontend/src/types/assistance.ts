export interface Region {
  id: string;
  name: string;
}

export interface FormInputSchema {
  key: string;
  label: string;
  type: string;
  options?: { value: string; score: number }[];
}

export interface FormDocumentSchema {
  key: string;
  label: string;
  description?: string;
  required?: boolean;
}

export interface AssistanceProgramSchema {
  id: string;
  title: string;
  slug: string;
  description: string;
  badge: string;
  banner_url: string | null;
  start_date: string | null;
  end_date: string | null;
  quota_total: number | null;
  benefit_amount: number | null;
  inputs: FormInputSchema[];
  documents: FormDocumentSchema[];
  has_submitted: boolean;
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
  is_evaluation?: boolean; 
}

export interface AssistanceResponse {
  success: boolean;
  message: string;
  data: {
    registration_number: string;
    status: string;
  };
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  total: number;
}

// ===== TIMELINE =====

export interface TimelineItem {
  id: string;
  status: string;
  label: string;
  date: string;
  color: 'blue' | 'yellow' | 'green' | 'red' | 'orange' | 'gray';
}

export interface SubmissionHistoryItem {
  program_id: string;
  program_name: string;
  registration_number: string;
  current_status: string;
  current_status_label: string;
  is_evaluation: boolean;
  latest_submission_id: string;
  revision_items?: string[];
  admin_note?: string | null;
  program?: {
    id: string;
    name: string;
  } | null;
  timeline: TimelineItem[];
  disbursement?: {
    amount?: number;
    method?: string;
    reference_number?: string;
    disbursed_at?: string;
  } | null;
}

// ===== RESPONSE =====

export interface HistoryResponse {
  success: boolean;
  data: SubmissionHistoryItem[];
  meta?: PaginationMeta;
}

// ===== SUBMISSION ITEM (TETAP — dipakai localStorage draft) =====

export interface SubmissionItem {
  id: string;
  registration_number: string;
  program: { name: string } | null;
  status: string;
  is_evaluation: boolean;
  revision_items: string[];
  admin_note: string | null;
  submitted_at: string;
  location: { village: string; district: string; regency: string; province: string };
  disbursement?: { reference_number: string; amount?: number; method?: string };
}

// ===== SUBMISSION DETAIL (dipakai Edit.vue fetchDetail) =====

export interface SubmissionDetailData {
  id: string;
  registration_number: string;
  status: string;
  program_id?: string;
  program?: { 
    id: string; 
    name: string; 
    title?: string; 
    benefit_amount?: number; 
    criteria?: { inputs?: FormInputSchema[]; documents?: FormDocumentSchema[] };
    banner_url?: string | null;
    description?: string;
  };
  citizen?: { id: string; nik: string; full_name: string };
  submission_data?: Record<string, string | number>;
  evidences?: Array<{ id: string; image_type: string; image_url: string; ai_result?: unknown }>;
  regency_id?: string;
  district_id?: string;
  village_id?: string;
  village?: { id: string; name: string };
  district?: { id: string; name: string };
  regency?: { id: string; name: string };
  disbursement_method?: string;
  bank_account_number?: string;
  verified_by_name?: string | null;
  verified_at?: string | null;
  revision_by_name?: string | null;
  revision_at?: string | null;
  revision_items?: string[];
  rejection_note?: string | null;
  revision_note?: string | null;
  evaluation_notes?: string | null;
  submitted_at?: string;
  smart_score?: number | null;
}

export interface DisbursementReceipt {
  registration_number: string;
  program_name: string;
  citizen_name: string;
  citizen_nik: string;
  family_card_number: string;
  amount: number;
  method: string;
  reference_number: string;
  disbursed_at: string;
  officer_name: string;
}

export interface DisbursementReceiptResponse {
  status: string;
  data: DisbursementReceipt;
}