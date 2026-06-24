export interface DisbursementSubmission {
  id: string;
  registration_number: string;
  status: string;
  citizen: {
    id: string;
    full_name: string;
    nik: string;
  };
  program: {
    id: string;
    name: string;
    benefit_amount: number | null;
  };
  disbursement_method: string;
  disbursement?: {
    id: string;
    amount: number;
    disbursed_at: string;
    method: string;
    reference_number: string;
    notes: string | null;
  } | null;
}

export interface DisbursementListResponse {
  status: string;
  data: DisbursementSubmission[];
}