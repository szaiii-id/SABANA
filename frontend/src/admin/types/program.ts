export interface ProgramCriteria {
  targets?: string[];
  documents?: string[];
  ahp_weights?: Record<string, number>;
  regency_quotas?: Record<string, number>;
}

export interface ProgramData {
  id: string;
  name: string;
  slug: string;
  description: string;
  criteria: ProgramCriteria | null;
  start_date: string | null;
  end_date: string | null;
  quota_total: number | null;
  benefit_amount: number | null;
  banner_url: string | null;
  status: 'draft' | 'active' | 'closed' | 'completed' | null;
  is_active: boolean;
  total_anggaran: number | null;
  submissions_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ProgramPayload {
  name: string;
  description: string;
  start_date: string;
  end_date: string;
  quota_total?: number | null;
  benefit_amount?: number | null;
  status: 'draft' | 'active';
  criteria?: ProgramCriteria;
  ai_config?: Record<string, any>; 
  banner?: File | null;
  banner_url?: string | null;
}

export interface ProgramFilters {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
}