export type EvaluationStatus = 'triggered' | 'updated' | 'approved' | 'revoked';

export type EvaluationDecision = 'approved' | 'revoked';

export interface Recommendation {
  label: string;
  color: string;
}

export interface EvaluationSubmission {
  id: string;
  registration_number: string;
  status: string;
}

export interface EvaluationNewSubmission {
  id: string;
  registration_number: string;
  status: string;
  smart_score?: number | null;
  recommendation?: Recommendation | null;
}

export interface EvaluationCitizen {
  id: string;
  full_name: string;
  nik: string;
}

export interface EvaluationProgram {
  id: string;
  name: string;
}

export interface EvaluationLocation {
  village: string | null;
  district: string | null;
  regency: string | null;
}

export interface EvaluationOldData {
  submission_id: string;
  registration_number: string;
  program_name: string;
  citizen_name: string;
  citizen_nik: string;
  family_card_number: string | null;
  submission_data: Record<string, any>;
  smart_score: number | null;
  disbursement_method: string;
  bank_account_number: string | null;
  snapshot_at: string;
}

export interface Evaluation {
  id: string;
  status: EvaluationStatus;
  status_label: string;
  decision_notes: string | null;
  triggered_at: string;
  decided_at: string | null;
  submission: EvaluationSubmission;
  new_submission: EvaluationNewSubmission | null;
  citizen: EvaluationCitizen;
  program: EvaluationProgram;
  old_data: EvaluationOldData | null;
  location: EvaluationLocation;
}

export interface EvaluationListResponse {
  status: string;
  data: Evaluation[];
  current_page?: number;
  total?: number;
  last_page?: number;
  per_page?: number;
}

export interface RevokeEvaluationPayload {
  notes: string;
}